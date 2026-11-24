<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Source extends Model {
    protected $guarded = [];
    protected $casts = ['injection_flags' => 'array'];
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function facts(): HasMany { return $this->hasMany(Fact::class); }
}
