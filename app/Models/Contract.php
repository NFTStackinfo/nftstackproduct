<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;

class Contract extends Model implements AuthenticatableContract, AuthorizableContract
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
        'collection_name', 'collection_symbol', 'mainnet_address', 'rinkeby_address', 'metadata_uri', 'mint_price',
        'presale_mint_price', 'total_count', 'limit_per_transaction', 'limit_per_wallet', 'presale_limit_per_wallet',
        'user_id', 'chain_id', 'type_id'
    ];


    /**
     * @param array $data
     * @return bool
     */
    public static function createContract(array $data): bool {
        return DB::table('contract')->insert([
            'collection_name' => $data['collection_name'],
            'collection_symbol' => $data['collection_symbol'],
            'metadata_uri' => $data['metadata_uri'],
            'mainnet_address' => $data['mainnet_address'],
            'rinkeby_address' => $data['rinkeby_address'],
            'mint_price' => $data['mint_price'],
            'presale_mint_price' => $data['presale_mint_price'],
            'total_count' => $data['total_count'],
            'limit_per_transaction' => $data['limit_per_transaction'],
            'limit_per_wallet' => $data['limit_per_wallet'],
            'presale_limit_per_wallet' => $data['presale_limit_per_wallet'],
            'user_id' => $data['user_id'],
            'chain_id' => $data['chain_id'],
            'type_id' => $data['type_id'],
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time())
        ]);
    }

    /**
     * @param array $where
     * @param array $data
     * @param string $operator
     * @return int
     */
    public static function updateContract(array $where, array $data, string $operator = '='): int {
        $data['updated_at'] = date('Y-m-d H:i:s', time());

        return DB::table('contract')->where(key($where), $operator, $where)->update($data);
    }

    /**
     * @param array $select // Should be indexed array ['id', 'address']
     * @param array $where // Should be multidimensional array for example
     * [['email' => 'test@gmail.com', 'operator' => '='], ['username'=>'test', 'operator'=> '!=']]
     * @param string $objectOperator // For example 'where' 'orWhere'
     * @return \Illuminate\Support\Collection
     */
    public static function getContract(array $select, array $where, string $objectOperator = 'where') {
        $select = DB::table('contract')->select($select);
        foreach ($where as $item) {
            $operator = $item['operator'];
            unset($item['operator']);
            $whereKey = array_keys($item);
            if (str_contains($objectOperator, 'or')) {
                $select = $select->orWhere($whereKey[0], $operator, $item[$whereKey[0]]);
            } else {
                $select = $select->where($whereKey[0], $operator, $item[$whereKey[0]]);
            }
        }

        return $select->get();
    }
}
