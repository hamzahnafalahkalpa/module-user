<?php

namespace Hanafalah\ModuleUser\Schemas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Hanafalah\ModuleUser\Concerns\User\UserValidation;
use Hanafalah\ModuleUser\Contracts\Schemas\User as ContractsUser;
use Hanafalah\ModuleUser\Contracts\Data\ChangePasswordData;
use Hanafalah\ModuleUser\Contracts\Data\UserData;
use Hanafalah\ModuleUser\Supports\BaseModuleUser;

class User extends BaseModuleUser implements ContractsUser
{
    use UserValidation;

    protected string $__entity = 'User';
    public $user_model;

    public function prepareStoreUser(UserData $user_dto): Model{
        if (isset($user_dto->id)) {
            $guard = ['id' => $user_dto->id];
        } else {
            $guard = [
                'username' => $user_dto->username,
                'email'    => $user_dto->email
            ];

            $add = ['email_verified_at' => $user_dto->email_verified_at];
        }
        if (!isset($user_dto->id) && $this->isPasswordValid()) {
            $add['password'] = Hash::make($user_dto->password ?? 'password');
        } else {
            if (isset($user_dto->password)) $add['password'] = Hash::make($user_dto->password);
            $add['username'] = $user_dto->username;
            $add['email']    = $user_dto->email;
        }

        $user = $this->user()->updateOrCreate($guard, $add ?? []);
        if (isset($user_dto->user_reference)) {
            $user_reference = &$user_dto->user_reference;
            $user_reference->user_id = $user->getKey();
            $this->schemaContract('user_reference')
                 ->prepareStoreUserReference($user_reference);
        }

        $this->fillingProps($user, $user_dto->props);
        $user->save();
        return $this->user_model = $user;
    }

    protected function isPasswordValid(): bool{
        return isset($user_dto->password, $user_dto->password_confirmation) && $user_dto->password == $user_dto->password_confirmation;
    }

    public function prepareChangePassword(ChangePasswordData $change_password_dto): ?bool{
        if (!isset($change_password_dto->id)) throw new \Exception('User Id is required', 422);
        if (!$this->checkRequestPassword()) throw new \Exception('New Password, old password and new password confirmation is required', 422);

        $this->UserModel()->updateOrCreate([
            'id' => $change_password_dto->id
        ], [
            'password' => Hash::make($change_password_dto->password)
        ]);

        return true;
    }

    public function changePassword(? ChangePasswordData $change_password_dto = null): ?bool{
        return $this->transaction(function() use ($change_password_dto){
            return $this->prepareChangePassword($change_password_dto ?? $this->requestDTO(ChangePasswordData::class));
        });
    }

    private function checkRequestPassword(): bool{
        return request()->has('password') && request()->has('old_password') && request()->has('password_confirmation');
    }

    public function getUserByUsernameId(string $username, mixed $user_id): ?Model{
        return $this->user()->where('username', $username)->find($user_id);
    }

    public function getUserByEmailId(string $email, mixed $user_id): ?Model{
        return $this->user()->where('email', $email)->find($user_id);
    }
}
