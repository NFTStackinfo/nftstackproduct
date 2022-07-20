<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Users;
use App\Models\WithdrawalAddresses;
use App\Services\EthereumValidator;
use App\Services\Helper;
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
     *    required=true,
     *    example="5555",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Public Mint",
     *    in="path",
     *    name="limitPerWallet",
     *    required=true,
     *    example="5",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Presale Mint",
     *    in="path",
     *    name="presaleLimitPerWallet",
     *    required=true,
     *    example="3",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Reserve Count",
     *    in="path",
     *    name="reserveCount",
     *    required=true,
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

        $contract = Contract::createContract([
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

        return response(['msg' => 'Successfully created', 'contractId' => $contract, 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    /**
     * @OA\Put(
     * path="/api/v1/contract/update",
     * summary="Update Mainnet or Rinkeby Addresses",
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
     *    description="Successfully updated",
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
        Contract::updateContract(['id' => $contract_id], $update_data);
        //$this->verifyOnEtherscan($chain_id, $contract_id);

        return response(['msg' => 'Successfully updated', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    /**
     * @OA\Put(
     * path="/api/v1/contract/edit",
     * summary="Edit User Contract",
     * tags={"Contract"},
     * @OA\Parameter(
     *    description="Contract Id",
     *    in="path",
     *    name="contractId",
     *    required=true,
     *    example="4",
     *    @OA\Schema(
     *       type="int",
     *    )
     * ),
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
     *    required=true,
     *    example="5555",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Public Mint",
     *    in="path",
     *    name="limitPerWallet",
     *    required=true,
     *    example="5",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Limit Per Wallet for Presale Mint",
     *    in="path",
     *    name="presaleLimitPerWallet",
     *    required=true,
     *    example="3",
     *    @OA\Schema(
     *       type="integer",
     *    )
     * ),
     * @OA\Parameter(
     *    description="Reserve Count",
     *    in="path",
     *    name="reserveCount",
     *    required=true,
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
     *    description="Successfully edited",
     *    @OA\JsonContent(
     *       @OA\Property(property="msg", type="string", example="Successfully created")
     *        )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function edit(Request $request) {
        $address = $request->header('address');
        $contract_id = $request->input('contractId');
        $mint_price = $request->input('mintPrice');
        $presale_mint_price = $request->input('presaleMintPrice');
        $total_count = $request->input('totalCount');
        # $limit_per_transaction = $request->input('limitPerTransaction');
        $limit_per_wallet = $request->input('limitPerWallet');
        $presale_limit_per_wallet = $request->input('presaleLimitPerWallet');
        $reserve_count = $request->input('reserveCount');
        $type_id = $request->input('typeId');
        $collection_name = $request->input('collectionName');
        $project_name = $request->input('projectName');
        $collection_symbol = $request->input('collectionSymbol');
        $metadata_uri = $request->input('metadataUri');
        $walletAddresses = $request->input('walletAddresses');

        if (empty($mint_price) || empty($presale_mint_price) || empty($total_count) || empty($limit_per_wallet) || empty($presale_limit_per_wallet)
            || empty($reserve_count) || empty($type_id) || empty($contract_id)) {
            return response(['msg' => 'Error required params are missing', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $user_id = Users::getIdByAddress($address);
        $contract = Contract::getContract(['*'], [['id' => $contract_id, 'operator' => '='], ['deleted' => 0, 'operator' => '=']]);

        if (empty($contract[0])) {
            return response(['msg' => 'Error contract not found', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if ((!empty($collection_name) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_name))
            || (!empty($collection_symbol) && preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $collection_symbol))) {
            return response(['msg' => 'Error specific symbols in name', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!filter_var($metadata_uri, FILTER_VALIDATE_URL)) {
            return response(['msg' => 'Error invalid metadata URI', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        Contract::updateContract(['id' => $contract_id], [
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
        return response(['msg' => 'Successfully edited', 'success' => true], 200)
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
     * @param $id
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function get(Request $request, $id) {
        $address = $request->header('address');
        $user_id = Users::getIdByAddress($address);
        $contract = Contract::getContract(['*'], [['id' => $id, 'operator' => '='], ['deleted' => 0, 'operator' => '=']]);

        if (empty($contract[0])) {
            return response(['msg' => 'No Contract', 'contract' => [], 'abi' => [], 'success' => false], 200)
                ->header('Content-Type', 'application/json');
        }

        $contract[0]->walletAddresses = WithdrawalAddresses::getWihdrawalAddress($user_id, $id);
        $abi_data = $this->compile($address, $id);

        return response(['msg' => 'Successfully', 'contract' => Helper::snakeToCamel($contract)[0], 'abi' => $abi_data,'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    /**
     * @OA\Get(
     * path="/api/v1/contract/get",
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
        $contracts = Contract::getContract(['id', 'project_name', 'collection_name', 'collection_symbol',
            'updated_at', 'type_id', 'mainnet_address', 'rinkeby_address'],
            [['user_id' => $user_id, 'operator' => '='], ['deleted' => 0, 'operator' => '=']]);

        return response(['msg' => 'Successfully created', 'contracts' => Helper::snakeToCamel($contracts),'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }


    /**
     * @param $address
     * @param $contract_id
     * @return array
     */
    public function compile($address, $contract_id) {
        $user_id = Users::getIdByAddress($address);
        $contract = Contract::getContract(['*'], [['id' => $contract_id, 'operator' => '='],
            ['user_id' => $user_id, 'operator' => '='], ['deleted' => 0, 'operator' => '=']])[0];

        $className = str_replace(' ', '', $contract->collection_name);

        $base_smart_contract_path = storage_path() . '/SmartContracts/BaseERC721.sol';

        $path = storage_path() . '/UsersSmartContract/' . $user_id . '/';

        // UserSmartContract/{user_id}/{contract_id}
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        if (!file_exists($path . $contract_id . '/')) {
            mkdir($path . $contract_id . '/', 0777, true);
        }

        $new_smart_contract_path = $path . $contract_id . '/' . $className . '.sol';

        $smart_contract_content = file_get_contents($base_smart_contract_path);

        $smart_contract_content = str_replace('(mintPrice)', $contract->mint_price, $smart_contract_content);
        $smart_contract_content = str_replace('(preSaleMintPrice)',  $contract->presale_mint_price, $smart_contract_content);
        $smart_contract_content = str_replace('(totalCount)', $contract->total_count, $smart_contract_content);
        $smart_contract_content = str_replace('$presaleLimitPerWallet', $contract->presale_limit_per_wallet, $smart_contract_content);
        $smart_contract_content = str_replace('$limitPerWallet', $contract->limit_per_wallet, $smart_contract_content);
        $smart_contract_content = str_replace('$className', $className, $smart_contract_content);
        $smart_contract_content = str_replace('$collectionName', $contract->collection_name, $smart_contract_content);
        $smart_contract_content = str_replace('$collectionSymbol', $contract->collection_symbol, $smart_contract_content);

        $smart_contract_content = str_replace('$reserveCount', $contract->reserve_count, $smart_contract_content);
        $smart_contract_content = str_replace('$reserveAtTime', $contract->reserve_count, $smart_contract_content);

        $withdrawal_addresses = WithdrawalAddresses::getWihdrawalAddress($user_id, $contract_id);


        $withdrawal_addresses = empty($withdrawal_addresses[0]) ? $address : $withdrawal_addresses;
        if (empty($withdrawal_addresses[0])) {
            $smart_contract_content = str_replace('$withdrawAddress', $withdrawal_addresses, $smart_contract_content);
            $smart_contract_content = str_replace('$address private OtherAddress$', 'address private OtherAddress', $smart_contract_content);
        } else {
            $variable_address = '';
            $variable_withdraw = '';
            $counter = 1;
            foreach ($withdrawal_addresses as $withdrawal_address) {
                $variable_address .= "address private OtherAddress$counter = $withdrawal_address->address;\r\n    ";
                $variable_withdraw .= "payable(OtherAddress$counter).transfer(balance * $withdrawal_address->percent / 100);\r\n        ";
                $counter += 1;
            }
            $smart_contract_content = str_replace('$address private OtherAddress$ = $withdrawAddress;', $variable_address, $smart_contract_content);
            $smart_contract_content = str_replace('$payable(OtherAddress).transfer(balance * 10000 / 10000);$', $variable_withdraw, $smart_contract_content);
        }

        file_put_contents($new_smart_contract_path, $smart_contract_content);

        $sols = ['Address.sol', 'Context.sol', 'ERC165.sol', 'ERC721.sol', 'ERC721Enumerable.sol', 'IERC165.sol',
            'IERC721.sol', 'IERC721Enumerable.sol', 'IERC721Metadata.sol', 'IERC721Receiver.sol', 'Ownable.sol', 'Strings.sol'];

        foreach ($sols as $item) {
            copy(storage_path() . '/SmartContracts/721/' . $item, $path . $contract_id . '/' . $item);
        }

        $base_path = base_path();
        shell_exec("rm -rf $base_path/build");
        shell_exec("solc --abi $new_smart_contract_path -o $base_path/build");
        $abi = file_get_contents($base_path. '/build/' . $className . '.abi');

        $bytecode = shell_exec("solc $new_smart_contract_path --bin");
        $bytecode = Helper::getStringBetween($bytecode, "$className.sol:$className =======\\nBinary:\\n", '\n');

        return ['abi' => $abi, 'bytecode' => $bytecode];
    }

    //public function verifyOnEtherscan($chain_id, $contract_id) {
    public function verifyOnEtherscan() {
//        $contract = Contract::getContract(['*'], [['id' => $contract_id, 'operator' => '='], ['deleted' => 0, 'operator' => '=']])[0];
//        $className = str_replace(' ', '', $contract->collection_name);
//        $get_sol_content = file_get_contents(storage_path().'/UsersSmartContract/'.$contract->user_id.'/'.$contract_id.'/'.$className.'.sol');
//        $get_verify_content = file_get_contents(storage_path().'/SmartContracts/EtherscanVerify.sol');
//        $get_verify_content = str_replace('$$contract', $get_sol_content, $get_verify_content);

        $className = 'Ashot55';
        $get_sol_content = file_get_contents(storage_path().'/UsersSmartContract/1/7/'.$className.'.sol');
        $get_verify_content = file_get_contents(storage_path().'/SmartContracts/EtherscanVerify.sol');

        $get_verify_content = str_replace('$$contract', $get_sol_content, $get_verify_content);
        $get_verify_content = str_replace('import "./ERC721Enumerable.sol";', '', $get_verify_content);
        $get_verify_content = str_replace('import "./Ownable.sol";', '', $get_verify_content);
        $get_verify_content = str_replace('// SPDX-License-Identifier: MIT', '', $get_verify_content);

        file_put_contents(storage_path().'/SmartContracts/EtherscanVerify.sol',$get_verify_content );
        $chain_id= 4;
        if ($chain_id == 1) {
            $etherscan_link = 'https://api.etherscan.io/api';
            //$address = $contract->mainnet_address;
        } elseif ($chain_id == 4) {
            $etherscan_link = 'https://api-rinkeby.etherscan.io/api';
            //$address = $contract->rinkeby_address;
            $address = '0x1857e281AF4fc704992AbA66058b9cd04F827685';
        }

        $ch = curl_init();

        $data = http_build_query([
            'apikey' => 'BMQ7Z4F5CC1UM4IJYMUPXCV8T6T2FSY7SG',
            'module' => 'contract',
            'action' => 'verifysourcecode',
            'contractaddress' => $address,
            'codeformat' => 'solidity-single-file',
            'contractname' => $className,
            'compilerversion' => 'v0.8.15+commit.e14f2714',
            'optimizationUsed' => '0',
            'sourceCode' => $get_verify_content
        ]);

        curl_setopt($ch, CURLOPT_URL, $etherscan_link);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);
        var_dump($server_output);
        curl_close ($ch);

    }
}
