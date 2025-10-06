<?php

namespace Hanafalah\ModuleUser\Resources;

use Illuminate\Http\Request;
use Hanafalah\LaravelSupport\Resources\ApiResource;

class ShowUser extends ViewUser
{
    public function toArray(Request $request): array
    {
        $arr = [
            'user_references' => $this->relationValidation('userReferences', function () {
                return $this->userReferences->transform(function ($userReference) {
                    return $userReference->toViewApi();
                });
            })
        ];
        $arr = $this->mergeArray(parent::toArray($request), $arr);
        return $arr;
    }
}
