<?php

namespace App\Http\Requests;

use App\Actions\FindPotentialDuplicateReports;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'captcha_answer' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $expected = $this->session()->get('report_captcha_answer');

                    if ($expected === null || ! hash_equals((string) $expected, trim((string) $value))) {
                        $fail('Jawaban verifikasi keamanan tidak tepat.');
                    }
                },
            ],
            'duplicate_confirmation' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['latitude', 'longitude', 'category_ids', 'category_ids.*'])
                || $this->boolean('duplicate_confirmation')) {
                return;
            }

            $duplicates = app(FindPotentialDuplicateReports::class)->handle(
                (float) $this->input('latitude'),
                (float) $this->input('longitude'),
                array_map('intval', (array) $this->input('category_ids', [])),
            );

            if ($duplicates->isNotEmpty()) {
                $ids = $duplicates->take(3)->map(
                    fn (Report $report): string => '#'.str_pad((string) $report->id, 5, '0', STR_PAD_LEFT),
                )->implode(', ');

                $validator->errors()->add(
                    'duplicate_confirmation',
                    "Terdeteksi laporan aktif serupa dalam radius 150 meter ({$ids}). Periksa kembali, lalu centang konfirmasi jika kondisinya memang berbeda.",
                );
            }
        }];
    }

    protected function passedValidation(): void
    {
        $this->session()->forget('report_captcha_answer');
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Pilih minimal satu kategori masalah.',
            'photo.required' => 'Foto bukti wajib diunggah.',
            'description.min' => 'Deskripsi minimal 20 karakter agar laporan mudah dipahami.',
            'latitude.required' => 'Pilih titik lokasi laporan pada peta.',
            'longitude.required' => 'Pilih titik lokasi laporan pada peta.',
            'captcha_answer.required' => 'Jawab verifikasi keamanan sebelum mengirim laporan.',
        ];
    }
}
