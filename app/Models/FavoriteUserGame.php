<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteUserGame extends Model
{
    use HasFactory;

	/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
		'user_id',
		'game_id',
		'rating',
    ];

	/**
     * Get the game associated with the favorite game id.
     */
    public function game()
    {
        return $this->hasOne(Game::class, 'game_id', 'game_id');
    }
}
