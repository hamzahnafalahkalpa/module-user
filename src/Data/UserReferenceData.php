<?php

namespace Hanafalah\ModuleUser\Data;

use Hanafalah\LaravelPermission\Data\RoleData;
use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\ModuleUser\Contracts\Data\UserReferenceData as DataUserReferenceData;
use Hanafalah\ModuleUser\Data\Transformers\RoleDataTransformer;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithTransformer;
use Illuminate\Support\Str;

class UserReferenceData extends Data implements DataUserReferenceData{
    #[MapInputName('id')]
    #[MapName('id')]
    public mixed $id = null;

    #[MapInputName('uuid')]
    #[MapName('uuid')]
    public ?string $uuid = null;
    
    #[MapInputName('user_id')]
    #[MapName('user_id')]
    public mixed $user_id = null;

    #[MapInputName('user')]
    #[MapName('user')]
    public ?UserData $user = null;

    #[MapInputName('reference_type')]
    #[MapName('reference_type')]
    public ?string $reference_type = null;

    #[MapInputName('reference_id')]
    #[MapName('reference_id')]
    public mixed $reference_id = null;

    #[MapInputName('reference')]
    #[MapName('reference')]
    public null|object|array $reference = null;

    #[MapInputName('workspace_type')]
    #[MapName('workspace_type')]
    public ?string $workspace_type = null;

    #[MapInputName('workspace_id')]
    #[MapName('workspace_id')]
    public mixed $workspace_id = null;

    #[MapInputName('role_ids')]
    #[MapName('role_ids')]
    public ?array $role_ids = [];

    #[MapInputName('roles')]
    #[MapName('roles')]
    #[WithTransformer(RoleDataTransformer::class)]
    public ?array $roles = [];

    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;
    
    public static function before(array &$attributes){
        $new = static::new();
        if (isset($attributes['id'])){
            $user_reference_model   = $new->UserReferenceModel()->with('reference')->findOrFail($attributes['id']);
            $attributes['reference_id']   ??= $reference['id'] = $user_reference_model->reference_id;
            $attributes['reference_type'] ??= $user_reference_model->reference_type;
        }else{
            $config_keys = array_keys(config('module-user.user_reference_types'));
            $keys        = array_intersect(array_keys($attributes),$config_keys);
            $key         = array_shift($keys);

            $attributes['reference_type'] ??= $key;
        }
        if (isset($attributes['reference_type'])){
            $attributes['reference_type'] = Str::studly($attributes['reference_type']);
        }
    }

    public static function after(UserReferenceData $data): UserReferenceData{
        $new = static::new();
        if (isset($data->reference)){
            $reference = &$data->reference;
            $reference = self::transformToData($data->reference_type, $reference);
        }

        if (isset($data->user,$data->user->id) && !isset($data->user_id)){
            $data->user_id = $data->user->id;
        }

        if (!isset($data->roles) && !isset($data->role_ids)) throw new \Exception('roles or role_ids is required');

        if(!empty($data->role_ids)){
            $data->roles = $data->fetchRolesFromIds($data->role_ids);
        }
        if (empty($data->role_ids) && count($data->roles) > 0){
            $data->role_ids = \array_column($data->roles,'id');
        }
        return $data;
    }

    private function fetchRolesFromIds(array $roleIds): array{
        $roles = $this->RoleModel()->whereIn('id',$roleIds)->get();
        if (count($roles) == 0) throw new \Exception(sprintf('There is no role data with id %s',implode(',',$roleIds)));
        return $roles->map(fn($role) => RoleData::from($role))->toArray();
    }

    private static function transformToData(string $entity,array $attributes){
        $new = static::new();
        return $new->requestDTO(config('app.contracts.'.$entity.'Data'),$attributes);
    }
}