<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Pelaksana\Approval\ApprovalController;
use App\Services\DropdownService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Dompdf\Dompdf;
use Symfony\Component\HttpFoundation\Response;
use Auth;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use App\Models\BoardMeeting;
use App\Models\ClaimMeeting;
use App\Models\KickOffMeeting;
use App\Models\Meeting;
use App\Models\MeetingEOT;
use App\Models\MeetingLI;
use App\Models\MeetingNP;
use App\Models\MeetingPT;
use App\Models\MeetingPTA;
use App\Models\MeetingPTE1;
use App\Models\MeetingPTE2;
use App\Models\MeetingVO;
use App\Models\ProjectTender;
use App\Models\Tender;
use App\Models\TenderAdv;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NumberToWords\NumberToWords;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function generateGuid()
    {
        $guid = Str::uuid();
        return $guid;
    }

    public function saveFile($file,$fileType,$refNo){

        try{
            DB::beginTransaction();

            $dok_FARefNo = $refNo;

            $autoNumber = new AutoNumber();
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();

            $folderPath = Carbon::now()->format('ymd');
            $originalName =  $file->getClientOriginalName() ?? Carbon::now()->format('ymd') . rand(1000,9999);
            $newFileExt = $file->getClientOriginalExtension();
            $newFileName = strval($generateRandomSHA256);

            $fileCode = $fileType;

            $fileContent = file_get_contents($file->getRealPath());

            Storage::disk('local')->put($folderPath.'\\'.$newFileName, $fileContent);

            $fileAttach = FileAttach::where('FARefNo',$dok_FARefNo)
                                    ->where('FAFileType',$fileCode)
                                    ->first();
            if ($fileAttach == null){
                $fileAttach = new FileAttach();
                $fileAttach->FACB 		= Auth::user() ? Auth::user()->USCode : $dok_FARefNo;
                $fileAttach->FAFileType 	= $fileCode;
                $fileAttach->FAGuidID  	= $this->generateGuid();
            }else{

                $filename   = $fileAttach->FAFileName;
                $fileExt    = $fileAttach->FAFileExtension ;

                Storage::disk('local')->delete($fileAttach->FAFilePath.'\\'.$filename.'.'.$fileExt);

            }
            $fileAttach->FARefNo     	    = $dok_FARefNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FAMB 			    = Auth::user() ? Auth::user()->USCode : $dok_FARefNo;
            $fileAttach->save();

            DB::commit();

            return $fileAttach;

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }

    }


	public function getActivationCode(){

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 20; $i++) {
          $index = rand(0, strlen($characters) - 1);
          $randomString .= $characters[$index];
        }

        return $randomString;
    }

}
