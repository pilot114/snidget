<?php

namespace App\Schema\API;

use Snidget\Kernel\Assert;
use Snidget\Kernel\Typing\Type;

class UserLogin extends Type
{
    #[Assert(minLength: 4, maxLength: 24)]
    public string $login;
    #[Assert(minLength: 8, maxLength: 24)]
    public string $password;
}
