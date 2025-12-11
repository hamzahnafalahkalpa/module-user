<?php

namespace Hanafalah\ModuleUser\Models\User;

use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Hanafalah\LaravelSupport\Models\BaseModel;

use Hanafalah\LaravelHasProps\Concerns\HasCurrent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\LaravelPermission\Concerns\HasRole;
use Hanafalah\ModuleUser\Resources\UserReference\{
    ViewUserReference, ShowUserReference
};
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class UserReference extends BaseModel
{
    use HasUlids, HasProps, SoftDeletes, HasCurrent, HasRole;

    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $primaryKey = 'id';
    protected $casts      = [
        'reference_id' => 'string'
    ];

    public function getConditions(): array{
        return ['reference_type', 'reference_id', 'user_id'];
    }

    protected $fillable = [
        'id','uuid','reference_type','reference_id',
        'user_id','workspace_type','workspace_id','current'
    ];

    protected $prop_attributes = [
        'Role' => [
            'id','name'
        ]
    ];

    public function viewUsingRelation(): array{
        return ['role'];
    }

    public function showUsingRelation(): array{
        return ['role','user','reference','workspace'];
    }

    public function getViewResource(){
        return ViewUserReference::class;
    }

    public function getShowResource(){
        return ShowUserReference::class;
    }

    public function reference(){return $this->morphTo();}
    public function user(){return $this->belongsToModel('User');}
    public function workspace(){return $this->morphTo();}
    public function tenant(){return $this->morphTo(__FUNCTION__, "workspace_type", "workspace_id");}
}
