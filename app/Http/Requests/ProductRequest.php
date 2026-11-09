<?php

namespace App\Http\Requests;

use App\Enums\CodArea;
use App\Enums\HandoverMethod;
use App\Enums\ProductCondition;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form Jual Barang dan Edit Barang (F2).
 */
class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'condition' => ['required', Rule::enum(ProductCondition::class)],
            'is_donation' => ['boolean'],
            'price' => ['exclude_if:is_donation,true', 'required', 'integer', 'min:0', 'max:100000000'],
            'handover_method' => ['required', Rule::enum(HandoverMethod::class)],
            'cod_areas' => ['required', 'array', 'min:1'],
            'cod_areas.*' => ['distinct', Rule::enum(CodArea::class)],
            'location' => ['nullable', 'string', 'max:100'],
            'photos' => ['array', 'max:'.config('jualin.max_photos')],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('jualin.max_photo_kb')],
            'photo_order' => ['array'],
            'photo_order.*' => ['string', 'regex:/^(e|n):\d+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cod_areas.required' => 'Pilih minimal satu area COD.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 2 MB.',
            'photos.*.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama barang',
            'description' => 'deskripsi',
            'category_id' => 'kategori',
            'condition' => 'kondisi',
            'price' => 'harga',
            'handover_method' => 'metode penyerahan',
            'location' => 'lokasi umum',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_donation' => $this->boolean('is_donation'),
            'price' => $this->filled('price') ? (int) preg_replace('/\D/', '', (string) $this->input('price')) : null,
        ]);
    }

    /**
     * Jumlah foto (foto lama yang dipertahankan + foto baru) harus 1–5.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $total = count($this->keptImageIds()) + count($this->file('photos', []));

                if ($total < 1) {
                    $validator->errors()->add('photos', 'Tambahkan minimal 1 foto barang.');
                } elseif ($total > config('jualin.max_photos')) {
                    $validator->errors()->add('photos', 'Maksimal '.config('jualin.max_photos').' foto per barang.');
                }
            },
        ];
    }

    /**
     * ID foto lama yang dipertahankan, hanya milik barang yang sedang diedit.
     *
     * @return array<int, int>
     */
    public function keptImageIds(): array
    {
        $product = $this->route('product');

        if (! $product instanceof Product) {
            return [];
        }

        $requested = collect($this->input('photo_order', []))
            ->filter(fn (mixed $token): bool => is_string($token) && str_starts_with($token, 'e:'))
            ->map(fn (string $token): int => (int) substr($token, 2));

        return $product->images()->whereKey($requested)->pluck('id')->all();
    }
}
