<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Collection;

class WaitList extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'email'
    ];

    /**
     * @param $unsubscribe_hash
     * @return int
     */
    public static function updateEmail($unsubscribe_hash) {
        return DB::table('wait_list')->where('unsubscribe_hash', $unsubscribe_hash)->update([
            'unsubscribe_status' => 1,
            'updated_at' => date('Y-m-d H:i:s', time())
        ]);
    }

    /**
     * @param $email
     * @param $unsubscribe
     * @return bool
     */
    public static function setEmail($email, $unsubscribe) {
        return DB::table('wait_list')->insert([
            'email' => $email,
            'unsubscribe_hash' => $unsubscribe,
            'unsubscribe_status' => 0,
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time())
        ]);
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function checkEmail(string $email): bool {
        return DB::table('wait_list')->where('email', $email)->exists();
    }
}
