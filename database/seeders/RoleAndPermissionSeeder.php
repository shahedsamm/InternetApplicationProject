<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
$adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
$citizenRole = Role::query()->firstOrCreate(['name' => 'citizen']);
$employeeRole = Role::query()->firstOrCreate(['name' => 'employee']);

        // Define permissions
        $permissions = [
            //Admin Permissions
            'complaint.store', 'complaint.update', 'complaint.destroy', 'complaint.show', 'complaint.index', 'complaint.export', 'complaint.stats',
            'user.store', 'user.update', 'user.destroy', 'user.show', 'user.index','citizen.complaint.create',
    'citizen.complaint.update','citizen.complaint.delete','citizen.complaint.list',
    'citizen.complaint.attachments', 'citizen.profile.update','citizen.profile.logout',
            'role_permission.store', 'role_permission.update', 'role_permission.destroy', 'role_permission.show', 'role_permission.index',
            'role_permission.permissions', 'role_permission.permissions_grouped', 'role_permission.assign_permissions', 'role_permission.remove_permissions',
            'role_permission.available_permissions', 'role_permission.can_delete', 'role_permission.statistics',
     

        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        //clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();


        // Assign permissions to roles
        $adminRole->syncPermissions([
             'complaint.destroy', 'complaint.show', 'complaint.index',  'complaint.export', 'complaint.stats',
            'user.store', 'user.update', 'user.destroy', 'user.show', 'user.index',
            'role_permission.store', 'role_permission.update', 'role_permission.destroy', 'role_permission.show', 'role_permission.index',
            'role_permission.permissions', 'role_permission.permissions_grouped', 'role_permission.assign_permissions', 'role_permission.remove_permissions',
            'role_permission.available_permissions', 'role_permission.can_delete', 'role_permission.statistics',

        ]);

       $citizenRole->syncPermissions([
    'citizen.complaint.create','citizen.complaint.update','citizen.complaint.delete',
     'citizen.complaint.list','citizen.complaint.attachments', 'citizen.profile.logout',
    
]);


        $employeeRole->syncPermissions([
            //Employee Permissions
             'complaint.store',
            'complaint.update', 'complaint.destroy', 'complaint.show', 'complaint.index',
        ]);

        /********************************************************************************/

        // Create users and assign roles
        $adminUser = User::query()->create([
            'name' => ' abo admin',
            'phone'=>'+963958564896',
            'email' => 'admin@admin.com',
            'password' => bcrypt('adminadmin'),
            'email_verified_at' => Carbon::now(),
        ]);
        $adminUser->assignRole($adminRole);
        $permissions = $adminRole->permissions()->pluck('name')->toArray();
        $adminUser->givePermissionTo($permissions);

        try {
            $media = $adminUser->addMedia(public_path('/seeder/client_profile_male.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('profile');
            $adminUser['profile_photo'] = $media->getUrl();
            $adminUser->save();
        } catch (FileDoesNotExist $e) {
            Log::warning('file does not exist: ' . $e->getMessage());
            Log::error($e);
        } catch (FileIsTooBig $e) {
            Log::warning('file is too big: ' . $e->getMessage());
            Log::error($e);
        }

        /********************************************************************************/

        $citizenUser = User::query()->create([
            'name' => ' Reem',
        'phone'=>'+9639922882679',
            'email' => 'reem@gmail.com',
             'password' => bcrypt('123456'),
            'email_verified_at' => Carbon::now(),
        ]);
        try {
            $media = $citizenUser->addMedia(public_path('/seeder/client_profile_female.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('profile');
            $citizenUser['profile_photo'] = $media->getUrl();
            $citizenUser->save();
        } catch (FileDoesNotExist $e) {
            Log::warning('file does not exist: ' . $e->getMessage());
            Log::error($e);
        } catch (FileIsTooBig $e) {
            Log::warning('file is too big: ' . $e->getMessage());
            Log::error($e);
        }

        $citizenUser->assignRole($citizenRole);
        $permissions = $citizenRole->permissions()->pluck('name')->toArray();
        $citizenUser->givePermissionTo($permissions);

        /********************************************************************************/

        $employeeUser = User::query()->create([
            'name' => ' Shahid',
            'phone'=>'+963954537163',
            'email' => 'shahid@gmail.com',
            'email_verified_at' => Carbon::now(),
            'password' => bcrypt('shahidshahid'),
        ]);

        $employeeUser->assignRole($employeeRole);

$permissions = $employeeRole->permissions()->pluck('name')->toArray();
$employeeUser->givePermissionTo($permissions); // ✅ الصحيح

        try {
            $media = $employeeUser->addMedia(public_path('/seeder/client_profile_female.jpg'))
                ->preservingOriginal()
                ->toMediaCollection('profile');
            $employeeUser['profile_photo'] = $media->getUrl();
            $employeeUser->save();
        } catch (FileDoesNotExist $e) {
            Log::warning('file does not exist: ' . $e->getMessage());
            Log::error($e);
        } catch (FileIsTooBig $e) {
            Log::warning('file is too big: ' . $e->getMessage());
            Log::error($e);
        }
    }
}
