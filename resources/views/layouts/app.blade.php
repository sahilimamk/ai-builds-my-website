<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title','AI Builds My Website')</title><link rel="stylesheet" href="/css/app.css"></head>
<body><header><a class="brand" href="/">🤖 AI Builds My Website</a><a href="/">Dashboard</a><a href="/businesses/create">+ New business</a></header>
<main>
@if(session('ok'))<div class="alert green">{{ session('ok') }}</div>@endif
@if(session('error'))<div class="alert red">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert red">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@yield('content')</main>
<footer>Golden rule: never invent facts. Every fact on a generated site carries a source; anything unconfirmed says so.</footer></body></html>
