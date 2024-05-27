<?php

namespace App\Jobs;

use App\Exports\ReportCertExport;
use App\Exports\ReportSignExport;
use App\Models\AutoNumber;
use App\Models\Certificate;
use App\Models\FileAttach;
use App\Models\GenerateReport;
use App\Models\SignDocument;
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

class SignReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $latestDate;
    protected $oldestDate;

    protected $dateNow;
    protected $timeNow;
    protected $generatedBy;
    protected $info;
    
    // search_signNo
    // search_certno
    // search_id
    // search_dateFrom
    // search_dateTo

    protected $params;

    protected $signNo;
    protected $certno;
    protected $id;
    protected $dateFrom;
    protected $dateTo;

    protected $GenerateReportNo;

    public function __construct(array $params = [], $GRNo)
    {
        $this->params = $params;

        $this->signNo        = $params['search_signNo'] ?? null;
        $this->certno        = $params['search_certno'] ?? null;
        $this->id            = $params['search_id'] ?? null;
        $this->dateFrom      = $params['search_dateFrom'] ?? null;
        $this->dateTo        = $params['search_dateTo'] ?? null;

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
                Log::info('Complete job sign pdf excel.');

            }else{
                Log::warning('Job sign PDF - ' . $resultPDF);
                Log::warning('Job sign EXCEL- ' . $resultExcel);

            }
            
        } catch (\Exception $e) {
            Log::error('Error occurred while handling job: ' . $e->getMessage());
        }
    }

    public function getData(){

        $signDocument = SignDocument::query();
        
        $signDocument = SignDocument::leftJoin('TRCertificate', 'TRSignDocument.SD_CENo', '=', 'TRCertificate.CENo')
            ->select(
                'TRSignDocument.SDNo',
                'TRSignDocument.SDCD',
                'TRCertificate.CENo',
                'TRCertificate.CEIDNo',
            );

        Log::info("masuk dalam get data");

        if (!is_null($this->signNo)) {
            $signDocument->where('SDNo', 'LIKE', '%' . $this->signNo  . '%');
        }

        if (!is_null($this->certno)) {
            $certNo = $this->certno;
            $signDocument->whereHas('certificate', function($query) use(&$certNo) {
                $query->where('CENo', 'LIKE', '%' . $certNo . '%');
            });
        }

        if (!is_null($this->id)) {
            $id = $this->id;
            $signDocument->whereHas('certificate', function($query) use(&$id) {
                $query->where('CEIDNo', 'LIKE', '%' . $id . '%');
            });
        }

        if (!is_null($this->dateFrom)) {
            $signDocument->whereDate('SDCD', '>=', $this->dateFrom );
            
        }

        if (!is_null($this->dateTo)) {
            $signDocument->whereDate('SDCD', '<=', $this->dateTo );
            
        }

        $data = $signDocument->get();

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

            $signData = array();
            
            foreach ($data as $sign) {
            
                $datas = array(
                    'SDNo' => $sign->SDNo,
                    'SDCD' => Carbon::parse($sign->SDCD)->format('d/m/Y'),
                    'CENo' => $sign->CENo,
                    'CEIDNo' => $sign->CEIDNo,
                );
            
                array_push($signData, $datas);
            }

            $export = new ReportSignExport($signData,$info);

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
            $originalName =  time() . '_report_sign' ;

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

            Log::info($info);

            $download = false;
            $name = "testing";
            $view = view('general.report.reportSign', compact('data','dateNow','timeNow','generatedBy'));
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