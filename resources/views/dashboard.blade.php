@extends('layouts.app')
@section('content')
<h1>Your businesses</h1>
<p class="muted">Give us what you have — name, links, files — and we turn it into a publishable website. Nothing is invented: every fact keeps its source.</p>
<a class="btn" href="/businesses/create">+ Build a website for my business</a>
<div class="grid">
@foreach($businesses as $b)
<div class="card">
  <h2>{{ $b->name }}</h2>
  <p class="muted">{{ $b->city }} · {{ $b->category ?? '—' }} · <span class="badge b-{{ $b->status==='published'?'ok':'unverified' }}">{{ $b->status }}</span></p>
  @if($b->site)
    <p><a href="/sites/{{ $b->site->slug }}/edit">Edit site</a> · <a href="/sites/{{ $b->site->slug }}/preview">Preview</a>
    @if($b->site->status==='published') · <a href="/sites/{{ $b->site->slug }}" target="_blank">View live ↗</a>@endif</p>
  @else
    <p><a class="btn small" href="/businesses/{{ $b->id }}/ingest">Read files &amp; search the web →</a></p>
  @endif
  <p><a href="/businesses/{{ $b->id }}/review">Review facts</a></p>
</div>
@endforeach
</div>
@if($businesses->isEmpty())<div class="card"><p>No businesses yet. Try the seeded demos — <strong>Nik Baker's</strong> and <strong>Indian Coffee House</strong> (both Chandigarh) — or add your own.</p></div>@endif
@endsection
