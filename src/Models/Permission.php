<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    /**
     * Table's name of the model
     *
     * @var string
     */
    protected $table = 'permissions';

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
        'group',
        'class',
        'method',
        'apply',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @var string[]
     */
    protected $casts = [
        'id'    => 'integer',
        'apply' => 'boolean',
    ];

    /**
     * @return BelongsToMany|null
     */
    public function roles(): ?BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }
}
