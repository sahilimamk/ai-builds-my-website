<?php
namespace App\Services;

use App\Exceptions\AiUnavailableException;
use App\Models\Business;

/**
 * Orchestrates generation: try Gemini, fall back to the template generator,
 * and log every attempt in `generations` (provider + status + error).
 * Only verified/chosen facts (or owner answers) ever reach a generator.
 */
class SiteGeneratorService
{
    public function __construct(
        private GeminiService $gemini,
        private TemplateSiteGenerator $template,
    ) {}

    /** @return array{sections: array, provider: string, ai_available: bool, note: ?string} */
    public function generate(Business $business): array
    {
        $verified = $business->usableFacts()->all();
        // Owner-answered questions count as verified facts too.
        foreach ($business->questions()->where('status', 'answered')->get() as $q) {
            if ($q->field && $q->answer) $verified[$q->field] = $q->answer;
        }
        $unverified = $business->facts()->where('confidence', 'unverified')->where('is_chosen', false)
            ->get()->groupBy('field')->map(fn ($g) => $g->first()->value)->all();

        try {
            $sections = $this->gemini->generateSections($business->name, $business->city, $business->category ?? '', $verified, $unverified);
            $this->log($business, 'gemini', 'ok', json_encode($sections));
            return ['sections' => $sections, 'provider' => 'gemini', 'ai_available' => true, 'note' => null];
        } catch (AiUnavailableException $e) {
            $sections = $this->template->generateSections($business->name, $business->city, $verified, $unverified);
            $this->log($business, 'template', 'fallback', null, $e->getMessage());
            return ['sections' => $sections, 'provider' => 'template', 'ai_available' => false,
                'note' => 'AI unavailable (' . $e->getMessage() . ') — built from verified facts with the template generator.'];
        }
    }

    private function log(Business $b, string $provider, string $status, ?string $raw, ?string $error = null): void
    {
        $b->generations()->create(['provider' => $provider, 'status' => $status, 'raw_response' => $raw, 'error' => $error]);
    }
}
