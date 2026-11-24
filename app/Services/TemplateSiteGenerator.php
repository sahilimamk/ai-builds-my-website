<?php
namespace App\Services;

/** Deterministic fallback: clean sections built ONLY from verified facts. No AI copy. */
class TemplateSiteGenerator
{
    /** @return array<int, array{key:string,title:string,body:string}> */
    public function generateSections(string $businessName, string $city, array $facts, array $unverified): array
    {
        $f = fn (string $k) => $facts[$k] ?? null;
        $nc = '<p><em>Not confirmed — ask the owner.</em></p>';
        $esc = fn ($v) => e($v);
        return [
            ['key' => 'hero', 'title' => $businessName,
             'body' => '<p>' . $esc($businessName) . ' — ' . $esc($city) . '.</p>'
                . ($f('about') ? '<p>' . $esc($f('about')) . '</p>' : $nc)],
            ['key' => 'about', 'title' => 'About',
             'body' => $f('about') ? '<p>' . $esc($f('about')) . '</p>' : $nc],
            ['key' => 'services', 'title' => 'What we offer',
             'body' => $f('services') ? '<p>' . $esc($f('services')) . '</p>' : $nc],
            ['key' => 'pricing', 'title' => 'Prices',
             'body' => $f('price_list') ? '<p>' . $esc($f('price_list')) . '</p>' : $nc],
            ['key' => 'contact', 'title' => 'Contact',
             'body' => '<p>' . ($f('phone') ? 'Phone: ' . $esc($f('phone')) : 'Phone: <em>Not confirmed — ask the owner.</em>') . '<br>'
                . ($f('address') ? 'Address: ' . $esc($f('address')) : 'Address: <em>Not confirmed — ask the owner.</em>') . '<br>'
                . ($f('email') ? 'Email: ' . $esc($f('email')) : '') . '</p>'],
            ['key' => 'hours', 'title' => 'Opening hours',
             'body' => $f('hours') ? '<p>' . $esc($f('hours')) . '</p>' : $nc],
        ];
    }
}
