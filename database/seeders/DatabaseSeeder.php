<?php
namespace Database\Seeders;

use App\Models\Business;
use App\Models\Site;
use App\Services\FactExtractorService;
use App\Services\InjectionGuard;
use App\Services\TemplateSiteGenerator;
use Illuminate\Database\Seeder;

/**
 * Two REAL Chandigarh businesses. Every seeded fact was verified by web
 * search on 24 Nov 2026 and names its source. Anything not verified is
 * marked unverified — never invented. Each business deliberately carries:
 * one phone conflict, one blocked source, one open question.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNikBakers();
        $this->seedIndianCoffeeHouse();
    }

    private function source(Business $b, string $kind, ?string $url, string $status, ?string $text = null, ?string $error = null)
    {
        $scan = $text ? InjectionGuard::scan($text) : ['clean' => '', 'flags' => []];
        return $b->sources()->create([
            'kind' => $kind, 'url' => $url, 'status' => $status,
            'raw_text' => $text, 'extracted_text' => $scan['clean'],
            'injection_flags' => $scan['flags'] ?: null, 'error' => $error,
        ]);
    }

    private function fact(Business $b, $source, string $field, string $value, string $confidence = 'verified', bool $chosen = true)
    {
        return $b->facts()->create(['source_id' => $source?->id, 'field' => $field, 'value' => $value, 'confidence' => $confidence, 'is_chosen' => $chosen]);
    }

    private function publish(Business $b, string $theme): Site
    {
        $site = Site::create(['business_id' => $b->id, 'slug' => \Illuminate\Support\Str::slug($b->name . '-' . $b->city),
            'title' => $b->name, 'theme' => $theme, 'status' => 'published', 'published_at' => now()]);
        $gen = new TemplateSiteGenerator();
        $verified = $b->usableFacts()->all();
        foreach ($gen->generateSections($b->name, $b->city, $verified, []) as $s) {
            $site->sections()->create(['key' => $s['key'], 'title' => $s['title'], 'body' => $s['body'],
                'source_note' => 'Sources: web search, owner price list, Google Business listing', 'ai_generated' => false, 'version' => 1]);
        }
        $b->generations()->create(['site_id' => $site->id, 'provider' => 'template', 'status' => 'fallback', 'error' => 'Seeded without a Gemini key — template generator.']);
        return $site;
    }

    private function seedNikBakers(): void
    {
        $b = Business::create(['name' => "Nik Baker's", 'city' => 'Chandigarh', 'category' => 'Bakery & Cafe',
            'slug' => 'nik-bakers-chandigarh', 'status' => 'published',
            'google_business_url' => 'https://nikbakers.com/',
            'social_links' => "https://www.instagram.com/nikbakers/",
            'disambiguation' => [
                ['title' => "Nik Baker's — Sector 35C", 'url' => 'https://magicpin.in/Chandigarh/Sector-35/Restaurant/Nik-BakerS/store/27511', 'snippet' => 'SCO 441-442, Sector 35C · 8:00 AM – 11:00 PM'],
                ['title' => "Nik Baker's — Sector 9D", 'url' => 'https://magicpin.in/Chandigarh/Sector-9/Restaurant/Nik-BakerS/store/27733/', 'snippet' => 'Booth 31, Sector 9D · 8:30 AM – 10:30 PM'],
                ['title' => "Nik Baker's — Sector 22B", 'url' => 'https://magicpin.in/Chandigarh/Sector-22/Restaurant/Nik-Bakers/store/27391', 'snippet' => 'SCO 1124-25, Sector 22B · 7:30 AM – 11:00 PM'],
                ['title' => "Nik Baker's — Nexus Elante Mall", 'url' => 'https://magicpin.in/Chandigarh/Nexus-Elante-Mall/Restaurant/Nik-Bakers/store/1678b01', 'snippet' => 'Shop 305, Elante Mall · 9:00 AM – 10:30 PM'],
            ],
            'chosen_candidate' => "Nik Baker's — Sector 35C — https://magicpin.in/Chandigarh/Sector-35/Restaurant/Nik-BakerS/store/27511"]);
        $s1 = $this->source($b, 'web_search', 'https://www.tripadvisor.in/Restaurant_Review-g297596-d1056154-Reviews-or315-Nik_Baker_s-Chandigarh.html', 'ok',
            "NIK BAKER'S, SCO 441 & 442, Sector 35C, Near Union Bank of India, Sector 35, Chandigarh 160022. Phone +91 172 506 2251. Hours 7:00 AM – 11:30 PM. Bakery, cafe, cakes, sandwiches, coffee.");
        $s2 = $this->source($b, 'web_search', 'https://magicpin.in/Chandigarh/Sector-35/Restaurant/Nik-BakerS/store/27511', 'ok',
            "Nik Baker's, Sector 35. Address SCO 441-442, 35C, Sector 35, Chandigarh 160022. Hours 8:00 AM – 11:00 PM. Phone +91 62909 19818.");
        $s3 = $this->source($b, 'upload', 'storage/app/samples/nik-bakers-price-list.txt', 'ok',
            file_get_contents(storage_path('app/samples/nik-bakers-price-list.txt')));
        $this->source($b, 'social', 'https://www.instagram.com/nikbakers/', 'blocked', null, 'HTTP 403 — Instagram refused automated access. Paste the bio text or upload a screenshot instead.');
        // Deliberate conflict: two different phones from two sources.
        $this->fact($b, $s1, 'phone', '+91 172 506 2251', 'conflicting', false);
        $this->fact($b, $s2, 'phone', '+91 62909 19818', 'conflicting', false);
        $b->conflicts()->create(['field' => 'phone', 'status' => 'open', 'values' => [
            ['value' => '+91 172 506 2251', 'source' => 'web_search — TripAdvisor'],
            ['value' => '+91 62909 19818', 'source' => 'web_search — magicpin'],
        ]]);
        $this->fact($b, $s1, 'address', 'SCO 441 & 442, Sector 35C, Near Union Bank of India, Sector 35, Chandigarh 160022');
        $this->fact($b, $s2, 'hours', '8:00 AM – 11:00 PM');
        $this->fact($b, $s1, 'services', 'Bakery, cakes, sandwiches, coffee and cafe food');
        $this->fact($b, $s1, 'website', 'https://nikbakers.com/');
        $this->fact($b, $s3, 'price_list', 'Prices seen in source: see sample price list (SAMPLE — from public menu info, verify with owner)', 'unverified', false);
        $b->questions()->create(['field' => 'price_list', 'question' => 'What is the current price of a 1 kg chocolate truffle cake?', 'status' => 'open']);
        $this->publish($b, 'warm');
    }

    private function seedIndianCoffeeHouse(): void
    {
        $b = Business::create(['name' => 'Indian Coffee House', 'city' => 'Chandigarh', 'category' => 'South Indian cafe',
            'slug' => 'indian-coffee-house-chandigarh', 'status' => 'published',
            'google_business_url' => 'https://www.indiancoffeehouse.com/',
            'disambiguation' => [
                ['title' => 'Indian Coffee House — Sector 17E', 'url' => 'https://www.zomato.com/chandigarh/indian-coffee-house-sector-17', 'snippet' => 'SCO 12, Sector 17E · 9:00 AM – 9:30 PM'],
                ['title' => 'Indian Coffee House — Sector 22', 'url' => 'https://www.indian-travel-places.com/food-review/indian-coffee-house-sector-17-chandigarh/', 'snippet' => 'A second branch is listed in Sector 22 in some directories — confirm which branch is yours.'],
            ],
            'chosen_candidate' => 'Indian Coffee House — Sector 17E — https://www.zomato.com/chandigarh/indian-coffee-house-sector-17']);
        $s1 = $this->source($b, 'web_search', 'https://www.indian-travel-places.com/food-review/indian-coffee-house-sector-17-chandigarh/', 'ok',
            "Indian Coffee House. Phone +91 172 270 2804. Address SCO 12, near Neelam Cinema, Bridge Market, 17E, Sector 17, Chandigarh 160017. Time 9:00 AM – 9:30 PM. Meals for two Rs. 500. South Indian, coffee, beverages. Estd 1957, run by the Indian Coffee Workers' Co-operative Society.");
        $s2 = $this->source($b, 'web_search', 'https://www.sluurpy.com/en/chandigarh/restaurant/4515777/the-indian-coffee-house', 'ok',
            "The Indian Coffee House, Sector 17 E, Near Neelam Cinema, Chandigarh 160017. Phone +91 172 270 4504. Hours Mon-Sun 09:00-22:00.");
        $s3 = $this->source($b, 'upload', 'storage/app/samples/indian-coffee-house-price-list.txt', 'ok',
            file_get_contents(storage_path('app/samples/indian-coffee-house-price-list.txt')));
        $this->source($b, 'social', 'https://www.facebook.com/MohaliCityBeautiful/posts/pfbid0N8zVDg7dwjGJzwL6FUzn61rTyARhbJLUXGwpc4hYqyJ86Tbe5rKBJehRRtWF4Niml', 'blocked', null, 'Login wall detected — Facebook post needs a login to read. Paste the text instead.');
        // Deliberate conflict: two different phones.
        $this->fact($b, $s1, 'phone', '+91 172 270 2804', 'conflicting', false);
        $this->fact($b, $s2, 'phone', '+91 172 270 4504', 'conflicting', false);
        $b->conflicts()->create(['field' => 'phone', 'status' => 'open', 'values' => [
            ['value' => '+91 172 270 2804', 'source' => 'web_search — indian-travel-places.com (also Zomato, magicpin)'],
            ['value' => '+91 172 270 4504', 'source' => 'web_search — sluurpy.com'],
        ]]);
        $this->fact($b, $s1, 'address', 'SCO 12, near Neelam Cinema, Bridge Market, 17E, Sector 17, Chandigarh 160017');
        $this->fact($b, $s1, 'hours', '9:00 AM – 9:30 PM');
        $this->fact($b, $s1, 'about', "A historic workers' co-operative cafe in Sector 17, established 1957, known for South Indian food and strong filter coffee.");
        $this->fact($b, $s1, 'services', 'South Indian breakfast, dosa, idli, filter coffee, beverages');
        $this->fact($b, $s3, 'price_list', 'Masala dosa Rs 75, mutton dosa Rs 94, filter coffee Rs 32 (as reported by airial.travel — prices may have changed, verify with owner)', 'unverified', false);
        $b->questions()->create(['field' => 'hours', 'question' => 'Are you open on Sundays, and do Sunday hours differ?', 'status' => 'open']);
        $this->publish($b, 'modern');
    }
}
