<?php
namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Source;
use App\Services\FactExtractorService;
use App\Services\FileIngestionService;
use App\Services\WebFetchService;
use App\Services\WebSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function index()
    {
        $businesses = Business::with('site')->latest()->get();
        return view('dashboard', compact('businesses'));
    }

    public function create() { return view('businesses.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'google_business_url' => 'nullable|url',
            'social_links' => 'nullable|string',
            'owner_notes' => 'nullable|string',
            'files.*' => 'file|max:10240',
        ]);
        $b = Business::create([
            'name' => $data['name'], 'city' => $data['city'], 'category' => $data['category'] ?? null,
            'slug' => Str::slug($data['name'] . '-' . $data['city']) . '-' . Str::random(4),
            'google_business_url' => $data['google_business_url'] ?? null,
            'social_links' => $data['social_links'] ?? null,
            'owner_notes' => $data['owner_notes'] ?? null,
        ]);
        // Store uploads as sources (text extracted at ingest step).
        foreach ($request->file('files', []) as $file) {
            $path = $file->store('uploads', 'local');
            $b->sources()->create(['kind' => 'upload', 'path' => $path, 'url' => $file->getClientOriginalName(), 'status' => 'pending']);
        }
        return redirect()->route('businesses.ingest', $b)->with('ok', 'Business created. Now we read your files and search the web.');
    }

    /** Runs ingestion + web search + extraction, then goes to the review page. */
    public function ingest(Business $business, FileIngestionService $files, WebFetchService $fetch, WebSearchService $search, FactExtractorService $facts)
    {
        // 1. Uploads
        foreach ($business->sources()->where('kind', 'upload')->where('status', 'pending')->get() as $s) {
            $files->ingest($s, storage_path('app/private/' . $s->path), $s->url ?? 'file');
        }
        // 2. Google Business URL + social links (each fetched; blocks recorded, never invented)
        $urls = array_filter(array_merge(
            [$business->google_business_url],
            preg_split('/\r\n|\r|\n/', $business->social_links ?? '')
        ));
        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') continue;
            $kind = str_contains($url, 'google') ? 'google_business' : 'social';
            if ($business->sources()->where('url', $url)->exists()) continue;
            $r = $fetch->fetch($url);
            $scan = \App\Services\InjectionGuard::scan($r['text']);
            $business->sources()->create([
                'kind' => $kind, 'url' => $url, 'status' => $r['status'],
                'raw_text' => $r['text'], 'extracted_text' => $scan['clean'],
                'injection_flags' => $scan['flags'] ?: null, 'error' => $r['error'],
            ]);
        }
        // 3. Web search (no key) — results become sources + disambiguation candidates.
        $results = $search->search($business->name . ' ' . $business->city);
        $candidates = [];
        foreach ($results as $res) {
            $candidates[] = ['title' => $res['title'], 'url' => $res['url'], 'snippet' => $res['snippet']];
            if ($business->sources()->where('url', $res['url'])->exists()) continue;
            $r = $fetch->fetch($res['url']);
            $scan = \App\Services\InjectionGuard::scan($r['text']);
            $business->sources()->create([
                'kind' => 'web_search', 'url' => $res['url'], 'status' => $r['status'],
                'raw_text' => $r['text'], 'extracted_text' => $scan['clean'],
                'injection_flags' => $scan['flags'] ?: null, 'error' => $r['error'],
            ]);
        }
        if (count($candidates) > 1) $business->update(['disambiguation' => $candidates]);
        // 4. Extract facts + conflicts from everything readable.
        $facts->extract($business);
        // 5. Ask the owner about anything important that is still missing.
        foreach (['phone' => 'What is the correct phone number?', 'address' => 'What is the full address?', 'hours' => 'What are the opening hours?'] as $field => $q) {
            if (!$business->chosenFact($field) && !$business->questions()->where('field', $field)->exists()) {
                $business->questions()->create(['field' => $field, 'question' => $q]);
            }
        }
        $business->update(['status' => $business->conflicts()->where('status', 'open')->exists() ? 'needs_info' : 'ready']);
        return redirect()->route('businesses.review', $business);
    }

    public function review(Business $business)
    {
        $business->load(['sources', 'facts.source', 'conflicts', 'questions']);
        return view('businesses.review', compact('business'));
    }

    public function pickCandidate(Business $business, Request $request)
    {
        $request->validate(['candidate' => 'required|string']);
        $business->update(['chosen_candidate' => $request->candidate]);
        return back()->with('ok', 'Candidate noted: ' . $request->candidate);
    }

    public function resolveConflict(Business $business, Request $request, FactExtractorService $facts)
    {
        $data = $request->validate(['conflict_id' => 'required|exists:conflicts,id', 'value' => 'required|string']);
        $conflict = $business->conflicts()->findOrFail($data['conflict_id']);
        $facts->resolveConflict($conflict, $data['value']);
        if (!$business->conflicts()->where('status', 'open')->exists()) $business->update(['status' => 'ready']);
        return back()->with('ok', "Conflict on {$conflict->field} resolved.");
    }

    public function answerQuestion(Business $business, Request $request)
    {
        $data = $request->validate(['question_id' => 'required|exists:questions,id', 'answer' => 'required|string']);
        $q = $business->questions()->findOrFail($data['question_id']);
        $q->update(['answer' => $data['answer'], 'status' => 'answered']);
        // Owner's answer is a verified fact.
        $business->facts()->create(['field' => $q->field ?? 'about', 'value' => $data['answer'], 'confidence' => 'verified', 'is_chosen' => true]);
        return back()->with('ok', 'Answer saved — it is now a verified fact.');
    }

    public function confirmFacts(Business $business, Request $request)
    {
        // Owner confirms the chosen facts; anything still unverified stays unverified
        // and will render as "Not confirmed — ask the owner" on the site.
        $business->facts()->where('is_chosen', true)->update(['confidence' => 'verified']);
        $business->update(['status' => 'ready']);
        return redirect()->route('businesses.generate', $business);
    }

    public function addManualSource(Business $business, Request $request)
    {
        // For blocked social pages: the owner pastes the text (or a screenshot note) by hand.
        $data = $request->validate(['text' => 'required|string', 'label' => 'nullable|string']);
        $scan = \App\Services\InjectionGuard::scan($data['text']);
        $business->sources()->create([
            'kind' => 'manual', 'url' => $data['label'] ?? 'Owner-pasted text',
            'status' => 'ok', 'raw_text' => $data['text'], 'extracted_text' => $scan['clean'],
            'injection_flags' => $scan['flags'] ?: null,
        ]);
        app(FactExtractorService::class)->extract($business);
        return back()->with('ok', 'Manual text saved and scanned for instructions (' . count($scan['flags']) . ' lines ignored).');
    }
}
