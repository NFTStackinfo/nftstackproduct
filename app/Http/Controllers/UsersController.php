<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsersController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/v1/user/create",
     * summary="Create User",
     * tags={"User"},
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
     * @OA\Parameter(
     *    description="Email",
     *    in="path",
     *    name="email",
     *    required=false,
     *    example="NFT Stack",
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
     *    description="User successfully added",
     *    @OA\JsonContent(
     *       @OA\Property(property="msg", type="string", example="User successfully added")
     *        )
     *     )
     * )
     *
     * @param Request $request
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function create(Request $request) {
        $address = $request->input('address');
        $email = $request->input('email');
        if (empty($address)) {
            return response(['msg' => 'Error wallet address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response(['msg' => 'Invalid email', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!Users::getIdByAddress($address)) {
            return response(['msg' => 'User already exist', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        Users::createUser(['wallet' => $address]);
        return response(['msg' => 'User successfully added', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * @OA\Post(
     * path="/api/v1/user/updateEmail",
     * summary="Update User Email",
     * tags={"User"},
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
     * @OA\Parameter(
     *    description="Email",
     *    in="path",
     *    name="email",
     *    required=true,
     *    example="NFT Stack",
     *    @OA\Schema(
     *       type="string",
     *    )
     * ),
     * @OA\RequestBody(
     *    required=true,
     *    description="User email address",
     *    @OA\JsonContent(
     *       required={"address"},
     *       @OA\Property(property="address", type="string", format="string", example="0x9DbF14C79847D1566419dCddd5ad35DAf0382E05"),
     *       @OA\Property(property="email", type="string", format="string", example="example@webly.pro"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Email successfully updated",
     *    @OA\JsonContent(
     *       @OA\Property(property="msg", type="string", example="Email successfully updated")
     *        )
     *     )
     * )
     *
     * @param Request $request
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function updateEmail(Request $request) {
        $address = $request->input('address');
        $email = $request->input('email');
        if (empty($address)) {
            return response(['msg' => 'Error wallet address', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response(['msg' => 'Invalid email', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        $id = Users::getIdByAddress($address);
        Users::updateEmail($id, $email);

        return response(['msg' => 'Email successfully updated', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }
}
