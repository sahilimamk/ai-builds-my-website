<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SiteSection extends Model {
    protected $guarded = [];
    protected $casts = ['ai_generated' => 'boolean'];
    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
}
