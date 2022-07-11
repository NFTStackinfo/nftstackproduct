<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Elliptic\EC;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class Web3AuthController
{
    /**
     * @OA\Get(
     * path="/api/v1/login-message/{address}",
     * summary="Create nonce",
     * tags={"Authentication"},
     * @OA\Response(
     *    response=200,
     *    description="Geting nonce for verify",
     *    @OA\JsonContent(
     *       @OA\Property(property="nonce", type="string", example="vsv433dc")
     *        )
     *     )
     * )
     * @param Request $request
     * @param String $address
     * @return \Illuminate\Http\response
     */
    public function message(Request $request, string $address): \Illuminate\Http\response {
        $nonce = Str::random();
        $redis = app('redis');

        if (empty($address)) {
            return response(['msg' => 'Error address not found'], 404)
                ->header('Content-Type', 'application/json');
        }

        $redis->set($address, $nonce);

        return response(['msg' => 'success', 'nonce' => $nonce, 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     *
     * @OA\Post(
     * path="/api/v1/login-verify",
     * summary="Verify Login",
     * tags={"Authentication"},
     * @OA\Parameter(
     *    description="Signature",
     *    in="path",
     *    name="signature",
     *    required=true,
     *    example="0x3DbF14C79847D1566419dCddd5ad35DAf0382E0514C79847D1566419dCddd5a",
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
     *       @OA\Property(property="signature", type="string", format="string", example="0x3DbF14C79847D1566419dCddd5ad35DAf0382E0514C79847D1566419dCddd5a"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Successfuly Verified",
     *    @OA\JsonContent(
     *       @OA\Property(property="hash", type="string", example="79847D1566419dCddd5C79847")
     *        )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function verify(Request $request): \Illuminate\Http\response {
        $redis = app('redis');
        $address = $request->header('address');
        $signature = $request->input('signature');

        if (empty($signature)) {
            return response(['msg' => 'Error signature not found'], 404)
                ->header('Content-Type', 'application/json');
        }

        if (empty($address)) {
            return response(['msg' => 'Error address not found'], 404)
                ->header('Content-Type', 'application/json');
        }

        $result = $this->verifySignature($redis->get($address), $signature, $address);
        $responce = md5($request->input('signature').'c324jn3ovn2o3nvo&T%^&%');

        $status = $result ? 200 : 401;
        $msg = $result ? 'success' : 'failed';
        $success = $result ? true : false;

        if ($status == 200) {
            $user_id = Users::getIdByAddress($address);
            if($user_id == 0) {
                Users::createUser(['wallet' => $address]);
            }
        }

        return response(['msg' => $msg, 'hash' => $responce, 'success' => $success], $status)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @OA\Get(
     * path="/api/v1/logout/",
     * summary="Log Out",
     * tags={"Authentication"},
     * @OA\Response(
     *    response=200,
     *    description="Successfully logouted",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="bool", example="true")
     *        )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function logOut(Request $request) {
        $address = $request->header('address');
        $redis = app('redis');

        if (empty($address)) {
            return response(['msg' => 'Error address not found', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $nonce = $redis->get($address);
        if(empty($nonce) || $nonce == '') {
            return response(['msg' => 'Error something wrong', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $redis->set($request->input($address), 'exit');

        return response(['msg' => 'Successfully log outed', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @param string $message
     * @param string $signature
     * @param string $address
     * @return bool
     * @throws \Exception
     */
    protected function verifySignature(string $message, string $signature, string $address) {
        $hash = Keccak::hash(sprintf("\x19Ethereum Signed Message:\n%s%s", strlen($message), $message), 256);
        $sign = [
            'r' => substr($signature, 2, 64),
            's' => substr($signature, 66, 64),
        ];
        $recid = ord(hex2bin(substr($signature, 130, 2))) - 27;

        if ($recid != ($recid & 1)) {
            return false;
        }

        $pubkey = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recid);
        $derived_address = '0x' . substr(Keccak::hash(substr(hex2bin($pubkey->encode('hex')), 1), 256), 24);

        return (Str::lower($address) === $derived_address);
    }
}
