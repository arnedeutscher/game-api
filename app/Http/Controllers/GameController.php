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

		$games = $this->getGamesById( $request['q'] );
		if ($games->status() != 200) { return response()->json(['error' => true, 'message' => $games->getReasonPhrase()], $games->status()); } // error check
		
		Cache::put($request['q'], $games->json(), now()->addMinutes(10)); // add response to cache for the next 10 minutes

		return response()->json([
			'error' => false,
			'message' => 'Games were loaded via api.', 
			'games' => $games->json()
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
	public function getGamesById(String $id): Response
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
