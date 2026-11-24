<?php
// AI settings. No secrets here — the Gemini key lives only in .env (GEMINI_API_KEY).
return [
    'provider' => env('AI_PROVIDER', 'gemini'),
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'key' => env('GEMINI_API_KEY', ''),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
];
