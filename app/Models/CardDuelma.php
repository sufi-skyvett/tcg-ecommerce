<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardDuelma extends Model
{
    use HasFactory;

    protected $table = 'card_duelmas';

    protected $fillable = [
        'name',
        'set_code',
        'collector_number',
        'card_type',
        'civilization',
        'mana_cost',
        'races',
        'effect_text',
        'image_path',
        'payload',
    ];
}
