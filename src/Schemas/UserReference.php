<?php

namespace Hanafalah\ModuleUser\Schemas;

use Illuminate\Database\Eloquent\Model;
use Hanafalah\ModuleUser\Contracts\Schemas\UserReference as ContractsUserReference;
use Hanafalah\ModuleUser\Contracts\Data\UserReferenceData;
use Hanafalah\ModuleUser\Supports\BaseModuleUser;
use Illuminate\Support\Str;

class UserReference extends BaseModuleUser implements ContractsUserReference
{
    protected string $__entity = 'UserReference';
    public $user_reference_model;

    public function prepareShowUserReference(? Model $model = null, ? array $attributes = null): Model{
        $attributes ??= request()->all();
        $model ??= $this->getUserReference();
        if (!isset($model)){
            $uuid = $attributes['uuid'] ?? null;
            if (!isset($uuid)) throw new \Exception('uuid is required');
            $model = $this->userReference()->with($this->showUsingRelation())->uuid($uuid)->firstOrFail();
        }else{
            $model->load($this->showUsingRelation());
        }
        return $this->user_reference_model = $model;
    }


    public function prepareStoreUserReference(UserReferenceData $user_reference_dto): Model{
        $reference_type   = $user_reference_dto->reference_type;
        if (isset($reference_type)){
            $reference_schema = config('module-user.user_reference_types.'.Str::snake($reference_type).'.schema');        
            if (isset($reference_schema) && isset($user_reference_dto->reference)) {
                $schema_reference = $this->schemaContract(Str::studly($reference_schema));
                $reference        = $schema_reference->prepareStore($user_reference_dto->reference);
                $user_reference_model = $reference->userReference;
                $user_reference_dto->reference_id = $reference->getKey();
                $user_reference_dto->id   = $user_reference_model->getKey();
                $user_reference_dto->uuid = $user_reference_model->uuid;
            }
        }

        if (isset($user_reference_dto->user)){
            $user = &$user_reference_dto->user;
            $user->id ??= $user_reference_dto->user_id ?? null;
            if (isset($user->username) && isset($user->email)){
                if (isset($user->email)) $user->email = strtolower($user->email);
                $user_model = $this->schemaContract('user')->prepareStoreUser($user);
                $user_reference_dto->user_id ??= $user_model->getKey();
            }
        }

        if (isset($user_reference_dto->id) || isset($user_reference_dto->uuid)) {
            $user_reference = $user_reference_model ?? $this->UserReferenceModel()
                ->when(isset($user_reference_dto->uuid),function($query) use ($user_reference_dto){
                    return $query->where('uuid',$user_reference_dto->uuid);
                })
                ->when(isset($user_reference_dto->id),function($query) use ($user_reference_dto){
                    return $query->where('id', $user_reference_dto->id);
                })
                ->firstOrFail();            
            $user_reference->user_id   ??= $user_reference_dto->user_id ?? null;
            $user_reference->workspace_id   ??= $user_reference_dto->workspace_id ?? null;
            $user_reference->workspace_type ??= $user_reference_dto->workspace_type ?? null;
        }else{
            $guard = [
                'user_id'        => $user_reference_dto->user_id,
                'reference_type' => $user_reference_dto->reference_type,
                'reference_id'   => $user_reference_dto->reference_id,
                'workspace_type' => $user_reference_dto->workspace_type,
                'workspace_id'   => $user_reference_dto->workspace_id
            ];
            $user_reference = $this->usingEntity()->updateOrCreate($guard);
        }
        if (isset($user_reference_dto->roles) && count($user_reference_dto->roles) > 0) {
            $role = end($user_reference_dto->roles);
            $this->setRole($user_reference, $role['id']);
            $user_reference->syncRoles($user_reference_dto->role_ids);
        } else {
            $user_reference->roles()->detach();
            $user_reference->setAttribute('prop_role', [
                'id'   => null,
                'name' => null
            ]);
        }

        $this->fillingProps($user_reference,$user_reference_dto->props);
        $user_reference->save();
        return $this->user_reference_model = $user_reference;
    }

    private function setRole($user_reference, $role){
        $role = $this->RoleModel()->findOrFail($role);
        $user_reference->sync($role);
    }    
}
