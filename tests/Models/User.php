<?php

namespace Univpancasila\StorageUp\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Test User Model
 * 
 * @author @abdansyakuro.id
 */
class User extends Model
{
    protected $fillable = ['name', 'email'];
}
