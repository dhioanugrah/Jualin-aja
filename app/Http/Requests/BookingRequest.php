<?php

namespace App\Http\Requests;

use App\Enums\CodArea;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bentuk input booking. Aturan bisnis (jam COD, batas booking aktif, area penjual)
 * diperiksa di BookingService supaya sama untuk semua jalur.
 */
class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Booking::class, $this->route('product')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cod_area' => ['required', Rule::enum(CodArea::class)],
            'cod_location_id' => ['required', 'integer', 'exists:cod_locations,id'],
            'cod_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'offer_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cod_area' => 'area COD',
            'cod_location_id' => 'titik COD',
            'cod_at' => 'waktu COD',
            'note' => 'catatan',
        ];
    }
}
