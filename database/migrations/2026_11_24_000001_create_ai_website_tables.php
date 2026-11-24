<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('city'); $t->string('category')->nullable();
            $t->string('slug')->unique(); $t->string('status')->default('draft'); // draft|needs_info|ready|published
            $t->text('owner_notes')->nullable(); $t->string('google_business_url')->nullable();
            $t->text('social_links')->nullable(); // newline-separated URLs, as typed by owner
            $t->text('disambiguation')->nullable(); // JSON candidates shown at review
            $t->string('chosen_candidate')->nullable();
            $t->timestamps();
        });
        Schema::create('sources', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('kind'); // upload|social|google_business|web_search|manual
            $t->string('url')->nullable(); $t->string('path')->nullable();
            $t->string('status')->default('pending'); // pending|ok|blocked|failed
            $t->longText('raw_text')->nullable(); $t->longText('extracted_text')->nullable();
            $t->text('error')->nullable(); $t->json('injection_flags')->nullable();
            $t->timestamps();
        });
        Schema::create('facts', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $t->string('field'); $t->text('value'); $t->string('confidence')->default('unverified'); // verified|conflicting|unverified
            $t->boolean('is_chosen')->default(false); $t->timestamps();
        });
        Schema::create('conflicts', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('field'); $t->json('values'); // [{value, source}]
            $t->string('status')->default('open'); $t->text('resolution')->nullable();
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('field')->nullable(); $t->text('question'); $t->string('status')->default('open');
            $t->text('answer')->nullable(); $t->timestamps();
        });
        Schema::create('sites', function (Blueprint $t) {
            $t->id(); $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('slug')->unique(); $t->string('title'); $t->string('theme')->default('modern');
            $t->string('status')->default('draft'); $t->timestamp('published_at')->nullable(); $t->timestamps();
        });
        Schema::create('site_sections', function (Blueprint $t) {
            $t->id(); $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('key'); $t->string('title'); $t->longText('body'); $t->text('source_note')->nullable();
            $t->boolean('ai_generated')->default(false); $t->unsignedInteger('version')->default(1);
            $t->timestamps();
        });
        Schema::create('generations', function (Blueprint $t) {
            $t->id(); $t->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $t->string('provider')->default('gemini'); $t->string('status')->default('pending');
            $t->longText('raw_response')->nullable(); $t->text('error')->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('generations'); Schema::dropIfExists('site_sections'); Schema::dropIfExists('sites');
        Schema::dropIfExists('questions'); Schema::dropIfExists('conflicts'); Schema::dropIfExists('facts');
        Schema::dropIfExists('sources'); Schema::dropIfExists('businesses');
    }
};
