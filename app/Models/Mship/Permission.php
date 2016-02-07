<?php

namespace App\Models\Mship;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\SoftDeletes as SoftDeletingTrait;
use App\Models\Mship\Role as RoleData;


/**
 * App\Models\Mship\Permission
 *
 * @property integer $permission_id
 * @property string $name
 * @property string $display_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Mship\Role[] $roles
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Mship\Permission isName($name)
 */
class Permission extends \App\Models\aModel {
    use SoftDeletingTrait, RecordsActivity;

    protected $table = "mship_permission";
    protected $primaryKey = "permission_id";
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['name', 'display_name'];
    protected $rules = [
        'name' => 'required',
        'display_name' => 'required|between:3,50',
    ];

    public static function eventDeleted($model) {
        parent::eventCreated($model);

        // When we delete a permission, we delete the role assignments too.
        $model->detachRoles($model->roles);
    }

    public static function scopeIsName($query, $name){
        return $query->whereName($name);
    }

    public function roles(){
        return $this->belongsToMany("\App\Models\Mship\Role", "mship_permission_role")->withTimestamps();
    }

    public function attachRole(RoleData $role){
        if($this->roles->contains($role->getKey())){
            return false;
        }

        return $this->roles()->attach($role);
    }

    public function attachRoles($roles){
        foreach($roles as $r){
            if($r instanceof RoleData){
                $this->attachRole($r);
            } elseif(is_numeric($r) && $r = RoleData::find($r)){
                $this->attachRole($r);
            }
        }
    }

    public function detachRole(RoleData $role){
        if(!$this->roles->contains($role->getKey())){
            return false;
        }

        return $this->roles()->detach($role);
    }

    public function detachRoles($roles){
        foreach($roles as $r){
            if($r instanceof RoleData){
                $this->detachRole($r);
            } elseif(is_numeric($r) && $r = RoleData::find($r)){
                $this->detachRole($r);
            }
        }
    }
}