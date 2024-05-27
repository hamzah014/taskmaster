<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;

class WebhooksController  extends Controller{

    public function index(Request $request)
    {
		log::debug($request);

        $mode = $request->hub_mode;
        $token = $request->hub_verify_token;
        $challenge = $request->hub_challenge;

        if ($mode == "subscribe" && $token == 'HAPPY') {
            // Respond with 200 OK and challenge token from the request
            //console.log("WEBHOOK_VERIFIED");
				return  response($challenge, 200, ['Content-Type' => 'text/plain']);
          } else {
            // Responds with '403 Forbidden' if verify tokens do not match
            //return response()->json(['status'=>'ERROR'], 403);
            return response("", 403)->header('Content-Type', 'text/html');
          }

        if (isset( $request->body->entry[0]->changes[0]->value)){
            $phone_number_id = $request->body->entry[0]->changes[0]->value->metadata->phone_number_id;
            $from = $request->body->entry[0]->changes[0]->value->messages[0]->from;
            $msg_body = $request->body->entry[0]->changes[0]->value->messages[0]->text->body;
        }
        return
        response()->json(['request'=>'aaa'], 200);
    }

    public function post(Request $request)
    {
		log::debug($request);

        return  response('SUCCESS', 200, ['Content-Type' => 'text/plain']);
    }

    public function postMessage($token, $from,$phone_number_id,$msg_body){

        if($phone_number_id != null) {
            try {
                $body   = '{
                    "messaging_product": "whatsapp",
                    "to": "'.$from.'",
                    "text": { body: "Ack2: " + '.$msg_body.' }
                }';

                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => "https://graph.facebook.com",
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                    ],
                    'body'   => $body
                ]);
                $url = 'v12.0/'.$phone_number_id."/messages?access_token=".$token;
                $httpRequest = $httpClient->post($url);
                $responseData = $httpRequest->getBody()->getContents();

                } catch(\GuzzleHttp\Exception\ConnectException $e){
                    /*
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Connection Error'
                    ]);*/
                }
            }

            return $responseData;

    }
}
