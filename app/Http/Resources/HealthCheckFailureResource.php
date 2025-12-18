<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\HealthCheckFailure
 */
class HealthCheckFailureResource extends JsonResource
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
            'project_name' => $this->project->name,
            'error_message' => $this->error_message,
            'response_code' => $this->response_code,
            'response_time_ms' => $this->response_time_ms,
            'checked_at' => $this->checked_at->toIso8601String(),
            'checked_at_human' => $this->checked_at->diffForHumans(),
        ];
    }
}
