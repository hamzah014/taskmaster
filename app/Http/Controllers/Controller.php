<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Auth;
use App\Models\FileAttach;
use App\Models\AutoNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function copyFile($originalRefNo, $copyRefNo)
    {
        DB::beginTransaction();
        try {
            // Retrieve the original file data
            $originalFileAttach = FileAttach::where('FARefNo', $originalRefNo)->first();

            if (!$originalFileAttach || !Storage::disk('local')->exists($originalFileAttach->FAFilePath . '\\' . $originalFileAttach->FAFileName . '.' . $originalFileAttach->FAFileExtension)) {
                // throw new \Exception("File does not exist.");
            }
            else{

                // Read the original file content
                $fileContent = Storage::disk('local')->get($originalFileAttach->FAFilePath . '\\' . $originalFileAttach->FAFileName . '.' . $originalFileAttach->FAFileExtension);

                // Generate new details for the copied file
                $newRefNo = $copyRefNo; // Update with the new reference number
                $autoNumber = new AutoNumber();
                $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
                $folderPath = Carbon::now()->format('ymd');
                $newFileName = strval($generateRandomSHA256);
                $newFilePath = $folderPath . '\\' . $newFileName;

                // Save the copied file in a new path
                Storage::disk('local')->put($newFilePath . '.' . $originalFileAttach->FAFileExtension, $fileContent);

                // Create a new file attach record
                $newFileAttach = new FileAttach();
                $newFileAttach->FARefNo = $newRefNo;
                $newFileAttach->FAFilePath = $folderPath . '\\' . $newFileName;
                $newFileAttach->FAFileName = $newFileName;
                $newFileAttach->FAOriginalName = $originalFileAttach->FAOriginalName;
                $newFileAttach->FAFileExtension = $originalFileAttach->FAFileExtension;
                $newFileAttach->FACB = Auth::user() ? Auth::user()->USCode : $newRefNo;
                $newFileAttach->FAMB = Auth::user() ? Auth::user()->USCode : $newRefNo;
                $newFileAttach->FAFileType = $originalFileAttach->FAFileType;
                $newFileAttach->FAGuidID = $this->generateGuid();
                $newFileAttach->save();

            }

            DB::commit();

            return $originalFileAttach;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error!'.$e->getMessage()
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
