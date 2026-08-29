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
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
