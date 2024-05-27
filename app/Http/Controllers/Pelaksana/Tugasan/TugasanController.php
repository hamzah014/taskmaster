<?php

namespace App\Http\Controllers\Pelaksana\Tugasan;

use App\Http\Controllers\Controller;
use App\Models\SSMCompany;
use App\Models\TugasanPelaksana;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Yajra\DataTables\DataTables;


use App\Http\Requests;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingProposal;
use App\Models\BoardMeetingTender;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Tender;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Session;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use App\Services\DropdownService;
use App\Models\TenderApplication;
use App\Models\TenderFormDetail;
use App\Models\TenderProcess;
use App\Models\TenderApplicationAuthSign;
use App\Models\TenderDetail;
use App\Models\TenderProposal;
use App\Models\TenderProposalDetail;
use App\Models\TenderProposalSpec;
use App\Models\FileAttach;

class TugasanController extends Controller
{
    public function __construct(DropdownService $dropdownService)
    {
        $this->dropdownService = $dropdownService;
    }

    public function index(){

        return view('pelaksana.tugasan.index');
    }

    public function tugasanDatatable(Request $request){

        $data = TugasanPelaksana::
        select(['RefNo', 'RefDate', 'Type', 'Status', 'Status2', 'Status3', 'Status4'])
            ->where(function ($query) {
                $query->where(function ($subquery) {
                    $subquery->where('Type', 'PTD')->where('Status', 'NEW');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM1V')->whereNull('Status2');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM2V')->whereNull('Status2');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'EM3V')->whereNull('Status2');
                    $subquery->orWhere('Type', 'PTD')->where('Status', 'REVISE')->whereNull('Status2');
                })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'TD')->where('Status', 'OT');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PT')->whereIn('Status', ['S']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PTKM')->where('Status', 'KO');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'KOM')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PM')->where('Status', 'I');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PC')->whereIn('Status', ['SM', 'AP']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PC')
                            ->whereIn('Status', ['CRT'])
                            ->where(function ($subsubquery) {
                                $subsubquery->where('Status2', 'SM')
                                    ->orWhereNull('Status2');
                            });
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'CM')->where('Status', 'NEW');
                    })
//                ->orWhere(function ($subquery) {
//                    $subquery->where('Type', 'EOT')->where('Status', 'SUBMIT')->where('Status2', 'LD')->where('Status3', 0);
//                })
//                ->orWhere(function ($subquery) {
//                    $subquery->where('Type', 'EOT')->where('Status', 'NEW')->where('Status3', 1);
//                })
//                ->orWhere(function ($subquery) {
//                    $subquery->where('Type', 'EOT')->where('Status', 'REVIEW')->where('Status2', 'LD')->where('Status3', 1);
//                })
//                ->orWhere(function ($subquery) {
//                    $subquery->where('Type', 'EOT')->where('Status', 'REVIEW')->where('Status2', 'ES')->where('Status3', 1);
//                })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'EOT2')->whereIn('Status', ['RQ', 'EOTN', 'EOTV', 'AJKV', 'PV', 'RQA', 'EOTA']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'MEA')->where('Status', 'NEW');
                    })
