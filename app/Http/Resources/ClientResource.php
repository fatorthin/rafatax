<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'code' => $this->code,
            'company_name' => $this->company_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'owner_name' => $this->owner_name,
            'owner_role' => $this->owner_role,
            'contact_person' => $this->contact_person,
            'npwp' => $this->npwp,
            'jenis_wp' => $this->jenis_wp,
            'grade' => $this->grade,
            'pph_25_reporting' => $this->pph_25_reporting,
            'pph_23_reporting' => $this->pph_23_reporting,
            'pph_21_reporting' => $this->pph_21_reporting,
            'pph_4_reporting' => $this->pph_4_reporting,
            'ppn_reporting' => $this->ppn_reporting,
            'spt_reporting' => $this->spt_reporting,
            'status' => $this->status,
            'type' => $this->type,

            // Relationships
            'mous' => $this->whenLoaded('mous'),
            'staff' => $this->whenLoaded('staff'),

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
