<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property float $rating
 * @property float $volume
 * @property \Carbon\Carbon $begin
 * @property \Carbon\Carbon $end
 * @property string $service
 * @property int $os_resource_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read OsResource $resource
 */
final class OsRating extends Model
{
    protected $fillable = [
        'rating',
        'volume',
        'begin',
        'end',
        'service',
        'os_resource_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'rating' => 'float',
        'volume' => 'float',
        'begin' => 'datetime',
        'end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(OsResource::class, 'os_resource_id', 'id');
    }
}
