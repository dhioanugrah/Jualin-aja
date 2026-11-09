<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('booking'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');
        $direction = $booking->reviewDirectionFor($this->user());

        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'distinct', Rule::in(config("jualin.review_tags.{$direction->value}"))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['rating' => 'bintang', 'comment' => 'ulasan', 'tags.*' => 'tag'];
    }
}
