<?php

namespace App\Http\Controllers\Perolehan\Setting;

use App\Http\Controllers\Controller;
use App\Models\SSMCompany;
use App\Providers\RouteServiceProvider;
use App\Services\DropdownService;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;


use App\Http\Requests;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;

class TenderController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){
        $tender_no = [
            0 => 'T00001',
            1 => 'T00002',
        ];

        return view('perolehan.setting.tender.index', compact('tender_no'));
    }

    public function create(){

        $jenis = $this->dropdownService->tender_sebutharga();
        $jenis_projek = $this->dropdownService->jenis_projek();
        $status = $this->dropdownService->status();
        $dokumen_lampiran = $this->dropdownService->dokumen_lampiran();
        $jenis_dokumen = $this->dropdownService->jenis_dokumen();
        $format_fail = $this->dropdownService->format_fail();
        $yn = $this->dropdownService->yn();

        return view('perolehan.setting.tender.create',
            compact('jenis','jenis_projek', 'status', 'dokumen_lampiran', 'jenis_dokumen', 'format_fail', 'yn')
        );
    }
}
