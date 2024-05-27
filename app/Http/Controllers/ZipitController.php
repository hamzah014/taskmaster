<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Models\ClaimMeetingDet;
use App\Models\ProjectClaim;
use App\Models\Tender;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalSpec;
use App\Models\TenderSpec;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Chumper\Zipper\Zipper;

use App\Http\Requests;
use App\Models\Contractor;
use App\Models\FileAttach;
use App\Models\LetterAcceptance;
use App\Models\LetterIntent;
use App\Models\TenderApplication;
use App\Models\TenderProposalDetail;
use App\Models\WebSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Session;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ZipitController extends Controller
{
    public function __construct(DropdownService $dropdownService, AutoNumber $autoNumber)
    {
        $this->dropdownService = $dropdownService;
        $this->autoNumber = $autoNumber;
    }

    public function index(){

        $tenderProposal = TenderProposal::whereHas('tenderProposalDetail')->get()->pluck('TP_TDNo','TPNo' )
        ->map(function ($item, $key) {
            $title = Tender::where('TDNo', $item)->value('TDTitle');
            return  $key . " (" . $title . ")" ;
        });


        return view('kewangan.zipit.index',
        compact('tenderProposal')
        );
    }

    public function generateZip($id,$type){

        try{
            $firstPath = Carbon::now()->format('ymd');

            $data_get = [];

            if($type == 'TP'){
                $data_TPD = TenderProposalDetail::where('TPD_TPNo',$id)->get();
                foreach($data_TPD as $data){
                    $data_get[] = [
                        'fafile' => 'REFNO',
                        'refNo' => $data->TPDNo,
                        'fileType' => NULL,
                    ];
                }
            }
            else if($type == 'TD'){
                $data_TDD = TenderDetail::where('TDD_TDNo',$id)->get();

                foreach($data_TDD as $data){
                    $data_get[] = [
                        'fafile' => 'REFNO_TYPE',
                        'refNo' => $data->TDDNo,
                        'fileName' => $data->TDDTitle,
                        'fileType' => 'TD-PD',
                    ];
                }
            }
            else if($type =='PU'){ //PublicUser
                $data_TP = TenderProposal::where('TPNo',$id)->first();

                $data_TPD = TenderProposalDetail::where('TPD_TPNo',$id)->get();
                foreach($data_TPD as $data){
                    $data_get[] = [
                        'fafile' => 'REFNO',
                        'refNo' => $data->TPDNo,
                        'fileName' => $data->tenderDetail->TDDTitle,
                        'fileType' => NULL,
                    ];
                }

                $data_TDD = TenderDetail::where('TDD_TDNo',$data_TP->TP_TDNo)->get();
                foreach($data_TDD as $data){
                    $data_get[] = [
                        'fafile' => 'REFNO_TYPE',
                        'refNo' => $data->TDDNo,
                        'fileName' => $data->TDDTitle,
                        'fileType' => 'TD-PD',
                    ];
                }

                $bankStatement = $data_TP->TP_TANo;

                $data_get[] = [
                    'fafile' => 'REFNO_TYPE',
                    'refNo' => $bankStatement,
                    'fileName' => 'PENYATA_BANK',
                    'fileType' => 'TA-BS',
                ];
                
            }
            else if($type =='P-TD'){ //Perolehan take Tender and TP(s) files
                $data_TDD = TenderDetail::where('TDD_TDNo',$id)->get();

                foreach($data_TDD as $data){
                    $data_get[] = [
                        'fafile' => 'REFNO_TYPE',
                        'refNo' => $data->TDDNo,
                        'fileName' => $data->TDDTitle,
                        'fileType' => 'TD-PD',
                    ];
                }

                $data_TP = TenderProposal::where('TP_TDNo' , $id)->with('tenderProposalDetail')->get();

                foreach ($data_TP as $proposal) {
                    foreach ($proposal->tenderProposalDetail as $detail) {
                        $data_get[] = [
                            'fafile' => 'REFNO',
                            'refNo' => $detail->TPDNo,
                            'fileName' => $detail->tenderDetail->TDDTitle,
                            'fileType' => NULL,
                        ];
                    }
                }

            }
            else{
                return response()->json([
                    'error' => '1',
                    'message' => 'No Type Found'
                ], 400);
            }

            //dd($data_get);
            //cari
            foreach($data_get as $data){

                //find in table TRFileAttach
                if($data['fafile']  == 'REFNO'){
                    $fileAttach = FileAttach::where('FARefNo',$data['refNo'])->first();
                }
                else if($data['fafile']  == 'REFNO_TYPE'){
                    $fileAttach = FileAttach::where('FARefNo',$data['refNo'], $data['fileType'])->first();
                }
                else if($data['fafile']  == 'USCODE'){
                    $fileAttach = FileAttach::where('FA_USCode',$data['refNo'])->first();
                }

                if ($fileAttach != null){

                    $folderPath	 = $fileAttach->FAFilePath;
                    $newFileName = $fileAttach->FAFileName;
                    $newFileExt	 = $fileAttach->FAFileExtension;

                    $filePath = $folderPath.'/'.$newFileName.'.'.$newFileExt;
                    $filePath = $fileAttach->FAFilePath;
                    $fileExt = strtolower($fileAttach->FAFileExtension);

                    // Source and destination folder paths on the FTP server
                    $destinationFolderPath = '/'.$firstPath.'/'.$id.'/'; // Replace with the destination folder path

                    /*LAST TIME TUKAR FROM 'refNo' to 'fileName'*/
                    // File name to be copied (include extension)
                    $fileName = $data['fileName'] . '.' . $fileExt; // Replace with the file name

                    // Create a Storage disk for the FTP connection
                    $sourceDisk = Storage::disk('fileStorage');

                    // Check if the source file exists
                    if (Storage::disk('fileStorage')->exists($filePath)) {
                        // Read the source file
                        $fileContents = $sourceDisk->get($filePath);

                        // Create a Storage disk for the FTP connection to the destination folder
                        $destinationDisk = Storage::disk('fileStorage');

                        // Write the file to the destination folder
                        $destinationDisk->put($destinationFolderPath . $fileName, $fileContents);

                        // You can now verify that the file has been copied to the destination folder
                        if ($destinationDisk->exists($destinationFolderPath . $fileName)) {
                            echo 'File copied successfully.';
                        } else {
                            echo 'Error copying file.';
                        }
                    } else {
                        echo 'Source file not found.';
                    }

                }

            }

            $pdfContent = null ;

            //generate
            if($type == 'TD'){

                //find[0][] way to remove [0]
                $pdfData  = $this->generateDocPDFTD($id);
                $pdfContent = $pdfData[0]['pdfContent'];
                $name = $pdfData[0]['name'];

            }
            else if($type == 'PU'){
                $pdfData = [];

                //find[0][] way to remove [0]
                $docPU = $this->generateDocPDFPU($id);
                $pdfData[] = [
                    'name' => $docPU[0]['name'],
                    'pdfContent' => $docPU[0]['pdfContent'],
                ];

                // $data_LOI = LetterIntent::where('LI_TPNo' , $id)->first();
                // $data_LOA = LetterAcceptance::where('LA_TPNo' , $id)->first();

                // if($data_LOI){
                //     $loiPDFData = $this->generateLOIPDF($id);
                //     $pdfData[] = [
                //         'name' => $loiPDFData[0]['name'],
                //         'pdfContent' => $loiPDFData[0]['pdfContent'],
                //     ];
                // }

                // if($data_LOA){
                //     $loaPDFData = $this->generateLOAPDF($id);
                //     $pdfData[] = [
                //         'name' => $loaPDFData[0]['name'],
                //         'pdfContent' => $loaPDFData[0]['pdfContent'],
                //     ];
                // }

            }
            else if($type == 'P-TD'){
                $pdfData = [];

                $data_TP = TenderProposal::where('TP_TDNo' , $id)->with('tenderProposalDetail')->get();

                foreach ($data_TP as $proposal) {
                    $tpNo = $proposal->TPNo;

                    // Process and store the 'TD' PDF data
                    $docPU = $this->generateDocPDFPU($tpNo);
                    $pdfData[] = [
                        'name' => $docPU[0]['name'],
                        'pdfContent' => $docPU[0]['pdfContent'],
                    ];

                    $data_LOI = LetterIntent::where('LI_TPNo', $tpNo)->first();
                    $data_LOA = LetterAcceptance::where('LA_TPNo', $tpNo)->first();

                    if ($data_LOI) {
                        $loiPDFData = $this->generateLOIPDF($tpNo);
                        $pdfData[] = [
                            'name' => $loiPDFData[0]['name'],
                            'pdfContent' => $loiPDFData[0]['pdfContent'],
                        ];
                    }

                    if ($data_LOA) {
                        $loaPDFData = $this->generateLOAPDF($tpNo);
                        $pdfData[] = [
                            'name' => $loaPDFData[0]['name'],
                            'pdfContent' => $loaPDFData[0]['pdfContent'],
                        ];
                    }

                }

            }


            $folderPath = '/'.$firstPath.'/'.$id.'/';

            if (Storage::disk('fileStorage')->exists($folderPath)) {
                $zipFileName = $id.'.zip';
                $tempZipPath = storage_path($zipFileName);

                // Create a new ZipArchive
                $zip = new ZipArchive();

                if ($zip->open($tempZipPath, ZipArchive::CREATE) === true) {
                    $files = Storage::disk('fileStorage')->files($folderPath);

                    foreach ($files as $file) {
                        $fileContent = Storage::disk('fileStorage')->get($file);

                        // Check if the file name contains "TP" (Assuming TPNo is part of the filename)
                        if (preg_match('/TP(\d+)/', basename($file), $matches)) {
                            $tpNo = $matches[1];
                            $tpFolderName = "TP{$tpNo}";

                            // Check if the folder for this TPNo exists in the ZIP archive
                            if (!$zip->statName($tpFolderName)) {
                                // If it doesn't exist, create it
                                $zip->addEmptyDir($tpFolderName);
                            }

                            // Add the file to the TPNo folder in the ZIP archive
                            $zip->addFromString($tpFolderName . '/' . basename($file), $fileContent);
                        } else {
                            // Handle non-TP files (TD or others) as needed
                            $zip->addFromString(basename($file), $fileContent);
                        }
                    }

                    foreach ($pdfData as $pdfItem) {
                        if (isset($pdfItem['name']) && isset($pdfItem['pdfContent'])) {
                            // Check if the file name contains "TP" (Assuming TPNo is part of the filename)
                            if (preg_match('/TP(\d+)/', $pdfItem['name'], $matches)) {
                                $tpNo = $matches[1];
                                $tpFolderName = "TP{$tpNo}";

                                // Check if the folder for this TPNo exists in the ZIP archive
                                if (!$zip->statName($tpFolderName)) {
                                    // If it doesn't exist, create it
                                    $zip->addEmptyDir($tpFolderName);
                                }

                                // Add the PDF file to the TPNo folder in the ZIP archive
                                $zip->addFromString($tpFolderName . '/' . $pdfItem['name'] . '.pdf', $pdfItem['pdfContent']);
                            } else {
                                // Handle PDFs for non-TP files (TD or others) as needed
                                $zip->addFromString($pdfItem['name'] . '.pdf', $pdfItem['pdfContent']);
                            }
                        }
                    }

                    // $tdFolderName = 'TD';
                    // $tpFolderName = 'TP';

                    // // List of 'TD' and 'TP' files
                    // $tdFiles = [];
                    // $tpFiles = [];
                    // $pdfFiles = [];

                    // foreach ($files as $file) {
                    //     $fileContent = Storage::disk('fileStorage')->get($file);
                    //     // Check if the file name contains "TD" or "TP"
                    //     if (strpos(basename($file), 'TD') !== false) {
                    //         // This is a 'TD' file
                    //         $tdFiles[] = ['name' => $tdFolderName . '/' . basename($file), 'content' => $fileContent];
                    //     } elseif (strpos(basename($file), 'TP') !== false) {
                    //         // This is a 'TP' file
                    //         $tpFiles[] = ['name' => $tpFolderName . '/' . basename($file), 'content' => $fileContent];
                    //     }
                    // }

                    // // Add 'TD' files to the 'TD' folder
                    // foreach ($tdFiles as $file) {
                    //     $zip->addFromString($file['name'], $file['content']);
                    // }

                    // // Add 'TP' files to the 'TP' folder
                    // foreach ($tpFiles as $file) {
                    //     $zip->addFromString($file['name'], $file['content']);
                    // }

                    // foreach ($pdfData as $pdfItem) {
                    //     if (isset($pdfItem['name']) && isset($pdfItem['pdfContent'])) {
                    //         // Add PDF files to the 'TD' folder
                    //         $pdfFiles[] = ['name' => $tdFolderName . '/' . $pdfItem['name'] . '.pdf', 'content' => $pdfItem['pdfContent']];
                    //     }
                    // }

                    // // Add PDF files to the 'TD' folder
                    // foreach ($pdfFiles as $pdfFile) {
                    //     $zip->addFromString($pdfFile['name'], $pdfFile['content']);
                    // }









                    // foreach ($files as $file) {
                    //     $fileContent = Storage::disk('fileStorage')->get($file);
                    //     // dd($fileContent);
                    //     $zip->addFromString(basename($file), $fileContent);
                    // }

                    // foreach ($pdfData as $pdfItem) {
                    //     if (isset($pdfItem['name']) && isset($pdfItem['pdfContent'])) {
                    //         $zip->addFromString($pdfItem['name'] . '.pdf', $pdfItem['pdfContent']);
                    //     }
                    // }

                    $zip->close();

                    // Move the ZIP file to the download location
                    Storage::disk('fileStorage')->put("/tempZip//".$zipFileName, File::get($tempZipPath));

                    // Clean up the temporary ZIP file
                    File::delete($tempZipPath);

                    $fileBase64 =  Storage::disk('fileStorage')->get("/tempZip//".$zipFileName);

				    return response($fileBase64)
                    ->header('Content-Type', 'application/zip')
                    ->header('Content-Disposition', 'attachment; filename="' . $zipFileName . '"');

                } else {
                    die('Failed to create ZIP archive: ' . $zip->getStatusString());
                }
            } else {
                abort(404, 'Folder not found');
            }


        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => 'Maklumat mesyuarat tidak berjaya dikemaskini!'.$e->getMessage()
            ], 400);
        }
    }

    public function generateDocPDFTD($id) {
        $tender = Tender::where('TDNo', $id)->first();
        $zipData = [];

        $tenderSpecs = TenderSpec::where('TDS_TDNo' , $id)->get();

        $tenderBQFs = TenderSpec::where('TDS_TDNo' , $id)->where('TDStockInd' , '1')->get();

        $tenderCF = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode', 'CF')->first();
        if ($tenderCF) {
            $nameCF = str_replace('/', '-', $tenderCF->TDDTitle);

            $template = "DOKUMEN";
            $templateName = "CF";
            $viewCF = View::make('general.templatePDF', compact('template' , 'templateName' , 'tender', 'tenderCF', 'tenderSpecs' , 'tenderBQFs'));
            $pdfContentCF = $this->generatePDF2($viewCF);
            $zipData[] = [
                'name' => $nameCF,
                'pdfContent' => $pdfContentCF,
            ];
        }

        $tenderBQF = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode', 'BQF')->first();
        if ($tenderBQF) {
            $nameBQF = str_replace('/', '-', $tenderBQF->TDDTitle);
            $template = "DOKUMEN";
            $templateName = "BQF";
            $viewBQF = View::make('general.templatePDF', compact('template' , 'templateName' , 'tender', 'tenderBQF', 'tenderSpecs' , 'tenderBQFs'));
            $pdfContentBQF = $this->generatePDF2($viewBQF);
            $zipData[] = [
                'name' => $nameBQF,
                'pdfContent' => $pdfContentBQF,
            ];
        }

        $tenderSPF = TenderDetail::where('TDD_TDNo', $tender->TDNo)->where('TDD_MTCode', 'SPF')->first();
        if ($tenderSPF) {
            $nameSPF = str_replace('/', '-', $tenderSPF->TDDTitle);
            $template = "DOKUMEN";
            $templateName = "SPF";
            $viewSPF = View::make('general.templatePDF', compact('template' , 'templateName' , 'tender', 'tenderSPF', 'tenderSpecs' , 'tenderBQFs'));
            $pdfContentSPF = $this->generatePDF2($viewSPF);
            $zipData[] = [
                'name' => $nameSPF,
                'pdfContent' => $pdfContentSPF,
            ];
        }

        return $zipData;
    }

    public function generateDocPDFPU($id) {
        $zipData = [];
        $department = $this->dropdownService->department();

        $proposal = TenderProposal::where('TPNo', $id)->first();

        $tenderProposalDetail = TenderProposalDetail::where('TPD_TPNo' , $proposal->TPNo)->first();

        $tenderDetail = TenderDetail::where('TDDNo' , $tenderProposalDetail->TPD_TDDNo)->first();

        $bakibank = $tenderDetail->TDD_MTCode;

        $dokumens = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%D%');
            })
            ->with(['tenderDetail'])
            ->get();

        $teknikals = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%T%');
            })
            ->with(['tenderDetail'])
            ->first();

        $kewangans = TenderProposalDetail::where('TPD_TPNo', $proposal->TPNo)
            ->whereHas('tenderDetail', function ($query){
                $query->where('TDDType', 'LIKE' ,'%F%');
            })
            ->with(['tenderDetail'])
            ->get();

        $idProposal = $proposal->TPNo;
        $idProposalDetail = $teknikals->TPDNo;

        $tenderProposalSpecs = $proposal->tenderProposalSpec()->with('tenderSpec')->get();

        $initialDate = Carbon::parse($proposal->tender->TDBankStmtYear);
        $bankInitialDate = Carbon::parse($proposal->tender->TDBankStmtYear);
        $bankfirstMonthAfter = $initialDate->copy()->addMonth();
        $banksecondMonthAfter = $initialDate->copy()->addMonths(2);

        $jabatan = $department[$proposal->tender->TD_DPTCode];
        $name = str_replace('/', '-', $tenderDetail->TDDTitle);
        $template = "PROPOSAL";
        $templateName = "PROPOSAL";
        $view = View::make('general.templatePDF', compact('id','templateName', 'proposal', 'dokumens', 'teknikals', 'kewangans',
        'template','jabatan', 'tenderProposalSpecs','bankInitialDate', 'bankfirstMonthAfter', 'banksecondMonthAfter' , 'bakibank'));
        $pdfContent = $this->generatePDF2($view);
        $zipData[] = [
            'name' => $name,
            'pdfContent' => $pdfContent,
        ];


        return $zipData;
    }

    public function generateLOIPDF($id){
        $letterIntent = LetterIntent::where('LI_TPNo', $id)->first();

        $li_date = Carbon::parse($letterIntent->LIDate)->format('d/m/Y');
        $li_time = Carbon::parse($letterIntent->LITime)->format('h:i A');

        $submitDate = Carbon::parse($letterIntent->LISubmitDate)->format('d/m/Y');

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterIntent->tenderProposal->contractor->COReg_StateCode];

        $meetingLocation = $this->dropdownService->meetingLocation();
        $location = $meetingLocation[$letterIntent->LILocation] ?? $letterIntent->LILocation;

        $qrCode = QrCode::size(80)->generate($letterIntent->LINo);

        $template = "LETTER";
        $templateName = "INTENT";
        $view = View::make('general.templatePDF',compact('template','templateName','letterIntent',
        'li_date','li_time','submitDate','COState' , 'location' , 'qrCode'));
        $pdfContent = $this->generatePDF2($view);
        $zipData[] = [
            'name' => 'SURAT NIAT',
            'pdfContent' => $pdfContent,
        ];

        return $zipData;
    }

    public function generateLOAPDF($id){
        $webSetting = WebSetting::first();

        $letterAccept = LetterAcceptance::where('LA_TPNo',$id)->first();

        $tenderProposal  = TenderProposal::where('TPNo' , $id)->first();

        $tenderApplication = TenderApplication::where('TANo' , $tenderProposal->TP_TANo)->first();

        $contractor = Contractor::where('CONo', $tenderApplication->TA_CONo)->first();

        $negeri = $this->dropdownService->negeri();
        $COState = $negeri[$letterAccept->tenderProposal->contractor->COReg_StateCode];

        $dpt = $this->dropdownService->department();
        $department = $dpt[$tenderApplication->tender->TD_DPTCode];

        $responseDate = \Carbon\Carbon::parse($letterAccept->LAResponseDate)->format('d/m/Y');

        $dateExp = \Carbon\Carbon::parse($letterAccept->LAResponseDate)
                    ->addDays($webSetting->SSTExpDay)
                    ->format('d/m/Y');

        $bondPercent = $letterAccept->LATotalAmount * $webSetting->SSTBondPercent;

        $bondInWords = $this->DigitToWords($bondPercent);

        $insurance  = $webSetting->SSTInsuranceAmt;

        $insuranceInWords = $this->DigitToWords($insurance);

        $qrCode = QRcode::size(80)->generate($letterAccept->LANo);

        $template = "LETTER";
        $templateName = "ACCEPTANCE";
        $view = View::make('general.templatePDF',compact('template','templateName','letterAccept' , 'COState', 'department' , 'responseDate' ,
        'dateExp' , 'bondPercent' , 'bondInWords'  , 'insurance' , 'insuranceInWords' , 'qrCode'));
        $pdfContent = $this->generatePDF2($view);
        $zipData[] = [
            'name' => 'SURAT SETUJU TERIMA',
            'pdfContent' => $pdfContent,
        ];

        return $zipData;
    }

    public function generatePDF2($view) {
        // Create a Dompdf instance
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $dompdf = new Dompdf($options);


        $html = $view->render();
        $dompdf->set_option('chroot', public_path());
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }


}
