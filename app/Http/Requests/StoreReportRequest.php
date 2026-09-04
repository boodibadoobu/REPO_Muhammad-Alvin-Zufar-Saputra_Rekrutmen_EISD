<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Report::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'photo' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Pilih minimal satu kategori masalah.',
            'photo.required' => 'Foto bukti wajib diunggah.',
            'description.min' => 'Deskripsi minimal 20 karakter agar laporan mudah dipahami.',
        ];
    }
}
