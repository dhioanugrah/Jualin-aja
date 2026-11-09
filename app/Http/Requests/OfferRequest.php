<?php

namespace App\Http\Requests;

use App\Models\Offer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Offer::class, $this->route('product')]);
    }

    /**
     * Harga tawaran harus di bawah harga barang.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $price = $this->route('product')->price;

        return [
            'price' => ['required', 'integer', 'min:1000', 'lt:'.$price],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.lt' => 'Harga tawaran harus di bawah harga barang ('.$this->route('product')->formatted_price.').',
            'price.min' => 'Harga tawaran minimal Rp 1.000.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->price)) {
            $this->merge(['price' => preg_replace('/\D/', '', $this->price)]);
        }
    }
}
