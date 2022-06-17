<?php

namespace App\Http\Controllers;

use App\Models\WaitList;
use Illuminate\Http\Request;

class WaitListController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \SendGrid\Mail\TypeException
     */
    public function sendEmail(Request $request) {
        $mail = $request->input('email');
        if (empty($mail)) {
            return response(['msg' => 'Error no email'], 405)
                ->header('Content-Type', 'application/json');
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return response(['msg' => 'Invalid address'], 406)
                ->header('Content-Type', 'application/json');
        }
        $checker = WaitList::checkEmail($mail);
        if ($checker) {
            return response(['msg' => 'Email already in waitlist'], 407)
                ->header('Content-Type', 'application/json');
        }


        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("product@nftstack.info", "Webly");
        $email->setSubject("Thank you for joining the Webly waitlist!");
        $email->addTo($mail);
        $email->setTemplateId('d-9bf89a4de7f24e04813144d8ac22bfeb');

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            WaitList::setEmail($mail);
            $response = $sendgrid->send($email);
            return response(['msg' => 'success'], $response->statusCode())
                ->header('Content-Type', 'application/json');
        } catch (Exception $e) {
            return response(['msg' => $e->getMessage()], 404)
                ->header('Content-Type', 'application/json');
        }
    }
}
