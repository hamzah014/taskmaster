<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Helper\Custom;
use App\Models\AutoNumber;
use App\Models\WebSetting;
use App\Models\FaceCompareLog;
use App\Models\Register;
use App\Models\Certificate;
use App\Models\SignDocument;
use App\Models\FileAttach;
use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
Use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Project;

class SignController extends Controller
{

    public function signPdfSoftCert(Request $request){

        $base64Pdf      = $request->base64Pdf;
        $id      = $request->id;
        $base64Stamp    = $request->base64Stamp;
        $pageToOverlay  = $request->pageToOverlay;
        $xPosition      = $request->xPosition;
        $yPosition      = $request->yPosition;
        $width          = $request->width;
        $height         = $request->height;
        $certificateBase64 = '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';
        $certificatePassword = '12345678';

        $outputPdfBase64 = $this->addDigitalSignature($id, $xPosition, $yPosition, $pageToOverlay, $width, $height, $base64Pdf, $base64Stamp, $certificateBase64, $certificatePassword);

        return response()->json([
            'data' => $outputPdfBase64
        ]);
    }
    
    public function multipleSignPdfSoftCert(Request $request){

		$messages = [
			'base64Pdf.required'					=> 'base64Pdf is required',
			'signatures.required'					=> 'signatures is required',
			'signatures.*.id.required'	            => 'id is required',
			'signatures.*.stampBase64.required'	    => 'stampBase64 is required',
			'signatures.*.pageToOverlay.required' 	=> 'pageToOverlay is required',
			'signatures.*.xPosition.required' 	    => 'xPosition is required',
			'signatures.*.yPosition.required' 	    => 'yPosition is required',
			'signatures.*.width.required' 	        => 'width is required',
			'signatures.*.height.required' 	        => 'height is required',
		];

        $validation = [
            'base64Pdf' 			    => 'required',
            'signatures' 	            => 'required',
            'signatures.*.stampBase64'  => 'required',
            'signatures.*.pageToOverlay'=> 'required',
            'signatures.*.xPosition'    => 'required',
            'signatures.*.yPosition'    => 'required',
            'signatures.*.width' 	    => 'required',
            'signatures.*.height' 	    => 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $pdfBase64  = $request->base64Pdf;
			
		$signatures=[];
		
		$signatures = $request->signatures;		
		if ($signatures == null){
			return response()->json([
				'status' => 'failed',
				'message' => trans('message.param.application.required'),
			]);
		}

        foreach($signatures as $signature){

            $timestamp = Carbon::now()->getPreciseTimestamp(3);
                
            // Convert PDF from base64 to a temporary file
            $pdfData = base64_decode($pdfBase64);
            $tempPdfPath = storage_path('app/temp/temp_pdf_'.$timestamp.'.pdf');
            file_put_contents($tempPdfPath, $pdfData);
        
            // Load PDF with FPDI
            $pdf = new FPDI();
            $pageCount = $pdf->setSourceFile($tempPdfPath);
            // Add a page and import existing page to the new one
            $pageWidth = $pdf->GetPageWidth();  // Width of Current Page
            $pageHeight = $pdf->GetPageHeight(); // Height of Current Page
            

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {

                $templateId = $pdf->importPage(1);
                $pdf->useTemplate($templateId);
                if ($pageHeight>=$pageWidth){
                    $pdf->AddPage('L');
                }else{
                    $pdf->AddPage('P');
                }

                $id             = $signature['id'];
                $stampBase64    = $signature['stampBase64'];
                $pageToOverlay  = $signature['pageToOverlay'];
                $xPosition      = $signature['xPosition'];
                $yPosition      = $signature['yPosition'];
                $width          = $signature['width'];
                $height         = $signature['height'];
        
                // Add signature field on each page
                $certificateBase64 = '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';
                $certificatePassword = '12345678';
            
                // Convert photo from base64 to a temporary file
                $stampData = base64_decode($stampBase64);
                $tempStampPath = storage_path('app/temp/temp_stamp_'.$id.$timestamp.'.png');
                file_put_contents($tempStampPath, $stampData);
            
                // Convert certificate from base64 to a temporary file
                $certificateData = base64_decode($certificateBase64);
                $tempCertificatePath = storage_path('app/temp/temp_certificate_'.$id.$timestamp.'.p12');
                file_put_contents($tempCertificatePath, $certificateData);

                if ($width == 0 || $height == 0){
                    // Decode the base64 image
                    $image = Image::make(base64_decode($stampBase64));
                    // Get the width and height
                    $width = $image->getWidth() * 0.2645833333;
                    $height = $image->getHeight() * 0.2645833333;
                }

                    // Set certificate for digital signature
                $info = array(
                        'Name' => 'Vista Kencana Sdn Bhd',
                        'Location' => 'Kuala Lumpur',
                        'Reason' => 'Pendaftar IDVET',
                        'ContactInfo' => 'https://vistakencana.com.my',
                        );
                // set document signature
                $pemData = $this->convertPfxToPem($tempCertificatePath, $certificatePassword);
                $keyData = $this->convertPfxToKey($tempCertificatePath, $certificatePassword);
        
                // Add a digital signature field
                $pdf->setSignature($pemData, $keyData,  $certificatePassword,'', 2, $info,'A');
                // define active area for signature appearance
                $pdf->setSignatureAppearance($xPosition, $yPosition, $width, $height, $pageNumber, $id.$timestamp);
                // Add photo (signature) to the page
                $pdf->Image($tempStampPath, $xPosition, $yPosition, $width, $height, 'PNG');

                $pdf->setXY($xPosition, $yPosition+$height+5);
                // print a line of text
                $text = 'This is a digitally signed by Vista Kencana';
                $pdf->writeHTML($text, true, 0, true, 0);

                // Delete temporary files
                unlink($tempStampPath);
                unlink($tempCertificatePath);
            }

            // Output signed PDF as base64
            ob_start();
            $pdf->Output();
            $pdfBase64 = base64_encode(ob_get_clean());

            // Delete temporary files
           // unlink($tempPdfPath);
                        
        }
    

        return response()->json([
            'data' => $pdfBase64
        ]);
    }

    public function addDigitalSignature($id, $xPosition, $yPosition, $pageToOverlay, $width, $height, $pdfBase64, $photoBase64, $certificateBase64, $certificatePassword)
    {
        $timestamp = Carbon::now()->getPreciseTimestamp(3);
        
        // Convert PDF from base64 to a temporary file
        $pdfData = base64_decode($pdfBase64);
        $tempPdfPath = storage_path('app/temp/temp_pdf_'.$timestamp.'.pdf');
        file_put_contents($tempPdfPath, $pdfData);

        // Convert photo from base64 to a temporary file
        $photoData = base64_decode($photoBase64);
        $tempPhotoPath = storage_path('app/temp/temp_photo_'.$timestamp.'.png');
        file_put_contents($tempPhotoPath, $photoData);

        // Convert certificate from base64 to a temporary file
        $certificateData = base64_decode($certificateBase64);
        $tempCertificatePath = storage_path('app/temp/temp_certificate_'.$timestamp.'.p12');
        file_put_contents($tempCertificatePath, $certificateData);

        // Load PDF with FPDI
        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($tempPdfPath);

        // Add a page and import existing page to the new one
        $pageWidth = $pdf->GetPageWidth();  // Width of Current Page
        $pageHeight = $pdf->GetPageHeight(); // Height of Current Page

        // Set certificate for digital signature
        $info = array(
                'Name' => 'Vista Kencana Sdn Bhd',
                'Location' => 'Kuala Lumpur',
                'Reason' => 'Pendaftar IDVET',
                'ContactInfo' => 'https://vistakencana.com.my',
                );
        // set document signature
        $pemData = $this->convertPfxToPem($tempCertificatePath, $certificatePassword);
        $keyData = $this->convertPfxToKey($tempCertificatePath, $certificatePassword);

        if ($width == 0 || $height == 0){
            // Decode the base64 image
            $image = Image::make(base64_decode($photoBase64));
            // Get the width and height
            $width = $image->getWidth() * 0.2645833333;
            $height = $image->getHeight() * 0.2645833333;
        }

        // Add signature field on each page
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage(1);
            $pdf->useTemplate($templateId);
            if ($pageHeight>=$pageWidth){
                $pdf->AddPage('L');
            }else{
                $pdf->AddPage('P');
            }
            
            $pdf->setXY($xPosition, $yPosition-10);
            // print a line of text
            $text = 'This is a <b color="#FF0000">digitally signed document</b> using the default (example) <b>tcpdf.crt</b> certificate.<br /><a href="http://www.tcpdf.org">www.tcpdf.org</a>';
            $pdf->writeHTML($text, true, 0, true, 0);
    
            // Add a digital signature field
            $pdf->setSignature($pemData, $keyData,  $certificatePassword,'', 2, $info,'A');
            // define active area for signature appearance
            $pdf->setSignatureAppearance($xPosition, $yPosition, $width, $height, $pageNumber, $id);
            // Add photo (signature) to the page
            $pdf->Image($tempPhotoPath, $xPosition, $yPosition, $width, $height, 'PNG');
        }
        // Output signed PDF as base64
        ob_start();
        $pdf->Output();
        $outputPdfBase64 = base64_encode(ob_get_clean());

        // Delete temporary files
        unlink($tempPdfPath);
        unlink($tempPhotoPath);
        unlink($tempCertificatePath);

		$data = array(
            "success"=> true,
            "message"=> "PDF digitally signed successfully.",
            'pageWidth'   => $pageWidth,
            'pageHeight'   => $pageHeight,
            'base64SignedPdf'   => $outputPdfBase64,
        );

       return $data;
       
    }

