<?php

namespace App\Helper;

use Carbon\Carbon;
use App\Models\WebSetting;
use App\Models\IntegrationLog;
use App\Models\Notification;
use App\Models\notificationType;
use App\Models\EmailLog;
use App\Models\HolidayCalendar;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Support\Facades\DB;
Use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Models\AutoNumber;
use App\Models\FileAttach;
use Image;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Iai\V20200303\IaiClient;
use TencentCloud\Iai\V20200303\Models\CompareFaceRequest;
use TencentCloud\Ocr\V20181119\OcrClient;
use TencentCloud\Ocr\V20181119\Models\MLIDCardOCRRequest;
use App\Helper\HWOcrClient\HWOcrClientToken;
use TencentCloud\Iai\V20200303\Models\DetectLiveFaceRequest;
use TencentCloud\Iai\V20200303\Models\DetectFaceRequest;
use TencentCloud\Faceid\V20180301\FaceidClient;
use TencentCloud\Faceid\V20180301\Models\LivenessCompareRequest;

class Custom {

	public function genFileURL($fileParam){
		return config('app.url').'/files?link='.$this->encrypt_decrypt($fileParam);
	}

	public function encrypt_decrypt($string, $action = 'encrypt'){

		$encrypt_method = "AES-256-ECB";
		$secret_key = 'WEPBwtFn1XkTII4ob3z7WEPBwtFn1XkTII4ob3z7DQA=';
		$key = base64_decode($secret_key);
		$iv_size = openssl_cipher_iv_length($encrypt_method);
		$iv = '';
		if ($iv_size > 0){
			$iv = openssl_random_pseudo_bytes($iv_size);
		}


		if ($action == 'encrypt') {
			$string = $string.substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 6);
			$output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA , $iv);

