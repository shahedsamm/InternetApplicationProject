<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;




Route::prefix('admin')
    ->controller(AuthController::class)
    ->group(function (){
        Route::post('login', 'login');
        
    });

Route::prefix('admin')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::controller(AuthController::class)
            ->group(function () {
                Route::post('logout', 'logout')
                    ->name('admin.logout')
                    ->middleware('can:admin.logout');
        });

        Route::controller(UserController::class)
            ->prefix('users')
            ->group(function () {
                // Store user (Create)
                Route::post('/', 'store')
                    ->name('user.store')
                    ->middleware('can:user.store');

                // Update user details
                Route::post('/{user}', 'update')
                    ->name('user.update')
                    ->middleware('can:user.update');

                // Delete a user
                Route::delete('/{user}', 'destroy')
                    ->name('user.destroy')
                    ->middleware('can:user.destroy');

                // Show a user's details
                Route::get('/{user}', 'show')
                    ->name('user.show')
                    ->middleware('can:user.show');

                // List all users
                Route::get('/', 'index')
                    ->name('user.index')
                    ->middleware('can:user.index');
            });

        Route::controller(RolePermissionController::class)
            ->prefix('roles-permissions')
            ->group(function () {
                // List all roles with permissions
                Route::get('/', 'index')
                    ->name('role_permission.index')
                    ->middleware('can:role_permission.index');

                // Get all permissions
                Route::get('/permissions', 'getPermissions')
                    ->name('role_permission.permissions')
                    ->middleware('can:role_permission.permissions');

                // Create new role
                Route::post('/', 'store')
                    ->name('role_permission.store')
                    ->middleware('can:role_permission.store');

                // Update role
                Route::post('/{role}', 'update')
                    ->name('role_permission.update')
                    ->middleware('can:role_permission.update');

                // Delete role
                Route::delete('/{role}', 'destroy')
                    ->name('role_permission.destroy')
                    ->middleware('can:role_permission.destroy');

                // Check if role can be deleted
                Route::get('/{role}/can-delete', 'canDelete')
                    ->name('role_permission.can_delete')
                    ->middleware('can:role_permission.can_delete');

                // Get roles statistics
                Route::get('/statistics', 'statistics')
                    ->name('role_permission.statistics')
                    ->middleware('can:role_permission.statistics');
            });

        Route::controller(ComplaintController::class)
            ->prefix('complaints')
            ->group(function () {
                Route::post('/', 'store')
                    ->name('complaint.store')
                    ->middleware('can:complaint.store');

                Route::post('/{complaint}', 'update')
                    ->name('complaint.update')
                    ->middleware('can:complaint.update');


                Route::get('/{complaint}', 'show')
                    ->name('complaint.show')
                    ->middleware('can:complaint.show');

                Route::get('/', 'index')
                    ->name('complaint.index')
                    ->middleware('can:complaint.index');

                Route::get('/{complaint}/stats', 'stats')
                    ->name('complaint.stats')
                    ->middleware('can:complaint.stats');

                Route::post('/{complaint}/export', 'export')
                    ->name('complaint.export')
                    ->middleware('can:complaint.export');

            });
        


    });

 //citizen
 Route::post('/citizen/register', [CitizenController::class, 'register']);
Route::post('/citizen/verify-otp', [CitizenController::class, 'verifyotp']);
Route::post('/citizen/resend-otp', [CitizenController::class, 'resendOtp']);
Route::post('/citizen/login', [CitizenController::class, 'login']);

Route::prefix('citizen')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // إدارة الشكاوى من Citizen
        Route::controller(ComplaintController::class)
            ->prefix('complaints')
            ->group(function () {
        Route::post('/store', 'storeComplaint');
        Route::post('put/{id}', [ComplaintController::class, 'updateComplaint'])
            ->middleware('can:citizen.complaint.update');
        Route::delete('delete/{id}', [ComplaintController::class, 'deleteComplaint'])
             ->middleware('can:citizen.complaint.delete');
        Route::get('/list', [ComplaintController::class, 'listComplaints'])
             ->middleware('can:citizen.complaint.list');
        Route::get('/track', [ComplaintController::class, 'trackComplaint']);
        
     Route::get('/details/{id}', [ComplaintController::class, 'showComplaint']);
                      });  
    Route::post('/logout', [CitizenController::class, 'logout'])
            ->middleware('can:citizen.profile.logout');

  });

  Route::post('/employee/login', [EmployeController::class, 'login']);
   Route::get('/employee/department/complaints', [EmployeController::class, 'departmentComplaints'])
             ->middleware(['auth:sanctum', 'can:complaint.index']);
Route::post('/employee/complaints/update-status', [EmployeController::class, 'updateStatus'])
            ->middleware(['auth:sanctum', 'can:complaint.update']);
Route::post('/save-fcm-token', [CitizenController::class, 'storeFcmToken'])
    ->middleware('auth:sanctum');



             