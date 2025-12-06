<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ComplaintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citizen = User::whereHas('roles', fn($q) => $q->where('name','citizen'))->inRandomOrder()->first() ?? User::first();

        $types = ['noise','garbage','infrastructure','other'];
        $sections = ['security','finance','education'];
        for ($i=0;$i<10;$i++) {
            $c = Complaint::create([
                'citizen_id' => $citizen->id,
                'type' => Arr::random($types),
                'national_id'=>'1401005247896',
                'section' => Arr::random($sections),
                'location' => 'Sample location ' . ($i+1),
                'description' => 'This is a seeded complaint number ' . ($i+1),
                'status' => Arr::random(['new','pending','done','rejected']),
                'notes' => null,
            ]);

            // example attach media from storage/app/seed-media if exists
            $path = public_path('seeder');
            if (is_dir($path)) {
                $files = glob($path . '/*');
                Log::info(json_encode($files));
                foreach ($files as $file) {
                    $c->addMedia($file)->toMediaCollection('attachments');
                }
            }
        }
    }
}
