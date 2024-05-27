<?php

use App\User;
use App\Models\UserDetail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class UpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create Permissions
        $permissions = [
            "parking" => [
                'parking_management' => [
                    'edit_parking_session',
                    'view_parking_payment_top_up',
                    'edit_parking_payment_top_up',
                ],
            ]
        ];
        foreach ($permissions as $key => $permission) {
            foreach ($permission as $k => $p) {
                if(is_array($p) && count($p)>0) {
                    foreach ($p as $l => $j) {
                        $permisi = new Permission();
                        $permisi -> name = $j;
                        $permisi -> group = $key;
                        $permisi -> sub_group = $k;
                        $permisi -> guard_name ='web';
                        $permisi -> save();
                    }
                }
                else{
                    $permisi = new Permission();
                    $permisi -> name = $p;
                    $permisi -> group = $key;
                    $permisi -> sub_group = '';
                    $permisi -> guard_name ='web';
                    $permisi -> save();
                }
            }
        }

        $role = Role::whereIn('name', ['Developer','Superadmin'])->get();
        foreach ($role as $item) {
            if($item->name == 'Superadmin' || $item->name == 'Developer') {
                // assign all permissions
                $item->syncPermissions(Permission::all());
            } else {
                // for others by default only read access
                $item->syncPermissions(Permission::where('name', 'LIKE', 'lihat_%')->get());
            }

        }


    }
}
