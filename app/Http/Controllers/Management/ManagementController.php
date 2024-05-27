<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AutoNumber;
use App\Providers\RouteServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\WebSetting;
use App\Services\DropdownService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Session;
use Yajra\DataTables\DataTables;

class ManagementController extends Controller
{
    
    public function index(){

        return view('management.index');

    }

    public function userDatatable(){

        $query = User::get();

        return $this->getUserDatatable($query);

        
    }
    
    public function searchUser(Request $request){ 

        try {
            
            $user = User::query();

            if ($request->filled('search_name')) {
                $user->where('USName', 'LIKE', '%' . $request->input('search_name') . '%');
            }

            if ($request->filled('search_email')) {
                $user->where('USEmail', 'LIKE', '%' . $request->input('search_email') . '%');
            }

            $query = $user->get();

            return $this->getUserDatatable($query);

    

        }catch (\Throwable $e) {

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Permohonan tidak berjaya!'.$e->getMessage()
            ], 400);
        }


    }
    
    public function getUserDatatable($query){

        $dropdownService = new DropdownService();

        return DataTables::of($query)
            ->addColumn('indexNo', function($row) use(&$count) {

                $count++;

                return $count;
            })
            ->editColumn('USActive', function($row){
                
                if($row->USActive == 0){
                    $result = '<span class="badge badge-outline badge-danger">Inactive</span>';
                }else{
                    $result = '<span class="badge badge-outline badge-success">Active</span>';

                }

                return $result;

            })
            ->addColumn('USRole', function($row) use($dropdownService){

                $userRole = $dropdownService->userRole();

                $result = $userRole[$row->US_RLCode] ?? "-";

                return $result;
            })
            ->addColumn('action', function($row){

                $route = route('management.user.edit',$row->USCode);

                if( Auth::user()->hasPermission('edit_user') ){
                
                    $result = '<a class="btn btn-secondary" href="'.$route.'" ><i class="text-dark fas fs-7 fa-eye"></i></a>';
                }
                else{
                    $result = "";
                }

                return $result;
            })
            ->rawColumns(['indexNo', 'USActive','USRole','action'])
            ->make(true);

    }

    public function create(){

        $dropdownService = new DropdownService();

        $userRole = $dropdownService->userRoleActive();

        return view('management.create', compact('userRole'));

    }

    public function store(Request $request){
        

        $messages = [
            'name.required' 		        => 'Name required.',
            'email.required' 		        => 'Email required.',
            'phoneNo.required' 		        => 'Phone Number required.',
            'role.required' 		        => 'Role required.',

        ];

        $validation = [
            'name' => 'required',
            'email' => 'required|email',
            'phoneNo' => 'required',
            'role' => 'required',
        ];
        
        $request->validate($validation, $messages);
        

        try {
            
            DB::beginTransaction();

            $user = Auth::user();

            $user = User::where('USEmail',$request->email)
                        ->where('USActive',1)
                        ->first();

            if($user){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Your email has been registered.'
                ], 400);

            }
            else{

                $roleStaff = $request->roleStaff;

                $autoNumber = new AutoNumber();
                
                $userCode = $autoNumber->generateUserCode();
                
                $randomPassword = $this->generateRandomPassword();
                
                $user = new User();
                $user->USCode       = $userCode;
                $user->USName       = $request->name ?? '';
                $user->USEmail      = $request->email ?? '';
                $user->USPwd        = Hash::make($randomPassword);
                $user->USResetPwd   = 0;
                $user->USType       = 'SU';
                $user->US_RLCode       = $request->role;
                $user->USPhoneNo       = $request->phoneNo;
                $user->USActive = 1;
                $user->USCB         = $userCode;
                $user->USMB         = $userCode;
                $user->save();

                $maskEmail = $this->maskEmail($request->email);

                // Send Email
                $emailData = array(
                    'email' => $user->USEmail,
                    'maskEmail' => $maskEmail,
                    'name' => $user->USName,
                    'password' => $randomPassword,
                    'domain' => config('app.url'),
                    'redirect' => route('login.index'),
                );
                
                try {
                    Mail::send(['html' => 'email.registerUserInformation'], $emailData, function($message) use ($emailData) {
                        $message->to($emailData['email'] ,$emailData['email'])->subject('Account Registration');
                    });

                } catch (\Exception $e) {
                    
                    return response()->json([
                        'error' => '1',
                        'message' => 'Failed send email! '.$e->getMessage()
                    ], 400);
                }

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('management.user.index'),
                    'message' => 'User successfully registered.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured! '.$e->getMessage()
            ], 400);
        }

    }
    
    public function edit($id){

        $dropdownService = new DropdownService();

        $inputDisabled = "";

        $userRole = $dropdownService->userRoleActive();
        $user = User::where('USCode', $id)->first();

        if($user->USActive == 0){
            $inputDisabled = "disabled";
        }

        return view('management.edit',compact('user','userRole', 'inputDisabled'));

    }

    public function update(Request $request, $id){
        

        $messages = [
            'name.required' 		        => 'Name required.',
            'email.required' 		        => 'Email required.',
            'phoneNo.required' 		        => 'Phone Number required.',
            'role.required' 		        => 'Role required.',

        ];

        $validation = [
            'name' => 'required',
            'email' => 'required|email',
            'phoneNo' => 'required',
            'role' => 'required',
        ];
        
        $request->validate($validation, $messages);

        try {
            
            DB::beginTransaction();

            $user = User::where('USCode',$id)
                        ->first();

            if(!$user){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Your account are not valid.'
                ], 400);

            }
            else{

                $userNow = Auth::user();

                $user->USName       = $request->name ?? '';
                $user->USEmail      = $request->email ?? '';
                $user->US_RLCode       = $request->role;
                $user->USPhoneNo       = $request->phoneNo;
                $user->USMB         = $userNow->USCode;
                $user->save();

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('management.user.index'),
                    'message' => 'User successfully updated.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Failed occured! '.$e->getMessage()
            ], 400);
        }
    }

    public function deactivateAccount(Request $request, $id){

        try {
            
            DB::beginTransaction();

            $user = User::where('USCode',$id)->first();

            if(!$user){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Account invalid.'
                ], 400);

            }
            else{

                $user->USDeactivateDate  = Carbon::now();
                $user->USDeactivateReason = $request->reason ?? "";
                $user->USActive = 0;
                $user->save();

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('management.user.edit',[$user->USCode]),
                    'message' => 'Account blocking success.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured!'.$e->getMessage()
            ], 400);
        }


    }

    public function activeAccount(Request $request, $id){

        try {
            
            DB::beginTransaction();

            $userNow = Auth::user();

            $user = User::where('USCode',$id)->first();

            if(!$user){
                    
                return response()->json([
                    'error' => '1',
                    'message' => 'Account invalid.'
                ], 400);

            }
            else{

                $user->USActive = 1;
                $user->USMB = $userNow->USCode;
                $user->save();

                DB::commit();

                return response()->json([
                    'success' => '1',
                    'redirect' => route('management.user.edit',[$user->USCode]),
                    'message' => 'Account activation success.'
                ]);


            }

        }catch (\Throwable $e) {
            DB::rollback();

            Log::info('ERROR', ['$e' => $e]);

            return response()->json([
                'error' => '1',
                'message' => 'Error occured! '.$e->getMessage()
            ], 400);
        }


    }

}
