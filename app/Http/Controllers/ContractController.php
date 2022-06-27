<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Users;
use App\Services\EthereumValidator;
use Illuminate\Http\Request;
use kornrunner\Keccak;

class ContractController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function create(Request $request) {
        $address = $request->input('address');

        $user_id = Users::getIdByAddress($address);

        $collection_name = $request->input('collectionName');
        $collection_symbol = $request->input('collectionSymbol');
        $metadata_uri = $request->input('metadataUri');

        if ((!empty($collection_name) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_name))
        || (!empty($collection_symbol) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_symbol))) {
            if(empty($nonce) || $nonce == '') {
                return response(['msg' => 'Error specific symbols in name', 'success' => false], 404)
                    ->header('Content-Type', 'application/json');
            }
        }

        if (!filter_var($metadata_uri, FILTER_VALIDATE_URL) === false) {
            if(empty($nonce) || $nonce == '') {
                return response(['msg' => 'Error invalid metadata URI', 'success' => false], 404)
                    ->header('Content-Type', 'application/json');
            }
        }

        $mainnet_address = $request->input('mainnetAddress');
        $rinkeby_address = $request->input('rinkebyAddress');

        if ((!empty($mainnet_address) && !EthereumValidator::isAddress($mainnet_address)) ||
            (!empty($rinkeby_address) && !EthereumValidator::isAddress($rinkeby_address))) {
            return response(['msg' => 'Error invalid contract address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $mint_price = $request->input('mintPrice');
        $presale_mint_price = $request->input('presaleMintPrice');
        $total_count = $request->input('totalCount');
        $limit_per_transaction = $request->input('limitPerTransaction');
        $limit_per_wallet = $request->input('limitPerWallet');
        $presale_limit_per_wallet = $request->input('presaleLimitPerWallet');

        $chain_id = $request->input('chainId');
        $type_id = $request->input('typeId');


        $contract =  Contract::createContract([
            'collection_name' => $collection_name,
            'collection_symbol' => $collection_symbol,
            'metadata_uri' => $metadata_uri,
            'mainnet_address' => $mainnet_address,
            'rinkeby_address' => $rinkeby_address,
            'mint_price' => $mint_price,
            'presale_mint_price' => $presale_mint_price,
            'total_count' => $total_count,
            'limit_per_transaction' => $limit_per_transaction,
            'limit_per_wallet' => $limit_per_wallet,
            'presale_limit_per_wallet' => $presale_limit_per_wallet,
            'user_id' => $user_id,
            'chain_id' => $chain_id,
            'type_id' => $type_id,
        ]);
        if($contract == 0) {
            return response(['msg' => 'error something wrong', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        return response(['msg' => 'Successfully created', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    public function update(Request $request) {

    }

    // todo
    public function get(Request $request) {
        $address = $request->input('address');
        $contract_id = $request->input('contractId');
        $user_id = Users::getIdByAddress($address);
    }

    //
}
