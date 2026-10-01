<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cards';

    protected $fillable = [
        'name',
        'card_code',
        'barcode',
        'description',
        'front_image_path',
        'back_image_path',
        'game',
        'brand',
        'language',
        'rarity',
        'finish',
        'set_id',
        'collector_number',
        'release_date',
        'is_active',
        'is_sealed_product',
    ];

    protected $casts = [
        'release_date'      => 'date',
        'is_active'         => 'boolean',
        'is_sealed_product' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFrontImageUrlAttribute()
    {
        return $this->front_image_path
            ? asset('storage/' . $this->front_image_path)
            : null;
    }

    public function getBackImageUrlAttribute()
    {
        return $this->back_image_path
            ? asset('storage/' . $this->back_image_path)
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (useful in POS search)
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGame($query, $game)
    {
        return $query->where('game', $game);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('card_code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships (future ready)
    |--------------------------------------------------------------------------
    */

    // If you later create sets table
    public function set()
    {
        return $this->belongsTo(Set::class);
    }

    // If later you split variants (recommended when scaling)
    public function variants()
    {
        return $this->hasMany(CardVariant::class);
    }

    // If you build inventory lots later
    public function inventoryLots()
    {
        return $this->hasMany(InventoryLot::class, 'variant_id');
    }
}
