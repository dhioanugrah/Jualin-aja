<?php

namespace App\Http\Requests;

use App\Enums\AdPlacement;
use App\Models\Ad;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ad = $this->route('ad');

        return $ad instanceof Ad
            ? $this->user()->can('update', $ad)
            : $this->user()->can('create', Ad::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = ! $this->route('ad') instanceof Ad;

        return [
            'title' => ['required', 'string', 'max:80'],
            'advertiser' => ['required', 'string', 'max:80'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'placement' => ['required', Rule::enum(AdPlacement::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('jualin.ad_max_image_kb')],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'advertiser' => 'pemasang iklan',
            'url' => 'tautan',
            'placement' => 'posisi',
            'starts_at' => 'tanggal mulai',
            'ends_at' => 'tanggal selesai',
            'image' => 'gambar',
        ];
    }
}
