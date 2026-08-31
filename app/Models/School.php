<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'forms' => 'array',
            'streams' => 'array',
            'combinations' => 'array',
            'result_links' => 'array',
            'programs' => 'array',
            'contact' => 'array',
            'applications_open' => 'boolean',
            'window_opens_at' => 'datetime',
            'window_closes_at' => 'datetime',
            'selections_published_at' => 'datetime',
            'joining_instruction_published_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Return the school, creating a default row when the table is empty.
     */
    public static function default(): self
    {
        return static::query()->firstOrCreate(
            ['name' => 'Shule Yetu'],
            [
                'short_name' => 'Shule Yetu',
                'monogram' => 'SY',
                'motto' => 'Knowledge · Discipline · Excellence',
                'type' => 'Secondary School',
                'region' => 'Kilimanjaro',
                'district' => 'Moshi',
                'rating' => 'A',
                'capacity' => 600,
                'forms' => [1, 2, 3, 4, 5, 6],
                'streams' => ['Science', 'Business', 'Humanities'],
                'combinations' => [
                    'PCM — Physics, Chemistry, Advanced Mathematics',
                    'PCB — Physics, Chemistry, Biology',
                    'HGL — History, Geography, Kiswahili',
                ],
                'programs' => [
                    ['name' => 'O-Level', 'forms' => 'Form 1 – Form 4', 'intake' => 'Form 1', 'description' => ''],
                    ['name' => 'A-Level', 'forms' => 'Form 5 – Form 6', 'intake' => 'Form 5', 'description' => ''],
                ],
                'contact' => [
                    'phone' => '',
                    'email' => '',
                    'address' => '',
                ],
                'applications_open' => false,
            ],
        );
    }
}
