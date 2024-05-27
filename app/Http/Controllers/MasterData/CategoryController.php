<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Category;
use Carbon\Carbon;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Validator;
use Auth;


class CategoryController extends Controller
{
    public function index(){
		
        return view('masterData.category.index');
    }

    public function create(){

        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no')
        ];
		
        return view('masterData.category.form',compact('isActive'));
    }

    public function store(Request $request){

        $messages = [
            'categoryCode.required' => trans('Category Code is required'),
            'categoryDesc.required'	=> trans('Category Description is required'),
        ];

        $validation = [
            'categoryCode'	=> 'required',
            'categoryDesc' 	=> 'required',
        ];

        $request->validate($validation, $messages);
		

        $category = Category::where('CGCode',$request->categoryCode)->first();
        if ($category != null){
            return response()->json([
                'error' => '1',
                'message' => trans('message.invalid.category')
            ], 400);
        }

        try {
            DB::beginTransaction();

            $category = new Category();
            $category->CGCode        = $request->categoryCode ?? '';
            $category->CGDesc        = $request->categoryDesc ?? '';
            $category->CGActive      = $request->isActive;
            $category->CGCB          = Auth::user()->USCode;
            $category->save();

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Add new vaccine brand was failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.category.index'),
            'message' => trans('message.category.create')
        ]);
    }

    public function edit($id){
		
        $isActive = [
            1 => trans('message.yes') ,
            0 => trans('message.no') 
        ];

        $category = Category::find($id);
      
        return view('masterData.category.form',compact('category','isActive'));
    }

    public function update(Request $request,$id) {

        $messages = [
            'categoryCode.required' => trans('Category Code is required'),
            'categoryDesc.required'	=> trans('Category Description is required'),
        ];

        $validation = [
            'categoryCode'	=> 'required',
            'categoryDesc' 	=> 'required',
        ];

        $request->validate($validation, $messages);
		
        try {
            DB::beginTransaction();

            $category = Category::find($id);

            $category->CGCode        = $request->categoryCode ?? '';
            $category->CGDesc        = $request->categoryDesc ?? '';
            $category->CGActive      = $request->isActive;
            $category->CGMB          = Auth::user()->USCode;
            $category->save();
			
            DB::commit();
        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
				'message' => trans('Update was failed !').$e->getMessage()
            ], 400);
        }
		
		return response()->json([
			'success' => '1',
			'redirect' => route('masterData.category.index'),
			'message' => trans('message.category.update')
		]);
    }

    public function datatable(){

        $categoryData = Category::orderby('CGID','asc')->get();

        $data = [];

        if(isset($categoryData) && count($categoryData)>0) {
            foreach ($categoryData as $x => $category) {

                array_push($data, [
                    'categoryID'	=> (float) $category->CGID ,
                    'code'		    => $category->CGCode ?? '',
                    'desc'			=> $category->CGDesc ?? '',
                    'isActive'		=> $category->CGActive ?? 0,
                    'createdBy'		=> $category->CGCB ?? 0,
                    'createdDate'	=> isset($category->CGCD) ? carbon::parse($category->CGCD)->format('d-m-Y H:i') : null,
                    'modifyBy'		=> $category->CGMB ?? 0,
                    'modifyDate'	=> isset($category->CGMD) ? carbon::parse($category->CGMD)->format('d-m-Y H:i') : null,
                ]);
            }
        }

        return datatables()->of($data)
            ->addIndexColumn()
            ->editColumn('code', function ($row) {
                return '<a href="'.route('masterData.category.edit', [$row['categoryID'] ]).'">'.$row['code'] .'</a>';
            })
            ->addColumn('isActive', function ($row) {
                if($row['isActive'] == 1) {
                    return '<a class="mb-6 btn-floating waves-effect waves-light gradient-45deg-green-teal"><i class="material-icons">check</i></a>';
                }else{
                    return '<a class="btn-floating mb-6 btn-flat waves-effect waves-light red darken-4 white-text"><i class="material-icons">clear</i></a>';
                }
            })
            ->addColumn('action', function ($row) {
                $data = '<a type="button" class="btn-floating mb-1 btn-flat waves-effect waves-light red darken-4 white-text" 
                    id="delete" data-id="' . $row['categoryID'] . '" data-url="' . route('masterData.category.delete', [$row['categoryID']]) . '">
                         <i class="material-icons">delete</i>
                         </a>';
                return $data;
            })
            ->rawColumns(['code','isActive', 'action'])
            ->make(true);
    }


    public function delete(Request $request){
        try {
            DB::beginTransaction();

            $category = Category::find($request->id);
            $category->delete();

            DB::commit();

        }catch (\Throwable $e) {
            DB::rollback();

            return response()->json([
                'error' => '1',
                'message' => trans('Category delete failed !').$e->getMessage()
            ], 400);
        }

        return response()->json([
            'success' => '1',
            'redirect' => route('masterData.category.index'),
            'message' => trans('message.category.delete')
        ]);
    }

}