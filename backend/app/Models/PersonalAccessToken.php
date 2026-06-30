<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function activeRole()
    {
        return $this->belongsTo(Role::class, 'active_role_id');
    }
}