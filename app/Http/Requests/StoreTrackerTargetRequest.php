<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrackerTargetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isChecklist = $this->input('type') === 'checklist';

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'period' => ['required', 'string', Rule::in(['Harian', 'Mingguan', 'Bulanan'])],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'string', Rule::in(['numeric', 'checklist'])],
            'target_value' => [
                Rule::requiredIf(! $isChecklist),
                'nullable',
                'numeric',
                Rule::when(! $isChecklist, ['gt:0']),
            ],
            'unit' => [
                Rule::requiredIf(! $isChecklist),
                'nullable',
                'string',
                'max:64',
            ],
            'steps' => [
                Rule::requiredIf($isChecklist),
                'nullable',
                'array',
                Rule::when($isChecklist, ['min:1']),
            ],
            'steps.*.id' => ['required_with:steps', 'string', 'max:128'],
            'steps.*.text' => ['required_with:steps', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul target wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'due_date.required' => 'Tenggat wajib diisi.',
            'due_date.after_or_equal' => 'Tenggat tidak boleh lebih awal dari tanggal mulai.',
            'target_value.required' => 'Nilai target harus berupa angka positif (> 0).',
            'target_value.gt' => 'Nilai target harus berupa angka positif (> 0).',
            'unit.required' => 'Satuan target wajib diisi (misal: sesi, halaman, km).',
            'steps.required' => 'Target checklist wajib memiliki minimal satu langkah yang terisi.',
            'steps.min' => 'Target checklist wajib memiliki minimal satu langkah yang terisi.',
            'steps.*.text.required_with' => 'Teks langkah checklist tidak boleh kosong.',
        ];
    }
}
