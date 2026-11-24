@extends('layouts.app')
@section('content')
<h1>Edit site: {{ $site->title }}</h1>
<p class="muted">Theme: {{ $site->theme }} · Status: {{ $site->status }} · Slug: <code>{{ $site->slug }}</code></p>
<p><a class="btn secondary" href="/sites/{{ $site->id }}/preview" target="_blank">Preview draft</a>
@if($site->status!=='published')
<form method="POST" action="/sites/{{ $site->id }}/publish" style="display:inline">@csrf<button class="btn">Publish → public URL</button></form>
@else <a class="btn" href="/sites/{{ $site->slug }}" target="_blank">View live site ↗</a> @endif</p>
@foreach($site->sections as $sec)
<div class="card">
<h2>{{ $sec->title }} <span class="muted">({{ $sec->key }} · v{{ $sec->version }} · {{ $sec->ai_generated ? 'AI' : 'template/manual' }})</span></h2>
<p class="muted">{{ $sec->source_note }}</p>
<form method="POST" action="/sites/{{ $site->id }}/sections/{{ $sec->id }}">@csrf
<label>Title</label><input name="title" value="{{ $sec->title }}">
<label>Body (simple HTML allowed)</label><textarea name="body" rows="5">{{ $sec->body }}</textarea>
<button class="btn small">Save section</button></form>
<form method="POST" action="/sites/{{ $site->id }}/sections/{{ $sec->id }}/regenerate" style="margin-top:8px">@csrf
<button class="btn small secondary">↻ Regenerate this section only</button></form>
</div>
@endforeach
@endsection