			$output = rawurlencode(base64_encode($output));
			$output = preg_replace_callback('/%[a-zA-Z0-9]{2}/', function($match) {
						return strtolower($match[0]);
					}, $output);
			//$output = base64_encode($output);

		} else if ($action == 'decrypt') {
			//$output = openssl_decrypt(base64_decode(urldecode($string)), $encrypt_method, $key, OPENSSL_RAW_DATA , $iv);

			$output = rawurldecode($string);
			$output = base64_decode($output);
			$output = openssl_decrypt($output, $encrypt_method, $key, OPENSSL_RAW_DATA , $iv);
			$output = substr($output, 0, -6);
		}

		/*
		if ($action == 'encrypt') {
			$output = $string.substr(str_shuffle("1234567890"), 0, 6);

		} else if ($action == 'decrypt') {
			$output = substr($string, 0, -6);
		}
		*/

		return $output;
	}

	public function encrypt($string){

		$encrypt_method = "AES-128-CBC";
		$secret_key = 'UCRKRWIDO3538558';
		$iv = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

		$encrypted = openssl_encrypt($string, $encrypt_method, $secret_key, OPENSSL_RAW_DATA, $iv);
		$base64Encoded = base64_encode($encrypted);
		$encyrptedHexBinary = bin2hex($base64Encoded);

		return $encyrptedHexBinary;
	}

	public function decrypt($encyrptedHexBinary){

		$encrypt_method = "AES-128-CBC";
		$secret_key = 'UCRKRWIDO3538558';
		$iv = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

		$base64Encoded = hex2bin($encyrptedHexBinary);
		$encrypted = base64_decode($base64Encoded);
		$string = openssl_decrypt($encrypted, $encrypt_method, $secret_key, OPENSSL_RAW_DATA , $iv);

		return $string;
	}

    public function getSetting(){
        $data = WebSetting::First();
        return $data;
    }

    public function sendPushNotification($notificationID){

		$setting = $this->getSetting();

        $notification = Notification::where('NOID',$notificationID)
									->OrderBy('NOID','DESC')
									->First();

		if ($notification != null){

			//SEND TO PUSH NOTIFICATION
			$pushDevice = User::SELECT('PNPushID')
								->join('MSPushNotification','PN_USCode','USCode')
								->where('PN_USCode',$notification->NO_USCode)
								->where('PNActive',1)
								->Where('PNPushID','<>','')
								->WhereNotNull('PNPushID')
								->orderby('PNPushID','asc')
								->get();

			if (count($pushDevice) > 0){
					$pushArray =[];
					$pushArray = $pushDevice->pluck('PNPushID');

					//log::debug(json_encode($pushArray));
					//print("\n[".Carbon::now()->toDateTimeString()."] sendPushNotification MPES".$pushArray);

					$headings = array(
						"en" => $notification->NOTitle
					);

					$content = array(
						"en" => $notification->NODescription
					);

					$fields = array(
						'app_id' => $setting->OneSignalAppID,
						'include_player_ids' => $pushArray,
						'data' => array(
							"foo" => "bar"
						),
						'headings' => $headings,
						'contents' => $content,
						//'large_icon' => config('app.url').'/announcements/'. $announcements->banner
					);

					$fields = json_encode($fields);
					//print("\nJSON sent:\n");
					//print($fields);

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json; charset=utf-8',
						 'Authorization: Basic '.$setting->OneSignalAppKey
					));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					curl_setopt($ch, CURLOPT_HEADER, FALSE);
					curl_setopt($ch, CURLOPT_POST, TRUE);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

					$responseData = curl_exec($ch);
					curl_close($ch);

					//return $response;
					//$data = json_decode($fields, true);
					$data = json_decode($responseData, true);
					//log::debug($data);
					//print("\n[".Carbon::now()->toDateTimeString()."] sendPushNotification MPES".$responseData);
			}
		};


    }

    public function deleteFile($fileAttachData){

		//$fileAttachData = FileAttach::where('FA_TVANo',$request->appNo)->where('FAID',$request->fileID)->get();

		if(isset($fileAttachData) && count($fileAttachData)>0) {
            foreach ($fileAttachData as $x => $fileAttach) {

				$filePath = $fileAttach->FAFilePath;
				$fileName =  $fileAttach->FAFileName;
				$myFile = explode('.',  $fileAttach->FAFileName);
				$fileExt = strtolower($myFile[count($myFile) - 1]);

				if ($fileExt == 'pdf'){
					$smallFileName = str_replace('.'.$fileExt, '', $fileName) . 's.jpg';
				}else{
					$smallFileName = str_replace('.'.$fileExt, '', $fileName) . 's.'.$fileExt;
				}

				$newFilePath = str_replace($fileName, $smallFileName, $fileAttach->FAFilePath);

				$fileAttach->delete();

				if (Storage::disk('fileStorage')->exists($filePath)== true){
					Storage::disk('fileStorage')->delete($filePath);
				}
				if (Storage::disk('fileStorage')->exists($newFilePath)== true){
					Storage::disk('fileStorage')->delete($newFilePath);
				}
			}
        }


    }

    public function faceCompare($imageA, $imageB, $fileTypeA=null, $fileTypeB=null ){

		$faceScore = 0;
		$faceResult = null;

		//log::info('imageA: '.$imageA);
		//log::info('imageB: '.$imageB);

		$image1 = $imageA;
		$image2 = $imageB;

		$heightA = Image::make($imageA)->height();
		$widthA = Image::make($imageA)->width();

		$heightB = Image::make($imageB)->height();
		$widthB = Image::make($imageB)->width();

		//log::info('heightA: '.$heightA);
		//log::info('widthA: '.$widthA);
		//log::info('heightB: '.$heightB);
		//log::info('widthB: '.$widthB);

		if ($fileTypeA=='JPG'){
			if ($heightA > 4000 ){
				$fileContent = Image::make($imageA);
				$fileContent->resize(null, 4000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('data-url');
				$imageA = preg_replace('#^data:image/\w+;base64,#i', '', $imageA);
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
			if ($widthA > 4000) {
				$fileContent = Image::make($imageA);
				$fileContent->resize(4000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('data-url');
				$imageA = preg_replace('#^data:image/\w+;base64,#i', '', $imageA);
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
		}else{
			if ($heightA > 2000 ){
				$fileContent = Image::make($imageA);
				$fileContent->resize(null, 2000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('data-url');
				$imageA = preg_replace('#^data:image/\w+;base64,#i', '', $imageA);
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
				//log::info('new imageA: '.$imageA);
			}
			if ($widthA > 2000) {
				$fileContent = Image::make($imageA);
				$fileContent->resize(2000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('data-url');
				$imageA = preg_replace('#^data:image/\w+;base64,#i', '', $imageA);
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
		}

		if ($fileTypeB=='JPG'){
			if ($heightB > 4000 ){
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(null, 4000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('data-url');
				$imageB = preg_replace('#^data:image/\w+;base64,#i', '', $imageB);
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
			if ($widthB > 4000) {
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(4000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('data-url');
				$imageB = preg_replace('#^data:image/\w+;base64,#i', '', $imageB);
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
		}else{
			if ($heightB > 2000 ){
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(null, 2000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('data-url');
				$imageB = preg_replace('#^data:image/\w+;base64,#i', '', $imageB);
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
			if ($widthB > 2000) {
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(2000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('data-url');
				$imageB = preg_replace('#^data:image/\w+;base64,#i', '', $imageB);
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
		}


		//log::info('new imageA: '.$imageA);
		//log::info('new imageB: '.$imageB);

		try {

			$cred = new Credential("IKIDZx40ZsugBPM2RZPyHJ4YmjQq0tVqtgCR ", "yg6F7j48vRihPq1vYuznKaZHNDm57sLY");
			$httpProfile = new HttpProfile();
			$httpProfile->setEndpoint("iai.tencentcloudapi.com");

			$clientProfile = new ClientProfile();
			$clientProfile->setHttpProfile($httpProfile);
			$client = new IaiClient($cred, "ap-guangzhou", $clientProfile);

			$req = new CompareFaceRequest();

			$params = array(
				"ImageA" => $imageA,
				"ImageB" => $imageB
			);

			$req->fromJsonString(json_encode($params));

		 $resp='';
			$resp = $client->CompareFace($req);

			//print_r($resp->toJsonString());

			$faceScore =  $resp->Score;
			//return response()->json([
			//	'status'  => 'success',
			//	'data' => $resp
			//]);
		}
		catch(TencentCloudSDKException $e) {
			//echo $e;
			log::error($e);
		}
		$faceResult = array(
			'faceScore' => $faceScore,
			'result' 	=> $resp,
		);
		return $faceResult;
    }


    public function faceCompareAI($imageA, $imageB, $fileTypeA=null, $fileTypeB=null ){

		$faceScore = 0;
		$faceResult = null;

		//log::info('imageA: '.$imageA);
		//log::info('imageB: '.$imageB);
		$imageA = base64_decode($imageA);
		$imageB = base64_decode($imageB);

		$image1 = $imageA;
		$image2 = $imageB;

		$heightA = Image::make($imageA)->height();
		$widthA = Image::make($imageA)->width();

		$heightB = Image::make($imageB)->height();
		$widthB = Image::make($imageB)->width();

		//log::info('heightA: '.$heightA);
		//log::info('widthA: '.$widthA);
		//log::info('heightB: '.$heightB);
		//log::info('widthB: '.$widthB);

		if ($fileTypeA=='JPG'){
			if ($heightA > 4000 ){
				$fileContent = Image::make($imageA);
				$fileContent->resize(null, 4000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('jpg');
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
			if ($widthA > 4000) {
				$fileContent = Image::make($imageA);
				$fileContent->resize(4000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('jpg');
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
		}else{
			if ($heightA > 2000 ){
				$fileContent = Image::make($imageA);
				$fileContent->resize(null, 2000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('jpg');
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
				//log::info('new imageA: '.$imageA);
			}
			if ($widthA > 2000) {
				$fileContent = Image::make($imageA);
				$fileContent->resize(2000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightA = $fileContent->height();
				$widthA = $fileContent->width();
				$imageA = $fileContent->encode('jpg');
				//log::info('new heightA: '.$heightA);
				//log::info('new widthA: '.$widthA);
			}
		}

		if ($fileTypeB=='JPG'){
			if ($heightB > 4000 ){
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(null, 4000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('jpg');
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
			if ($widthB > 4000) {
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(4000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('jpg');
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
		}else{
			if ($heightB > 2000 ){
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(null, 2000, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('jpg');
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
			if ($widthB > 2000) {
				$fileContentB = Image::make($imageB);
				$fileContentB->resize(2000, null, function ($constraint) {
					$constraint->aspectRatio();
				});
				$heightB = $fileContentB->height();
				$widthB = $fileContentB->width();
				$imageB = $fileContentB->encode('jpg');
				//log::info('new heightB: '.$heightB);
				//log::info('new widthB: '.$widthB);
			}
		}

		//log::info('new imageA: '.$imageA);
		//log::info('new imageB: '.$imageB);

		try {
			$userName = 'admin';
			$password = 'admin123';

			$httpClient = new \GuzzleHttp\Client([
                'base_uri' => "http://192.168.3.200",
                'headers' => [
					'Accept'       	=> 'application/json',
                ]
			]);
			$httpRequest = $httpClient->get('/auth/login/challenge?username='.$userName);
			$responseData = json_decode($httpRequest->getBody()->getContents());
			if ($responseData != null && $responseData->code == 0){
				$challenge 	= $responseData->data->challenge ?? '';
				$salt 		= $responseData->data->salt ?? '';
				$session_id = $responseData->data->session_id ?? '';
				$svr_sdk_ver= $responseData->data->svr_sdk_ver ?? '';
				$sha256Password = hash("sha256",$password.$salt.$challenge);
			}else{
				return array(
					'faceScore' => 0,
					'message' 	=> $responseData->message ?? null,
				);
			}
			
			$httpClient = new \GuzzleHttp\Client([
                'base_uri' => "http://192.168.3.200",
                'headers' => [
					'Accept'  => 'application/json',
					'Content-Type'  => 'application/json',
				],
				'body' => json_encode(
					[
						"session_id" => $session_id,
						"username" => $userName,
						"password" => $sha256Password
					]
				)
			]);
			$httpRequest = $httpClient->post('/auth/login');
			$responseData = json_decode($httpRequest->getBody()->getContents());
			if ($responseData != null && $responseData->code == 0){
				$session_id = $responseData->data->session_id ?? '';
			}else{
				return array(
					'faceScore' => 0,
					'message' 	=> $responseData->message ?? null,
				);
			}
		
			$httpClient = new \GuzzleHttp\Client([
                'base_uri' => "http://192.168.3.200",
                'headers' => [
					'Accept'       	=> 'application/json',
					'Content-Type'  => 'multipart/form-data',
					'Cookie'  => 'sessionID='.$session_id,
                ],
				'multipart' => [
					[
						'name'     => 'faces_info',
						'contents'=> '{"compare_data": [{ "face1_data_type": "jpeg", "face2_data_type": "jpeg" }]}'
					],
					[
						'name'     => 'face1',
						'contents' => $imageA,
					],
					[
						'name'     => 'face2',
						'contents' => $imageB,
					]
				]
			]);
			$httpRequest = $httpClient->post('/face_manager/compare_face');
			$responseData = json_decode($httpRequest->getBody()->getContents());
			
			if ($responseData != null){
				$faceScore = $responseData->data->compare_result[0]->compare_score ?? 0;
				$livenessScore = $responseData->data->compare_result[0]->detection_info[1]->livenessScore ?? 0;
			}
			
		} catch(\GuzzleHttp\Exception\ConnectException $e){
			//echo $e;
			log::error($e);
		}
		$faceResult = array(
			'faceScore' 	=> $faceScore,
			'result' 		=> $responseData ?? null,
			'livenessScore' => $livenessScore,
		);
		return $faceResult;
    }


    public function passportOCR($image){
		$dataPassportOCR =[];
		//log::info('start OCR');

		try {

			$domainName = "thirdlink";
			$username = "admundng";
			$password = "Welcome@99";
			$regionName = "ap-southeast-1";
			$uri = "/v2/f77e501cb2f247ed9b04b068c88fea57/ocr/passport";
			$option = [];
			$option["country_code"] = "GENERAL";

			$ocrServer = new HWOcrClientToken(
				$username,
				$password,
				$domainName,
				$regionName,
				$uri
			);

			$dataPassportOCR =  $ocrServer->RequestOcrResult($image, $option);

			//log::info('dataPassportOCR'.$dataPassportOCR);
			$dataPassportOCR  = json_decode($dataPassportOCR);

		} catch (\Throwable $e) {
			log::error('OCR Error: '.$e);
			//throw $e;
		}

		return $dataPassportOCR;

    }

    public function mykadOCR($image){
		$dataOCR =[];
		//log::info('start OCR');

		$error = '';

		try {

			$cred = new Credential("IKIDdEbhX6NPAeqmSrzqQjlW1GEuJSKvFJ6b ", "HH0SdXAWbOvB9qHQUN8dsrb5KJ2WjiRv");
			$httpProfile = new HttpProfile();
			$httpProfile->setEndpoint("ocr.tencentcloudapi.com");

			$clientProfile = new ClientProfile();
			$clientProfile->setHttpProfile($httpProfile);
			$client = new OcrClient($cred, "ap-guangzhou", $clientProfile);

			$req = new MLIDCardOCRRequest();

			$params = array(
				"ImageBase64" => $image,
			);

			$req->fromJsonString(json_encode($params));

			$dataOCR = $client->MLIDCardOCR($req);
			//print_r($dataOCR->toJsonString());

		}

		catch(TencentCloudSDKException $e) {
			log::error('OCR Error: '.$e);
			//throw $e;
		}

		return $dataOCR;

    }

    public function updateProjectProgress($projectNo){

        $completeMilestoneDay = ProjectMileStone::where('PM_PTNo',$projectNo)->where('PM_PMSCode','C')->count('PMWorkDay');
        $totalMilestoneDay = ProjectMileStone::where('PM_PTNo',$projectNo)->count('PMWorkDay');

        $progress =  $completeMilestoneDay / $totalMilestoneDay;

        $lastMilestone = ProjectMileStone::where('PM_PTNo',$projectNo)->orderby('PMStartDate','asc')->first();

        $project = Project::where('PTNo',$projectNo)->first();
        if ($lastMilestone != null){
            $project->PTPriotiy  = $lastMilestone->PMPriotiy;
            $project->PTProgress = $progress;
        }else{
            $project->PTPriotiy  = 1;
            $project->PTProgress = 1;
        }
            $project->save();


    }

    public function sendNotification($module, $notificationTypeCode, $refNo, $param){

        $notificationType = NotificationType::where('NTCode',$notificationTypeCode)->first();
        if (isset($notificationType)){

            $title = str_replace('{TENDERNO}',$param->tenderNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{TENDERNO}',$param->tenderNo ?? '', $notificationType->NTDesc);
            $title = str_replace('{PROJECTNO}',$param->projectNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{PROJECTNO}',$param->projectNo ?? '', $notificationType->NTDesc);
            $title = str_replace('{PROPOSALNO}',$param->proposalNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{PROPOSALNO}',$param->proposalNo ?? '', $notificationType->NTDesc);
            $title = str_replace('{CLAIMNO}',$param->claimNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{CLAIMNO}',$param->claimNo ?? '', $notificationType->NTDesc);
            $title = str_replace('{EOTNO}',$param->eotNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{EOTNO}',$param->eotNo ?? '', $notificationType->NTDesc);
            $title = str_replace('{VONO}',$param->voNo ?? '', $notificationType->NTTitle);
            $desc = str_replace('{VONO}',$param->voNo ?? '', $notificationType->NTDesc);

            //SEND TO CONTRACTOR
            $notification = new Notification();
            $notification->NO_NTCode    = $notificationTypeCode;
            $notification->NOTitle      = $title;
            $notification->NODescription= $desc;
            $notification->NORead       = 0;
            $notification->NOSent       = 0;
            $notification->NOActive     = 1;
            $notification->NO_RefCode   = $refNo;
            $notification->NOType       = $module;
            $notification->save();


        }
    }

    public function sendWhatsappMeeting($phoneNo,$title,$date,$time,$location){

        if($phoneNo != null) {
            try {
                $body   = '{
                    "to": "+6'.$phoneNo.'",
                    "type": "template",
                    "template": {
						"namespace": "'.config::get('app.wa_namespace').'",
                        "name": "meeting_notification",
                        "language": {
                            "code": "en",
                            "policy": "deterministic"
                        },
                        "components": [
                            {
                                "type": "header",
                                "parameters": [
                                    {
                                        "type": "text",
                                        "text": "Mesyuarat Jemputan"
                                    }
                                ]
                            },
                            {
                                "type": "body",
                                "parameters": [
                                    {
                                        "type": "text",
                                        "text": "'.$title.'"
                                    },
                                    {
                                        "type": "text",
                                        "text": "'.$date.'"
                                    },
                                    {
                                        "type": "text",
                                        "text": "'.$time.'"
                                    },
                                    {
                                        "type": "text",
                                        "text": "'.$location.'"
                                    }
                                ]
                            }
                        ]
                    }
                }';

                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => "https://waba.360dialog.io",
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                        'D360-Api-Key' => config::get('app.wa_apikey')
                    ],
                    'body'   => $body
                ]);
                $url = 'v1/messages';
                $httpRequest = $httpClient->post($url);
                $responseData = $httpRequest->getBody()->getContents();


                $refNo = 'IL'.Carbon::now()->getPreciseTimestamp(3);
                $integrationLog = new IntegrationLog();
                $integrationLog->ILNo = $refNo;
                $integrationLog->ILRefNo = $phoneNo;
                $integrationLog->ILType = 'WABA';
                $integrationLog->ILPayload = $body;
                $integrationLog->ILResponse = $responseData;
                $integrationLog->ILComplete = 1;
                $integrationLog->save();

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

    public function sendWhatsappDirector($phoneNo,$company,$code){

		$pautan = route('welcome.index');
		$routeLink = "welcome";

        if($phoneNo != null) {
            try {
                $body   = '{
					"to": "+6'.$phoneNo.'",
					"type": "template",
					"template": {
						"namespace": "'.config::get('app.wa_namespace').'",
						"name": "auth_user_notice",
						"language": {
							"code": "en",
							"policy": "deterministic"
						},
						"components": [
							{
								"type": "header",
								"parameters": [
									{
										"type": "text",
										"text": "'.$company.'"
									}
								]
							},
							{
								"type": "body",
								"parameters": [
									{
										"type": "text",
										"text": "'.$pautan.'"
									},
									{
										"type": "text",
										"text": "'.$code.'"
									}
								]
							},
							{
								"type": "button",
								"sub_type": "url",
								"index": "0",
								"parameters": [
									{
										"type": "text",
										"text": "'.$routeLink.'"
									}
								]
							}
						]
					}
				}';

                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => "https://waba.360dialog.io",
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                        'D360-Api-Key' => config::get('app.wa_apikey')
                    ],
                    'body'   => $body
                ]);
                $url = 'v1/messages';
                $httpRequest = $httpClient->post($url);
                $responseData = $httpRequest->getBody()->getContents();


                $refNo = 'IL'.Carbon::now()->getPreciseTimestamp(3);
                $integrationLog = new IntegrationLog();
                $integrationLog->ILNo = $refNo;
                $integrationLog->ILRefNo = $phoneNo;
                $integrationLog->ILType = 'WABA';
                $integrationLog->ILPayload = $body;
                $integrationLog->ILResponse = $responseData;
                $integrationLog->ILComplete = 1;
                $integrationLog->save();

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

    public function sendWhatsappLetter($templateName,$phoneNo,$title,$refNo,$refNo2 = "",$refNo3 = ""){

        if($phoneNo != null) {
            try {

				if( in_array($templateName, ['active_project_notice']) ){

					$body   = '{
						"to": "+6'.$phoneNo.'",
						"type": "template",
						"template": {
							"namespace": "'.config::get('app.wa_namespace').'",
							"name": "'.$templateName.'",
							"language": {
								"code": "en",
								"policy": "deterministic"
							},
							"components": [
								{
									"type": "body",
									"parameters": [
										{
											"type": "text",
											"text": "'.$title.'"
										},
										{
											"type": "text",
											"text": "'.$refNo.'"
										},
										{
											"type": "text",
											"text": "'.$refNo2.'"
										}
									]
								},
								{
									"type": "button",
									"sub_type": "url",
									"index": "0",
									"parameters": [
										{
											"type": "text",
											"text": "contractor/reset/password/'.$refNo3.'"
										}
									]
								}
							]
						}
					}';

				}
				elseif(in_array($templateName, ['change_password_notice'])){

					$body   = '{
						"to": "+6'.$phoneNo.'",
						"type": "template",
						"template": {
							"namespace": "'.config::get('app.wa_namespace').'",
							"name": "'.$templateName.'",
							"language": {
								"code": "en",
								"policy": "deterministic"
							}
						}
					}';

				}
				else{

					$body   = '{
						"to": "+6'.$phoneNo.'",
						"type": "template",
						"template": {
							"namespace": "'.config::get('app.wa_namespace').'",
							"name": "'.$templateName.'",
							"language": {
								"code": "en",
								"policy": "deterministic"
							},
							"components": [
								{
									"type": "body",
									"parameters": [
										{
											"type": "text",
											"text": "'.$title.'"
										},
										{
											"type": "text",
											"text": "'.$refNo.'"
										}
									]
								}
							]
						}
					}';

				}

                $httpClient = new \GuzzleHttp\Client([
                    'base_uri' => "https://waba.360dialog.io",
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                        'D360-Api-Key' => config::get('app.wa_apikey')
                    ],
                    'body'   => $body
                ]);
                $url = 'v1/messages';
                $httpRequest = $httpClient->post($url);
                $responseData = $httpRequest->getBody()->getContents();


                $refNo = 'IL'.Carbon::now()->getPreciseTimestamp(3);
                $integrationLog = new IntegrationLog();
                $integrationLog->ILNo = $refNo;
                $integrationLog->ILRefNo = $phoneNo;
                $integrationLog->ILType = 'WABA';
                $integrationLog->ILPayload = $body;
                $integrationLog->ILResponse = $responseData;
                $integrationLog->ILComplete = 1;
                $integrationLog->save();

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

	public function generateDigitalCert($file , $stamp , $refNo , $fileType){

		try {
			$body   = '
			{
				"base64Pdf": "'.$file.'",
				"base64Stamp": "'.$stamp.'",
				"pageToOverlay": "3",
				"xPosition": "13",
				"yPosition": "23"
			  }
			';

			$httpClient = new \GuzzleHttp\Client([
				'base_uri' => "http://162.19.153.196:17000",
				'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
				],
				'body'   => $body
			]);
			$url = 'Sign/digitally-sign-pdf';
			$httpRequest = $httpClient->post($url);
			//$responseData = $httpRequest->getBody()->getContents();
			$responseData = json_decode($httpRequest->getBody()->getContents(), true);

				if (isset($responseData['base64SignedPdf'])) {
					$base64SignedPdf = $responseData['base64SignedPdf'];

					//Log::debug($base64SignedPdf);
					$decodedFile = base64_decode($base64SignedPdf);

					$autoNumber = new AutoNumber();

					$generateRandomSHA256 = $autoNumber->generateRandomSHA256();
					$file = $decodedFile;
					// $fileType = 'PT-CPRDC';
					$refNo = $refNo ;


					$folderPath = Carbon::now()->format('ymd');
					$originalName =  'LAPORAN_TAMAT_PROJEK';
					//$newFileExt = pathinfo($decodedFile, PATHINFO_EXTENSION);
					$newFileExt = 'pdf';
					$newFileName = strval($generateRandomSHA256);

					$fileCode = $fileType;

					Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedFile);

					$fileAttach = FileAttach::where('FARefNo',$refNo)
						->where('FAFileType',$fileType)
						->first();

					if ($fileAttach == null){
						$fileAttach = new FileAttach();
						$fileAttach->FACB 		= 'SYSTEM';
						$fileAttach->FAFileType 	= $fileCode;
					}else{

					$filename   = $fileAttach->FAFileName;
					$fileExt    = $fileAttach->FAFileExtension ;

					Storage::disk('fileStorage')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

					}

					$fileAttach->FACB 		= 'SYSTEM';
					$fileAttach->FAFileType 	= $fileCode;
					
					$fileAttach->FARefNo     	    = $refNo;
					$fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
					$fileAttach->FAFileName 	    = $newFileName;
					$fileAttach->FAOriginalName 	= $originalName;
					$fileAttach->FAFileExtension    = strtolower($newFileExt);
					$fileAttach->FAMB 			    = 'SYSTEM';
					$fileAttach->save();

				}


			} catch(\GuzzleHttp\Exception\ConnectException $e){
				
				return response()->json([
					'status' => 'failed',
					'message' => 'Connection Error'
				]);
			}

		return $responseData;
	}

     	public function registerCert($project, $id, $name, $password){

        $project = Project::where('PJCode',$project)->first();
        $apiKey = $project->PJApiKeyServer ?? '';

        try {

			$httpClient = new \GuzzleHttp\Client([
				'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
				],
				'body' => json_encode(
					[
                        'id' 	    => $id,
                        'name' 		=> $name,
                        'password'  => $password,
                        'key' 		=> $apiKey,
					]
				)
			]);

			$url = config::get('app.ca_url');
			$httpRequest = $httpClient->post($url.'/api/cms/gencert');
			$responseData = json_decode($httpRequest->getBody()->getContents());

			if($responseData->status_code=="0"){
                $base64Cert = $responseData->certificate;
			}else{
				$error = $responseData->status_code;
                $message = $responseData->message;
			}

		} catch(\GuzzleHttp\Exception\ConnectException $e){
			log::error($e->messsage);
			$error = $e->message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}catch(\GuzzleHttp\Exception\RequestException $message){
			log::error($message);
			$error = $message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}
        $data = [
            'error'=>$error ?? '',
            'message'=>$message ?? '',
            'base64Cert'=>$base64Cert ?? ''
        ];
        
        return $data;
    }

	
    public function revokeCert($project, $id, $password){

        $project = Project::where('PJCode',$project)->first();
        $apiKey = $project->PJApiKeyServer ?? '';

        try {

			$httpClient = new \GuzzleHttp\Client([
				'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
				],
				'body' => json_encode(
					[
                        'id' 	    => $id,
                        'key' 		=> $apiKey,
					]
				)
			]);

			$url = config::get('app.ca_url');
			$httpRequest = $httpClient->post($url.'/api/cms/revoke');
			$responseData = json_decode($httpRequest->getBody()->getContents());

			if($responseData->status_code=="0"){
             
			}else{
				$error = $responseData->status_code;
                $message = $responseData->message;
			}

		} catch(\GuzzleHttp\Exception\ConnectException $e){
			log::error($e->messsage);
			$error = $e->message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}catch(\GuzzleHttp\Exception\RequestException $message){
			log::error($message);
			$error = $message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}
        $data = [
            'error'=>$error ?? '',
            'message'=>$message ?? '',
        ];
        
        return $data;
    }

	
    public function signPdf($project, $id, $password, $base64Pdf, $base64Stamp, $page_no, $left_lower_x, $left_lower_y, $right_upper_x, $right_upper_y ){

        $project = Project::where('PJCode',$project)->first();
        $apiKey = $project->PJApiKeyServer ?? '';

        try {

			$httpClient = new \GuzzleHttp\Client([
				'headers' => [
					'Content-Type' 	=> 'application/json',
					'Accept' 		=> 'application/json',
				],
				'body' => json_encode(
					[
                        'user_id' 	    => $id,
                        'password' 	    => $password,
                        'location' 	    => 'Kuala Lumpur',
                        'reason' 	    => 'Digital Signature',
                        'docpdf'        => $base64Pdf,
                        'esignature'    => $base64Stamp,
                        'left_lower_x'  => $left_lower_x,
                        'left_lower_y'  => $left_lower_y,
                        'right_upper_x' => $right_upper_x,
                        'right_upper_y' => $right_upper_y,
                        'show_id' 		=> 0,
                        'show_timestamp'=> 1,
                        'page_no'       => $page_no,
                        'key' 			=> $apiKey,
					]
				)
			]);

			$url = config::get('app.ca_url');
			$httpRequest = $httpClient->post($url.'/api/signer/signpdfSP');
			$responseData = json_decode($httpRequest->getBody()->getContents());

			if($responseData->status_code=="0"){
                $base64SignedPdf = $responseData->data;
			}else{
				$error = $responseData->status_code;
                $message = $responseData->message;
			}

		} catch(\GuzzleHttp\Exception\ConnectException $e){
			log::error($e->messsage);
			$error = $e->message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}catch(\GuzzleHttp\Exception\RequestException $message){
			log::error($message);
			$error = $message;
			/*
			return response()->json([
				'status' => 'failed',
				'message' => 'Connection Error'
			]);*/
		}
        $data = [
            'error'=>$error ?? '',
            'message'=>$message ?? '',
            'base64SignedPdf'=>$base64SignedPdf ?? ''
        ];
        
        return $data;
    }

    public function sendWhatsappOTP($phoneNo,$tacCode){

        if($phoneNo != null) {

            $data = '';
            try {
                $body   = '{
                    "from": "+6'.config::get('app.wa_phonenumber').'",
                    "to": ["+6'.$phoneNo.'"],
                    "body":{
                        "type": "template",
                        "template": {
                            "namespace": "'.config::get('app.wa_namespace').'",
                            "name": "idvet_request_otp",
                            "language": "en",
                            "components": [
                                {
                                    "type": "body",
                                    "parameters": [
                                        {
                                            "type": "text",
                                            "text": "'.$tacCode.'"
                                        }
                                    ]
                                }
                            ]
                        }
                    }
                }';


                $httpClient = new \GuzzleHttp\Client([
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                        'Authorization' => 'Basic '.config::get('app.wa_apikey')
                    ],
                    'body'   => $body
                ]);
                $url = config::get('app.wa_url');
                $httpRequest = $httpClient->post($url);
                $responseData = json_decode($httpRequest->getBody()->getContents());

                // $refNo = 'IL'.Carbon::now()->getPreciseTimestamp(3);
                // $integrationLog = new IntegrationLog();
                // $integrationLog->ILNo = $refNo;
                // $integrationLog->ILRefNo = $tacLog->TACPhone;
                // $integrationLog->ILType = 'WABA';
                // $integrationLog->ILPayload = $body;
                // $integrationLog->ILResponse = $responseData;
                // $integrationLog->ILComplete = 1;
                // $integrationLog->save();

            } catch(\GuzzleHttp\Exception\ConnectException $e){
                log::error($e->messsage);
                /*;
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Connection Error'
                ]);*/
            }
        }

            return $responseData;

    }

}
