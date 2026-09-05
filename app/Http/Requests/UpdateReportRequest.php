<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Pilih minimal satu kategori masalah.',
            'description.min' => 'Deskripsi minimal 20 karakter agar laporan mudah dipahami.',
            'latitude.required' => 'Pilih titik lokasi laporan pada peta.',
            'longitude.required' => 'Pilih titik lokasi laporan pada peta.',
        ];
    }
}
