<?php

namespace Database\Seeders;

use App\Models\HomeFeature;
use Illuminate\Database\Seeder;

class HomeFeatureSeeder extends Seeder
{
    /**
     * Seed the default landing-page feature cards.
     */
    public function run(): void
    {
        if (HomeFeature::query()->exists()) {
            return;
        }

        $features = [
            ['title' => 'Simple Online Application', 'text' => 'Complete your Form 1 or Form 5 application in minutes with a guided, step-by-step form designed for parents and students.', 'image' => 'https://loremflickr.com/640/300/application'],
            ['title' => 'All Forms, One Application', 'text' => 'From Form 1 all the way to Form 6, apply to every level through a single online application.', 'image' => 'https://loremflickr.com/640/300/school,students'],
            ['title' => 'Track in Real Time', 'text' => 'Use your application reference number to follow the status of your application from submission to selection.', 'image' => 'https://loremflickr.com/640/300/smartphone'],
            ['title' => 'Instant Notifications', 'text' => 'Get notified the moment your selection result is published. No more queuing or missing important dates.', 'image' => 'https://loremflickr.com/640/300/notification'],
            ['title' => 'Fair Selection Process', 'text' => 'Our selection is transparent and merit-based. Every application is considered on its own merit and records.', 'image' => 'https://loremflickr.com/640/300/graduation'],
            ['title' => 'Secure & Private', 'text' => 'Your data is encrypted and protected. Only the school and relevant authorities can access your application.', 'image' => 'https://loremflickr.com/640/300/security'],
        ];

        foreach ($features as $i => $feature) {
            HomeFeature::query()->create([
                ...$feature,
                'sort_order' => $i,
            ]);
        }
    }
}
