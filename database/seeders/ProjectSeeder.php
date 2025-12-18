<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $project = Project::factory()->create([
            'name' => 'Laravel Test App',
            'health_check_url' => 'http://laravel.test/up',
            'is_active' => true,
        ]);

        $project->notificationEmails()->create([
            'email' => 'test@example.com',
        ]);
    }
}
