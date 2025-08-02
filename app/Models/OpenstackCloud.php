<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OpenstackCloud Model.
 *
 * Represents an OpenStack cloud configuration.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $region_name
 * @property string $interface
 * @property string $identity_api_version
 * @property string|null $endpoint_rating
 * @property string $auth_url
 * @property string $auth_username Encrypted
 * @property string $auth_password Encrypted
 * @property string $auth_project_id Encrypted
 * @property string|null $auth_project_name
 * @property string $auth_user_domain_name
 * @property string|null $access_token Encrypted
 * @property \Carbon\Carbon|null $access_token_expires_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User $user
 */
final class OpenstackCloud extends Model
{
    /** @use HasFactory<\Database\Factories\OpenstackCloudFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'region_name',
        'interface',
        'identity_api_version',
        'auth_url',
        'auth_project_name',
        'auth_user_domain_name',
        'auth_username',
        'auth_password',
        'auth_project_id',
    ];

    protected $casts = [
        'auth_username' => 'encrypted',
        'auth_password' => 'encrypted',
        'auth_project_id' => 'encrypted',
        'access_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'auth_username',
        'auth_password',
        'auth_project_id',
        'access_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
