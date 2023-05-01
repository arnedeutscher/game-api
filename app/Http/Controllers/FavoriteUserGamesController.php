<?php

namespace App\Http\Controllers;

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\Game;
use App\Models\FavoriteUserGame;

use App\Http\Requests\FavoriteUserGame\StoreFavoriteUserGameRequest;
use App\Http\Requests\FavoriteUserGame\DestroyFavoriteUserGameRequest;

use Auth;

class FavoriteUserGamesController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(GameController $gameController)
	{
		$this->gameController = new GameController();
	}

	/**
	 * Get users favorite games.
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(): JsonResponse
	{
		if (!Auth::guard('api')->check()) { return response()->json(['error' => true, 'message' => 'Unauthorized.'], 401); }

		$user_id = Auth::guard('api')->user()->id;
		$favorites = FavoriteUserGame::where('user_id', '=', $user_id)->get();

		if ($favorites->isEmpty()) { return response()->json(['error' => true, 'message' => 'User has no favorite games.', 'favorites' => null], 404); } // error check

		// eager load game details of each favorite game
		$favorites = $favorites->load('game'); 
		$favorites = $favorites->toArray(); // make it editable in foreach loop

		// if an game not found in the database with eager loading, add it from api to database and add game-details to this response
		foreach ($favorites as $key => $favorite) {
			if ($favorite['game'] === null) {

				$game_details = $this->gameController->getGameDetailsById($favorite['game_id']);

				if ($game_details->status() != 200) { abort(404, 'Game not found on api.'); } // should not be possible, because on store function we checked this

				$game_details = $game_details->json();

				$game = $this->gameController->storeGameDetailsInDatabase(
					$game_details['id'],
					$game_details['name'],
					$game_details['description'],
					$game_details['released'],
					$game_details['background_image'],
				);

				$favorites[$key]['game'] = $game;
			}
		}

		return response()->json(['error' => 'false', 'message' => 'Success.', 'games' => $favorites], 200);
	}

	/**
	 * Add games to users favorite games.
	 * 
	 * @param App\Http\Requests\FavoriteUserGame\StoreFavoriteUserGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(StoreFavoriteUserGameRequest $request): JsonResponse
	{
		$user_id = Auth::guard('api')->user()->id;
		$game_id = $request['game_id'];

		// get favorite game of user
		$favorite = FavoriteUserGame::where('user_id', '=', $user_id)->where('game_id', '=', $game_id)->first();

		// check if game exist on database
		if ($favorite) { return response()->json(['error' => true, 'message' => 'Game with id ' . $game_id . ' already exist in the databse.'], 404); }

		// get game from api
		$game_exist = $this->gameController->getGameById( $game_id );
		$game_exist = $game_exist->json();
		$game_exist = $game_exist['results'];

		// check if game exist in api
		if ($game_exist == []) { return response()->json(['error' => true, 'message' => 'Game with id ' . $game_id . ' not found.'], 404); }

		// create new
		$favorites = FavoriteUserGame::create([
			'user_id' => $user_id,
			'game_id' => $game_id,
		]);

		return response()->json(['error' => false, 'message' => 'Game with game_id ' . $game_id . ' stored successful in the database.'], 200);
	}

	/**
	 * Destroy game from users favorite games.
	 * 
	 * @param App\Http\Requests\FavoriteUserGame\DestroyFavoriteUserGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(DestroyFavoriteUserGameRequest $request): JsonResponse
	{
		$user_id = Auth::guard('api')->user()->id;
		$game_id = $request['game_id'];

		$favorite = FavoriteUserGame::where('user_id', '=', $user_id)->where('game_id', '=', $game_id)->first();

		if ($favorite == null) { return response()->json(['error' => true, 'message' => 'Favorite game with game_id ' . $game_id . ' not found in the database.'], 404); } // error check

		$favorite->delete();

		return response()->json(['error' => false, 'message' => 'Game with game_id ' . $game_id . ' successful removed from the database.'], 200);
	}
}