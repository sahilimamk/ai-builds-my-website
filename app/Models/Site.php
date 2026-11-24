<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Site extends Model {
    protected $guarded = [];
    protected $casts = ['published_at' => 'datetime'];
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function sections(): HasMany { return $this->hasMany(SiteSection::class); }
    public function generations(): HasMany { return $this->hasMany(Generation::class); }
    public function section(string $key): ?SiteSection {
        return $this->sections()->where('key', $key)->first();
    }
}
