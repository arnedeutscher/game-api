<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\FavoriteUserGame;
use App\Http\Requests\RatingFavoriteUserGame\RateFavoriteUserGame;
use App\Http\Requests\RatingFavoriteUserGame\RemoveRateFromFavoriteUserGame;

use Auth;

class RatingFavoriteUserGamesController extends Controller
{
	/**
	 * Rate favorite game by respective user.
	 * 
	 * @param App\Http\Requests\RatingFavoriteUserGame\RateFavoriteUserGame
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function rate(RateFavoriteUserGame $request): JsonResponse
	{
		$game_id = $request['game_id'];
		$rating = $request['rating'];

		return $this->update_rating($game_id, $rating);
	}

	/**
	 * Remove rating of favorite game by respective user.
	 * 
	 * @param App\Http\Requests\RatingFavoriteUserGame\RemoveRateFromFavoriteUserGame
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function remove_rating(RemoveRateFromFavoriteUserGame $request): JsonResponse
	{
		return $this->update_rating($request['game_id'], null);
	}

	/**
	 * Update rating of favorite game.
	 * 
	 * @param Int $game_id
	 * @param Int|Null $rating
	 * 
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update_rating(Int $game_id, Int|Null $rating): JsonResponse
	{
		$user_id = Auth::guard('api')->user()->id;
		$favorite = FavoriteUserGame::where('user_id', '=', $user_id)->where('game_id', '=', $game_id)->first();
		
		// error checks
		if (!$favorite) { return response()->json(['error' => true, 'message' => 'Favorite game with combination of user_id and game_id ' . $game_id . ' not found.'], 404); } // not found
		if ($favorite['rating'] === $rating) { 

			$response_message = $rating === null ? 
			'Rating of favorite game with game_id ' . $game_id . ' has already default value.' :
			'Rating of favorite game with game_id ' . $game_id . ' is already rated with ' . $rating . ' points.';
			
			return response()->json(['error' => true, 'message' => $response_message], 400);
		} // already exist

		// update
		$favorite->update(['rating' => $rating]);

		$response_message = $rating === null ? 
			'Game with game_id ' . $game_id . ' was reset to default.' :
			'Game with game_id ' . $game_id . ' was rated with ' . $rating . ' points.';

		return response()->json(['error' => false, 'message' => $response_message], 200);
	}
}