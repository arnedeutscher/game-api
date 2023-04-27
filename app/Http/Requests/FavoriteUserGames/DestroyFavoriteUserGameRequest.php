<?php

namespace App\Http\Requests\FavoriteUserGames;

use Illuminate\Foundation\Http\FormRequest;

use Auth;

class DestroyFavoriteUserGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'game_id' => ['required', 'numeric'],
        ];
    }
}
