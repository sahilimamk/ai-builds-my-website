<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Conflict extends Model {
    protected $guarded = [];
    protected $casts = ['values' => 'array'];
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
}
