<?php

namespace App\Http\Controllers;

use Elliptic\EC;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class Web3AuthController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\response
     */
    public function message(Request $request): \Illuminate\Http\response {
        $nonce = Str::random();
        $redis = app('redis');
        $address = $request->input('address');

        if ($address == '' || $address == null) {
            return response(['msg' => 'error no address'], 404)
                ->header('Content-Type', 'application/json');
        }

        $redis->set($request->input($address), $nonce);

        return response(['msg' => 'success', 'nonce' => $nonce], 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function verify(Request $request): \Illuminate\Http\response {
        $redis = app('redis');
        $address = $request->input('address');

        if ($address == '' || $address == null) {
            return response(['msg' => 'error no address'], 404)
                ->header('Content-Type', 'application/json');
        }

        $result = $this->verifySignature($redis->get($address), $request->input('signature'), $address);
        $responce = md5($request->input('signature').'c324jn3ovn2o3nvo&T%^&%');

        $status = $result ? 200 : 401;
        $msg = $result ? 'success' : 'failed';

        return response(['msg' => $msg, 'hash' => $responce], $status)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function logOut(Request $request) {
        $redis = app('redis');
        $address = $request->input('address');
        $nonce = $redis->get($address);
        if(empty($nonce) || $nonce == '') {
            return response(['msg' => 'error something wrong'], 404)
                ->header('Content-Type', 'application/json');
        }

        $redis->set($request->input($address), 'exit');

        return response(['msg' => 'success', 'nonce' => $nonce], 200)
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
