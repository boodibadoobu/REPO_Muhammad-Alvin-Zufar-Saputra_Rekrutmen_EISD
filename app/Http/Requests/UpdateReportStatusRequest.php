<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(ReportStatus::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nextStatus = ReportStatus::tryFrom((string) $value);
                    $report = $this->route('report');

                    if ($nextStatus && $report && ! $report->status->canTransitionTo($nextStatus)) {
                        $fail("Status {$report->status->label()} tidak dapat langsung diubah menjadi {$nextStatus->label()}.");
                    }
                },
            ],
            'officer_note' => ['nullable', 'required_if:status,ditolak', 'string', 'max:1000'],
            'resolution_photo' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('status') === ReportStatus::Selesai->value
                    && $this->route('report')?->status === ReportStatus::Diproses),
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'officer_note.required_if' => 'Catatan petugas wajib diisi ketika laporan ditolak.',
            'resolution_photo.required' => 'Foto bukti penyelesaian wajib diunggah untuk menutup laporan.',
        ];
    }
}
