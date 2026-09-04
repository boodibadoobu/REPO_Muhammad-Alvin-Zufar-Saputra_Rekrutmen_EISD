<?php

namespace App\Http\Requests;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'officer_note' => ['nullable', 'required_if:status,ditolak', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'officer_note.required_if' => 'Catatan petugas wajib diisi ketika laporan ditolak.',
        ];
    }
}
