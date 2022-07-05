<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function handle($request, Closure $next) {
        $redis = app('redis');
        $address1 = $request->input('address');
        $address2 = $redis->get($address1);
        if (empty($address1) || $address1 == '' || $address1 == 'exit' || $address1 != $address2) {
            if(empty($nonce) || $nonce == '') {
                return response(['msg' => 'Unauthorized'], 401)
                    ->header('Content-Type', 'application/json');
            }
        }

        return $next($request);
    }
}
