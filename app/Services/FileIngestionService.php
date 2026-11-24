<?php
namespace App\Services;

use App\Models\Source;

/**
 * Reads uploaded files into plain text. PDF via pdftotext (present on most
 * Linux hosts) else a naive stream extract; txt/csv/md read directly.
 * Images are stored, never OCR'd — we record "photo — no text extracted".
 */
class FileIngestionService
{
    public function ingest(Source $source, string $diskPath, string $originalName): Source
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $text = '';
        if (in_array($ext, ['txt', 'csv', 'md', 'json'])) {
            $text = (string) file_get_contents($diskPath);
        } elseif ($ext === 'pdf') {
            $text = $this->pdfText($diskPath);
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $text = '[Photo — no text extracted. No OCR is run; the image is shown in the gallery.]';
        } else {
            $text = '[Unsupported file type — stored, no text extracted.]';
        }
        $scan = InjectionGuard::scan($text);
        $source->update([
            'raw_text' => $text,
            'extracted_text' => $scan['clean'],
            'injection_flags' => $scan['flags'] ?: null,
            'status' => 'ok',
        ]);
        return $source->fresh();
    }

    private function pdfText(string $path): string
    {
        $bin = trim((string) shell_exec('command -v pdftotext'));
        if ($bin !== '') {
            $out = shell_exec(escapeshellarg($bin).' -layout '.escapeshellarg($path).' - 2>/dev/null');
            if (is_string($out) && trim($out) !== '') return $out;
        }
        // Naive fallback: pull readable strings out of the PDF stream.
        $raw = (string) file_get_contents($path);
        preg_match_all('/\(([^()]{3,})\)/', $raw, $m);
        return implode(' ', $m[1] ?? []) ?: '[PDF could not be read — please paste the text instead.]';
    }
}
