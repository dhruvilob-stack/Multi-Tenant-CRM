<?php

namespace App\Models;

class SuperAdminUser extends User
{
    protected $connection = 'landlord';

    protected $table = 'users';
}

