<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Business extends Model {
    protected $guarded = [];
    protected $casts = ['disambiguation' => 'array'];
    public function sources(): HasMany { return $this->hasMany(Source::class); }
    public function facts(): HasMany { return $this->hasMany(Fact::class); }
    public function conflicts(): HasMany { return $this->hasMany(Conflict::class); }
    public function questions(): HasMany { return $this->hasMany(Question::class); }
    public function site(): HasOne { return $this->hasOne(Site::class); }
    public function generations(): HasMany { return $this->hasMany(Generation::class); }
    /** Facts the site builder may use: chosen, or verified, or owner-answered. */
    public function usableFacts() {
        return $this->facts()->where(function ($q) {
            $q->where('is_chosen', true)->orWhere('confidence', 'verified');
        })->get()->groupBy('field')->map(fn ($g) => $g->first()->value);
    }
    public function chosenFact(string $field): ?string {
        $f = $this->facts()->where('field', $field)->where('is_chosen', true)->first()
            ?? $this->facts()->where('field', $field)->where('confidence', 'verified')->first();
        return $f?->value;
    }
}
