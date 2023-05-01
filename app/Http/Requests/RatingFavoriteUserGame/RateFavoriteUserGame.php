<?php

namespace App\Http\Requests\RatingFavoriteUserGame;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

use Auth;

class RateFavoriteUserGame extends FormRequest
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
			'rating' => ['required', 'numeric', 'min:0', 'max:10'],
        ];
    }

	/**
     * Returns validations as a json object.
	 * 
	 * @param Illuminate\Contracts\Validation\Validator
     *
     * @return Illuminate\Http\JsonResponse
     */
	protected function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(
            response()->json([
                'error' => true,
                'message' => $validator->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
