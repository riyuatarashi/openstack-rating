<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $project_identifier encrypted
 * @property int $os_cloud_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read OsCloud $cloud
 * @property-read \Illuminate\Database\Eloquent\Collection<OsResource> $resources
 * @property-read \Illuminate\Database\Eloquent\Collection<OsRating> $ratings
 */
final class OsProject extends Model
{
    protected $fillable = [
        'name',
        'description',
        'project_identifier',
        'os_cloud_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cloud(): BelongsTo
    {
        return $this->belongsTo(OsCloud::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(OsResource::class);
    }

    public function ratings(): HasManyThrough
    {
        return $this->hasManyThrough(
            OsRating::class,
            OsResource::class,
            'os_project_id', // Foreign key on OSResource table
            'os_resource_id', // Foreign key on OSRating table
            'id', // Local key on OSProject table
            'id'  // Local key on OSResource table
        );
    }
}
