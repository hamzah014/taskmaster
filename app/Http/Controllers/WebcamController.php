<?php

namespace App\Http\Controllers;

use App\Helper\Custom;
use App\Models\Approval;
use App\Models\AutoNumber;
use App\Models\FaceCompareLog;
use App\Models\FileAttach;
use App\Models\WebSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Auth;
use Storage;
use Intervention\Image\Facades\Image;


class WebcamController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function index()
    {
        return view('webcam.index');
    }

    /**
     * Write code on Method
     *
     * @return response()
     */


    public function store(Request $request)
    {

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $FaceScoreRate = $webSetting->FaceScoreRate *100;

        //$livenessScoreRate = $webSetting->MinLivenessScore * 100;
        $livenessScoreRate = 80;

        // Process the image data
        $imageData = $request->data['imageData'];

        $fileExt = '';
        $fileBase64 = null;
        if(isset($request->data['refNo'])){
            if($request->data['refNo'] == null){
                $request->data['refNo'] = 'PJKME';
            }
        }
        else{
            $request->data['refNo'] = $user->USCode;
        }

        $fileImage = FileAttach::WHERE('FARefNo',$request->data['refNo'])
            ->WHERE('FAFileType', 'US-FP')->first();

        if ($fileImage != null){
            $filePath = $fileImage->FAFilePath;
            if (\Illuminate\Support\Facades\Storage::disk('fileStorage')->exists($filePath)== true){
                $fileBase64 =  Storage::disk('fileStorage')->get($filePath) ;
            }
            $fileExt = strtolower($fileImage->FAFileExtension);
            $image1 = base64_encode(Storage::disk('fileStorage')->get($filePath));
        }
        else{
            $result = [
                'success' => false,
                'image1' => null,
                'image2' => null,
                'faceScore' => 0,
                'facePass' => false,
                'livenessPass' => false,
                'livenessScore' => false,
                'respondResult' => null
            ];

            return $result;
        }

        // $image2 = $imageData;
        $image2 = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);

        $helper = new Custom();
        $faceResult = $helper->faceCompareAI($image1,$image2);
        $faceResult2 = number_format($faceResult['faceScore'],2,'.','.' );
        $facePass = $faceResult['faceScore'] >= $FaceScoreRate;
        $livenessScore = number_format($faceResult['livenessScore'],2,'.','.' );
        $livenessPass = $faceResult['livenessScore'] >= $livenessScoreRate;

        $result = [
            'success' => true,
            'image1' =>$image1,
            'image2' =>$image2,
            'faceScore' => $faceResult2,
            'facePass' => $facePass,
            'livenessPass' => $livenessPass,
            'livenessScore' => $livenessScore,
            'respondResult' => $faceResult['result']
        ];

        $fileType = 'AP';
        $autoNumber = new AutoNumber();
        $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

        $folderPath = Carbon::now()->format('ymd');
        $timeStamp = Carbon::now()->format('Ymd').'T'.Carbon::now()->format('Hms');
        $originalName =  'liveness_'.$timeStamp.'jpg';
        $newFileExt = 'jpg';
        $newFileName = strval($generateRandomSHA256);

        $fileContent = base64_decode($image2);

        Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

        $fileAttach = FileAttach::where('FARefNo',$user->USCode)->where('FAFileType',$fileType)->where('FAActive',1)->first();
        if ($fileAttach != null){
            $fileAttach->FAActive   = 0;
            $fileAttach->Save();
        }

        $fileAttach = new FileAttach();
        $fileAttach->FAFileType 	    = $fileType;
        $fileAttach->FA_USCode     	    = $user->USCode;
        $fileAttach->FARefNo     	    = $user->USCode;
        $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
        $fileAttach->FAFileName 	    = $newFileName;
        $fileAttach->FAOriginalName 	= $originalName;
        $fileAttach->FAFileExtension    = strtolower($newFileExt);
        $fileAttach->FACB 		        = $user->USCode;
        $fileAttach->FAMB 			    = $user->USCode;
        $fileAttach->save();

        $autoNumber = new AutoNumber();
        $fclNo = $autoNumber->generateFCLNo();

        if ($result['faceScore']!=null){
            $faceCompareLog = new FaceCompareLog();
            $faceCompareLog->FCLNo = $fclNo;
            $faceCompareLog->FCLRefNo = $request->data['APNo'];
            $faceCompareLog->FCLRefNo = $request->data['APNo'];
            $faceCompareLog->FCLRefType = 'AP';
            $faceCompareLog->FCL_FAID1 	= $fileImage->FAID;
            $faceCompareLog->FCL_FAID2 	= $fileAttach->FAID;
            $faceCompareLog->FCLFaceScore= $faceResult2 ;
            $faceCompareLog->FCLResult 	= json_encode($faceResult['result']);
            $faceCompareLog->FCLCB	    = $user->USCode;
            $faceCompareLog->FCLCD	    = carbon::now();
            $faceCompareLog->save();
        }

        $approval = Approval::where('APNo', $request->data['APNo'])->first();
        $approval->APFaceScore = $faceResult2;
        $approval->APMB = $user->USCode;
        $approval->save();

        return $result;

