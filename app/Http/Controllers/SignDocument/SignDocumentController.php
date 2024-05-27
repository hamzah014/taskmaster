<?php

namespace App\Http\Controllers\SignDocument;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Models\SignDocument;

class SignDocumentController extends Controller
{

    public function index(){

        
        return view('signDocument.index');
       
    }

    public function view($id){

        $signDocument = SignDocument::where('SDID', $id)->first();

        return view('signDocument.view', compact('signDocument'));
    }


    public function signDocDatatable(Request $request){
        $query = SignDocument::orderBy('SDCD', 'DESC')
            ->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('SDMD', function($row){
                // Assuming 'date' is the column in SignDocument model
                return date('d/m/Y', strtotime($row->SDMD));
            })
            // ->addColumn('CEIDNo', function($row){

            //     return $row->certicate->CEIDNo;
            // })
            ->addColumn('CEIDNo', function($row){
                if ($row->certificate) {
                    return $row->certificate->CEIDNo;
                } else {
                    return 'No Certificate';
                }
            })
            ->addColumn('action', function($row){

                $route = route('signDocument.view',$row->SDID);
                
                $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fa-regular fa-eye"></i></a>';

                return $result;
            
            })

            ->rawColumns(['indexNo', 'action', 'CEIDNo','SDMD'])
            ->make(true);
    }

    public function searchFilter(Request $request){
        try {
            $signdoc = SignDocument::query();
 
            if ($request->filled('search_docno')) {
                $signdoc->where('SDNo', 'LIKE', '%' . $request->search_docno . '%');
            }
            if ($request->filled('search_certno')) {
                $signdoc->where('SD_CENo', 'LIKE', '%' . $request->search_certno . '%');
            }
            // if ($request->filled('search_ic')) {
            //     $signdoc->where('CEIDNo', 'LIKE', '%' . $request->search_ic . '%');
            // }
            if ($request->filled('search_ic')) {
                $search_ic = $request->search_ic;
                $signdoc->whereHas('certificate', function ($q) use ($search_ic) {
                    $q->where('CEIDNo', 'LIKE', '%' . $search_ic . '%');
                });
            }
            
            $query = $signdoc->orderBy('SDCD', 'DESC')->get();

            return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('SDMD', function($row){
                // Assuming 'date' is the column in SignDocument model
                return date('d/m/Y', strtotime($row->SDMD));
            })
            // ->addColumn('CEIDNo', function($row){

            //     return $row->certicate->CEIDNo;
            // })
            ->addColumn('CEIDNo', function($row){
                if ($row->certificate) {
                    return $row->certificate->CEIDNo;
                } else {
                    return 'No Certificate';
                }
            })
            ->addColumn('action', function($row){

                $route = route('signDocument.view',$row->SDID);
                
                $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fa-regular fa-eye"></i></a>';

                return $result;
            
            })

            ->rawColumns(['indexNo', 'action', 'CEIDNo','SDMD'])
            ->make(true);

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Permohonan tidak berjaya!'.$e->getMessage()
            ], 400);
        }
    }


}
