<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteUserGames extends Model
{
    use HasFactory;

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'user_id',
		'game_ids',
		'summary',
		'release_date',
		'cover_url',
    ];
}
