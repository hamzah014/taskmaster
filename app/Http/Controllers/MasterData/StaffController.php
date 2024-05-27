<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Embassy;
use App\Models\EmbassyStaff;
use App\Models\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    // Index
    public function index()
    {
        return view('masterData.staff.index');
    }

    // Datatable
    public function datatable()
    {
        // Check user
        if (auth()->user()->US_RLCode == 'ADMIN') {
            // Get all the staff
            $data = EmbassyStaff::all();
        } else {
            // Get user info
            $user = EmbassyStaff::where('ESCode', auth()->user()->USCode)->first();

            // Get all the staff
            $data = EmbassyStaff::where('ES_EMCode', $user->ES_EMCode)->get();
        }

        // Return the data
        return datatables()::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                if (auth()->user()->US_RLCode == 'ADMIN' || auth()->user()->US_RLCode == 'EMBADMIN') {    // Admin
                    $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text"
                    id="delete" data-id="' . $row->ESID . '" data-url="' . route('masterData.staff.delete', [$row->ESID]) . '">
                        <i class="material-icons">delete</i>
                        </a>';
                    return $data;
                } else {
                    $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text" href="javascript:void();" style="pointer-events: none;">
                        <i class="material-icons">delete</i>
                        </a>';
                    return $data;
                }
            })
            ->editColumn('ESCode', function ($row) {
                return '<a href="' . route('masterData.staff.edit', [$row->ESID]) . '">' . $row->ESCode . '</a>';
            })
            ->editColumn('ESActive', function ($row) {
                if ($row->ESActive == 1) {
                    return '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal" style="pointer-events: none;"><i class="material-icons">check</i></a>';
                } else {
                    return '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text" style="pointer-events: none;"><i class="material-icons">clear</i></a>';
                }
            })
            ->editColumn('ESCD', function ($row) {
                return [
                    'display' => $row->ESCD->format('d/m/Y H:i'),
                    'timestamp' => $row->ESCD->timestamp
                ];
            })
            ->editColumn('USRole', function ($row) {
                return $row->user->role->RLName;
            })
            ->editColumn('ES_EMCode', function ($row) {
                return $row->embassy->EMName;
            })
            ->rawColumns(['ESCode', 'ESActive', 'action'])
            ->make(true);
    }

    // Create Embassy Admin
    public function create()
    {
        // Determine active status
        $isActive = [
            '1' => 'Active',
            '0' => 'Inactive'
        ];

        // Set the user to null
        $staff = null;

        // Get all the embassies
        $embassies = Embassy::get()->pluck('EMName', 'EMCode');

        // Get user
        $user = EmbassyStaff::where('ESCode', auth()->user()->USCode)->first();

        // Get current user
        $currentUser = auth()->user()->US_RLCode;

        // Get the user roles
        $roles = [
            'EMBADMIN' => 'Embassy Admin',
            'EMBSTAFF' => 'Embassy Staff'
        ];

        // Return the view
        return view('masterData.staff.form', compact('isActive', 'staff', 'embassies', 'roles', 'user', 'currentUser'));
    }

    // Store Embassy Admin
    public function store(Request $request)
    {
        // Message
        $message = [
            'name.required' => 'Please enter the name.',
            'embassy.required' => 'Please select the embassy.',
            'isActive.required' => 'Please select the active status.',
            'email.required' => 'Please enter the email.',
            'email.unique' => 'Email already exists.',
            'password.required' => 'Please enter the password.',
            'role.required' => 'Please select the role.',
        ];

        // Validation
        $validation = [
            'name' => 'required',
            'embassy' => 'required',
            'isActive' => 'required',
            'email' => 'required|unique:MSEmbassyStaff,ESEmail',
            'password' => 'required',
            'role' => 'required',
        ];

        // Validate
        $request->validate($validation, $message);

        // Store
        try {
            DB::beginTransaction();

            // Get the current staff numbering
            $staffNumber = EmbassyStaff::where('ESCode', 'like', 's%')->orderBy('ESCode', 'desc')->first()->ESCode;

            // Remove the 's' from the staff number
            $removeS = str_replace('s', '', $staffNumber);

            // Create ESCode
            $ESCode = 's' . str_pad($removeS + 1, 2, '0', STR_PAD_LEFT);

            // Create the staff
            $staff = new EmbassyStaff();
            $staff->ESCode = $ESCode;
            $staff->ES_EMCode = $request->embassy ?? null;
            $staff->ESName = $request->name ?? null;
            $staff->ESActive = $request->isActive ?? null;
            $staff->ESEmail = $request->email ?? null;
            $staff->ESPhoneNo = $request->phone ?? null;
            $staff->ESCD = now();
            $staff->ESMD = now();
            $staff->ESCB = auth()->user()->USCode;
            $staff->save();

            // Create the user based on the staff
            $user = new User();
            $user->USCode = $ESCode;
            $user->USPwd = Hash::make($request->password);
            $user->US_RLCode = $request->role;
            $user->USCB = auth()->user()->USCode;
            $user->USCD = now();
            $user->USMD = now();
            $user->save();

            // Commit the transaction
            DB::commit();

            // Return
            return response()->json([
                'success' => '1',
                'redirect' => route('masterData.staff.index'),
                'message' => 'Staff successfully created'
            ], 200);
        } catch (\Throwable $th) {
            // Rollback the transaction
            DB::rollBack();
            // Return error
            return response()->json([
                'error' => '1',
                'message' => 'Embassy failed to create' . $th->getMessage()
            ], 500);
        }
    }

    // Edit Embassy Admin/Staff
    public function edit($id)
    {
        // Get current user
        $currentUser = auth()->user()->US_RLCode;

        // Get user role
        $staff = EmbassyStaff::where('ESID', $id)->first();

        // Determine active status
        $isActive = [
            '1' => 'Active',
            '0' => 'Inactive'
        ];

        // Get the user
        $user = User::where('USCode', $staff->ESCode)->first();

        // Get all the embassies
        $embassies = Embassy::get()->pluck('EMName', 'EMCode');

        // Roles
        $roles = Role::where('RLCode', '=', 'EMBADMIN')->orWhere('RLCode', '=', 'EMBSTAFF')->get()->pluck('RLName', 'RLCode');

        // Return the view
        return view('masterData.staff.form', compact('isActive', 'user', 'embassies', 'roles', 'staff', 'currentUser'));
    }

    // Update Embassy Admin/Staff
    public function update(Request $request, $id)
    {
        // Validate the data
        $validatedData = $request->validate([
            'name' => 'required',
            'embassy' => 'required',
            'isActive' => 'required',
            'email' => 'required',
            'role' => 'required',
        ]);

        // Get the staff
        $staff = EmbassyStaff::where('ESID', $id)->first();

        // Update the staff
        $staff->ES_EMCode = $validatedData['embassy'];
        $staff->ESName = $validatedData['name'];
        $staff->ESActive = $validatedData['isActive'];
        $staff->ESEmail = $validatedData['email'];
        $staff->ESPhoneNo = $request->phone ?? null;
        $staff->ESMD = now();
        $staff->ESMB = auth()->user()->USCode;

        if ($staff->save()) {
            // Get the user
            $user = User::where('USCode', $staff->ESCode)->first();

            // Update the user
            $user->US_RLCode = $validatedData['role'];

            // Check if the reset password is checked
            if ($request->resetPassword == 1) {
                $user->USPwd = Hash::make($request['password']);
            }

            $user->USMD = now();
            $user->USMB = auth()->user()->USCode;

            if ($user->save()) {
                // Return the success message
                return response()->json([
                    'success' => '1',
                    'redirect' => route('masterData.staff.index'),
                    'message' => trans('Staff updated successfully!')
                ]);

                // dd('success update staff');
            } else {
                // Return the error message
                return response()->json([
                    'error' => '1',
                    'message' => trans('Staff creation failed!')
                ], 400);

                // dd('failed update staff');
            }
        } else {
            // Return the error message
            return response()->json([
                'error' => '1',
                'message' => trans('Staff creation failed!')
            ], 400);

            // dd('failed update staff');
        }
    }

    // Delete Embassy Admin/Staff
    public function destroy($id)
    {
        // Get the staff
        $staff = EmbassyStaff::where('ESID', $id)->first();

        // Delete the staff
        if ($staff->delete()) {
            // Get the user
            $user = User::where('USCode', $staff->ESCode)->first();

            // Delete the user
            if ($user->delete()) {
                // Return the success message
                return response()->json([
                    'success' => '1',
                    'redirect' => route('masterData.staff.index'),
                    'message' => trans('Staff updated successfully!')
                ]);

                // dd('success delete staff');
            } else {
                // Return the error message
                return response()->json([
                    'error' => '1',
                    'message' => trans('Staff creation failed!')
                ], 400);

                // dd('failed delete staff');
            }
        } else {
            // Return the error message
            return response()->json([
                'error' => '1',
                'message' => trans('Staff creation failed!')
            ], 400);

            // dd('failed delete staff');
        }
    }
}
