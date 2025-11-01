<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'email' => $this->email,
            'no_ktp' => $this->no_ktp,
            'phone' => $this->phone,
            'no_spk' => $this->no_spk,
            'jenjang' => $this->jenjang,
            'jurusan' => $this->jurusan,
            'university' => $this->university,
            'no_ijazah' => $this->no_ijazah,
            'tmt_training' => $this->tmt_training,
            'periode' => $this->periode,
            'selesai_training' => $this->selesai_training,
            'is_active' => (bool) $this->is_active,
            'salary' => $this->salary,
            'position_status' => $this->position_status,

            // Conditional Attributes (hanya muncul jika sudah di-load)
            'department' => $this->whenLoaded('departmentReference', function () {
                return [
                    'id' => $this->departmentReference->id,
                    'name' => $this->departmentReference->name ?? null,
                ];
            }),

            'position' => $this->whenLoaded('positionReference', function () {
                return [
                    'id' => $this->positionReference->id,
                    'name' => $this->positionReference->name ?? null,
                ];
            }),

            // Relationships
            'clients' => $this->whenLoaded('clients'),
            'trainings' => $this->whenLoaded('trainings'),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0.0',
            ],
        ];
    }
}
