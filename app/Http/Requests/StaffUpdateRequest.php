<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $staffId = $this->route('staff');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('staff')->ignore($staffId)],
            'no_ktp' => ['nullable', 'string', 'max:50', Rule::unique('staff')->ignore($staffId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'no_spk' => ['nullable', 'string', 'max:100'],
            'jenjang' => ['nullable', 'string', 'max:100'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'university' => ['nullable', 'string', 'max:255'],
            'no_ijazah' => ['nullable', 'string', 'max:100'],
            'tmt_training' => ['nullable', 'date'],
            'periode' => ['nullable', 'string', 'max:100'],
            'selesai_training' => ['nullable', 'date'],
            'department_reference_id' => ['nullable', 'integer', 'exists:department_references,id'],
            'position_reference_id' => ['nullable', 'integer', 'exists:position_references,id'],
            'is_active' => ['nullable', 'boolean'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'position_status' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama staff wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'no_ktp.unique' => 'Nomor KTP sudah terdaftar',
            'department_reference_id.exists' => 'Department tidak ditemukan',
            'position_reference_id.exists' => 'Posisi tidak ditemukan',
        ];
    }
}
