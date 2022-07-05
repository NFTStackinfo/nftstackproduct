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
            return response(['msg' => 'Error no email', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return response(['msg' => 'Invalid email', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }
        $checker = WaitList::checkEmail($mail);
        if ($checker) {
            return response(['msg' => 'Email already in waitlist', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }


        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("info@webly.pro", "Webly");
        $email->setSubject("Thank you for joining the Webly waitlist!");
        $email->addTo($mail);
        $email->setTemplateId('d-9bf89a4de7f24e04813144d8ac22bfeb');

        $unsubscribe = md5(md5(md5('no24ECH(&#(@#OCHWBdb9h9dd' . $mail)));
        $email->addDynamicTemplateData('unsubscribe', 'https://webly.pro/unsubscribe/' . $unsubscribe);

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            WaitList::setEmail($mail, $unsubscribe);
            $response = $sendgrid->send($email);
            return response(['msg' => 'success', 'success' => true], $response->statusCode())
                ->header('Content-Type', 'application/json');
        } catch (Exception $e) {
            return response(['msg' => $e->getMessage(), 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }
    }


    /**
     * @param $id
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function unsubscribe($id) {
        $updated = WaitList::updateEmail($id);
        if($updated == 0) {
            return response(['msg' => 'Wrong hash', 'success' => false], 404)
                ->header('Content-Type', 'application/json');
        }
        return response(['msg' => 'You are successfully unsubscribed', 'success' => true], 200)
            ->header('Content-Type', 'application/json');
    }
}
