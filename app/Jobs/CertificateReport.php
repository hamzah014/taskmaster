<?php

namespace App\Jobs;

use App\Exports\ReportCertExport;
use App\Models\AutoNumber;
use App\Models\Certificate;
use App\Models\FileAttach;
use App\Models\GenerateReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CertificateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $latestDate;
    protected $oldestDate;

    protected $dateNow;
    protected $timeNow;
    protected $generatedBy;
    protected $info;

    // "search_project",
    // "search_certNo",
    // "search_status",
    // "search_id",
    // "search_isuDateFrom",
    // "search_isuDateTo",
    // "search_validDay",
    // "search_revokeDateFrom",
    // "search_revokeDateTo",

    protected $params;
    protected $project;
    protected $certNo;
    protected $status;
    protected $id;
    protected $isuDateFrom;
    protected $isuDateTo;
    protected $validDay;
    protected $revokeDateFrom;
    protected $revokeDateTo;
    protected $GenerateReportNo;

    public function __construct(array $params = [], $GRNo)
    {
        $this->params = $params;
        $this->project           = $params['search_project'] ?? null;
        $this->certNo            = $params['search_certNo'] ?? null;
        $this->status            = $params['search_status'] ?? null;
        $this->id                = $params['search_id'] ?? null;
        $this->isuDateFrom       = $params['search_isuDateFrom'] ?? null;
        $this->isuDateTo         = $params['search_isuDateTo'] ?? null;
        $this->validDay          = $params['search_validDay'] ?? null;
        $this->revokeDateFrom    = $params['search_revokeDateFrom'] ?? null;
        $this->revokeDateTo      = $params['search_revokeDateTo'] ?? null;
        $this->GenerateReportNo = $GRNo;
    }

    public function handle()
    {
        try {

            $data = $this->getData();

            $resultPDF = $this->generatePDF($data, $this->info);
            $resultExcel = $this->generateExcel($data, $this->info);
            // $resultPDF = true;
            // $resultExcel = true;

            if($resultExcel == true && $resultPDF == true){
                $generateReport = GenerateReport::where('GRNo', $this->GenerateReportNo)->first();
                $generateReport->GRStatus = '1';
                $generateReport->GRMB = 'SYSTEM';
                $generateReport->save();
                Log::info('Complete job session staf.');

            }else{
                Log::warning('Job session staf PDF - ' . $resultPDF);
                Log::warning('Job session staf EXCEL- ' . $resultExcel);

            }
            
        } catch (\Exception $e) {
            Log::error('Error occurred while handling job: ' . $e->getMessage());
        }
    }

    public function getData(){

        $certificate = Certificate::query();
        
        $certificate = Certificate::leftJoin('MSProject', 'TRCertificate.CE_PJCode', '=', 'MSProject.PJCode')
            ->select(
                'TRCertificate.*',
                'MSProject.PJDesc',
            );

        Log::info("masuk dalam get");
            
        if (!is_null($this->project)) {
            $certificate->where('CE_PJCode', $this->project );
        }

        if (!is_null($this->certNo)) {
            $certificate->where('CENo', 'LIKE', '%' . $this->certNo . '%');
        }

        if (!is_null($this->status)) {

            if($this->status == 1){
                $certificate->whereDate('CEEndDate', '>=', now());
            }
            else{
                $certificate->whereDate('CEEndDate', '<', now());
                $certificate->orWhere('CERevokeInd', 1);
            }
            
        }

        if (!is_null($this->id)) {
            $certificate->where('CEIDNo', 'LIKE', '%' . $this->id . '%');
        }

        if (!is_null($this->isuDateFrom)) {
            $certificate->whereDate('CEStartDate', '>=', $this->isuDateFrom );
            
        }

        if (!is_null($this->isuDateTo)) {
            $certificate->whereDate('CEEndDate', '<=', $this->isuDateTo );
            
        }

        if (!is_null($this->validDay)) {
            //here
            $validityDays = $this->validDay;
            
            // Calculate the end date based on current date and validity days
            $endDate = Carbon::now()->addDays($validityDays)->endOfDay();
            
            // Filter records where the end date is within the validity days
            $certificate->where('CEEndDate', '<=', $endDate);
        }

        if (!is_null($this->revokeDateTo)) {
            $certificate->whereDate('CERevokeDate', '>=', $this->revokeDateFrom);
            
        }

        if (!is_null($this->revokeDateTo)) {
            $certificate->whereDate('CERevokeDate', '<=', $this->revokeDateTo );
            
        }

        $data = $certificate->get();

        foreach($data as $cert){
                
            $date1 = Carbon::now();
            $date2 = Carbon::parse($cert->CEEndDate);

            $diffInDays = $date1->diffInDays($date2);

            $cert->validDay = $diffInDays;
            
            if ($cert->CEEndDate > $cert->CEStartDate) {
                $cert->CEStatus = 'Active';
            } else {
                $cert->CEStatus = 'Expired';
            }

        }

        $this->dateNow = Carbon::now()->format('d/m/Y');
        $this->timeNow = Carbon::now()->format('H:i:s');

        $generateReport = GenerateReport::where('GRNo', $this->GenerateReportNo)->first();

        $this->generatedBy = $generateReport->userBy->USName;

        $this->info = array(
            'dateNow' => $this->dateNow,
            'timeNow' => $this->timeNow,
            'generatedBy' => $this->generatedBy,
        );
        Log::info($data);

        return $data;

    }

    protected function generateExcel($data,$info){
        try {

            $certData = array();
            
            foreach ($data as $cert) {
                $date1 = Carbon::now();
                $date2 = Carbon::parse($cert->CEEndDate);
            
                $diffInDays = $date1->diffInDays($date2);
            
                $validDay = $diffInDays;

                if($cert->CERevokeInd == 1){
                    $status = "Inactive";
                    $validDay = 0;
                }
                else{
                    $status = Carbon::now()->lessThanOrEqualTo($cert->CEEndDate) ? 'Active' : 'Inactive';

                    if( !Carbon::now()->lessThanOrEqualTo($cert->CEEndDate)){
                        $validDay = 0;
                    }

                }
            
                $dataCert = array(
                    'project' => $cert->PJDesc,
                    'CENo' => $cert->CENo,
                    'CEIDNo' => $cert->CEIDNo,
                    'CEName' => $cert->CEName,
                    'CECD' => Carbon::parse($cert->CECD)->format('d/m/Y'),
                    'CEEndDate' => Carbon::parse($cert->CEEndDate)->format('d/m/Y'),
                    'validDay' => $validDay,
                    'CERevokeDate' => $cert->CERevokeDate ? Carbon::parse($cert->CERevokeDate)->format('d/m/Y') : '-',
                    'status' => $status
                );
            
                array_push($certData, $dataCert);
            }

            $export = new ReportCertExport($certData,$info);

            $autoNumber = new AutoNumber();
            $generateReport = GenerateReport::where('GRNo' , $this->GenerateReportNo)->first();
            Log::info("Job task Excel:");
            Log::info($generateReport);

            $folderPath =Carbon::now()->format('ymd');
            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            $newFileName = strval($generateRandomSHA256);
            $fileType = $generateReport->GR_GRTCode;
            $refNo = $generateReport->GRNo;
            $newFileExt = 'xlsx';
            $originalName =  time() . '_report_certificate' ;

            Excel::store($export, $folderPath . '/' . $newFileName . '.' . $newFileExt, 'fileStorage', null, [
                'visibility' => 'private',
            ]);

            $fileAttach = FileAttach::where('FARefNo', $refNo)
                ->where('FAFileType', $fileType)
                ->where('FAFileExtension' , $newFileExt)
                ->first();

            if ($fileAttach == null) {
                $fileAttach = new FileAttach();
                $fileAttach->FACB = 'SYSTEM';
                $fileAttach->FAFileType = $fileType;
            } else {
                $filename = $fileAttach->FAFileName;
                $fileExt = $fileAttach->FAFileExtension;
                Storage::disk('fileStorage')->delete($fileAttach->FAFilePath . '\\' . $filename . '.' . $fileExt);
            }

            $fileAttach->FARefNo = $refNo;
            $fileAttach->FAFilePath = $folderPath . '\\' . $newFileName . '.' .$newFileExt;
            $fileAttach->FAFileName = $newFileName;
            $fileAttach->FAOriginalName = $originalName;
            $fileAttach->FAFileExtension = strtolower($newFileExt);
            $fileAttach->FAMB = 'SYSTEM';
            $fileAttach->save();

            Log::info($fileAttach);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Error occurred while handling job: ' . $e->getMessage());
        }
    }

    protected function generatePDF($data,$info){
        try {

            $generateReport = GenerateReport::where('GRNo', $this->GenerateReportNo)->first();

            $dateNow    = $info['dateNow'];
            $timeNow    = $info['timeNow'];
            $generatedBy    = $info['generatedBy'];

            $certData = array();
            
            foreach ($data as $cert) {
                $date1 = Carbon::now();
                $date2 = Carbon::parse($cert->CEEndDate);
            
                $diffInDays = $date1->diffInDays($date2);
            
                $validDay = $diffInDays;

                if($cert->CERevokeInd == 1){
                    $status = "Inactive";
                    $validDay = 0;
                }
                else{
                    $status = Carbon::now()->lessThanOrEqualTo($cert->CEEndDate) ? 'Active' : 'Inactive';

                    if( !Carbon::now()->lessThanOrEqualTo($cert->CEEndDate)){
                        $validDay = 0;
                    }

                }
            
                $dataCert = array(
                    'project' => $cert->PJDesc,
                    'CENo' => $cert->CENo,
                    'CEIDNo' => $cert->CEIDNo,
                    'CEName' => $cert->CEName,
                    'CECD' => Carbon::parse($cert->CECD)->format('d/m/Y'),
                    'CEEndDate' => Carbon::parse($cert->CEEndDate)->format('d/m/Y'),
                    'validDay' => $validDay,
                    'CERevokeDate' => $cert->CERevokeDate ? Carbon::parse($cert->CERevokeDate)->format('d/m/Y') : '-',
                    'status' => $status
                );
            
                array_push($certData, $dataCert);
            }

            Log::info($info);

            $download = false;
            $name = "testing";
            $view = view('general.report.reportCertificate', compact('certData','dateNow','timeNow','generatedBy'));
            $output = $view->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $pdf = new Dompdf($options);
            $pdf->loadHtml($output);
            $pdf->set_option('chroot', public_path());
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();

            $fileContent = $pdf->output();
        
            // $temporaryFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time() .'.pdf';

            // file_put_contents($temporaryFilePath, $pdf->output());

            $autoNumber = new AutoNumber();

            $generateRandomSHA256 = $autoNumber->generateRandomSHA256();
            // $file = $fileContent;
            $fileType = $generateReport->GR_GRTCode;
            $refNo = $generateReport->GRNo;

            $folderPath = Carbon::now()->format('ymd');
            $originalName =  time() . rand(100,999) ;
            $newFileExt = 'pdf';
            $newFileName = strval($generateRandomSHA256);

            $fileCode = $fileType;

            // $fileContent = file_get_contents($file);

            Storage::disk('fileStorage')->put($folderPath.'\\'.$newFileName, $fileContent);

            $fileAttach = FileAttach::where('FARefNo',$refNo)
                ->where('FAFileType',$fileType)
                ->where('FAFileExtension' , 'pdf')
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
            $fileAttach->FARefNo     	    = $refNo;
            $fileAttach->FAFilePath 	    = $folderPath.'\\'.$newFileName;
            $fileAttach->FAFileName 	    = $newFileName;
            $fileAttach->FAOriginalName 	= $originalName;
            $fileAttach->FAFileExtension    = strtolower($newFileExt);
            $fileAttach->FAMB 			    = 'SYSTEM';
            $fileAttach->save();
            
            Log::info($fileAttach);

            return true;

        } catch (\Exception $e) {
            Log::error('Error occurred while handling job: ' . $e->getMessage());
        }
    }

}