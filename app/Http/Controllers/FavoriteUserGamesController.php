<?php

namespace App\Http\Controllers;

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\FavoriteUserGames;
use App\Models\Game;

use App\Http\Requests\FavoriteUserGames\StoreFavoriteUserGameRequest;
use App\Http\Requests\FavoriteUserGames\DestroyFavoriteUserGameRequest;

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
		if (!Auth::guard('api')->check()) { return response()->json(['message' => 'Unauthorized.'], 401); }

		$user_id = Auth::guard('api')->user()->id;
		$favorites = FavoriteUserGames::where('user_id', '=', $user_id)->first();

		if ($favorites == null) { return response()->json(['error' => true, 'message' => 'User has no entry.', 'favorites' => null], 404); } // error check

		$game_ids = $favorites['game_ids'];
		$game_ids = json_decode($game_ids, true); // "true" makes it an array

		$favorites_db = Game::whereIn('external_id', $game_ids)->get(); // take a look on the database first before making api requests
		$array_of_favorite_games = $favorites_db->toArray(); // collection to array for better handling the next steps

		foreach($game_ids as $game_id) {

			// Check if game_id already exist in database array
			foreach($array_of_favorite_games as $fav) {
				if ($game_id == $fav['external_id']) { continue 2; } // it exist, no api request necessary, so continue both iterations
			}

			$game_detail = $this->gameController->getGameDetailsById( $game_id );
			
			// existing?
			if ($game_detail->status() == 200) {

				// store into database for the next query, to save api requests
				$game_detail = $this->gameController->storeGameDetailsInDatabase(
					$game_detail['id'],
					$game_detail['name'],
					$game_detail['description'],
					$game_detail['released'],
					$game_detail['background_image'],
				);

				//$game_detail['from_db'] = false; // only to dev-check if entry from db or not
				$array_of_favorite_games[] = $game_detail->toArray(); // add to main array
			}
		}

		return response()->json(['error' => 'false', 'message' => 'Success.', 'games' => $array_of_favorite_games], 200);
	}

	/**
	 * Add games to users favorite games.
	 * 
	 * @param App\Http\Requests\FavoriteUserGames\StoreFavoriteUserGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(StoreFavoriteUserGameRequest $request): JsonResponse
	{
		$user_id = Auth::guard('api')->user()->id;
		$game_id = $request['game_id'];

		$favorites = FavoriteUserGames::where('user_id', '=', $user_id)->first();

		if ($favorites == null) {

			// create new
			$favorites = FavoriteUserGames::create([
				'user_id' => $user_id,
				'game_ids' => json_encode([$game_id]) // first id must be added to an array so more ids can be added later
			]);

		}else{

			// update
			$game_ids = $favorites['game_ids'];
			$game_ids = json_decode($game_ids, true); // "true" makes it an array
			if ($game_ids == null) { $game_ids = []; }; // Make shure, $game_ids is an array (can happen when the database column is manually deleted).

			// already in the database?
			if (in_array($game_id, $game_ids)) { return response()->json(['error' => true, 'message' => 'Game with game_id ' . $game_id . ' already stored in the database.'], 400); }

			$game_ids[] = $game_id;
			$game_ids = json_encode($game_ids);
			$favorites->update(['game_ids' => $game_ids]);
		}

		return response()->json(['error' => false, 'message' => 'Game with game_id ' . $game_id . ' stored successful in the database.'], 200);
	}

	/**
	 * Destroy game from users favorite games.
	 * 
	 * @param App\Http\Requests\FavoriteUserGames\DestroyFavoriteUserGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(DestroyFavoriteUserGameRequest $request): JsonResponse
	{
		$user_id = Auth::guard('api')->user()->id;
		$game_id = $request['game_id'];

		$favorites = FavoriteUserGames::where('user_id', '=', $user_id)->first();

		if ($favorites == null) { return response()->json(['error' => true, 'message' => 'User has no entry.', 'favorites' => null], 404); } // error check

		// get game ids
		$game_ids = $favorites['game_ids'];
		
		$game_ids = json_decode($game_ids, true); // "true" makes at an array
		if ($game_ids == null) { $game_ids = []; }; // Make shure, $game_ids is an array (can happen when the database column is manually deleted).

		// get key of entry
		$key = array_search($game_id, $game_ids);

		// check if entry exist
		if ($key === false) { return response()->json(['error' => true, 'message' => 'Game with game_id ' . $game_id . ' not found in the database.'], 404); }

		unset($game_ids[$key]); // remove id from array
		$game_ids = json_encode($game_ids);
		$favorites->update(['game_ids' => $game_ids]);

		return response()->json(['error' => false, 'message' => 'Game with game_id ' . $game_id . ' successful removed from the database.'], 200);
	}
}