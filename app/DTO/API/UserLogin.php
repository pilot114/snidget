<?php

namespace App\DTO\API;

use Snidget\Attribute\Assert;
use Snidget\Typing\Type;

class UserLogin extends Type
{
    #[Assert(minLength: 4, maxLength: 24)]
    public string $login;
    #[Assert(minLength: 8, maxLength: 24)]
    public string $password;
}
