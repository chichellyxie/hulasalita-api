<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    public const ENGLISH = 'en';
    public const FILIPINO = 'fil';
    public const PANGASINAN = 'pag';

    protected $fillable = [
        'code',
        'name',
    ];

    public function puzzles(): HasMany
    {
        return $this->hasMany(Puzzle::class);
    }
}
