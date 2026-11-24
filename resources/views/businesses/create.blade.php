@extends('layouts.app')
@section('content')
<h1>Tell us about your business</h1>
<p class="muted">Give us any mix — whatever you have is enough. We read the files, fetch the links, search the web, and ask you when sources disagree or something is missing.</p>
<form method="POST" action="/businesses" enctype="multipart/form-data" class="card">
@csrf
<label>Business name *</label><input name="name" required placeholder="e.g. Nik Baker's">
<label>City *</label><input name="city" required placeholder="e.g. Chandigarh">
<label>Category</label><input name="category" placeholder="e.g. Bakery &amp; cafe">
<label>Google Business listing URL</label><input name="google_business_url" type="url" placeholder="https://maps.google.com/...">
<label>Social media links (one per line)</label><textarea name="social_links" rows="3" placeholder="https://instagram.com/...&#10;https://facebook.com/..."></textarea>
<label>Upload files — PDF, price list, photos (multiple)</label><input type="file" name="files[]" multiple>
<label>Anything else we should know?</label><textarea name="owner_notes" rows="3"></textarea>
<button class="btn">Create &amp; read my files →</button>
</form>
@endsection
