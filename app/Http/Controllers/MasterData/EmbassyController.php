<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Embassy;
use App\Models\EmbassyPermission;
use App\Models\Service;
use Illuminate\Http\Request;

class EmbassyController extends Controller
{
    // Index
    public function index()
    {
        return view('masterdata.embassy.index');
    }

    // Datatable
    public function datatable()
    {
        $data = Embassy::all();

        // return the data
        return datatables()::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                if (auth()->user()->US_RLCode == 'ADMIN') {
                    $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text"
                    id="delete" data-id="' . $row->EMID . '" data-url="' . route('masterData.embassy.delete', [$row->EMID]) . '">
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
            ->editColumn('EMCode', function ($row) {
                return '<a href="' . route('masterData.embassy.edit', [$row->EMID]) . '">' . $row->EMCode . '</a>';
            })
            ->editColumn('EMName', function ($row) {
                return $row->EMName;
            })
            ->editColumn('EM_CTCode', function ($row) {
                return $row->country->CTDesc;
            })
            ->editColumn('EMActive', function ($row) {
                if ($row->EMActive == 1) {
                    return '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal" style="pointer-events: none;"><i class="material-icons">check</i></a>';
                } else {
                    return '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text" style="pointer-events: none;"><i class="material-icons">clear</i></a>';
                }
            })
            ->editColumn('EMCD', function ($row) {
                return [
                    'display' => $row->EMCD->format('d/m/Y H:i'),
                    'timestamp' => $row->EMCD->timestamp
                ];
            })
            ->rawColumns(['action', 'EMCode', 'EMName', 'EM_CTCode', 'EMActive', 'EMCD'])
            ->make(true);
    }

    // Create
    public function create()
    {
        // Null on creation
        $embassy = null;

        // Status
        $isActive = [
            '1' => 'Active',
            '0' => 'Inactive'
        ];

        // Get the country
        $embassyCountry = Country::get()->pluck('CTDesc', 'CTCode');

        // Get embassy services
        $services = Service::all();

        // Check the purposes if it exists in database
        $checkedServices = '';
        foreach ($services as $service) {
            $checkedServices .= '<div class="input-field col m3 s6 form-group"> <label for="permission-' . $service->SVID . '"> <input type="checkbox" name="permission[]" value="' . $service->SVCode . '" id="permission-' . $service->SVID . '"> <span>' . $service->SVDesc . '</span> </label> </div>';
        }

        return view('masterData.embassy.form', compact('embassy', 'isActive', 'embassyCountry', 'services', 'checkedServices'));
    }

    // Store
    public function store(Request $request)
    {
        // Messages
        $messages = [
            'EMCode.required' => 'Embassy Code is required',
            'EMName.required' => 'Embassy Name is required',
            'EM_CTCode.required' => 'Country is required',
            'EMActive.required' => 'Status is required',
            'permission.required' => 'At least one service is required',
        ];

        // Validation
        $validation = [
            'EMCode' => 'required',
            'EMName' => 'required',
            'EM_CTCode' => 'required',
            'EMActive' => 'required',
            'permission' => 'required',
        ];

        // Validate
        $request->validate($validation, $messages);

        // Store
        $embassy = Embassy::where('EMCode', $request->EMCode)->first();

        // Check if embassy exists
        if ($embassy != null) {
            return response()->json([
                'error' => '1',
                'message' => 'Embassy code already exists'
            ], 400);
        }

        try {
            // Store Embassy
            Embassy::create([
                'EMCode' => $request->EMCode,
                'EMName' => $request->EMName,
                'EM_CTCode' => $request->EM_CTCode,
                'EMActive' => $request->EMActive,
                'EMCD' => now(),
                'EMMD' => now(),
                'EMCB' => auth()->user()->USCode,
            ]);

            // Store Permission
            foreach ($request->permission as $permission) {
                EmbassyPermission::create([
                    'EMP_EMCode' => $request->EMCode,
                    'EMP_SVCode' => $permission,
                    'EMPMD' => now(),
                    'EMPCB' => auth()->user()->USCode,
                ]);
            }

            // Return
            return response()->json([
                'success' => '1',
                'redirect' => route('masterData.embassy.index'),
                'message' => 'Embassy successfully created'
            ], 200);
        } catch (\Exception $e) {
            // Return error
            return response()->json([
                'error' => '1',
                'message' => 'Embassy failed to create' . $e->getMessage()
            ], 400);
        }
    }

    // Edit
    public function edit($id)
    {
        // Get the embassy
        $embassy = Embassy::find($id);

        // Status
        $isActive = [
            '1' => 'Active',
            '0' => 'Inactive'
        ];

        // Get the country
        $embassyCountry = Country::get()->pluck('CTDesc', 'CTCode');

        // Get embassy services
        $services = Service::all();

        // Get embassy permission
        $embassyPermission = EmbassyPermission::where('EMP_EMCode', $embassy->EMCode)->get()->pluck('EMP_SVCode')->toArray();

        // Check the purposes if it exists in database
        $checkedServices = '';
        foreach ($services as $service) {
            if (in_array($service->SVCode, $embassyPermission)) {
                $checkedServices .= '<div class="input-field col m3 s6 form-group"><label for="permission-'.$service->SVID.'"> <input type="checkbox" name="permission[]"value="' . $service->SVCode . '" id="permission-'.$service->SVID.'" checked="checked"> <span>' . $service->SVDesc . '</span> </label></div>';
            } else {
                $checkedServices .= '<div class="input-field col m3 s6 form-group"> <label for="permission-'.$service->SVID.'"> <input type="checkbox" name="permission[]" value="' . $service->SVCode . '" id="permission-'.$service->SVID.'"> <span>' . $service->SVDesc . '</span> </label> </div>';
            }
        }

        return view('masterData.embassy.form', compact('embassy', 'isActive', 'embassyCountry', 'services', 'checkedServices'));
    }

    // Update
    public function update(Request $request, $id)
    {
        // Messages
        $messages = [
            'EMCode.required' => 'Embassy Code is required',
            'EMName.required' => 'Embassy Name is required',
            'EM_CTCode.required' => 'Country is required',
            'EMActive.required' => 'Status is required',
        ];

        // Validation
        $validation = [
            'EMCode' => 'required',
            'EMName' => 'required',
            'EM_CTCode' => 'required',
            'EMActive' => 'required',
        ];

        // Validate
        $request->validate($validation, $messages);

        try {
            // Update Embassy
            Embassy::where('EMID', $id)->update([
                'EMCode' => $request->EMCode,
                'EMName' => $request->EMName,
                'EM_CTCode' => $request->EM_CTCode,
                'EMActive' => $request->EMActive,
                'EMMD' => now(),
                'EMCB' => auth()->user()->USCode,
            ]);

            // Delete Embassy Permission
            EmbassyPermission::where('EMP_EMCode', $request->EMCode)->delete();

            // Store Permission
            foreach ($request->permission as $permission) {
                EmbassyPermission::create([
                    'EMP_EMCode' => $request->EMCode,
                    'EMP_SVCode' => $permission,
                    'EMPMD' => now(),
                    'EMPCB' => auth()->user()->USCode,
                    'EMPMB' => auth()->user()->USCode,
                ]);
            }

            // Return
            return response()->json([
                'success' => '1',
                'redirect' => route('masterData.embassy.index'),
                'message' => 'Embassy successfully updated'
            ], 200);
        } catch (\Exception $e) {
            // Return error
            return response()->json([
                'error' => '1',
                'message' => 'Embassy failed to update' . $e->getMessage()
            ], 400);
        }
    }

    // Delete
    public function delete($id)
    {
        // Get the embassy
        $embassy = Embassy::find($id);

        // Check if embassy exists
        if ($embassy == null) {
            return response()->json([
                'error' => '1',
                'message' => 'Embassy does not exist'
            ], 400);
        }

        try {
            // Delete embassy permission
            EmbassyPermission::where('EMP_EMCode', $embassy->EMCode)->delete();

            // Delete embassy
            $embassy->delete();

            // Return
            return response()->json([
                'success' => '1',
                'message' => 'Embassy successfully deleted',
                'redirect' => route('masterData.embassy.index'),
            ], 200);
        } catch (\Exception $e) {
            // Return error
            return response()->json([
                'error' => '1',
                'message' => 'Embassy failed to delete' . $e->getMessage()
            ], 400);
        }
    }
}
