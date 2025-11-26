<?php

namespace Hanafalah\ModuleUser\Data;

use Carbon\Carbon;
use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\ModuleUser\Contracts\Data\UserData as DataUserData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class UserData extends Data implements DataUserData{
    #[MapInputName('id')]
    #[MapName('id')]
    public mixed $id = null;
    
    #[MapInputName('username')]
    #[MapName('username')]
    public ?string $username = null;
    
    #[MapInputName('password')]
    #[MapName('password')]
    #[Password]
    public ?string $password = null;

    #[MapInputName('password_confirmation')]
    #[MapName('password_confirmation')]
    #[Password]
    public ?string $password_confirmation = null;

    #[MapInputName('email')]
    #[MapName('email')]
    #[Email]
    #[Unique('users', 'email')]
    public ?string $email = null;

    #[MapInputName('email_verified_at')]
    #[MapName('email_verified_at')]
    #[WithTransformer(DateTimeInterfaceTransformer::class)]
    public ?string $email_verified_at = null;

    #[MapInputName('user_reference')]
    #[MapName('user_reference')]
    public ?UserReferenceData $user_reference = null;

    #[MapInputName('props')]
    #[MapName('props')]
    public ?array $props = null;
}