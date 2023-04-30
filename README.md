# Coding Challenge

## Title: Laravel Game API Aggregator

### Description:

Your task is to create a REST API using Laravel that aggregates game data from an external open API. The API should allow users to search for games, retrieve game details, and manage a list of favorite games. As a bonus, you can secure the application with basic user authentication and enable favorite games functionality only for authenticated users.

### Requirements:

1. Set up a new Laravel project using Laravel 8 or above.
2. Use a SQLite database for this project.
3. Register for an API key from an open game database API, such as RAWG (https://rawg.io/apidocs) or IGDB (https://api.igdb.com/).
4. Create a Game model with the following attributes: id, external_id, title, summary, release_date, and cover_url.
5. Create a migration for the Game model and run the migration to create the games table in the database.
6. Create a GameController that handles the following operations:
    1. Search for games using the external API based on a query string.
    2. Retrieve game details from the external API and store them in the local database.
    3. Manage a list of favorite games for authenticated users (optional).
7. Create the following API routes for managing games:
    1. GET /api/games/search?q={query}: Search for games using the external API based on a query string and return the results as JSON.
    2. GET /api/games/{external_id}: Retrieve game details from the external API based on the external_id, store them in the local database, and return the game details as JSON.
    3. GET /api/favorites: Retrieve a list of favorite games for the authenticated user as JSON (optional).
    4. POST /api/favorites: Add a game to the authenticated user's list of favorite games (optional).
    5. DELETE /api/favorites/{game_id}: Remove a game from the authenticated user's list of favorite games (optional).
8. Run `php artisan passport:keys` to deploying passport.
9. Cache the results of game searches and game details to reduce the number of calls to the external API.

### Bonus:

1. Implement basic user authentication using Laravel's built-in authentication system, such as Laravel Passport, to secure the API.
2. Ensure that only authenticated users can add or remove games from their list of favorite games.
3. Implement rate limiting for the API to prevent excessive usage.
4. ~~Add the ability to filter game searches by release date, platform, or genre.~~
5. ~~Implement a recommendation system that suggests games based on the authenticated user's list of favorite games.~~

### Submission:

Please provide a link to a GitHub or Gitlab repository containing your Laravel project. Make sure your repository is public so we can review your code. Include a README file with instructions on how to set up and use the API, as well as any necessary API key registration information.

### Installation

1. Download this repository
2. `composer install`
3. `composer update`
4. `npm install`
5. Rename the `.env.example` file to `.env` and add the following configurations:
	- `DB_CONNECTION=sqlite`
	- `DB_DATABASE="C:\\absolute\\path\\to\\game-api\\database\\database.sqlite"`
	- `RAWG_API_KEY=YOUR-RAWG-API-KEY`
	- `RAWG_API_HTTP="https://api.rawg.io/api"`
6. Create the file `database.sqlite` in the project-folder `database`
7. Migrate the database and seed with `php artisan migrate --seed` 
8. Run `php artisan passport:keys` to deploying passport.
9. Create passport client for testing by using `php artisan passport:client --personal` and choose `0` when asking `[0] users`
10. Start the webserver with `php artisan serve` and use the webserver url to get access to the api.

### Authentification
It is recommended to use a software like [Postman](https://www.postman.com/) to make api requests.

####  Create and get bearer token
use `POST /api/login` and add following form data:
```
email : "test@example.com"
password : "password"
```
The request returns the token, which must be used for future requests.

Use the token to check if the authentification was successful by using:
`GET /api/user` with Bearer-Token, you got in the step before.

### How to use API

- Simply get game from RAWG API and cache the result *(works without authentication).* 
<br/>`GET /api/games/search?q={query}`

- Get games by id and save it to database. Use also database entry if exist to save api calls *(works without authentication).*
<br/>`GET /api/games/{id}`

- Get favorite games from **authenticated** user.
<br/>`GET /api/user/games/favorites`

- Add favorite game to **authenticated** user
<br/>`POST /api/user/games/favorites?game_id={id}`

- Remove favorite game from **authenticated** user
<br/>`DELETE /api/user/games/favorites?game_id={id}`

### Rate Limiting
All routes that are not authenticated have a 5 hits per minute rate limiter middleware. To change that, edit the `$perMinute` variable in `App\Http\Middleware\SearchGameRequestLimiter` and `App\Http\Middleware\RetrieveGameDetailsRequestLimiter`.
