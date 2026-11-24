<?php
namespace App\Services;

/**
 * Prompt-injection guard — the heart of the golden rule.
 *
 * EVERYTHING from uploads / scraped pages is UNTRUSTED DATA, never instructions.
 * Lines that look like instructions are stripped, stored in source.injection_flags,
 * and never reach the AI prompt or the fact extractor.
 */
class InjectionGuard
{
    // Patterns that mark a line as instruction-like. Keep the list visible & editable.
    public const PATTERNS = [
        '/ignore\s+(all\s+|any\s+|previous\s+|prior\s+|above\s+|the\s+)?instructions/i',
        '/you are now/i',
        '/disregard (the )?(previous|above|system|all)/i',
        '/\bdelete\b.*\b(database|site|all|everything)\b/i',
        '/send (an? )?(email|mail)/i',
        '/reveal (your|the|any) ?(api ?)?key/i',
        '/system prompt/i',
        '/do not (tell|show|mention).*(owner|user)/i',
        '/new instructions?:/i',
        '/\bact as\b/i',
    ];

    /** @return array{clean: string, flags: string[]} */
    public static function scan(string $text): array
    {
        $flags = [];
        $kept = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $bad = false;
            foreach (self::PATTERNS as $p) {
                if (preg_match($p, $line)) { $bad = true; break; }
            }
            if ($bad) { $flags[] = trim($line); } else { $kept[] = $line; }
        }
        return ['clean' => implode("\n", $kept), 'flags' => $flags];
    }
}
