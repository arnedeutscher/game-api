<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'game_id',
		'title',
		'summary',
		'release_date',
		'cover_url',
    ];

	/**
     * Get the favorite user games associated with the game id.
     */
    public function favoriteUserGame()
    {
        return $this->hasMany(FavoriteUserGame::class);
    }

}
