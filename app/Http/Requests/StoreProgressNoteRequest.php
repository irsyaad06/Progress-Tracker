<?php

namespace App\Http\Requests;

use App\Models\TrackerTarget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProgressNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation when multipart JSON strings are sent.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('completed_step_ids'))) {
            $decoded = json_decode((string) $this->input('completed_step_ids'), true);
            $this->merge(['completed_step_ids' => is_array($decoded) ? $decoded : []]);
        }

        if (is_string($this->input('existing_photos'))) {
            $decoded = json_decode((string) $this->input('existing_photos'), true);
            $this->merge(['existing_photos' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tracker_target_id' => ['required', 'integer', 'exists:tracker_targets,id'],
            'note_date' => ['required', 'date'],
            'added_value' => ['nullable', 'numeric', 'min:0'],
            'completed_step_ids' => ['nullable', 'array'],
            'completed_step_ids.*' => ['string', 'max:128'],
            'work_result' => ['nullable', 'string', 'max:5000'],
            'obstacle' => ['nullable', 'string', 'max:5000'],
            'obstacle_action' => ['nullable', 'string', Rule::in(['keep', 'mark', 'resolve'])],
            'next_step' => ['nullable', 'string', 'max:1000'],
            'existing_photos' => ['nullable', 'array'],
            'existing_photos.*.id' => ['required', 'integer'],
            'existing_photos.*.caption' => ['nullable', 'string', 'max:500'],
            'new_photos' => ['nullable', 'array'],
            'new_photos.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:10240'],
            'new_photo_captions' => ['nullable', 'array'],
            'new_photo_captions.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Cross-field validation rules.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $target = TrackerTarget::query()->find($this->integer('tracker_target_id'));
                $hasWork = trim((string) $this->input('work_result')) !== '';
                $hasObstacle = trim((string) $this->input('obstacle')) !== '';

                $hasAchievement = $target?->type === 'checklist'
                    ? count((array) $this->input('completed_step_ids', [])) > 0
                    : (float) $this->input('added_value', 0) > 0;

                if (! $hasWork && ! $hasObstacle && ! $hasAchievement) {
                    $validator->errors()->add(
                        'work_result',
                        'Isi minimal Hasil Pekerjaan, Kendala, atau tambahan capaian.',
                    );
                }
            },
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
            'tracker_target_id.required' => 'Pilih target terlebih dahulu.',
            'tracker_target_id.exists' => 'Target yang dipilih tidak ditemukan.',
            'note_date.required' => 'Tanggal catatan wajib diisi.',
            'added_value.min' => 'Tambahan capaian harus berupa angka 0 atau lebih besar.',
            'new_photos.*.mimes' => 'File foto bukti harus berformat gambar (JPG, PNG, WEBP, GIF, atau SVG).',
            'new_photos.*.max' => 'Ukuran setiap foto maksimal 10 MB.',
        ];
    }
}
