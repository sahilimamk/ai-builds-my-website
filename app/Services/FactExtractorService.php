<?php
namespace App\Services;

use App\Models\Business;
use App\Models\Conflict;
use App\Models\Source;

/**
 * Regex extraction from cleaned source text. Only ever creates facts with a
 * source attached. When two sources disagree on the same field, the fact is
 * marked 'conflicting' and a Conflict row is opened — the owner decides.
 * Phones are compared by digits only (so +91 172 270 2804 == 0172 270 2804).
 */
class FactExtractorService
{
    public const FIELDS = ['phone', 'address', 'hours', 'email', 'services', 'price_list', 'about', 'website'];

    public function extract(Business $business): void
    {
        foreach ($business->sources()->where('status', 'ok')->get() as $source) {
            $text = $source->extracted_text ?? '';
            if (trim($text) === '') continue;
            foreach ($this->extractFromText($text) as $field => $value) {
                $this->storeFact($business, $source, $field, $value);
            }
        }
        $this->detectConflicts($business);
    }

    /** @return array<string,string> field => value */
    public function extractFromText(string $text): array
    {
        $out = [];
        // Phone: Indian mobile / landline, with or without +91 / 0.
        if (preg_match('/(?:\+91[\s-]?|0)?(?:[6-9][\s-]?\d{4}[\s-]?\d{5}|172[\s-]?\d{3}[\s-]?\d{4}|\d{2,4}[\s-]?\d{6,8})/', $text, $m)) {
            $out['phone'] = trim($m[0]);
        }
        if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $text, $m)) {
            $out['email'] = $m[0];
        }
        // Hours: "9 AM – 9:30 PM" style ranges.
        if (preg_match('/\b\d{1,2}(:\d{2})?\s*(?:AM|PM|am|pm)\s*[–\-—to]+\s*\d{1,2}(:\d{2})?\s*(?:AM|PM|am|pm)\b/', $text, $m)) {
            $out['hours'] = trim($m[0]);
        }
        // Prices: ₹ / Rs amounts, kept as a list line.
        if (preg_match_all('/(?:₹|Rs\.?|INR)\s?[\d,]+(?:\.\d{2})?/', $text, $m) && $m[0]) {
            $out['price_list'] = 'Prices seen in source: ' . implode(', ', array_unique($m[0]));
        }
        // Address: line containing SCO / Sector / Road / Marg + a number.
        if (preg_match('/\b(?:SCO|Shop|Booth)[^,\n]{0,80}/i', $text, $m)) {
            $out['address'] = trim($m[0]);
        }
        if (preg_match('/https?:\/\/[^\s"\'<>]+/', $text, $m)) {
            $out['website'] = $m[0];
        }
        return $out;
    }

    public static function normalisePhone(string $v): string
    {
        $d = preg_replace('/\D+/', '', $v);
        // Drop leading 91 country code / leading 0 for comparison.
        if (str_starts_with($d, '91') && strlen($d) > 10) $d = substr($d, 2);
        return ltrim($d, '0');
    }

    private function storeFact(Business $b, Source $s, string $field, string $value): void
    {
        $existing = $b->facts()->where('field', $field)->get();
        foreach ($existing as $e) {
            $same = $field === 'phone'
                ? self::normalisePhone($e->value) === self::normalisePhone($value)
                : trim(strtolower($e->value)) === trim(strtolower($value));
            if ($same) return; // already recorded from another source
        }
        $b->facts()->create([
            'source_id' => $s->id, 'field' => $field, 'value' => $value,
            'confidence' => 'unverified', 'is_chosen' => false,
        ]);
    }

    /** Groups facts per field; differing values => Conflict + confidence=conflicting. */
    public function detectConflicts(Business $business): void
    {
        foreach ($business->facts()->get()->groupBy('field') as $field => $facts) {
            $distinct = $facts->map(fn ($f) => $field === 'phone'
                ? self::normalisePhone($f->value) : trim(strtolower($f->value))
            )->unique();
            if ($distinct->count() > 1) {
                $facts->each->update(['confidence' => 'conflicting']);
                Conflict::updateOrCreate(
                    ['business_id' => $business->id, 'field' => $field, 'status' => 'open'],
                    ['values' => $facts->map(fn ($f) => [
                        'value' => $f->value, 'source' => $f->source?->kind . ($f->source?->url ? ' — ' . $f->source->url : ''),
                    ])->values()->all()],
                );
            } elseif ($facts->count() === 1) {
                // Single source, corroborated by nothing: stays unverified until owner confirms.
                $facts->first()->update(['confidence' => 'unverified']);
            }
        }
    }

    /** Owner picks one value for a conflict: chosen fact wins, others stay as rejected history. */
    public function resolveConflict(Conflict $conflict, string $chosenValue): void
    {
        $conflict->business->facts()->where('field', $conflict->field)->each(function ($fact) use ($chosenValue, $conflict) {
            $isChosen = $conflict->field === 'phone'
                ? self::normalisePhone($fact->value) === self::normalisePhone($chosenValue)
                : trim($fact->value) === trim($chosenValue);
            $fact->update(['is_chosen' => $isChosen, 'confidence' => $isChosen ? 'verified' : 'conflicting']);
        });
        $conflict->update(['status' => 'resolved', 'resolution' => "Owner chose: $chosenValue"]);
    }
}
