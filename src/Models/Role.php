<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    /**
     * Table's name of the model
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that should be not changed
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are table's timestamps
     *
     * @var string[]
     */
    public $timestamps = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code',
        'label',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * @return ?BelongsToMany
     */
    public function permissions(): ?BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions');
    }

    /**
     * @return ?HasMany
     */
    public function userRoles(): ?HasMany
    {
        return $this->hasMany(UserRole::class);
    }
}
