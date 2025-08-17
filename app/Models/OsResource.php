<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $resource_identifier encrypted
 * @property string|null $flavor_name
 * @property string|null $state
 * @property int $os_project_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read OsProject $project
 */
final class OsResource extends Model
{
    protected $fillable = [
        'name',
        'description',
        'resource_identifier',
        'flavor_name',
        'state',
        'os_project_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(OsProject::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(OsRating::class, 'os_resource_id');
    }
}
