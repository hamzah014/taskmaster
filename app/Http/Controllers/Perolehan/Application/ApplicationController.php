<?php

namespace App\Http\Controllers\Perolehan\Application;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use App\Models\TenderApplication;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;

class ApplicationController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){
        return view('perolehan.application.index');
    }

    public function view($id){

        $bank_status = $this->dropdownService->bank_status();
        $submission_status = $this->dropdownService->statusSubmission();

        $bank_name = $this->dropdownService->bank_name();

        $news = [
            [
                'description' => 'ABC SDN BHD MENDAPAT AWARD SYARIKAT TERHEBAT',
                'url' => 'http://abc.com.my',
                'qr' => 'https://www.w3schools.com/images/w3schools_green.jpg',
            ],
            [
                'description' => 'ABC TERSEDAP DI HARTAMAS',
                'url' => 'http://sinar-harian.com.my',
                'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=185x185&ecc=L&qzone=1&data=http%3A%2F%2Fexample.com%2F',
            ],
            [
                'description' => 'BURSA SAHAM ABC SDN BHD MENINGKAT 50%',
                'url' => 'http://abc.com.my',
                'qr' => 'C:/Users/User/Pictures/qr.png',
            ]
        ];

        $tenderApp = TenderApplication::where('TANo', $id)->with('tender','contractor')->first();

        return view('perolehan.application.view', compact('bank_status', 'submission_status', 'bank_name', 'news','tenderApp'));
    }

    //{{--Working Code Datatable with indexNo--}}
    public function applicationDatatable(Request $request){
        $query = TenderApplication::with('tender','contractor')->get();

        return datatables()->of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->addColumn('COCompNo', function($row) {

                $result = "";

                if(!empty($row->contractor))
                    $result = $row->contractor->COCompNo;

                return $result;

            })
            ->addColumn('TDNo', function($row) {

                $result = "";

                if(!empty($row->tender))
                    $result = $row->tender->TDNo;

                return $result;

            })
            ->addColumn('TANo', function($row) {

                $route = route('perolehan.application.view',[$row->TANo] );

                $result = '<a href="'.$route.'">'.$row->TANo.'</a>';

                return $result;
            })
            ->editColumn('TACD', function($row) {
                $carbonDatetime = Carbon::parse($row->TACD);

                // Format the Carbon instance to get the date in 'Y-m-d' format
                $formattedDate = $carbonDatetime->format('d/m/Y');

                return $formattedDate;
            })
            ->with(['count' => 0]) // Initialize the counter outside of the column definitions
            ->setRowId('indexNo')
            ->rawColumns(['indexNo','COCompNo', 'TDNo', 'TANo', 'TACD'])
            ->make(true);
    }

}
