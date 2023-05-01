<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\Response;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

use App\Models\Game;
//use PhpParser\Node\Expr\Cast\Array_;

use App\Http\Requests\Game\SearchGameRequest;
use App\Http\Requests\Game\FilterGameRequest;

class GameController extends Controller
{
	private $api_key;
	private $api_http;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->api_key = env('RAWG_API_KEY');
		$this->api_http = env('RAWG_API_HTTP');
	}

	/**
	 * Search for games using the external API based on a query string.
	 * 
	 * @param App\Http\Requests\SearchGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
    public function search(SearchGameRequest $request): JsonResponse
	{
		// check if id already exists in cache (work for later: put this into an seperate request)
		$cache = Cache::get($request['q']); // query
		if ($cache != null) {
			return response()->json([
				'error' => false,
				'message' => 'Games were loaded from the cache.',
				'games' => $cache['results']
			], 200);
		} // return cached data

		$games = $this->getGameById( $request['q'] );
		if ($games->status() != 200) { return response()->json(['error' => true, 'message' => $games->getReasonPhrase()], $games->status()); } // error check
		
		Cache::put($request['q'], $games->json(), now()->addMinutes(10)); // add response to cache for the next 10 minutes

		return response()->json([
			'error' => false,
			'message' => 'Games were loaded via api.', 
			'games' => $games->json()
		], 200);
	}

	/**
	 * Get games by filter.
	 * 
	 * @param App\Http\Requests\FilterGameRequest
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 * 
	 * @link https://api.rawg.io/docs/#operation/games_list
	 */
    public function filter(FilterGameRequest $request): JsonResponse
	{
		$release_date = $request['release_date'] ?? null;
		$platform = $request['platform'] ?? null;
		$genre = $request['genre'] ?? null;

		// at least one element is required
		if (!$release_date && !$platform && !$genre) {
			return response()->json([
				'error' => true,
				'message' => 'At least one filter of release_date, platform, or genre field is required.', 
			], 200);
		}

		// prepare seach array
		$search_array = [ 'key' => $this->api_key, ]; // add api key as default

		// associate $requested filters with RAWG filter names
		/*
			Why this complicated way?
			Even if RAWG allows a filter selection with several elements, the task in this challenge is to be able to use only one element per filter.
		*/
		if ($release_date) { $search_array['dates'] = $release_date . ',' . $release_date; } // 'dates' required two dates to work
		if ($platform) { $search_array['platforms'] = $platform; }
			/*
				Available platform filters:
				Atari 8-bit: 78, Commodore / Amiga: 3, Linux: 6, MSX: 16, Mac: 14, Nintendo 3DS: 41, Nintendo 64: 43, Nintendo DS: 20, Nintendo GameCube: 22,
				Nintendo Switch: 7, Nintendo Wii: 5, Nintendo Wii U: 130, PC: 4, PlayStation: 27, PlayStation 2: 19, PlayStation 3: 9, PlayStation 4: 18,
				PlayStation 5: 187, PlayStation Portable: 13, PlayStation Vita: 46, SEGA 32X: 74, SEGA CD: 72, SEGA Master System: 43, SEGA Mega Drive/Genesis: 18,
				SEGA Saturn: 33, Xbox: 12, Xbox 360: 11, Xbox One: 1, Xbox Series X/S: 186
			*/
		if ($genre) { $search_array['genres'] = $genre; }
			/*
				Available genre filters:
				Action: 4, Adventure: 3, Casual: 5, Indie: 51, Massively Multiplayer: 10, Racing: 1, RPG: 5, Simulation: 14, Sports: 15, Strategy: 2,
				Educational: 19, Fighting: 6, Puzzle: 7, Card: 17, Board Games: 16, Family: 11, Platformer: 9, Arcade: 23, Music: 8
			*/

		// make cache identifier, for this, a null value must be identified in a string
		$cache_key = ($release_date == null ? '_null_' : $release_date) . 
					 ($platform == null ? '_null_' : $platform) . 
					 ($genre == null ? '_null_' : $genre);

		// check if combination of $release_date, $platform, $genre already exists in cache
		$cache = Cache::get($cache_key); // query
		if ($cache != null) {
			return response()->json([
				'error' => false,
				'message' => 'Filtered games were loaded from the cache.',
				'games' => $cache['results']
			], 200);
		} // return cached data

		// make api request
		$games = Http::get($this->api_http . '/games', $search_array);

		// error check
		if ($games->status() != 200) { return response()->json(['error' => true, 'message' => $games->getReasonPhrase()], $games->status()); }
		
		Cache::put($cache_key, $games->json(), now()->addMinutes(10)); // add response to cache for the next 10 minutes

		return response()->json([
			'error' => false,
			'message' => 'Filtered games were loaded via api.', 
			'game' => $games->json()
		], 200);
	}

	/**
	 * Retrieve game details from the external API and store them in the local database.
	 * 
	 * @param String  $external_id
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function retrieve_details(String $external_id): JsonResponse
	{
		if (!is_numeric($external_id)) { return response()->json(['error' => true, 'message' => 'Id must be numeric.'], 400); }

		// check if id already exists in cache
		$cache = Cache::get('eID_' . $external_id);
		if ($cache != null) {
			return response()->json([
				'error' => false,
				'message' => 'Games were loaded from the database.',
				'data' => Game::where('external_id', '=', $external_id)->first(),
			], 200);
		} // return cached data

		$game_details = $this->getGameDetailsById($external_id);

		if ($game_details->status() != 200) { return response()->json(['error' => true, 'message' => $game_details->getReasonPhrase()], $game_details->status()); } // error check

		$game_details = $game_details->json();

		$data = $this->storeGameDetailsInDatabase(
			$game_details['id'],
			$game_details['name'],
			$game_details['description'],
			$game_details['released'],
			$game_details['background_image'],
		);

		Cache::put('eID_' . $external_id, true, now()->addMinutes(10)); // add response to cache for the next 10 minutes

		return response()->json([
			'error' => false,
			'message' => 'Game details stored to the database.',
			'data' => $data,
		], 200);
	}

	/**
	 * Store game details into database.
	 * 
	 * @param String $external_id
	 * @param String $title
	 * @param String $summary
	 * @param String $release_date
	 * @param String $cover_url
	 * 
	 * @return
	 */
	public function storeGameDetailsInDatabase(String $external_id, String $title, String $summary, String $release_date, String $cover_url)
	{
		// only create entry if not exist
		return Game::firstOrCreate([
			'external_id' => $external_id,
			'title' => $title,
			'summary' => $summary,
			'release_date' => $release_date,
			'cover_url' => $cover_url,
		]);
	}

	/**
	 * Get rawg.io games.
	 * 
	 * @param String $id
	 * 
	 * @return Illuminate\Http\Client\Response
	 */
	public function getGameById(String $id): Response
	{
		return Http::get($this->api_http . '/games', [
			'key' => $this->api_key,
			'search' => $id,
		]);
	}

	/**
	 * Get rawg.io game details.
	 * 
	 * @param String $id
	 * 
	 * @return Illuminate\Http\Client\Response
	 */
	public function getGameDetailsById(String $id): Response
	{
		return Http::get($this->api_http . '/games/' . $id, [
			'key' => $this->api_key,
		]);
	}
}
