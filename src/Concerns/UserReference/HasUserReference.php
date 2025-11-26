<?php

namespace Hanafalah\ModuleUser\Concerns\UserReference;

use Hanafalah\LaravelSupport\Concerns\Support\HasRequestData;

trait HasUserReference
{
    use HasRequestData;

    protected $generate_user_reference = true;

    // public static function bootHasUserReference(){
    //     static::created(function($query){
    //         if ($query->isGenerateUserReference()){
    //             $user_reference = app(config('app.contracts.UserReference'))->prepareStoreUserReference($query->requestDTO(
    //                 config('app.contracts.UserReferenceData'),
    //                 [
    //                     'reference_type' => $query->getMorphClass(),
    //                     'reference_id'   => $query->getKey(),
    //                     'reference_model' => $query,
    //                 ]
    //             ));
    //             $user_reference = $query->userReference()->firstOrCreate();
    //             $query->uuid = $user_reference->uuid;
    //             $query->save();
    //         }
    //     });
    // }

    public function isGenerateUserReference(): bool{
        return $this->generate_user_reference;
    }

    public function setGenerateUserReference(bool $value): void{
        $this->generate_user_reference = $value;
    }
    
    //SCOPE SECTION
    public function scopeHasRefUuid($builder, $uuid, $uuid_name = 'uuid')
    {
        return $builder->whereHas('userReference', function ($query) use ($uuid_name, $uuid) {
            $query->where($uuid_name, $uuid);
        });
    }

    //EIGER SECTION
    public function userReference(){
        return $this->morphOneModel('UserReference', 'reference');
    }
    public function userReferences(){
        return $this->morphManyModel('UserReference', 'reference');
    }

    public function user()
    {
        return $this->hasOneThroughModel(
            'User',
            'UserReference',
            'reference_id',
            $this->getKeyName(),
            $this->getKeyName(),
            $this->getForeignKey()
        )->where('reference_type', $this->getMorphClass());
    }
}
