<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'health_check_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProjectNotificationEmail, $this>
     */
    public function notificationEmails(): HasMany
    {
        return $this->hasMany(ProjectNotificationEmail::class);
    }

    /**
     * @return HasMany<HealthCheckFailure, $this>
     */
    public function healthCheckFailures(): HasMany
    {
        return $this->hasMany(HealthCheckFailure::class);
    }
}
