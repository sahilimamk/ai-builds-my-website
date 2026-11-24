@extends('layouts.app')
@section('content')
<h1>Review: {{ $business->name }} — {{ $business->city }}</h1>
<p class="muted">Status: <strong>{{ $business->status }}</strong>. Check the facts below before we generate anything. Nothing here is invented — each fact names its source.</p>

@php $flagged = $business->sources->filter(fn($s)=>!empty($s->injection_flags)); @endphp
@if($flagged->count())
<div class="alert red"><strong>⚠ Ignored {{ $flagged->sum(fn($s)=>count($s->injection_flags)) }} instruction-like line(s) in your uploads.</strong>
Uploaded text is treated as data, never as instructions — those lines were quarantined and never reached the AI or the facts.
@foreach($flagged as $s) @foreach($s->injection_flags as $line)<div><code>{{ $line }}</code></div>@endforeach @endforeach</div>
@endif

@if($business->disambiguation)
<div class="card"><h2>Which business is yours?</h2>
<p class="muted">We found several places with this name. Pick the right one — or "none of these".</p>
@foreach($business->disambiguation as $c)
<form method="POST" action="/businesses/{{ $business->id }}/pick-candidate" style="display:inline">@csrf
<input type="hidden" name="candidate" value="{{ $c['title'] }} — {{ $c['url'] }}">
<button class="btn small secondary">{{ $c['title'] }}</button></form>
<span class="muted">{{ $c['snippet'] }}</span><br><br>
@endforeach
<form method="POST" action="/businesses/{{ $business->id }}/pick-candidate">@csrf
<input type="hidden" name="candidate" value="none of these — new business"><button class="btn small">None of these / new business</button></form>
@if($business->chosen_candidate)<p>Chosen: <strong>{{ $business->chosen_candidate }}</strong></p>@endif
</div>
@endif

<div class="card"><h2>Sources</h2>
<table><tr><th>Kind</th><th>Source</th><th>Status</th><th>Note</th></tr>
@foreach($business->sources as $s)
<tr><td>{{ $s->kind }}</td><td>{{ $s->url ?? $s->path }}</td>
<td><span class="badge b-{{ $s->status }}">{{ $s->status }}</span></td>
<td>@if($s->status==='blocked') Could not read — please paste the text below or upload a screenshot/PDF. Nothing was invented from it.
@elseif($s->status==='failed') {{ $s->error }} @elseif(!empty($s->injection_flags)) {{ count($s->injection_flags) }} instruction-like line(s) ignored. @else Read OK. @endif</td></tr>
@endforeach</table>
<h3>Paste text from a blocked page</h3>
<form method="POST" action="/businesses/{{ $business->id }}/manual-source">@csrf
<input name="label" placeholder="Where is this from? e.g. Instagram bio"><textarea name="text" rows="3" required placeholder="Paste the text here…"></textarea>
<button class="btn small">Save pasted text</button></form>
</div>

<div class="card"><h2>Facts</h2>
<table><tr><th>Field</th><th>Value</th><th>Confidence</th><th>Source</th></tr>
@foreach($business->facts as $f)
<tr><td>{{ $f->field }}</td><td>{{ $f->value }}</td>
<td><span class="badge b-{{ $f->confidence }}">{{ $f->confidence }}</span>@if($f->is_chosen) ✓ chosen @endif</td>
<td>{{ $f->source?->kind }} {{ $f->source?->url }}</td></tr>
@endforeach</table>
@if($business->facts->isEmpty())<p class="muted">No facts extracted yet — answer the questions below or paste text above.</p>@endif
</div>

@if($business->conflicts->where('status','open')->count())
<div class="card"><h2>⚠ Sources disagree — you decide</h2>
@foreach($business->conflicts->where('status','open') as $c)
<p><strong>{{ ucfirst($c->field) }}:</strong></p>
@foreach($c->values as $v)
<form method="POST" action="/businesses/{{ $business->id }}/resolve-conflict" style="display:inline">@csrf
<input type="hidden" name="conflict_id" value="{{ $c->id }}"><input type="hidden" name="value" value="{{ $v['value'] }}">
<button class="btn small">Use “{{ $v['value'] }}”</button></form> <span class="muted">from {{ $v['source'] }}</span><br>
@endforeach
@endforeach</div>
@endif

@if($business->questions->where('status','open')->count())
<div class="card"><h2>Questions for you</h2>
@foreach($business->questions->where('status','open') as $q)
<form method="POST" action="/businesses/{{ $business->id }}/answer-question">@csrf
<input type="hidden" name="question_id" value="{{ $q->id }}">
<label>{{ $q->question }}</label><input name="answer" required><button class="btn small">Save answer</button></form>
@endforeach</div>
@endif

<form method="POST" action="/businesses/{{ $business->id }}/confirm-facts">@csrf
<button class="btn">Confirm facts &amp; generate website →</button></form>
<p class="muted">Unresolved conflicts and unanswered questions stay as “Not confirmed — ask the owner” on the site. Nothing is guessed.</p>
@endsection
