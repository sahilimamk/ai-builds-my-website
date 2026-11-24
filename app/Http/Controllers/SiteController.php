<?php
namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Site;
use App\Services\SiteGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    /** Generate (or regenerate all) sections for a business. */
    public function generate(Business $business, SiteGeneratorService $gen)
    {
        $result = $gen->generate($business);
        $site = $business->site()->firstOrCreate(
            ['business_id' => $business->id],
            ['slug' => Str::slug($business->name . '-' . $business->city), 'title' => $business->name, 'theme' => 'modern']
        );
        // Unique slug if a namesake site already exists.
        if (Site::where('slug', $site->slug)->where('id', '!=', $site->id)->exists()) {
            $site->slug .= '-' . Str::random(4);
        }
        $site->sections()->delete();
        foreach ($result['sections'] as $s) {
            $site->sections()->create([
                'key' => $s['key'], 'title' => $s['title'], 'body' => $s['body'],
                'source_note' => 'Sources: ' . $business->sources()->where('status', 'ok')->pluck('kind')->unique()->implode(', '),
                'ai_generated' => $result['provider'] === 'gemini', 'version' => 1,
            ]);
        }
        $business->update(['status' => 'ready']);
        return redirect()->route('sites.edit', $site)
            ->with('ok', $result['ai_available'] ? 'Site generated with Gemini AI.' : ($result['note'] ?? 'Site built from verified facts (template).'));
    }

    public function edit(Site $site)
    {
        $site->load(['business', 'sections']);
        return view('sites.edit', compact('site'));
    }

    public function updateSection(Request $request, Site $site, \App\Models\SiteSection $section)
    {
        abort_unless($section->site_id === $site->id, 404);
        $data = $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string']);
        $section->update(['title' => $data['title'], 'body' => $data['body'], 'version' => $section->version + 1, 'ai_generated' => false]);
        return back()->with('ok', "Section '{$section->key}' saved (v{$section->version}).");
    }

    /** Regenerate ONE section only — every other section is left untouched. */
    public function regenerateSection(Site $site, \App\Models\SiteSection $section, SiteGeneratorService $gen)
    {
        abort_unless($section->site_id === $site->id, 404);
        $result = $gen->generate($site->business);
        $fresh = collect($result['sections'])->firstWhere('key', $section->key);
        if (!$fresh) return back()->with('error', 'No fresh copy for this section.');
        $section->update(['title' => $fresh['title'], 'body' => $fresh['body'],
            'version' => $section->version + 1, 'ai_generated' => $result['provider'] === 'gemini']);
        return back()->with('ok', "Section '{$section->key}' regenerated with " . $result['provider'] . " (v{$section->version}). Other sections untouched.");
    }

    public function publish(Site $site)
    {
        $site->update(['status' => 'published', 'published_at' => now()]);
        $site->business->update(['status' => 'published']);
        return redirect()->route('sites.public', $site->slug)->with('ok', 'Published! Public URL: /sites/' . $site->slug);
    }

    public function preview(Site $site)
    {
        $site->load(['business', 'sections']);
        return view('sites.public', ['site' => $site, 'preview' => true]);
    }

    /** Public renderer — no auth. Unverified facts show as "Not confirmed". */
    public function public(string $slug)
    {
        $site = Site::where('slug', $slug)->where('status', 'published')->with(['business.sources', 'business.facts', 'sections'])->firstOrFail();
        return view('sites.public', ['site' => $site, 'preview' => false]);
    }
}