//                ->orWhere(function ($subquery) {
//                    $subquery->where('Type', 'VO')->where('Status', ['NEW', 'REVIEW']);
//                })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'VO2')->whereIn('Status', ['RQS', 'VON', 'VOV', 'AJKV', 'PV', 'VOA']);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'MVA')->where('Status', 'NEW');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'PTSAK')->where('Status', 'LAC');
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('Type', 'SAK')->whereIn('Status', ['NEW','UPLOAD']);
                    });
            })
            ->orderBy('RefDate', 'DESC')
            ->get();

        return datatables()->of($data)
            ->editColumn('RefNo', function($row) {

                // if($row->Type == 'PTD' && $row->Status  == 'NEW'){
                if($row->Type == 'PTD' && in_array($row->Status, ['NEW','EM1V','EM2V','EM3V','REVISE'])){
                    $route = route('pelaksana.projectTender.edit',[$row->RefNo]);
                }
                else if($row->Type == 'TD' && $row->Status  == 'OT'){
                    $route = route('pelaksana.tender.penilaian',[$row->RefNo]);
                }
                else if($row->Type == 'PT' && $row->Status  == 'S'){
                    $route = route('pelaksana.project.view',[$row->RefNo]);
                }
                // else if($row->Type == 'PT' && $row->Status == 'N' && $row->Status3  == 'SAK'){
                //     $route = route('pelaksana.sak.create',[$row->RefNo]);
                // }
                else if($row->Type == 'PTKM' && $row->Status  == 'KO'){
                    $route = route('pelaksana.meeting.kickoff.create',['projectNo'=>$row->RefNo]);
                }
                else if($row->Type == 'KOM' && $row->Status  == 'NEW'){
                    $route = route('pelaksana.meeting.kickoff.edit',[$row->RefNo]);
                }
                else if($row->Type == 'PM' && $row->Status  == 'I'){
                    $route = route('pelaksana.project.milestone.review',[$row->RefNo]);
                }
                else if($row->Type == 'PC' && $row->Status  == 'SM'){
                    $route = route('pelaksana.claim.review',[$row->RefNo]);
                }
                else if($row->Type == 'PC' && $row->Status  == 'AP'){
                    $route = route('pelaksana.meeting.create',['claimNo' => $row->RefNo]);
                }
                else if($row->Type == 'CM' && $row->Status  == 'NEW'){
                    $route = route('pelaksana.meeting.edit',[$row->RefNo]);
                }
                else if($row->Type == 'PC' && $row->Status  == 'CRT' && $row->Status2  == NULL){
                    $route = route('pelaksana.claim.review',[$row->RefNo]);
                }
                else if($row->Type == 'PC' && $row->Status  == 'CRT' && $row->Status2  == 'SM'){
                    $route = route('pelaksana.claim.review',[$row->RefNo]);
                }
//                else if($row->Type == 'EOT' && $row->Status  == 'SUBMIT' && $row->Status2  == 'LD' && $row->Status3  == 0){
//                    $route = route('pelaksana.eot.view',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.editPaidService',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.editPaidService',[$row->RefNo]);
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'NEW'){
//                    $route = route('pelaksana.vo.edit',[$row->RefNo]);
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'REVIEW'){
//                    $route = route('pelaksana.VO.edit',[$row->RefNo]);
//                }
                elseif($row->Type == 'VO2' && in_array($row->Status, ['RQS','VON', 'VOV', 'AJKV','PV'])){
                    $route = route('pelaksana.vo.edit',[$row->RefNo]);
                }
                else if($row->Type == 'PTSAK' && $row->Status  == 'LAC'){
                    $route = route('pelaksana.sak.create',[$row->RefNo]);
                }
                else if($row->Type == 'SAK' && in_array($row->Status, ['NEW', 'UPLOAD'])){
                    $route = route('pelaksana.sak.edit',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'RQ'){
                    $route = route('pelaksana.eot.view',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['RQA', 'EOTA']) ){
                    $route = route('pelaksana.meeting.ajk.create', ['type'=>'MEA', 'refNo' => $row->RefNo] );
                }
                else if($row->Type == 'EOT2' && $row->Status4  == '1' && $row->Status  == 'AJKV'&& $row->Status3  == '0'){
                    $route = route('pelaksana.eot.editNoPaidLD',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status4  == '1' && $row->Status  == 'AJKV'&& $row->Status3  == '1'){
                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status2  == 'LD' && $row->Status3  == '0' && in_array($row->Status, ['EOTV'])){
                    $route = route('pelaksana.eot.editNoPaidLD',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status2  == 'ES' && $row->Status3  == '0' && in_array($row->Status, ['EOTV'])){
                    $route = route('pelaksana.eot.editNoPaidLD',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status2  == 'LD' && $row->Status3  == '1' && in_array($row->Status, ['EOTN', 'EOTV', 'PV'])){
                    $route = route('pelaksana.eot.create.eotBerbayar',[$row->RefNo]);
                }
                else if($row->Type == 'EOT2' && $row->Status2  == 'ES' && $row->Status3  == '1' && in_array($row->Status, ['EOTN', 'EOTV', 'PV'])){
                    $route = route('pelaksana.eot.editPaidService',[$row->RefNo]);
                }
                else if($row->Type == 'MEA' && $row->Status  == 'NEW'){
                    $route = route('pelaksana.meeting.ajk.edit',[$row->RefNo]);
                }
                else if($row->Type == 'VO2' && in_array($row->Status, ['VOA']) ){
                    $route = route('pelaksana.meeting.ajk.create', ['type'=>'MVA', 'refNo' => $row->RefNo] );
                }
                else if($row->Type == 'MVA' && $row->Status  == 'NEW'){
                    $route = route('pelaksana.meeting.ajk.edit',[$row->RefNo]);
                }
                else{
                    $route = '#';
                }

                $result = '<a class="text-decoration-underline" href="'.$route.'">'.$row->RefNo.'</a>';

                return $result;
            })
            ->editColumn('RefDate', function($row) {

                $result = '-';

                if(isset($row->RefDate)){
                    $result = \Carbon\Carbon::parse($row->RefDate)->format('d/m/Y H:i');
                }

                return $result;
            })
            ->addColumn('Arahan', function($row) {

                $result = '-';

                if($row->Type == 'PTD' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Projek Tender Dihantar.';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM1V'){
                    $result = 'Menunggu Projek Tender disemak semula dan dihantar ke Mesyuarat Pengesahan 1 Cadangan Projek.';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM2V'){
                    $result = 'Menunggu Projek Tender disemak semula dan dihantar ke Mesyuarat Pengesahan 2 Cadangan Projek.';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'EM3V'){
                    $result = 'Menunggu Projek Tender disemak semula dan dihantar ke Mesyuarat Kelulusan Cadangan Projek.';
                }
                elseif($row->Type == 'PTD' && $row->Status  == 'REVISE'){
                    $result = 'Penyediaan semula Cadangan Projek.';
                }
                else if($row->Type == 'TD' && $row->Status  == 'OT'){
                    $result = 'Penilaian Teknikal.';
                }
                else if($row->Type == 'PTKM' && $row->Status  == 'KO'){
                    $result = 'Mesyuarat KickOff.';
                }
                else if($row->Type == 'PT' && $row->Status  == 'S'){
                    $result = 'Semakan Projek Yang Dihantar.';
                }
                // else if($row->Type == 'PT' && $row->Status == 'N' && $row->Status3  == 'SAK'){
                //     $result = 'Penyediaan Surat Arahan Kerja.';
                // }
                else if($row->Type == 'KOM' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat KickOff Dihantar.';
                }
                else if($row->Type == 'PM' && $row->Status  == 'I'){
                    $result = 'Semakan Milestone Projek.';
                }
                else if($row->Type == 'PC' && $row->Status  == 'SM'){
                    $result = 'Semakan Tuntutan Projek.';
                }
                else if($row->Type == 'PC' && $row->Status  == 'AP'){
                    $result = 'Mesyuarat Tuntutan.';
                }
                else if($row->Type == 'CM' && $row->Status  == 'NEW'){
                    $result = 'Keputusan Mesyuarat Tuntutan.';
                }
                else if($row->Type == 'PC' && $row->Status  == 'CRT' && $row->Status2  == NULL){
                    $result = 'Tuntutan Lulus Bersyarat.';
                }
                else if($row->Type == 'PC' && $row->Status  == 'CRT' && $row->Status2  == 'SM'){
                    $result = 'Semakan Tuntutan Lulus Bersyarat.';
                }
//                else if($row->Type == 'EOT' && $row->Status  == 'SUBMIT' && $row->Status2  == 'LD'){
//                    $result = 'Semakan Lanjutan Masa.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $result = 'Semak Semula Lanjutan Perkhidmatan Berbayar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'REVIEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $result = 'Semak Semula Lanjutan Masa Berbayar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'ES' && $row->Status3  == 1){
//                    $result = 'Menunggu Lanjutan Perkhidmatan Berbayar Dihantar.';
//                }
//                else if($row->Type == 'EOT' && $row->Status  == 'NEW' && $row->Status2  == 'LD' && $row->Status3  == 1){
//                    $result = 'Menunggu Lanjutan Masa Berbayar Dihantar.';
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'NEW'){
//                    $result = 'Menunggu Perubahan Kerja Dihantar.';
//                }
//                else if($row->Type == 'VO' && $row->Status  == 'REVIEW'){
//                    $result = 'Semak Semula Perubahan Kerja.';
//                }
                else if($row->Type == 'PTSAK' && $row->Status  == 'LAC'){
                    $result = 'Penyediaan Surat Arahan Kerja.';
                }
                else if($row->Type == 'SAK' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Surat Arahan Kerja Dihantar.';
                }
                else if($row->Type == 'SAK' && $row->Status  == 'UPLOAD'){
                    $result = 'Semakan Surat Arahan Kerja.';
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'RQ'){
                    $result = 'Semakan Lanjutan Masa.';
                }
                else if($row->Type == 'EOT2' && in_array($row->Status, ['RQA', 'EOTA'])){
                    $result = 'Mesyuarat AJK.';
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'EOTN'){
                    $result = 'Menunggu Lanjutan Masa Dihantar.';
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'EOTV'){
                    $result = 'Lanjutan Masa Dimohon Semak Semula.';
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'AJKV'){
                    $result = 'Semak Semula Dari Mesyuarat AJK.';
                }
                else if($row->Type == 'EOT2' && $row->Status  == 'PV'){
                    $result = 'Semak Semula Dari Perolehan.';
                }
                else if($row->Type == 'MEA' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat AJK Dihantar.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'RQS'){
                    $result = 'Maklumat Perubahan Kerja Telah Diterima.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'VON'){
                    $result = 'Menunggu Perubahan Kerja Dihantar.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'VOV'){
                    $result = 'Perubahan Kerja Dimohon Semak Semula.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'AJKV'){
                    $result = 'Semak Semula Dari Mesyuarat AJK.';
                }
                else if($row->Type == 'VO2' && $row->Status  == 'PV'){
                    $result = 'Semak Semula Dari Perolehan.';
                }
                else if($row->Type == 'VO2' && in_array($row->Status, ['VOA'])){
                    $result = 'Mesyuarat AJK.';
                }
                else if($row->Type == 'MVA' && $row->Status  == 'NEW'){
                    $result = 'Menunggu Mesyuarat AJK Dihantar.';
                }
                return $result;
            })
            ->rawColumns(['RefNo','RefDate', 'Arahan', 'Type', 'Status', 'Status2'])
            ->make(true);
    }

}
