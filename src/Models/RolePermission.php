<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
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
        'role_id',
        'permission_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = [
        'id'            => 'integer',
        'role_id'       => 'integer',
        'permission_id' => 'integer',
    ];

    /**
     * @return BelongsTo|null
     */
    public function role(): ?BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo|null
     */
    public function permission(): ?BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
