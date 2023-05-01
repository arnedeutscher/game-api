<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class FilterGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'release_date' => 	['nullable', 'date'], // format: 2010-01-01
			'platform' => 		['nullable', 'numeric'],
			'genre' => 			['nullable', 'numeric'],
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