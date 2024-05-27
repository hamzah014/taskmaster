<?php

namespace App\Http\Controllers;

use App\Models\ProjectClaimDet;
use App\Models\TenderDetail;
use App\Models\TenderProposalDetail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\FileAttach;
use App\Helper\Custom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Auth;
use Image;
use Imagick;
use App\Models\AutoNumber;
use App\Models\LetterAcceptanceDet;
use App\Services\DropdownService;

use Illuminate\Support\Facades\File;

class FileController extends Controller
{

    public function index(){
        return view('welcome');
    }

    public function download($fileName){

		$newfileExt = '';
		$fileBase64 = null;

		$fileAttach = FileAttach::WHERE('FAGuidID',$fileName)->first();
		if ($fileAttach != null){
			$filePath = $fileAttach->FAFilePath;
			$fileExt = strtolower($fileAttach->FAFileExtension);
			$fileName = $fileAttach->FAOriginalName.'.'.$fileExt;
			if (Storage::disk('local')->exists($filePath)== true){
				//$fileBase64 =  Storage::disk('local')->get($filePath) ;
                return Storage::disk('local')->download($filePath, $fileName);
			}
			return null;

		}else{
			return null;
		}
    }

    public function viewFile($fileName){

		$fileExt = '';
		$fileBase64 = null;
        $fileOriginalName = '';

		$fileAttach = FileAttach::WHERE('FAGuidID',$fileName)->first();
		if ($fileAttach != null){
			$filePath = $fileAttach->FAFilePath;
            $fileOriginalName = $fileAttach->FAOriginalName;
			if (Storage::disk('local')->exists($filePath)== true){
				$fileBase64 =  Storage::disk('local')->get($filePath) ;
			}
			$fileExt = strtolower($fileAttach->FAFileExtension);

		}else{
			return null;
		}

		if ($fileBase64 == null){
				return null;
		}

		switch ($fileExt){
			case 'png':
				return response($fileBase64)->header('Content-Type', 'image/png')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
				break;

			case 'jpg':
				return response($fileBase64)->header('Content-Type', 'image/jpg')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
				break;

			case 'jpeg':
				return response($fileBase64)->header('Content-Type', 'image/jpeg')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
				break;

			case 'gif':
				return response($fileBase64)->header('Content-Type', 'image/gif')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
				break;

            case 'doc':
                return response($fileBase64)->header('Content-Type', 'application/msword')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
                break;

            case 'docx':
                return response($fileBase64)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')->header('Content-disposition', 'inline; filename='.$fileOriginalName);;
                break;

			case 'pdf':
				return response($fileBase64)->header('Content-Type', 'application/pdf')->header('Content-disposition', 'inline; filename='.$fileOriginalName);
				break;

			default:
				return null;
		}
    }


	public function getFile($fileGuid)
	{

        $newfileExt = '';

        $fileAttach = FileAttach::WHERE('FAGuidID',$fileGuid)->first();

        if ($fileAttach != null){
            // $filePath = $fileAttach->FAFilePath.'\\'. $fileAttach->FAFileName.'\\'. $fileAttach->FAFileExtension;

			$folderPath	 = $fileAttach->FAFilePath;
			$newFileName = $fileAttach->FAFileName;
			$newFileExt	 = $fileAttach->FAFileExtension;

            $filePath = $folderPath.'/'.$newFileName.'.'.$newFileExt;

			// Check if the file exists in storage
			if (Storage::disk('local')->exists($filePath)) {
				$fileContents = Storage::disk('local')->get($filePath);

				// You can return the file as a response with appropriate headers to view in the browser or force download
				return response($fileContents, 200)
					->header('Content-Type', Storage::mimeType($filePath));
			} else {

				abort(404, 'File not found');
			}

        }else{
            return null;
        }

	}

	public function getFileRefNo($refNo)
	{

        $newfileExt = '';

        $fileAttach = FileAttach::WHERE('FARefNo',$refNo)->first();

        if ($fileAttach != null){
            // $filePath = $fileAttach->FAFilePath.'\\'. $fileAttach->FAFileName.'\\'. $fileAttach->FAFileExtension;

			$folderPath	 = $fileAttach->FAFilePath;
			$newFileName = $fileAttach->FAFileName;
			$newFileExt	 = $fileAttach->FAFileExtension;

            $filePath = $folderPath.'/'.$newFileName.'.'.$newFileExt;

			// Check if the file exists in storage
			if (Storage::exists($filePath)) {
				$fileContents = Storage::get($filePath);

				// You can return the file as a response with appropriate headers to view in the browser or force download
				return response($fileContents, 200)
					->header('Content-Type', Storage::mimeType($filePath));
			} else {
				abort(404, 'File not found');
			}

        }else{
            return null;
        }
	}

    public function getFileRefNoFileType($refNo, $faFileType)
    {

        $newfileExt = '';

        $fileAttach = FileAttach::WHERE('FARefNo',$refNo)->where('FAFileType', $faFileType)->first();

        if ($fileAttach != null){
            // $filePath = $fileAttach->FAFilePath.'\\'. $fileAttach->FAFileName.'\\'. $fileAttach->FAFileExtension;

            $folderPath	 = $fileAttach->FAFilePath;
            $newFileName = $fileAttach->FAFileName;
            $newFileExt	 = $fileAttach->FAFileExtension;

            $filePath = $folderPath.'/'.$newFileName.'.'.$newFileExt;

            // Check if the file exists in storage
            if (Storage::exists($filePath)) {
                $fileContents = Storage::get($filePath);

                // You can return the file as a response with appropriate headers to view in the browser or force download
                return response($fileContents, 200)
                    ->header('Content-Type', Storage::mimeType($filePath));
            } else {
                abort(404, 'File not found');
            }

        }else{
            return null;
        }
    }

