<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Country;
use App\Models\State;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\User;
use App\Models\Customer;
use App\Models\FileAttach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Validator;
use Auth;


class CustomerController extends Controller
{
    public function index()
    {

        return view('masterData.customer.index');
    }

    public function create()
    {

        $isActive = [
            1 => trans('message.yes'),
            0 => trans('message.no')
        ];

        $customer = null;
        $profilePhotoURL = '';

        $state = [];

        $country = Country::where('CTActive', 1)
            ->orderby('CTDesc', 'asc')
            ->get()->pluck('CTDesc', 'CTCode');

        $mState = State::where('StateActive', 1)
            ->where('State_CTCode', 'MYS')
            ->orderby('StateDesc', 'asc')
            ->get()->pluck('StateDesc', 'StateCode');

        return view('masterData.customer.form', compact('isActive', 'customer', 'profilePhotoURL', 'country', 'state', 'mState'));
    }

    public function store(Request $request)
    {

        $messages = [
            'registerNo.required'              => trans('Register No is required'),
            'companyName.required'          => trans('Company Name is required'),
            'address1.required'             => trans('Address 1 is required'),
            'postcode.required'             => trans('Postcode is required'),
            'city.required'                 => trans('City is required'),
            'state.required'                  => trans('State is required'),
            'compAddress1.required'         => trans('Company Address 1 is required'),
            'compPostcode.required'         => trans('Company Postcode required'),
            'compCity.required'             => trans('Company City is required'),
            'compState.required'              => trans('Company State is required'),
            'password.required'            => trans('Password is required'),
        ];

        $validation = [
            'registerNo'             => 'required',
            'companyName'             => 'required',
            'address1'                => 'required',
            'postcode'                 => 'required',
            'city'                     => 'required',
            'state'                 => 'required',
            'compAddress1'            => 'required',
            'compPostcode'             => 'required',
            'compCity'                 => 'required',
            'compState'             => 'required',
            'password'                 => 'required|min:6|required_with:password-confirm|same:password-confirm',
            'password-confirm'        => 'nullable|min:6',
        ];

        $request->validate($validation, $messages);

        $user = Auth::user();

        $recordExists = Customer::where('CSEmail', $request->email)->first();
        if ($recordExists != null) {
            return response()->json([
                'error' => '1',
                'message' => 'Email already exists!'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $autoNumber = new AutoNumber();
            $customerCode = $autoNumber->generateCustomerCode();

            $customer = new Customer();
            $customer->CSCode        = $customerCode;
            $customer->CSName         = $request->name ?? '';
            $customer->CSEmail         = $request->email ?? '';
            $customer->CSPhoneNo     = $request->phone ?? '';

            $customer->CSStreet1         = $request->street1 ?? '';
            $customer->CSStreet2         = $request->street2 ?? '';
            $customer->CSPostcode         = $request->postcode ?? '';
            $customer->CSCity             = $request->city ?? '';
            $customer->CS_StateCode     = $request->state ?? '';
            $customer->CS_CTCode         = $request->country ?? '';

            $customer->CSMStreet1         = $request->mStreet1 ?? '';
            $customer->CSMStreet2         = $request->mStreet2 ?? '';
            $customer->CSMPostcode         = $request->mPostcode ?? '';
            $customer->CSMCity             = $request->mCity ?? '';
            $customer->CSM_StateCode     = $request->mState ?? '';

            $customer->CSActive         = $request->isActive;
            $customer->CSMB             = $user->USCode;
            $employer->save();

            $user = new User();
            $user->USCode       = $customerCode;
            $user->USPwd        = Hash::make($request->password ?? '123456');
            $user->USType       = 'CS';
            $user->USResetPwd   = 0;
            $user->USActive     = $request->isActive;
            $user->USCB         = $user->USCode;
            $user->save();

            if ($request->file != null) {

                //*** SAVE FILE TO STORAGE ***
                $folderPath = carbon::now()->format('ymd') . '\\user\\' . $user->USCode;
                $newFileExt = request()->file->getClientOriginalExtension();
                $newFileName = $user->USCode . '_' . time() . '.' . $newFileExt;

                //SAVE PHOTO
                $fileContent = file_get_contents($request->file);
                Storage::disk('fileStorage')->put($folderPath . '\\' . $newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode', $user->USCode)->first();
                if ($fileAttach == null) {
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB         = $user->USCode;
                }
                $fileAttach->FAFileType         = 'MU';
                $fileAttach->FA_UScode             = $user->USCode;
                $fileAttach->FAFilePath         = $folderPath . '\\' . $newFileName;
                $fileAttach->FAFileName         = $newFileName;
                $fileAttach->FAFileExtension     = $newFileExt;
                $fileAttach->FAMB                 = $user->USCode;
                $fileAttach->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Add new employer was failed !') . $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.customer.index'),
            'message' => 'Customer has been added successful !',
        ]);
    }

    public function edit($id)
    {

        $isActive = [
            1 => trans('message.yes'),
            0 => trans('message.no')
        ];

        $customer   = Customer::where('CSID', $id)->first();

        $state = State::where('StateActive', 1)
            ->where('State_CTCode', $customer->CS_CTCode)
            ->orderby('StateDesc', 'asc')
            ->get()->pluck('StateDesc', 'StateCode');

        $country = Country::where('CTActive', 1)
            ->where('CTCode', '!=', 'MYS')
            ->orderby('CTDesc', 'asc')
            ->get()->pluck('CTDesc', 'CTCode');

        $mState = State::where('StateActive', 1)
            ->where('State_CTCode', 'MYS')
            ->orderby('StateDesc', 'asc')
            ->get()->pluck('StateDesc', 'StateCode');

        $profilePhotoURL = '';

        $fileAttach = FileAttach::where('FA_USCode', $customer->CSCode)->where('FAActive', 1)->first();
        if ($fileAttach != null) {
            $profilePhotoURL =  config::get('app_url') . '/file/' . $fileAttach->FAFileName;
        }

        return view('masterData.customer.form', compact('isActive', 'customer', 'profilePhotoURL', 'country', 'state', 'mState'));
    }

    public function update(Request $request, $id)
    {

        $messages = [
            'name.required'         => trans('message.name.required'),
            'email.required'        => trans('message.email.required'),
            'email.email'           => trans('message.email.email'),
            'email.max'             => trans('message.email.max'),
            'email.unique'          => trans('message.email.unique'),
        ];

        $validation = [
            'name'                     => 'required',
            'email'                    => 'required|email|max:255',
            'street1'                 => 'required',
            'street2'                 => 'nullable',
            'postcode'                 => 'required',
            'city'                     => 'required',
            'state'                 => 'required',
            'country'                 => 'required',
            'mStreet1'                 => 'nullable',
            'mStreet2'                 => 'nullable',
            'mPostcode'             => 'required',
            'mCity'                 => 'required',
            'mState'                 => 'required',
            'resetPassword'         => 'nullable',
            'password'                 => 'nullable|min:6|required_with:password-confirm|same:password-confirm',
            'password-confirm'        => 'nullable|min:6',
        ];

        $request->validate($validation, $messages);

        $user = Auth::user();

        $customer = Customer::where('CSID', $id)->first();

        try {
            DB::beginTransaction();

            $customer->CSName         = $request->name ?? '';
            $customer->CSEmail         = $request->email ?? '';
            $customer->CSPhoneNo     = $request->phone ?? '';

            $customer->CSStreet1         = $request->street1 ?? '';
            $customer->CSStreet2         = $request->street2 ?? '';
            $customer->CSPostcode         = $request->postcode ?? '';
            $customer->CSCity           = $request->city ?? '';
            $customer->CS_StateCode     = $request->state ?? '';
            $customer->CS_CTCode         = $request->country ?? '';

            $customer->CSMStreet1         = $request->mStreet1 ?? '';
            $customer->CSMStreet2         = $request->mStreet2 ?? '';
            $customer->CSMPostcode         = $request->mPostcode ?? '';
            $customer->CSMCity             = $request->mCity ?? '';
            $customer->CSM_StateCode     = $request->mState ?? '';

            $customer->CSActive         = $request->isActive;
            $customer->CSMB             = $user->USCode;
            $customer->save();

            $user = User::where('USCode', $customer->CSCode)->first();
            if ($user == null) {
                $user = new User();
                $user->USCode          = $customer->CSCode;
                $user->USPwd        = Hash::make($request->password ?? '123456');
                $user->USType       = 'CS';
                $user->USResetPwd   = 0;
                $user->USCB         = $user->USCode;
            }
            if ($request->resetPassword == '1') {
                $user->USResetPwd   = $request->resetPassword;
                $password           = $request->password ?? '';
                $user->USPwd        = Hash::make($password);
            } else {
                $user->USResetPwd   = 0;
            }
            $user->USActive     = $request->isActive;
            $user->USMB         = $user->USCode;
            $user->save();


            if ($request->file != null) {

                //*** SAVE FILE TO STORAGE ***
                $folderPath = 'user';
                $newFileExt = request()->file->getClientOriginalExtension();
                $newFileName = $user->USCode . '_' . time() . '.' . $newFileExt;

                //SAVE PHOTO
                $fileContent = file_get_contents($request->file);
                Storage::disk('fileStorage')->put($folderPath . '\\' . $newFileName, $fileContent);

                $fileAttach = FileAttach::where('FA_USCode', $customer->CSCode)->first();
                if ($fileAttach == null) {
                    $fileAttach = new FileAttach();
                    $fileAttach->FACB         = $user->USCode;
                }
                $fileAttach->FAFileType         = 'MU';
                $fileAttach->FA_UScode             = $customer->CSCode;
                $fileAttach->FAFilePath         = $folderPath . '\\' . $newFileName;
                $fileAttach->FAFileName         = $newFileName;
                $fileAttach->FAFileExtension     = $newFileExt;
                $fileAttach->FAMB                 = $user->USCode;
                $fileAttach->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Failed !') . $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.customer.index'),
            'message' => trans('Update was successful !')
        ]);
    }

    public function datatable()
    {

        $customer = Customer::orderby('CSCD', 'desc')->get();

        return datatables()->of($customer)
            ->addIndexColumn()
            ->editColumn('CSCode', function ($row) {
                return '<a href="' . route('masterData.customer.edit', [$row->CSID]) . '">' . $row->CSCode . '</a>';
            })
            ->editColumn('CSActive', function ($row) {
                if ($row->CSActive == 1) {
                    return '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal"><i class="material-icons">check</i></a>';
                } else {
                    return '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text"><i class="material-icons">clear</i></a>';
                }
            })->editColumn('CSCD', function ($row) {
                return [
                    'display' => e(carbon::parse($row['BRF_DOB'])->format('d/m/Y H:i')),
                    'timestamp' => carbon::parse($row['BRF_DOB'])->timestamp
                ];
            })->addColumn('action', function ($row) {
                $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text"
                    id="delete" data-id="' . $row->CSID . '" data-url="' . route('masterData.customer.delete', [$row->CSID]) . '">
                         <i class="material-icons">delete</i>
                         </a>';
                return $data;
            })
            ->rawColumns(['CSCode', 'CSActive', 'action'])
            ->make(true);
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();
            $customer = Customer::where('CSID', $request->id)->first();
            $user = User::where('USCode', $customer->CSCode)->first();

            FileAttach::where('FA_USCode', $customer->CSCode)->delete();
            $user->delete();
            $customer->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Customer delete failed !') . $e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.customer.index'),
            'message' => trans('Customer delete was successful !')
        ]);
    }

    public function info($id)
    {
        $customer = Customer::where('CSID', $id)->first();

        return view('masterData.employer.info', compact('customer'));
    }


    public function populateState(Request $request)
    {
        $state = State::select('StateCode', 'StateDesc')
            ->where('State_CTCode', $request->country)
            ->where('StateActive', 1)
            ->orderby('StateDesc', 'asc')
            ->get();

        return response()->json($state);
    }
}
