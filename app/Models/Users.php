<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;

class Users extends Model implements AuthenticatableContract, AuthorizableContract
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
        'wallet', 'email'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * @param array $data
     * @return bool
     */
    public static function createUser(array $data): bool {
        return DB::table('users')->insert([
            'wallet' => $data['wallet'],
            'email' => !empty($data['email']) ? $data['email']: null,
            'api_key' => self::generateRandomString(),
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time())
        ]);
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString(int $length = 32): string {
        $characters = '0123456789!@#$%^&*()))_+|}{?><:abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        return $random_string;
    }

    /**
     * @param string $address
     * @return int
     */
    public static function getIdByAddress(string $address): int {
        $result = DB::table('users')->select('id')->where('wallet', $address)->get();
        if (empty($result)) {
            return 0;
        }

        return $result[0]->id;
    }

    /**
     * @param int $id
     * @param string $email
     * @return int
     */
    public static function updateEmail(int $id, string $email): int {
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $data['email'] = $email;

        return DB::table('contract')->where('id', '=', $id)->update($data);
    }
}
