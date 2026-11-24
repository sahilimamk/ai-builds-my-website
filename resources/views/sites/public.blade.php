<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $site->title }} — {{ $site->business->city }}</title><link rel="stylesheet" href="/css/app.css"></head>
<body class="theme-{{ $site->theme }}">
@if($preview)<div class="alert" style="margin:0;border-radius:0">DRAFT PREVIEW — not published yet. <a href="/sites/{{ $site->id }}/edit">Back to editor</a></div>@endif
<main>
<div class="site-hero"><h1>{{ $site->title }}</h1><p>{{ $site->business->city }}@if($site->business->category) · {{ $site->business->category }}@endif</p></div>
@foreach($site->sections as $sec)
<section class="site-section"><h2>{{ $sec->title }}</h2>{!! $sec->body !!}<p class="muted">Source: {{ $sec->source_note }}</p></section>
@endforeach
@php $photos = $site->business->sources->where('kind','upload')->filter(fn($s)=>preg_match('/\.(jpg|jpeg|png|webp)$/i', $s->path ?? '')); @endphp
@if($photos->count())<section class="site-section"><h2>Gallery</h2><div class="gallery">@foreach($photos as $p)<img src="/storage/{{ $p->path }}" alt="Photo from {{ $site->title }}">@endforeach</div></section>@endif
<section class="site-section"><h2>Sources &amp; verification</h2>
<p class="muted">Every fact above comes from one of these sources. Anything we could not verify is marked “Not confirmed — ask the owner” — it is never guessed.</p>
<ul>@foreach($site->business->sources as $s)<li>{{ $s->kind }} — {{ $s->url ?? $s->path }} ({{ $s->status }})</li>@endforeach</ul>
@php $unverified = $site->business->facts->where('confidence','unverified'); @endphp
@if($unverified->count())<p><strong>Not confirmed:</strong> {{ $unverified->pluck('field')->unique()->implode(', ') }} — ask the owner.</p>@endif
<p class="muted">Built with AI Builds My Website · {{ $site->business->name }}, {{ $site->business->city }}</p></section>
</main></body></html>