    public function convertPfxToPem($pfxFilePath, $pfxPassword)
    {
        $timestamp = Carbon::now()->getPreciseTimestamp(3);

        // Read the contents of the PFX file
        $pfxData = file_get_contents($pfxFilePath);

        // Temporarily save the PFX file
        $tempPfxPath = storage_path('app/temp/temp_cert'.$timestamp.'.pfx');
        file_put_contents($tempPfxPath, $pfxData);

        // Execute OpenSSL command to convert PFX to PEM
        $output = [];
        $command = "openssl pkcs12 -in {$tempPfxPath} -out {$tempPfxPath}.pem -nodes -password pass:{$pfxPassword}";
        exec($command, $output);

        // Check if the conversion was successful
        if (!file_exists("{$tempPfxPath}.pem")) {
            throw new \Exception('Failed to convert PFX to PEM.');
        }

        // Read the contents of the PEM file
        $pemData = file_get_contents("{$tempPfxPath}.pem");

        // Delete temporary files
        unlink($tempPfxPath);
        unlink("{$tempPfxPath}.pem");

        return $pemData;
    }

    public function convertPfxToKey($pfxFilePath, $pfxPassword)
    {
        $timestamp = Carbon::now()->getPreciseTimestamp(3);

        // Read the contents of the PFX file
        $pfxData = file_get_contents($pfxFilePath);

        // Temporarily save the PFX file
        $tempPfxPath = storage_path('app/temp/temp_cert'.$timestamp.'.pfx');
        file_put_contents($tempPfxPath, $pfxData);

        // Execute OpenSSL command to convert PFX to PEM
        $output = [];
        $command = "openssl pkcs12 -in {$tempPfxPath} -nocerts -out {$tempPfxPath}.key -nodes -password pass:{$pfxPassword}";
        exec($command, $output);

        // Check if the conversion was successful
        if (!file_exists("{$tempPfxPath}.key")) {
            throw new \Exception('Failed to convert PFX to Private Key.');
        }

        // Read the contents of the PEM file
        $keyData = file_get_contents("{$tempPfxPath}.key");

        // Delete temporary files
        unlink($tempPfxPath);
        unlink("{$tempPfxPath}.key");

        return $keyData;
    }

