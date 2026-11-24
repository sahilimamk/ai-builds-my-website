<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Fact extends Model {
    protected $guarded = [];
    protected $casts = ['is_chosen' => 'boolean'];
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function source(): BelongsTo { return $this->belongsTo(Source::class); }
}
