<?php

use App\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class AclSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run()
	{
		// Create Permissions
		$permissions = [
            "main_menu" => [
				"general" => [
					'dashboard',
					'setup',
					'report',
				],
            ],
            "setup" => [
                'user' => [
                    'view_user',
                    'edit_user',
                    'delete_user',
                ],
                'operator' => [
                    'view_operator',
                    'edit_operator',
                    'delete_operator',
                ],
                'company group' => [
                    'view_company_group',
                    'edit_company_group',
                    'delete_company_group',
                ],
                'company' => [
                    'view_company',
                    'edit_company',
                    'delete_company',
                ],
                'category' => [
                    'view_category',
                    'edit_category',
                    'delete_category',
                ],
                'product' => [
                    'view_product',
                    'edit_product',
                    'delete_product',
                ],
            ],
			"report" => [
                'view_sales_report',
            ],
		];
		
		foreach ($permissions as $pkey => $grouplist) {
			foreach ($grouplist as $gKey => $subgrouplist) {
                if(is_array($subgrouplist) && count($subgrouplist)>0) {	
                    foreach ($subgrouplist as $skey => $namelist) {	
					
                       $permisi = Permission::where('PermissionName',$namelist)
									->where('SubGroup',$gKey)
									->where('MainGroup',$pkey)
									->first();
						if(Is_Null($permisi)){
							$permisi = new Permission();
						}
						$permisi -> PermissionName = $namelist;
						$permisi -> SubGroup = $gKey;
						$permisi -> MainGroup = $pkey;
						$permisi -> GuardName ='web';
						$permisi -> save();
                    }
                }
                else{
				   $permisi = Permission::where('PermissionName',$subgrouplist)
								->where('SubGroup','')
								->where('MainGroup',$pkey)
								->first();
					if(Is_Null($permisi)){
						$permisi = new Permission();
					}	
					$permisi -> PermissionName = $subgrouplist;
					$permisi -> SubGroup = '';
					$permisi -> MainGroup = $pkey;
                    $permisi -> GuardName ='web';
                    $permisi -> save();
                }
            }
		}

		// Create Roles
		$roles = [
			'Administrator',
			'Management',
			'Manager',
			'Operator',
		];

		foreach($roles as $role) {
			$role = Role::firstOrCreate(['PermissionName' => trim($role), 'GuardName' => 'web']);

			if($role->RoleName == 'Administrator') {
				// assign all permissions
				$role->syncPermissions(Permission::all());
			} else {
				// for others by default only read access
				$role->syncPermissions(Permission::where('name', 'LIKE', 'lihat_%')->get());
			}
		}

		// Create Administrator
		$user = User::where('UserCode','SA')->first();
		if(Is_Null($user)){
			$user = new User();
		}			
        $user->UserCode = 'SA';
		$user->UserName = 'System Admin';
		$user->Email    = 'admin@ucpos.com';
		$user->password = Hash::make('123456');
		$user->save();

		$user->assignRole('Administrator');

		// Create Management
		$user = User::where('UserCode','MGMT')->first();
		if(Is_Null($user)){
			$user = new User();
		}			
        $user->UserCode = 'MGMT';
		$user->UserName = 'Management';
		$user->Email    = 'mgmt@ucpos.com';
		$user->password = Hash::make('123456');
		$user->save();

		$user->assignRole('Management');

		// Create admin user
		$user			= User::where('email','admin@admin.com')->first();
		if(Is_Null($user)){
			$user = new User();
		}								
		$user->name     = 'admin';
		$user->email    = 'admin@admin.com';
		$user->password = Hash::make('123456');
		$user->save();

		$user->assignRole('Admin');
	}
}