    public function registerCert(Request $request){

		$messages = [
			'id.required'	                => 'id is required',
			'name.required'	                => 'name is required',
			'type.required'	                => 'type is required',
			'type.in'	                    => 'type is M/F',
			'nationality.required_if'	    => 'nationality is required',
			'base64IdentityDoc.required'    => 'base64IdentityDoc is required',
			'base64FacePhoto.required'	    => 'base64FacePhoto is required',
		];

        $validation = [
            'id'                => 'required',
            'name'              => 'required',
            'type'              => 'required|string|in:F,M',
            'nationality'       => 'required_if:type,F',
            'email'             => 'nullable',
            'phoneno'           => 'nullable',
            'base64IdentityDoc' => 'required',
            'base64FacePhoto'   => 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $project = Project::where('PJActive',1)->where('PJApiKeyClient',$request->header('api-key'))->first();
        if ($project == null){
            return response()->json([
                'error' => '1',
                'message' => 'Project not found!'
            ], 400);
        }

        if ($request->type == 'F'){
            if ($request->nationality == 'MYS'){
                return response()->json([
                    'error' => '1',
                    'message' => 'Nationality is invalid!'
                ], 400); 
            }
            $nationality = $request->nationality;
        }else{
            $nationality = '';
        }

        $register =  Register::where('RG_PJCode', $project->PJCode)->where('RGIDNo',$request->id)->where('RGNationality',$nationality)->first() ;
        if ($register != null){
            $certExists =  Certificate::where('CE_RGNo',$register->RGNo)->where('CE_CSCode','!=','REVOKE')->first() ;
            if ($certExists != null){
                return response()->json([
                    'error' => '1',
                    'message' => 'User already register certificate!'
                ], 400);
            }
        }


        $base64IdentityDoc = $request->base64IdentityDoc;
        $base64FacePhoto = $request->base64FacePhoto;

        try {
            DB::beginTransaction();

            $helper = new Custom();
            $autoNumber = new AutoNumber();
            $regNo = $autoNumber->generateRegNo();

            $register = new Register();
            $register->RGNo          = $regNo;
            $register->RGIDNo        = $request->id;
            $register->RGName        = $request->name;
            $register->RGNationality = $request->nationality;
            $register->RGType        = $request->type;
            $register->RGEmail       = $request->email;
            $register->RGPhoneNo     = $request->phoneNo;
            $register->RG_PJCode     = $project->PJCode;
            $register->save();

            $folderPath = Carbon::now()->format('ymd');
            $newFileExt = 'jpg';

            //IDENTITY DOCUMENT
            $decodedIdentityDoc = base64_decode($base64IdentityDoc);
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $originalName =  'identity_doc_'.Carbon::now()->format('ymdHis');

            Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedIdentityDoc);

            $fileAttach = new FileAttach();
            $fileAttach->FAFileType 	    = 'RG-ID';
            $fileAttach->FARefNo     	    = $regNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FACB 		        = 'SYSTEM';
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();

            //APPLICANT PHOTO
            $decodedFacePhoto = base64_decode($base64FacePhoto);
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $originalName =  'applicant_photo_'.Carbon::now()->format('ymdHis');

            Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedFacePhoto);

            //FileAttach::where('FARefNo',$refNo)->where('FAFileType',$fileType)->update(['FAActive' => 0]);
            $fileAttach = new FileAttach();
            $fileAttach->FAFileType 	    = 'RG-FP';
            $fileAttach->FARefNo     	    = $regNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FACB 		        = 'SYSTEM';
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();

            $fileAttachIdentityDoc  =FileAttach::where('FAFileType','RG-ID')->where('FARefNo',$regNo)->first();
            $identityDocID = $fileAttachIdentityDoc->FAID;
    
            $fileAttachFacePhoto  =FileAttach::where('FAFileType','RG-FP')->where('FARefNo',$regNo)->first();
            $facePhotoID = $fileAttachFacePhoto->FAID;
            

            if ($request->type == 'F'){
                $certID = $request->nationality.$request->id;
            }else{
                $certID = $request->id;
            }
            
            $hashed_random_password = Hash::make($certID);
            $certNo = $autoNumber->generateCertNo();

            $cert = new Certificate();
            $cert->CENo         = $certNo;
            $cert->CEIDNo       = $certID;
            $cert->CEName       = $request->name;
            $cert->CEPassword   = $hashed_random_password;
            $cert->CEStartDate  = carbon::today();
            $cert->CEEndDate    = carbon::today()->addYear()->addDay(-1);
            $cert->CE_CSCode    = 'DRAFT';
            $cert->CE_PJCode    = $project->PJCode;
            $cert->CE_RGNo      = $register->RGNo;
            $cert->save();
            
            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Certificate has been unsuccessful-'.$e->getMessage()
            ], 400);
        }
            
        $data = $helper->registerCert($project->PJCode, $certID,$request->name, $hashed_random_password);
            
        if ($data['error'] != ''){
            DB::rollback();
            return response()->json([
                'error' => $data['error'],
                'message' => $data['message']
            ], 400);
        }

        $cert->CE_CSCode    = 'ISSUE';
        $cert->CEFileBase64 = $data['base64Cert'];
        $cert->save();

        return response()->json([
            'success' => '1',
            'data' => [
                'regNo'     =>$register->RGNo ,
                'certNo'    =>$cert->CENo ,
                'startDate' =>carbon::parse($cert->CEStartDate)->format('Y-m-d') ,
                'endDate'   =>carbon::parse($cert->CEEndDate)->format('Y-m-d'),
            ]
        ]);

 /*
            $webSetting = WebSetting::first();
            $faceScoreRate = $webSetting->FaceScoreRate * 100;
            
            $faceResult = $helper->faceCompareAI($base64IdentityDoc,$base64FacePhoto);
    
            if ($faceResult==null){
                DB::rollback();
                return response()->json([
                    'error' => '1',
                    'message' => 'Document verification failed.',
                ], 400);
            }
        
            $faceScore = number_format($faceResult['faceScore'],2,'.','.' );
            $facePass = $faceResult['faceScore'] >= $faceScoreRate;

            $faceCompareLog = new FaceCompareLog();
            $faceCompareLog->FCLRefNo 	= $regNo; 
            $faceCompareLog->FCL_FAID1 	= $identityDocID; //Identity Doc
            $faceCompareLog->FCL_FAID2 	= $facePhotoID; //Face
            $faceCompareLog->FCLFaceScore= $faceScore ;
            $faceCompareLog->FCLPassRate= $faceScoreRate ;
            $faceCompareLog->FCLFacePass= $facePass ;
            $faceCompareLog->FCLResult 	= json_encode($faceResult['result']);
            $faceCompareLog->FCLCB	    = 'SYSTEM';
            $faceCompareLog->FCLCD	    = carbon::now();
            $faceCompareLog->save();
            
            if ($faceScore >= $faceScoreRate ){	

                if ($request->type == 'F'){
                    $certID = $request->nationality.$request->id;
                }else{
                    $certID = $request->id;
                }
                
                $hashed_random_password = Hash::make($certID);
                $certNo = $autoNumber->generateCertNo();

                $cert = new Certificate();
                $cert->CENo         = $certNo;
                $cert->CEIDNo       = $certID;
                $cert->CEName       = $request->name;
                $cert->CEPassword   = $hashed_random_password;
                $cert->CEStartDate  = carbon::today();
                $cert->CEEndDate    = carbon::today()->addYear()->addDay(-1);
                $cert->CE_CSCode    = 'DRAFT';
                $cert->CE_PJCode    = $project->PJCode;
                $cert->CE_RGNo      = $register->RGNo;
                $cert->save();
            }

            DB::commit();

            if ($faceScore >= $faceScoreRate ){	

                $data = $helper->registerCert($project->PJCode, $certID,$request->name, $hashed_random_password);
                
                if ($data['error'] != ''){
                    DB::rollback();
                    return response()->json([
                        'error' => $data['error'],
                        'message' => $data['message']
                    ], 400);
                }

                $cert->CE_CSCode    = 'ISSUE';
                $cert->CEFileBase64 = $data['base64Cert'];
                $cert->save();

                return response()->json([
                    'success' => '1',
                    'data' => [
                        'regNo'     =>$register->RGNo ,
                        'certNo'    =>$cert->CENo ,
                        'startDate' =>carbon::parse($cert->CEStartDate)->format('Y-m-d') ,
                        'endDate'   =>carbon::parse($cert->CEEndDate)->format('Y-m-d'),
                    ]
                ]);

            }else{
                return response()->json([
                    'error' => '1',
                    'message' => 'Image verification failed.',
                    'data' => [
                        'refNo'  =>$register->RGNo ,
                    ]
                ], 400);
            }
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Certificate has been unsuccessful-'.$e->getMessage()
            ], 400);
        }
*/
    }

    
    public function renewCert(Request $request){

		$messages = [
			'certNo.required'	=> 'certNo is required',
		];

        $validation = [
            'certNo'     => 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $project = Project::where('PJActive',1)->where('PJApiKeyClient',$request->header('api-key'))->first();
        if ($project == null){
            return response()->json([
                'error' => '1',
                'message' => 'Project not found!'
            ], 400);
        }

        $cert =  Certificate::where('CE_PJCode', $project->PJCode)->where('CENo',$request->certNo)->first() ;
        if ($cert == null){
            return response()->json([
                'error' => '1',
                'message' => 'Cerificate does not exists!'
            ], 400);
        }

        if ($cert->CE_CSCode == 'REVOKE' ){
            return response()->json([
                'error' => '1',
                'message' => 'Cerificate has been revoked!'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $helper = new Custom();
            $data = $helper->revokeCert($project->PJCode, $cert->CEIDNo,$cert->CEPassword);

            if ($data['error'] != ''){
                return response()->json([
                    'error' => $data['error'],
                    'message' => $data['message']
                ], 400);
            }
            
            $cert->CE_CSCode    = 'REVOKE';
            $cert->CERevokeDate = carbon::now();
            $cert->save();

            DB::commit();
            
            return response()->json([
                'success' => '1',
                'message' => 'Certificate has been revoked successful.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Certificate has been revoked unsuccessful-'.$e->getMessage()
            ], 400);
        }
    }
    
    
    public function revokeCert(Request $request){

		$messages = [
			'certNo.required'	=> 'certNo is required',
		];

        $validation = [
            'certNo'     => 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $project = Project::where('PJActive',1)->where('PJApiKeyClient',$request->header('api-key'))->first();
        if ($project == null){
            return response()->json([
                'error' => '1',
                'message' => 'Project not found!'
            ], 400);
        }

        $cert =  Certificate::where('CE_PJCode', $project->PJCode)->where('CENo',$request->certNo)->first() ;
        if ($cert == null){
            return response()->json([
                'error' => '1',
                'message' => 'Cerificate does not exists!'
            ], 400);
        }

        if ($cert->CE_CSCode == 'REVOKE' ){
            return response()->json([
                'error' => '1',
                'message' => 'Cerificate has been revoked!'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $helper = new Custom();
            $data = $helper->revokeCert($project->PJCode, $cert->CEIDNo,$cert->CEPassword);

            if ($data['error'] != ''){
                return response()->json([
                    'error' => $data['error'],
                    'message' => $data['message']
                ], 400);
            }
            
            $cert->CE_CSCode    = 'REVOKE';
            $cert->CERevokeDate = carbon::now();
            $cert->save();

            DB::commit();
            
            return response()->json([
                'success' => '1',
                'message' => 'Certificate has been revoked successful.'
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Certificate has been revoked unsuccessful-'.$e->getMessage()
            ], 400);
        }
    }
    
    public function signPdf(Request $request){
        
		$messages = [
			'certNo.required'	        => 'certNo is required',
			'base64Pdf.required'	    => 'base64Pdf is required',
			'base64Stamp.required'	    => 'base64Stamp is required',
			'page_no.required' 	        => 'pageToOverlay is required',
			'left_lower_x.required' 	=> 'left_lower_x is required',
			'left_lower_y.required' 	=> 'left_lower_y is required',
			'right_upper_x.required' 	=> 'right_upper_x is required',
			'right_upper_y.required' 	=> 'right_upper_y is required',
		];

        $validation = [
            'certNo'        => 'required',
            'base64Pdf'     => 'required',
            'base64Stamp'   => 'required',
            'page_no'       => 'required',
            'left_lower_x'  => 'required',
            'left_lower_y'  => 'required',
            'right_upper_x' => 'required',
            'right_upper_y' => 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $project = Project::where('PJActive',1)->where('PJApiKeyClient',$request->header('api-key'))->first();
        if ($project == null){
            return response()->json([
                'error' => '1',
                'message' => 'Project is invalid!'
            ], 400);
        }

        $cert =  Certificate::where('CE_PJCode', $project->PJCode)->where('CENo',$request->certNo)->whereDate('CEEndDate','>=',carbon::now())->where('CERevokeInd',0)->first() ;
        if ($cert == null){
            return response()->json([
                'error' => '1',
                'message' => 'Cerificate is invalid!'
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            $id             = $cert->CEIDNo;
            $password       = $cert->CEPassword;
            $base64Pdf      = $request->base64Pdf;
            $base64Stamp    = $request->base64Stamp;
            $page_no        = $request->page_no;
            $left_lower_x   = $request->left_lower_x;
            $left_lower_y   = $request->left_lower_y;
            $right_upper_x  = $request->right_upper_x;
            $right_upper_y  = $request->right_upper_y;

            $helper = new Custom();
            $data = $helper->signPdf($project->PJCode, $id, $password, $base64Pdf, $base64Stamp, $page_no, $left_lower_x, $left_lower_y, $right_upper_x, $right_upper_y);

            if ($data['error'] != ''){
                DB::rollback();
                return response()->json([
                    'error' => $data['error'],
                    'message' => $data['message']
                ], 400);
            }
            
            $base64SignedPdf= $data['base64SignedPdf'];
            if ($base64SignedPdf == ''){
                DB::rollback();
                return response()->json([
                    'error' => 1,
                    'message' => 'Signed Pdf does not exists'
                ], 400);
            }
			
            $autoNumber = new AutoNumber();
            $signDocNo = $autoNumber->generateSignDocNo();

            $signDoc = new SignDocument();
            $signDoc->SDNo          = $signDocNo;
            $signDoc->SD_CENo       = $cert->CENo;
            $signDoc->SDPayload     = json_encode($request->except(['base64Pdf','base64Stamp']));
            $signDoc->SDCD          = carbon::now();
            $signDoc->save();

            $folderPath = Carbon::now()->format('ymd');
            $newFileExt = 'pdf';

            //ORIGINAL PDF FILE
            $decodedPdf = base64_decode($base64Pdf);
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $originalName =  'pdf_'.Carbon::now()->format('ymdHis');

            Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedPdf);

            $fileAttach = new FileAttach();
            $fileAttach->FAFileType 	    = 'SD-OF';
            $fileAttach->FARefNo     	    = $signDocNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FACB 		        = 'SYSTEM';
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();

            //STAMP IMAGE
            $decodedStamp = base64_decode($base64Stamp);
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $originalName =  'stamping_'.Carbon::now()->format('ymdHis');

            Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedStamp);

            $fileAttach = new FileAttach();
            $fileAttach->FAFileType 	    = 'SD-SI';
            $fileAttach->FARefNo     	    = $signDocNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FACB 		        = 'SYSTEM';
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();

            //SIGNED PDF FILE
            $decodedSignedPdf = base64_decode($base64SignedPdf);
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $originalName =  'signdoc_'.Carbon::now()->format('ymdHis');

            Storage::disk('fileStorage')->put($folderPath . '/' . $newFileName, $decodedSignedPdf);

            //FileAttach::where('FARefNo',$refNo)->where('FAFileType',$fileType)->update(['FAActive' => 0]);
            $fileAttach = new FileAttach();
            $fileAttach->FAFileType 	    = 'SD-SF';
            $fileAttach->FARefNo     	    = $signDocNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FACB 		        = 'SYSTEM';
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();

   
            DB::commit();

            $data = [
                'refNo'             =>$signDoc->SDNo ,
                'base64SignedPdf'   =>$base64SignedPdf ,
            ];
            
            return response()->json([
                'success' => '1',
                'data' => $data
            ]);

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Signed PDF has been unsuccessful-'.$e->getMessage()
            ], 400);
        }
 
        return response()->json([
            'success' => '1',
            'data' => $data
        ]);
    }
    
    public function multipleSignPdf(Request $request){
        
		$messages = [
			'base64Pdf.required'					=> 'base64Pdf is required',
			'signatures.required'					=> 'signatures is required',
			'signatures.*.id.required'	            => 'id is required',
			'signatures.*.base64Stamp.required'	    => 'base64Stamp is required',
			'signatures.*.page_no.required' 	    => 'page_no is required',
			'signatures.*.left_lower_x.required'    => 'left_lower_x is required',
			'signatures.*.left_lower_y.required'    => 'left_lower_y is required',
			'signatures.*.right_upper_x.required'   => 'right_upper_x is required',
			'signatures.*.right_upper_y.required'   => 'right_upper_y is required',
		];

        $validation = [
            'base64Pdf' 			    => 'required',
            'signatures' 	            => 'required',
            'signatures.*.id'           => 'required',
            'signatures.*.base64Stamp'  => 'required',
            'signatures.*.page_no'      => 'required',
            'signatures.*.left_lower_x' => 'required',
            'signatures.*.left_lower_y' => 'required',
            'signatures.*.right_upper_x'=> 'required',
            'signatures.*.right_upper_y'=> 'required',
        ];

        $validator = Validator::make($request->all(), $validation, $messages);
		
		if ($validator->fails()) {
			return response()->json([
				'status'  => 'failed',
				'message' => $validator->messages()->First()
			]);
		}

        $pdfBase64  = $request->base64Pdf;
			
		$signatures=[];
		
		$signatures = $request->signatures;		
		if ($signatures == null){
			return response()->json([
				'status' => 'failed',
				'message' => 'At least one signature is required!',
			]);
		}

        $base64Pdf  = $request->base64Pdf;

        foreach($signatures as $signature){
            
            $id             = $signature['id'];
            $left_lower_x   = $signature['left_lower_x'];
            $left_lower_y   = $signature['left_lower_y'];
            $right_upper_x  = $signature['right_upper_x'];
            $right_upper_y  = $signature['right_upper_y'];
            $base64Stamp    = $signature['base64Stamp'];
            $page_no        = $signature['page_no'];

            try {

                $httpClient = new \GuzzleHttp\Client([
                    'headers' => [
                        'Content-Type' 	=> 'application/json',
                        'Accept' 		=> 'application/json',
                    ],
                    'body' => json_encode(
                        [
                            'user_id' 	    => $id,
                            'password' 	    => '12345678',
                            'location' 	    => 'Kuala Lumpur',
                            'reason' 	    => 'Digital Signature',
                            'docpdf'        => $base64Pdf,
                            'esignature'    => $base64Stamp,
                            'left_lower_x'  => $left_lower_x,
                            'left_lower_y'  => $left_lower_y,
                            'right_upper_x' => $right_upper_x,
                            'right_upper_y' => $right_upper_y,
                            'page_no'       => $page_no,
                            'key' 		    => '4moC4zFGwgMpJJ4I4aFWc1Rey2ji9rMnVtdb7TtS',
                        ]
                    )
                ]);
                $url = config::get('app.ca_url');
                $httpRequest = $httpClient->post($url.'/api/signer/signpdf');
                $responseData = json_decode($httpRequest->getBody()->getContents());

                if($responseData->status_code=="0"){
                    $base64Pdf = $responseData->data;
                }

            } catch(\GuzzleHttp\Exception\ConnectException $e){
                log::error($e);
                /*
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Connection Error'
                ]);*/
            }catch(\GuzzleHttp\Exception\RequestException $message){
                log::error($message);
                /*
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Connection Error'
                ]);*/
            }
        
        }

        $data = [
            'signedPdfBase64'=>$base64Pdf ?? ''
        ];
 
        return response()->json([
            'success' => '1',
            'data' => $data
        ]);
    }
}


