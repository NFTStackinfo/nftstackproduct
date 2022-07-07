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
     * @OA\Post(
     * path="/api/v1/contract/create",
     * summary="Create Contract",
     * tags={"Contract"},
     * @OA\Parameter(
     *    description="Metamask Address",
     *    in="path",
     *    name="address",
     *    required=false,
     *    example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Name of Collection",
     *    in="path",
     *    name="collectionName",
     *    required=false,
     *    example="NFT Stack",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Symbol of Collection",
     *    in="path",
     *    name="collectionSymbol",
     *    required=false,
     *    example="NFTS",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Metadata Base Uri",
     *    in="path",
     *    name="metadataUri",
     *    required=false,
     *    example="https://racingsocialclub.mypinata.cloud/ipfs/Qmcozo8XKVWXGCMNjtaQvAtvopz2A62Y5qrpygmJKSWcXr/",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Deployed Mainnet Address",
     *    in="path",
     *    name="mainnetAddress",
     *    required=false,
     *    example="0x45c4B350BB6aE5836AfC78aaF06d2bEf6367AA7b",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Deployed Rinkeby Address",
     *    in="path",
     *    name="rinkebyAddress",
     *    required=false,
     *    example="0x45c4B350BB6aE5836AfC78aaF06d2bEf6367AA7b",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Mint Price",
     *    in="path",
     *    name="mintPrice",
     *    required=false,
     *    example="0.15",
     *    @OA\Schema(
     *       type="float",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Presale Mint Price",
     *    in="path",
     *    name="presaleMintPrice",
     *    required=false,
     *    example="0.1",
     *    @OA\Schema(
     *       type="float",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Total Count of Collection",
     *    in="path",
     *    name="totalCount",
     *    required=false,
     *    example="5555",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Public Mint",
     *    in="path",
     *    name="limitPerWallet",
     *    required=false,
     *    example="5",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Presale Mint",
     *    in="path",
     *    name="presaleLimitPerWallet",
     *    required=false,
     *    example="3",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Chain Id, for example Polygon Chain Id is 2",
     *    in="path",
     *    name="chainId",
     *    required=false,
     *    example="2",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Contract Type Id, for example ERC1155 Type Id is 3",
     *    in="path",
     *    name="typeId",
     *    required=false,
     *    example="3",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Client credentials",
     *    @OA\JsonContent(
     *       required={"address","limitPerWallet"},
     *       @OA\Property(property="address", type="string", format="string", example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05"),
     *       @OA\Property(property="limitPerWallet", type="integer", format="integer", example="5"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully created",
     *    @OA\JsonContent(
     *       @OA\Property(property="msg", type="string", example="Successfully created")
     *        )
     *     )
     * )
     *
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


    /**
     * @OA\Get(
     * path="/api/v1/contract/get/all",
     * summary="Get User Contracts",
     * tags={"Contract"},
     * @OA\Parameter(
     *    description="Metamask Address",
     *    in="path",
     *    name="address",
     *    required=true,
     *    example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="User wallet address",
     *    @OA\JsonContent(
     *       required={"address"},
     *       @OA\Property(property="address", type="string", format="string", example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully logouted",
     *    @OA\JsonContent(
     *       @OA\Property(property="nonce", type="object", example="vsv433dc")
     *        )
     *     )
     * )
     *
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function getUserContracts(Request $request) {
        $address = $request->input('address');

        if (empty($address)) {
            return response(['msg' => 'Error wallet address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $user_id = Users::getIdByAddress($address);
        $contracts = Contract::getContract(['collection_name', 'collection_symbol'], [['id' => $user_id]]);

        return response(['msg' => 'Successfully created', 'contracts' => $contracts,'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }
}
