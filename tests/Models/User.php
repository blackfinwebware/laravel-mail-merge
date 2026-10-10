<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function registration(): ?ConferenceRegistration
    {
        return ConferenceRegistration::where('user_id', $this->id)->first();
    }
}