	public function submitUploadFile(Request $request){

		$messages = [
            'dok_upload.required' 	=> "Sila pilih fail untuk muat naik.",
		];

		$validation = [
			'dok_upload' 	=> 'required',
		];


        $request->validate($validation, $messages);

        try {
            DB::beginTransaction();
            $autoNumber = new AutoNumber();

			$dok_FAUSCode		    = $request->dok_FAUSCode;
			$dok_FARefNo		    = $request->dok_FARefNo;
			$dok_FAFileType		    = $request->dok_FAFileType;
			$dok_redirect		    = $request->dok_redirect;
            $dok_idRef              = $request->dok_idRef;
            $dok_Type               = $request->dok_Type;

            //FILE UPLOAD
            if ($request->hasFile('dok_upload')) {

                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

                $file = $request->file('dok_upload');

                $folderPath = Carbon::now()->format('ymd');
                $originalName =  $file->getClientOriginalName();
                $newFileExt = $file->getClientOriginalExtension();
                $newFileName = strval($generateRandomSHA256);

                $fileCode = $dok_FAFileType;

                $fileContent = file_get_contents($file->getRealPath());

                Storage::disk('local')->put($folderPath.'\\'.$newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode',$dok_FAUSCode)
                                        ->where('FARefNo',$dok_FARefNo)
                                        ->where('FAFileType',$fileCode)
                                        ->first();
                if ($fileAttach == null){
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB 		= Auth::user()->USCode;
                    $fileAttach->FAFileType 	= $fileCode;
                }else{

                    $filename   = $fileAttach->FAFileName;
                    $fileExt    = $fileAttach->FAFileExtension ;

                    $returnval = Storage::disk('local')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

                }
                $fileAttach->FARefNo     	    = $dok_FARefNo;
                $fileAttach->FA_USCode     	    = $dok_FAUSCode;
                $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
                $fileAttach->FAFileName 	    = $newFileName;
                $fileAttach->FAOriginalName 	= $originalName;
                $fileAttach->FAFileExtension    = strtolower($newFileExt);
                $fileAttach->FAMB 			    = Auth::user()->USCode;
                $fileAttach->save();

            }

            if($dok_Type == 'TPD-T'){
                $proposalDetail = TenderProposalDetail::where('TPDNo', $dok_idRef)->first();
                $proposalDetail->TPDCompleteTE = 1;
                $proposalDetail->save();
            }
            else if($dok_Type == 'TPD-F'){
                $proposalDetail = TenderProposalDetail::where('TPDNo', $dok_idRef)->first();
                $proposalDetail->TPDCompleteFE = 1;
                $proposalDetail->save();
            }
            else if($dok_Type == 'TDD-T' || $dok_Type == 'TDD-D'){
                $tenderDetail = TenderDetail::where('TDDNo', $dok_idRef)->first();
                $tenderDetail->TDDCompleteT = 1;
                $tenderDetail->save();
            }
            else if($dok_Type == 'TDD-F'){
                $tenderDetail = TenderDetail::where('TDDNo', $dok_idRef)->first();
                $tenderDetail->TDDCompleteF = 1;
                $tenderDetail->save();
            }
            else if($dok_Type == 'LAD-DD'){
                $letterAcceptDet = LetterAcceptanceDet::where('LADNo', $dok_idRef)->first();
                $letterAcceptDet->LADCompleteP = 1;
                $letterAcceptDet->save();
            }
            else if($dok_Type == 'LAD-LA'){
                $letterAcceptDet = LetterAcceptanceDet::where('LADNo', $dok_idRef)->first();
                $letterAcceptDet->LADCompleteP = 1;
                $letterAcceptDet->save();
            }
            else if($dok_Type == 'LAD-SD'){
                $letterAcceptDet = LetterAcceptanceDet::where('LADNo', $dok_idRef)->first();
                $letterAcceptDet->LADCompleteU = 1;
                $letterAcceptDet->save();
            }
            else if($dok_Type == 'PCD'){
                $letterAcceptDet = ProjectClaimDet::where('PCDNo', $dok_idRef)->first();
                $letterAcceptDet->PCDComplete = 1;
                $letterAcceptDet->save();
            }


            DB::commit();

			return response()->json([
				'success' => '1',
				'redirect' => $dok_redirect,
				'message' => 'Fail berjaya dimuat naik.'
			]);

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Fail tidak berjaya dimuat naik!'.$e->getMessage()
            ], 400);
        }


	}

    public function delete($fileguid){


        $fileAttach = FileAttach::WHERE('FAGuidID',$fileguid)->first();
        if ($fileAttach != null){
            $fileAttach->delete();

            $returnval = Storage::disk('local')->delete($fileAttach->FAFilePath.'\\'.$fileAttach->FAFileName.'.'.$fileAttach->FAFileExtension);

            return redirect()->back()->with('success', 'Fail telah dipadam.');

        }else{
            return redirect()->back()->with('success', 'Fail tiada didalam sistem.');
        }
    }


}
