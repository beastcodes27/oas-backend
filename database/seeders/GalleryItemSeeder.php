<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class GalleryItemSeeder extends Seeder
{
    /**
     * Seed a few placeholder gallery items.
     *
     * NOTE: the image paths point at bundled sample images in the public
     * storage directory. Replace them with real school photos via the admin
     * gallery manager.
     */
    public function run(): void
    {
        if (GalleryItem::query()->exists()) {
            return;
        }

        $items = [
            ['caption' => 'School Assembly', 'description' => 'Students gather for the morning assembly.', 'image' => 'gallery/sample.jpg'],
            ['caption' => 'Science Laboratory', 'description' => 'Students conducting practical experiments.', 'image' => 'gallery/sample.jpg'],
            ['caption' => 'Football Team', 'description' => 'Our school football team after a match.', 'image' => 'gallery/sample.jpg'],
        ];

        foreach ($items as $i => $item) {
            GalleryItem::query()->create([
                'image' => $item['image'],
                'caption' => $item['caption'],
                'description' => $item['description'],
                'sort_order' => $i,
            ]);
        }
    }
}
