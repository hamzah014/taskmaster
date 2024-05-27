<?php

namespace App\Http\Controllers\Osc\Approval;

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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;

class ApprovalController extends Controller
{

    public function index(){


        return view('osc.approval.index'
        );
    }

    public function view(Request $request, $id){

//        dd($request->input(), $id);
        $type = $request->type ;

        if($type == 'syarikat'){
            $jenis_penyertaan = [
                0 => 'Semua (Tender, Sebutharga & Kerja Undi',
                1 => 'Tender & Sebutharga Sahaja',
                2 => 'Kerja Undi Sahaja',
                3 => 'Perkhidmatan Perunding',
            ];

            $jenis_pendaftaran = [
                0 => 'Sdn. Bhd.',
                1 => 'Bhd.',
                2 => 'Koperasi',
                3 => 'Pertubuhan',
                4 => 'Persatuan',
                5 => 'Perseorangan',
                6 => 'Perkongsian',
            ];

            $jenis_milikan_pejabat = [
                0 => 'Lain-lain',
                1 => 'Milikan Sendiri.',
                2 => 'Sewa',
            ];

            $negeri = [
                0 => 'Kuala Lumpur',
                1 => 'Selangor',
            ];

            $parlimen = [
                0 => 'Kuala Lumpur',
                1 => 'Selangor',
            ];

            $gred = [
                0 => 'G1',
                1 => 'G2',
            ];

            $jenis = [
                0 => 'Awam, Bangunan & Mekanikal',
                1 => 'Mekanikal',
                2 => 'Awam & Bangunan',
                3 => 'Elektrik',
            ];

            $kod_bidang = [
                0 => 'B01 - Kategori B - IBS:Sistem Konkrit Pasang Siap',
                1 => 'B02 - Kategori B - IBS:Sistem Kerangka Keluli',
                2 => 'CE01 - Kategori CE - Pembinaan Jalan dan Pavmen',
                3 => 'CE02 - Kategori CE - Pembinaan Jambatan',
            ];

            $status_bumiputra = [
                0 => 'Tidak',
                1 => 'Ya',
            ];

            $kod_bidang_kkm = [
                0 => '010101 - Bahan Bacaan terbitan luar negara',
                1 => '010102 - Bahan Bacaan',
                2 => '010103 - Penerbitan Elektronik Atas Talian',
                3 => '010104 - Bahan Penerbitan Elektronik Dan Muzik/Lagu (siap cetak)',
            ];

            return view('osc.approval.viewSyarikat',
                compact('jenis_penyertaan', 'jenis_pendaftaran', 'jenis_milikan_pejabat', 'negeri', 'parlimen', 'gred', 'jenis',
                    'kod_bidang', 'status_bumiputra', 'kod_bidang_kkm')
            );
        }
        else if($type == 'kkm'){
            $status_bumiputra = [
                0 => 'Tidak',
                1 => 'Ya',
            ];

            $kod_bidang_kkm = [
                0 => '010101 - Bahan Bacaan terbitan luar negara',
                1 => '010102 - Bahan Bacaan',
                2 => '010103 - Penerbitan Elektronik Atas Talian',
                3 => '010104 - Bahan Penerbitan Elektronik Dan Muzik/Lagu (siap cetak)',
            ];

            return view('osc.approval.viewKKM',
                compact('status_bumiputra', 'kod_bidang_kkm')
            );
        }
        else if($type == 'ppk'){
            $gred = [
                0 => 'G1',
                1 => 'G2',
            ];

            $jenis = [
                0 => 'Awam, Bangunan & Mekanikal',
                1 => 'Mekanikal',
                2 => 'Awam & Bangunan',
                3 => 'Elektrik',
            ];

            $kod_bidang = [
                0 => 'B01 - Kategori B - IBS:Sistem Konkrit Pasang Siap',
                1 => 'B02 - Kategori B - IBS:Sistem Kerangka Keluli',
                2 => 'CE01 - Kategori CE - Pembinaan Jalan dan Pavmen',
                3 => 'CE02 - Kategori CE - Pembinaan Jambatan',
            ];

            return view('osc.approval.viewPPK',
                compact('gred', 'jenis', 'kod_bidang')
            );
        }
        else{
            return view('osc.approval.index'
            );
        }
    }
}
