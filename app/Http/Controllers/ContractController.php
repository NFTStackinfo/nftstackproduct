<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Users;
use App\Models\WithdrawalAddresses;
use App\Services\EthereumValidator;
use Illuminate\Http\Request;
use kornrunner\Keccak;

class ContractController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/v1/contract/create",
     * summary="Create User Contract",
     * tags={"Contract"},
     * @OA\Parameter(
     *    description="Name of Collection",
     *    in="path",
     *    name="collectionName",
     *    required=true,
     *    example="NFT Stack",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Project Name",
     *    in="path",
     *    name="projectName",
     *    required=true,
     *    example="NFT Stack",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Symbol of Collection",
     *    in="path",
     *    name="collectionSymbol",
     *    required=true,
     *    example="NFTS",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Metadata Base Uri",
     *    in="path",
     *    name="metadataUri",
     *    required=true,
     *    example="https://racingsocialclub.mypinata.cloud/ipfs/Qmcozo8XKVWXGCMNjtaQvAtvopz2A62Y5qrpygmJKSWcXr/",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Mint Price",
     *    in="path",
     *    name="mintPrice",
     *    required=true,
     *    example="0.15",
     *    @OA\Schema(
     *       type="float",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Presale Mint Price",
     *    in="path",
     *    name="presaleMintPrice",
     *    required=true,
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
     *    description="Reserve Count",
     *    in="path",
     *    name="reserveCount",
     *    required=false,
     *    example="50",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Contract Type Id, for example ERC1155 Type Id is 3",
     *    in="path",
     *    name="typeId",
     *    required=true,
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
        $address = $request->header('address');

        $user_id = Users::getIdByAddress($address);

        $collection_name = $request->input('collectionName');
        $project_name = $request->input('projectName');
        $collection_symbol = $request->input('collectionSymbol');
        $metadata_uri = $request->input('metadataUri');
        $walletAddresses = $request->input('walletAddresses');

        if ((!empty($collection_name) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_name))
        || (!empty($collection_symbol) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_symbol))) {
            return response(['msg' => 'Error specific symbols in name', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!filter_var($metadata_uri, FILTER_VALIDATE_URL)) {
            return response(['msg' => 'Error invalid metadata URI', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }


        $mint_price = $request->input('mintPrice');
        $presale_mint_price = $request->input('presaleMintPrice');
        $total_count = $request->input('totalCount');
        # $limit_per_transaction = $request->input('limitPerTransaction');
        $limit_per_wallet = $request->input('limitPerWallet');
        $presale_limit_per_wallet = $request->input('presaleLimitPerWallet');
        $reserve_count = $request->input('reserveCount');

        $type_id = $request->input('typeId');
        if (empty($mint_price) || empty($presale_mint_price) || empty($total_count) || empty($limit_per_wallet) || empty($presale_limit_per_wallet)
        || empty($reserve_count) || empty($type_id)) {
            return response(['msg' => 'Error required params are missing', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $contract =  Contract::createContract([
            'collection_name' => $collection_name,
            'project_name' => $project_name,
            'collection_symbol' => $collection_symbol,
            'metadata_uri' => $metadata_uri,
            'mainnet_address' => null,
            'rinkeby_address' => null,
            'mint_price' => $mint_price,
            'presale_mint_price' => $presale_mint_price,
            'total_count' => $total_count,
            'limit_per_transaction' => null,
            'limit_per_wallet' => $limit_per_wallet,
            'reserve_count' => $reserve_count,
            'presale_limit_per_wallet' => $presale_limit_per_wallet,
            'user_id' => $user_id,
            'chain_id' => null,
            'type_id' => $type_id,
        ]);

        if(!$contract) {
            return response(['msg' => 'error something wrong', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        foreach ($walletAddresses as $item) {
            WithdrawalAddresses::create([
                'user_id' => $user_id,
                'contract_id' => $contract,
                'percent' => $item['split'],
                'address' => $item['address'],
            ]);
        }

        return response(['msg' => 'Successfully created', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    /**
     * @OA\Post(
     * path="/api/v1/contract/update",
     * summary="Update User Contract",
     * tags={"Contract"},
     * @OA\Parameter(
     *    description="Mainnet Address",
     *    in="path",
     *    name="mainnetAddress",
     *    required=false,
     *    example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Rinkeby Address",
     *    in="path",
     *    name="rinkebyAddress",
     *    required=false,
     *    example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Chain Id",
     *    in="path",
     *    name="chainId",
     *    required=false,
     *    example="4",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Contract ID",
     *    in="path",
     *    name="contractId",
     *    required=false,
     *    example="3",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Deployed Contract address",
     *    @OA\JsonContent(
     *       required={"address"},
     *       @OA\Property(property="address", type="string", format="string", example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfully logouted",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="object", example="true")
     *        )
     *     )
     * )
     *
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function update(Request $request) {
        $mainnet_address = $request->input('mainnetAddress');
        $rinkeby_address = $request->input('rinkebyAddress');
        $chain_id = $request->input('chainId');
        $contract_id = $request->input('contractId');

        if (empty($contract_id)) {
            return response(['msg' => 'Error contract id not found', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }
        if ((!empty($mainnet_address) && !EthereumValidator::isAddress($mainnet_address)) ||
            (!empty($rinkeby_address) && !EthereumValidator::isAddress($rinkeby_address))) {
            return response(['msg' => 'Error invalid contract address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $update_data = [];
        if (!empty($mainnet_address)) {
            $update_data['mainnet_address'] = $mainnet_address;
        } else {
            $update_data['rinkeby_address'] = $rinkeby_address;
        }
        $update_data['chain_id'] = $chain_id;
        $contract = Contract::updateContract(['id' => $contract_id], $update_data);

        return response(['msg' => 'Successfully created', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @OA\Get(
     * path="/api/v1/contract/get/{id}",
     * summary="Get User Contract",
     * tags={"Contract"},
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
    public function get(Request $request, string $id) {
        $address = $request->header('address');
        $user_id = Users::getIdByAddress($address);
        $contracts = Contract::getContract(['*'], [['id' => $id, 'operator' => '='], ['user_id' => $user_id, 'operator' => '=']]);

        return response(['msg' => 'Successfully created', 'contracts' => $contracts,'success' => true], 200)
            ->header('Content-Type', 'application/json');
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
        $address = $request->header('address');
        if (empty($address)) {
            return response(['msg' => 'Error wallet address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $user_id = Users::getIdByAddress($address);
        $contracts = Contract::getContract(['project_name', 'collection_name', 'collection_symbol', 'updated_date', 'type_id'], [['user_id' => $user_id, 'operator' => '=']]);

        return response(['msg' => 'Successfully created', 'contracts' => $contracts,'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    public function compile($address, $contract_id) {
//        $address = $request->header('address');
//        $contract_id = $request->input('contract_id');

        $address = '0x9DbF14C79847D1566419dCddd5ad35DAf0382E05';
        $contract_id = 1;

        $user_id = Users::getIdByAddress($address);
        $contract = Contract::getContract(['*'], [['id' => $contract_id, 'operator' => '='], ['user_id' => $user_id, 'operator' => '=']])[0];

        $className = str_replace(' ', '', $contract->collection_name);

        $base_smart_contract_path = storage_path() . '/SmartContracts/BaseERC721.sol';

        $path = storage_path() . '/UsersSmartContract/' . $user_id . '/';

        // UserSmartContract/{user_id}/{contract_id}
        if (!file_exists($path)) {
            mkdir($path);
            if (!file_exists($path . $contract_id . '/')) {
                mkdir($path . $contract_id . '/');
            }
        }

        $new_smart_contract_path = $path . $contract_id . '/' . $className . '.sol';

        $smart_contract_content = file_get_contents($base_smart_contract_path);

        $smart_contract_content = str_replace('$mintPrice', $contract->mint_price, $smart_contract_content);
        $smart_contract_content = str_replace('$preSaleMintPrice',  $contract->presale_mint_price, $smart_contract_content);
        $smart_contract_content = str_replace('$totalCount', $contract->total_count, $smart_contract_content);
        $smart_contract_content = str_replace('$presaleLimitPerWallet', $contract->presale_limit_per_wallet, $smart_contract_content);
        $smart_contract_content = str_replace('$limitPerWallet', $contract->limit_per_wallet, $smart_contract_content);
        $smart_contract_content = str_replace('$className', $className, $smart_contract_content);
        $smart_contract_content = str_replace('$collectionName', $contract->collection_name, $smart_contract_content);
        $smart_contract_content = str_replace('$collectionSymbol', $contract->collection_symbol, $smart_contract_content);

        $smart_contract_content = str_replace('$reserveCount', $contract->reserve_count, $smart_contract_content);
        $smart_contract_content = str_replace('$reserveAtTime', $contract->reserve_count, $smart_contract_content);

        $withdrawal_addresses = Contract::getWihdrawalAddress($user_id, $contract_id);
        $withdrawal_addresses = empty($withdrawal_addresses[0]) ? $address : $withdrawal_addresses[0]->address;

        $smart_contract_content = str_replace('$withdrawAddress', $withdrawal_addresses, $smart_contract_content);

        file_put_contents($new_smart_contract_path, $smart_contract_content);

        $sols = ['Address.sol', 'Context.sol', 'ERC165.sol', 'ERC721.sol', 'ERC721Enumerable.sol', 'IERC165.sol',
            'IERC721.sol', 'IERC721Enumerable.sol', 'IERC721Metadata.sol', 'IERC721Receiver.sol', 'Ownable.sol', 'Strings.sol'];
        foreach ($sols as $item) {
            copy(storage_path() . '/SmartContracts/721/' . $item, $path . $item);
        }
    }
}