//        if ($faceResult['faceScore'] >= $FaceScoreRate) {
//            return response()->json(['message' => 'Face recognition successful ' . $faceResult['faceScore']]);
//        } else {
//
//            return response()->json(['message' => 'Face recognition failed ' . $faceResult['faceScore']]);
//        }
    }

    public function faceRegisterChecking($fRNo)
    {

        $user = Auth::user();

        $webSetting = WebSetting::first();

        $FaceScoreRate = $webSetting->FaceScoreRate *100;

        $fileAttach = FileAttach::WHERE('FARefNo',$fRNo)
            ->WHERE('FAFileType', 'US-FP')->first();

        $fileAttach2 = FileAttach::WHERE('FARefNo',$fRNo)
            ->WHERE('FAFileType', 'US-ID')->first();

        if ($fileAttach != null && $fileAttach2 != null){
            $filePath = $fileAttach->FAFilePath;
            if (\Illuminate\Support\Facades\Storage::disk('fileStorage')->exists($filePath)== true){
                $fileBase64 =  Storage::disk('fileStorage')->get($filePath) ;
            }
            $fileExt = strtolower($fileAttach->FAFileExtension);
            $image1 = base64_encode(Storage::disk('fileStorage')->get($filePath));

            $filePath2 = $fileAttach2->FAFilePath;
            if (\Illuminate\Support\Facades\Storage::disk('fileStorage')->exists($filePath2)== true){
                $fileBase642 =  Storage::disk('fileStorage')->get($filePath2) ;
            }

            $fileExt2 = strtolower($fileAttach->FAFileExtension);
            $image2 = base64_encode(Storage::disk('fileStorage')->get($filePath2));
        }
        else{
            $result = [
                'success' => false,
                'image1' => null,
                'image2' => null,
                'faceScore' => 0,
                'facePass' => false,
                'respondResult' => null
            ];

            return $result;
        }

        $helper = new Custom();
        $faceResult = $helper->faceCompareAI($image1,$image2);
        $faceResult2 = number_format($faceResult['faceScore'],2,'.','.' );
        $facePass = $faceResult['faceScore'] >= $FaceScoreRate;

        $result = [
            'success' => true,
            'image1' =>$image1,
            'image2' =>$image2,
            'faceScore' => $faceResult2,
            'facePass' => $facePass,
            'respondResult' => $faceResult['result']
        ];

        $autoNumber = new AutoNumber();
        $fclNo = $autoNumber->generateFCLNo();

        if ($result['faceScore']!=null){
            $faceCompareLog = new FaceCompareLog();
            $faceCompareLog->FCLNo = $fclNo;
            $faceCompareLog->FCLRefNo = $fRNo;
            $faceCompareLog->FCLRefType = 'FR';
            $faceCompareLog->FCL_FAID1 	= $fileAttach2->FAID;
            $faceCompareLog->FCL_FAID2 	= $fileAttach->FAID;
            $faceCompareLog->FCLFaceScore= $faceResult2 ;
            $faceCompareLog->FCLResult 	= json_encode($faceResult['result']);
            $faceCompareLog->FCLCB	    = $user->USCode;
            $faceCompareLog->FCLCD	    = carbon::now();
            $faceCompareLog->save();
        }

        return $result;
    }
}
