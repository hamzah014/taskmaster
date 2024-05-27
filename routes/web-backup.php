<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Auth::routes();

//Route::get('/send-otp', [OtpController::class, 'sendOtp']);

Route::post('/sendOTP', [
    'uses'  => 'PublicUser\Auth\RegisterController@sendOTP',
    'as'    => 'publicUser.register.sendOTP'
]);

//Route::get('/', 'FileController@index');
Route::get('/file/{fileName}', 'FileController@viewFile');
Route::get('/files', 'FileController@viewEncryptFile');

Route::get('/', [
    'uses'  => 'Auth\LoginController@index',
]);

Route::get('/login', [
    'uses'  => 'Auth\LoginController@index',
    'as'    => 'login.index',
]);

Route::post('/login', [
    'uses'  => 'Auth\LoginController@login',
    'as'    => 'login.validate'
]);

Route::get('/register', [
    'uses'  => 'Auth\RegisterController@index',
    'as'    => 'register.index',
]);

Route::post('/register/create', [
    'uses'  => 'Auth\RegisterController@create',
    'as'    => 'register.create',
]);

Route::get('/register/activate/{email}', [
    'uses'  => 'Auth\RegisterController@activate',
    'as'    => 'register.activate'
]);

Route::get('/password/forgot', [
    'uses'  => 'Auth\ForgotPasswordController@index',
    'as'    => 'forgotPassword.index'
]);

Route::post('/password/forgot', [
    'uses'  => 'Auth\ForgotPasswordController@email',
    'as'    => 'forgotPassword.email'
]);

Route::get('/resetPassword/{token}', [
    'uses'  => 'Auth\ResetPasswordController@showResetForm',
    'as'    => 'resetPassword.reset'
]);

Route::post('/resetPassword/update', [
    'uses'  => 'Auth\ResetPasswordController@reset',
    'as'    => 'resetPassword.update'
]);

Route::group(['prefix' => 'customer'], function () {
    Route::get('/login', [
        'uses'  => 'Customer\Auth\LoginController@index',
        'as'    => 'customer.login.index',
    ]);

    Route::post('/login', [
        'uses'  => 'Customer\Auth\LoginController@login',
        'as'    => 'customer.login.validate'
    ]);

    Route::get('/register', [
        'uses'  => 'Customer\Auth\RegisterController@index',
        'as'    => 'customer.register.index',
    ]);

    Route::post('/register/create', [
        'uses'  => 'Customer\Auth\RegisterController@create',
        'as'    => 'customer.register.create',
    ]);

    Route::get('/register/activate/{email}', [
        'uses'  => 'Customer\Auth\RegisterController@activate',
        'as'    => 'customer.register.activate'
    ]);

    Route::get('/password/forgot', [
        'uses'  => 'Customer\Auth\ForgotPasswordController@index',
        'as'    => 'customer.forgotPassword.index'
    ]);

    Route::post('/password/forgot', [
        'uses'  => 'Customer\Auth\ForgotPasswordController@email',
        'as'    => 'customer.forgotPassword.email'
    ]);

    Route::get('/resetPassword/{token}', [
        'uses'  => 'Customer\Auth\ResetPasswordController@showResetForm',
        'as'    => 'customer.resetPassword.reset'
    ]);

    Route::post('/resetPassword/update', [
        'uses'  => 'Customer\Auth\ResetPasswordController@reset',
        'as'    => 'customer.resetPassword.update'
    ]);
});

Route::group(['prefix' => 'contractor'], function () {
    Route::get('/login', [
        'uses'  => 'Contractor\Auth\LoginController@index',
        'as'    => 'contractor.login.index',
    ]);

    Route::post('/login', [
        'uses'  => 'Contractor\Auth\LoginController@login',
        'as'    => 'contractor.login.validate'
    ]);

    Route::get('/register', [
        'uses'  => 'Contractor\Auth\RegisterController@index',
        'as'    => 'contractor.register.index',
    ]);

    Route::post('/register/create', [
        'uses'  => 'Contractor\Auth\RegisterController@create',
        'as'    => 'contractor.register.create',
    ]);

    Route::get('/register/activate/{email}', [
        'uses'  => 'Contractor\Auth\RegisterController@activate',
        'as'    => 'contractor.register.activate'
    ]);

    Route::get('/password/forgot', [
        'uses'  => 'Contractor\Auth\ForgotPasswordController@index',
        'as'    => 'contractor.forgotPassword.index'
    ]);

    Route::post('/password/forgot', [
        'uses'  => 'Contractor\Auth\ForgotPasswordController@email',
        'as'    => 'contractor.forgotPassword.email'
    ]);

    Route::get('/resetPassword/{token}', [
        'uses'  => 'Contractor\Auth\ResetPasswordController@showResetForm',
        'as'    => 'contractor.resetPassword.reset'
    ]);

    Route::post('/resetPassword/update', [
        'uses'  => 'Contractor\Auth\ResetPasswordController@reset',
        'as'    => 'contractor.resetPassword.update'
    ]);
});

Route::group(['prefix' => 'publicUser'], function () {
    Route::get('/login', [
        'uses'  => 'PublicUser\Auth\LoginController@index',
        'as'    => 'publicUser.login.index',
    ]);

//    Route::post('/login', [
//        'uses'  => 'PublicUser\Auth\LoginController@index',
//        'as'    => 'publicUser.login.index',
//    ]);

    Route::post('/login', [
        'uses'  => 'PublicUser\Auth\LoginController@login',
        'as'    => 'publicUser.login.validate'
    ]);

    Route::get('/register', [
        'uses'  => 'PublicUser\Auth\RegisterController@index',
        'as'    => 'publicUser.register.index',
    ]);

    Route::post('/register/create', [
        'uses'  => 'PublicUser\Auth\RegisterController@create',
        'as'    => 'publicUser.register.create',
    ]);

    Route::get('/register/activate/{email}', [
        'uses'  => 'PublicUser\Auth\RegisterController@activate',
        'as'    => 'publicUser.register.activate'
    ]);

    Route::get('/password/forgot', [
        'uses'  => 'PublicUser\Auth\ForgotPasswordController@index',
        'as'    => 'publicUser.forgotPassword.index'
    ]);

    Route::post('/password/forgot', [
        'uses'  => 'PublicUser\Auth\ForgotPasswordController@email',
        'as'    => 'publicUser.forgotPassword.email'
    ]);

    Route::get('/resetPassword/{token}', [
        'uses'  => 'PublicUser\Auth\ResetPasswordController@showResetForm',
        'as'    => 'publicUser.resetPassword.reset'
    ]);

    Route::post('/resetPassword/update', [
        'uses'  => 'PublicUser\Auth\ResetPasswordController@reset',
        'as'    => 'publicUser.resetPassword.update'
    ]);

//{{--Working Code Datatable--}}
    Route::post('/beritaDatatable', [
        'uses'  => 'PublicUser\Auth\LoginController@beritaDatatable',
        'as'    => 'publicUser.login.beritaDatatable'
    ]);
});

Route::group(['prefix' => 'pelaksana'], function () {
    Route::get('/login', [
        'uses'  => 'Pelaksana\Auth\LoginController@index',
        'as'    => 'pelaksana.login.index',
    ]);

    Route::post('/login', [
        'uses'  => 'Pelaksana\Auth\LoginController@login',
        'as'    => 'pelaksana.login.validate'
    ]);

    Route::get('/register', [
        'uses'  => 'Pelaksana\Auth\RegisterController@index',
        'as'    => 'pelaksana.register.index',
    ]);

    Route::post('/register/create', [
        'uses'  => 'Pelaksana\Auth\RegisterController@create',
        'as'    => 'pelaksana.register.create',
    ]);

    Route::get('/register/activate/{email}', [
        'uses'  => 'Pelaksana\Auth\RegisterController@activate',
        'as'    => 'pelaksana.register.activate'
    ]);

    Route::get('/password/forgot', [
        'uses'  => 'Pelaksana\Auth\ForgotPasswordController@index',
        'as'    => 'pelaksana.forgotPassword.index'
    ]);

    Route::post('/password/forgot', [
        'uses'  => 'Pelaksana\Auth\ForgotPasswordController@email',
        'as'    => 'pelaksana.forgotPassword.email'
    ]);

    Route::get('/resetPassword/{token}', [
        'uses'  => 'Pelaksana\Auth\ResetPasswordController@showResetForm',
        'as'    => 'pelaksana.resetPassword.reset'
    ]);

    Route::post('/resetPassword/update', [
        'uses'  => 'Pelaksana\Auth\ResetPasswordController@reset',
        'as'    => 'pelaksana.resetPassword.update'
    ]);
});

Route::group(['middleware' => 'auth'], function () {

    Route::get('/signout', [
        'uses'  => 'Auth\LoginController@logout',
        'as'    => 'signout'
    ]);

    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/unauthorization', 'HomeController@unauthorization')->name('unauthorization');

    Route::group(['prefix' => 'customer'], function () {

        Route::get('/signout', [
            'uses'  => 'Customer\Auth\LoginController@logout',
            'as'    => 'customer.signout'
        ]);

        Route::group(['prefix' => 'profile'], function () {
            Route::get('/{id}', [
                'uses' => 'Customer\ProfileController@index',
                'as' => 'customer.profile.index'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'Customer\ProfileController@edit',
                'as' => 'customer.profile.edit'
            ]);
            Route::post('/image/{id}', [
                'uses' => 'Customer\ProfileController@image',
                'as' => 'customer.profile.profileImage'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'Customer\ProfileController@update',
                'as' => 'customer.profile.update'
            ]);
            Route::post('/resetPassword/{id}', [
                'uses' => 'Customer\ProfileController@resetPassword',
                'as' => 'customer.profile.resetPassword'
            ]);
            Route::post('/populateState', [
                'uses' => 'Customer\ProfileController@populateState',
                'as' => 'customer.profile.populateState'
            ]);
        });

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [
                'uses' => 'Customer\DashboardController@index',
                'as' => 'customer.dashboard.index'
            ]);
            Route::post('/filterDate', [
                'uses' => 'Customer\DashboardController@filterDate',
                'as' => 'customer.dashboard.filterDate'
            ]);
        });

        Route::group(['prefix' => 'transaction'], function () {

            Route::group(['prefix' => 'serviceApplication'], function () {
                Route::get('/', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@index',
                    'as' => 'customer.trans.serviceApplication.index'
                ]);
                Route::get('/create', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@create',
                    'as' => 'customer.trans.serviceApplication.create'
                ]);
                Route::post('/store', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@store',
                    'as' => 'customer.trans.serviceApplication.store'
                ]);
                Route::get('/edit/{id}', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@edit',
                    'as' => 'customer.trans.serviceApplication.edit'
                ]);
                Route::post('/datatable', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@datatable',
                    'as' => 'customer.trans.serviceApplication.datatable'
                ]);
                Route::post('/populateEmbassy', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@populateEmbassy',
                    'as' => 'customer.trans.serviceApplication.populateEmbassy'
                ]);
                Route::post('/populateService', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@populateService',
                    'as' => 'customer.trans.serviceApplication.populateService'
                ]);
                Route::get('/exportPDF/{id}', [
                    'uses' => 'Customer\Transaction\ServiceApplicationController@exportPDF',
                    'as'   => 'customer.trans.serviceApplication.exportPDF'
                ]);
            });

            Route::group(['prefix' => 'birthRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@edit',
                    'as' => 'customer.trans.birthRegistration.edit'
                ]);
                Route::post('/update/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@update',
                    'as' => 'customer.trans.birthRegistration.update'
                ]);
                Route::post('/updateChild/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateChild',
                    'as' => 'customer.trans.birthRegistration.updateChild'
                ]);
                Route::post('/updateDeliver/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateDeliver',
                    'as' => 'customer.trans.birthRegistration.updateDeliver'
                ]);
                Route::post('/updateMother/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateMother',
                    'as' => 'customer.trans.birthRegistration.updateMother'
                ]);
                Route::post('/updateFather/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateFather',
                    'as' => 'customer.trans.birthRegistration.updateFather'
                ]);
                Route::post('/updateInformant/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateInformant',
                    'as' => 'customer.trans.birthRegistration.updateInformant'
                ]);
                Route::post('/updateDocument/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateDocument',
                    'as' => 'customer.trans.birthRegistration.updateDocument'
                ]);
                Route::post('/updateAppointment/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateAppointment',
                    'as' => 'customer.trans.birthRegistration.updateAppointment'
                ]);
                Route::post('/populateAvailableTime/{id}', [
                    'uses' => 'Customer\Transaction\BirthRegistrationController@populateAvailableTime',
                    'as' => 'customer.trans.birthRegistration.populateAvailableTime'
                ]);
                Route::get('/education/{id}',[
                    'uses' => 'Customer\Transaction\BirthRegistrationController@education',
                    'as' => 'customer.trans.birthRegistration.education'
                ]);
                Route::post('/updateEducation/{id}',[
                    'uses' => 'Customer\Transaction\BirthRegistrationController@updateEducation',
                    'as' => 'customer.trans.birthRegistration.updateEducation'
                ]);
            });

            Route::group(['prefix' => 'marriageRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@edit',
                    'as' => 'customer.trans.marriageRegistration.edit'
                ]);
                Route::post('/updateMale/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@updateMale',
                    'as' => 'customer.trans.marriageRegistration.updateMale'
                ]);
                Route::post('/updateFemale/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@updateFemale',
                    'as' => 'customer.trans.marriageRegistration.updateFemale'
                ]);
                Route::post('/updateMarriage/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@updateMarriage',
                    'as' => 'customer.trans.marriageRegistration.updateMarriage'
                ]);
                Route::post('/updateAppointment/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@updateAppointment',
                    'as' => 'customer.trans.marriageRegistration.updateAppointment'
                ]);
                Route::post('/populateAvailableTime/{id}', [
                    'uses' => 'Customer\Transaction\MarriageRegistrationController@populateAvailableTime',
                    'as' => 'customer.trans.marriageRegistration.populateAvailableTime'
                ]);
            });

            Route::group(['prefix' => 'deathRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Customer\Transaction\DeathRegistrationController@edit',
                    'as' => 'customer.trans.deathRegistration.edit'
                ]);
                Route::post('/updateInfo/{id}', [
                    'uses' => 'Customer\Transaction\DeathRegistrationController@updateInfo',
                    'as' => 'customer.trans.deathRegistration.updateInfo'
                ]);
                Route::post('/updateAppointment/{id}', [
                    'uses' => 'Customer\Transaction\DeathRegistrationController@updateAppointment',
                    'as' => 'customer.trans.deathRegistration.updateAppointment'
                ]);
                Route::post('/populateAvailableTime/{id}', [
                    'uses' => 'Customer\Transaction\DeathRegistrationController@populateAvailableTime',
                    'as' => 'customer.trans.deathRegistration.populateAvailableTime'
                ]);
            });

            Route::group(['prefix' => 'letterGoodConduct'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@edit',
                    'as' => 'customer.trans.letterGoodConduct.edit'
                ]);
                Route::post('/populateState', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@populateState',
                    'as' => 'customer.trans.letterGoodConduct.populateState'
                ]);
                Route::post('/updatePersonal/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@updatePersonal',
                    'as' => 'customer.trans.letterGoodConduct.updatePersonal'
                ]);
                Route::post('/updateAdditional/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@updateAdditional',
                    'as' => 'customer.trans.letterGoodConduct.updateAdditional'
                ]);
                Route::post('/updateAppointment/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@updateAppointment',
                    'as' => 'customer.trans.letterGoodConduct.updateAppointment'
                ]);
                Route::post('/onload/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@onload',
                    'as' => 'customer.trans.letterGoodConduct.onload'
                ]);
                Route::post('/populateAvailableTime/{id}', [
                    'uses' => 'Customer\Transaction\LetterGoodConductController@populateAvailableTime',
                    'as' => 'customer.trans.letterGoodConduct.populateAvailableTime'
                ]);
            });
        });


        Route::group(['prefix' => 'inquiry'], function () {

            //Employer Info
            /*Route::group(['prefix' => 'employerInfo'], function () {
					Route::get('/', [
						'uses' => 'Mohr\Inquiry\EmployerInfoController@index',
						'as' => 'mohr.inquiry.employerInfo.index'
					]);
					Route::post('/validation',[
						'uses' => 'Mohr\Inquiry\EmployerInfoController@validation',
						'as'   => 'mohr.inquiry.employerInfo.validation'
					]);
					Route::post('/datatable', [
						'uses' => 'Mohr\Inquiry\EmployerInfoController@datatable',
						'as' => 'mohr.inquiry.employerInfo.datatable'
					]);
				});*/

            //Employee Info
            /*Route::group(['prefix' => 'employeeInfo'], function () {
					Route::get('/', [
						'uses' => 'Mohr\Inquiry\EmployeeInfoController@index',
						'as' => 'mohr.inquiry.employeeInfo.index'
					]);
					Route::post('/validation',[
						'uses' => 'Mohr\Inquiry\EmployeeInfoController@validation',
						'as'   => 'mohr.inquiry.employeeInfo.validation'
					]);
					Route::post('/datatable', [
						'uses' => 'Mohr\Inquiry\EmployeeInfoController@datatable',
						'as' => 'mohr.inquiry.employeeInfo.datatable'
					]);
				});*/


            //Complaint Case Info
            /*Route::group(['prefix' => 'complaintCaseInfo'], function () {
					Route::get('/', [
						'uses' => 'Mohr\Inquiry\ComplaintCaseInfoController@index',
						'as' => 'mohr.inquiry.complaintCaseInfo.index'
					]);
					Route::post('/validation',[
						'uses' => 'Mohr\Inquiry\ComplaintCaseInfoController@validation',
						'as'   => 'mohr.inquiry.complaintCaseInfo.validation'
					]);
					Route::post('/datatable', [
						'uses' => 'Mohr\Inquiry\ComplaintCaseInfoController@datatable',
						'as' => 'mohr.inquiry.complaintCaseInfo.datatable'
					]);
				});*/
        });

        /*Route::group(['prefix' => 'report'], function () {

				//Check In Smary Report
				Route::group(['prefix' => 'checkInSmry'], function () {
					Route::get('/', [
						'uses' => 'Mohr\Report\CheckInSmryController@index',
						'as' => 'mohr.report.checkInSmry.index'
					]);
					Route::post('/validation',[
						'uses' => 'Mohr\Report\CheckInSmryController@validation',
						'as'   => 'mohr.report.checkInSmry.validation'
					]);
					Route::post('/datatable', [
						'uses' => 'Mohr\Report\CheckInSmryController@datatable',
						'as' => 'mohr.report.checkInSmry.datatable'
					]);
					Route::post('/exportPDF',[
						'uses' => 'Mohr\Report\CheckInSmryController@exportPDF',
						'as'   => 'mohr.report.checkInSmry.exportPDF'
					]);
					Route::post('/exportExcel',[
						'uses' => 'Mohr\Report\CheckInSmryController@exportExcel',
						'as'   => 'mohr.report.checkInSmry.exportExcel'
					]);
				});

				//Check In Details Report
				Route::group(['prefix' => 'checkInDetails'], function () {
					Route::get('/', [
						'uses' => 'Mohr\Report\CheckInDetailsController@index',
						'as' => 'mohr.report.checkInDetails.index'
					]);
					Route::post('/validation',[
						'uses' => 'Mohr\Report\CheckInDetailsController@validation',
						'as'   => 'mohr.report.checkInDetails.validation'
					]);
					Route::post('/datatable', [
						'uses' => 'Mohr\Report\CheckInDetailsController@datatable',
						'as' => 'mohr.report.checkInDetails.datatable'
					]);
					Route::post('/exportPDF',[
						'uses' => 'Mohr\Report\CheckInDetailsController@exportPDF',
						'as'   => 'mohr.report.checkInDetails.exportPDF'
					]);
					Route::post('/exportExcel',[
						'uses' => 'Mohr\Report\CheckInDetailsController@exportExcel',
						'as'   => 'mohr.report.checkInDetails.exportExcel'
					]);
				});

			});*/
    });

    Route::group(['prefix' => 'embassy'], function () {

        Route::group(['prefix' => 'profile'], function () {
            Route::get('/{id}', [
                'uses' => 'Embassy\ProfileController@index',
                'as' => 'embassy.profile.index'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'Embassy\ProfileController@edit',
                'as' => 'embassy.profile.edit'
            ]);
            Route::post('/image/{id}', [
                'uses' => 'Embassy\ProfileController@image',
                'as' => 'embassy.profile.profileImage'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'Embassy\ProfileController@update',
                'as' => 'embassy.profile.update'
            ]);
            Route::post('/resetPassword/{id}', [
                'uses' => 'Embassy\ProfileController@resetPassword',
                'as' => 'embassy.profile.resetPassword'
            ]);
        });

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [
                'uses' => 'Embassy\Dashboard\DashboardController@index',
                'as' => 'embassy.dashboard.index'
            ]);
            Route::post('/filterDate', [
                'uses' => 'Embassy\Dashboard\DashboardController@filterDate',
                'as' => 'embassy.dashboard.filterDate'
            ]);
        });

        Route::group(['prefix' => 'inquiry'], function () {

            //Employee Info
            Route::group(['prefix' => 'employeeInfo'], function () {
                Route::get('/', [
                    'uses' => 'Embassy\Inquiry\EmployeeInfoController@index',
                    'as' => 'embassy.inquiry.employeeInfo.index'
                ]);
                Route::post('/validation', [
                    'uses' => 'Embassy\Inquiry\EmployeeInfoController@validation',
                    'as'   => 'embassy.inquiry.employeeInfo.validation'
                ]);
                Route::post('/datatable', [
                    'uses' => 'Embassy\Inquiry\EmployeeInfoController@datatable',
                    'as' => 'embassy.inquiry.employeeInfo.datatable'
                ]);
            });


            //Complaint Case Info
            Route::group(['prefix' => 'complaintCaseInfo'], function () {
                Route::get('/', [
                    'uses' => 'Embassy\Inquiry\ComplaintCaseInfoController@index',
                    'as' => 'embassy.inquiry.complaintCaseInfo.index'
                ]);
                Route::post('/validation', [
                    'uses' => 'Embassy\Inquiry\ComplaintCaseInfoController@validation',
                    'as'   => 'embassy.inquiry.complaintCaseInfo.validation'
                ]);
                Route::post('/datatable', [
                    'uses' => 'Embassy\Inquiry\ComplaintCaseInfoController@datatable',
                    'as' => 'embassy.inquiry.complaintCaseInfo.datatable'
                ]);
            });
        });
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [
            'uses' => 'Dashboard\DashboardController@index',
            'as' => 'dashboard.index'
        ]);
        Route::post('/filterDate', [
            'uses' => 'Dashboard\DashboardController@filterDate',
            'as' => 'dashboard.filterDate'
        ]);
    });

    Route::group(['prefix' => 'profile'], function () {
        Route::get('/{id}', [
            'uses' => 'ProfileController@index',
            'as' => 'profile.index'
        ]);
        Route::get('/edit/{id}', [
            'uses' => 'ProfileController@edit',
            'as' => 'profile.edit'
        ]);
        Route::post('/image/{id}', [
            'uses' => 'ProfileController@image',
            'as' => 'profile.profileImage'
        ]);
        Route::post('/update/{id}', [
            'uses' => 'ProfileController@update',
            'as' => 'profile.update'
        ]);
        Route::post('/resetPassword/{id}', [
            'uses' => 'ProfileController@resetPassword',
            'as' => 'profile.resetPassword'
        ]);
    });

    Route::group(['prefix' => 'masterData'], function () {

        Route::group(['prefix' => 'customer'], function () {
            Route::get('/', [
                'uses' => 'MasterData\CustomerController@index',
                'as' => 'masterData.customer.index'
            ]);
            Route::get('/create', [
                'uses' => 'MasterData\CustomerController@create',
                'as' => 'masterData.customer.create'
            ]);
            Route::post('/store', [
                'uses' => 'MasterData\CustomerController@store',
                'as' => 'masterData.customer.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'MasterData\CustomerController@edit',
                'as' => 'masterData.customer.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'MasterData\CustomerController@update',
                'as' => 'masterData.customer.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'MasterData\CustomerController@delete',
                'as' => 'masterData.customer.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'MasterData\CustomerController@datatable',
                'as' => 'masterData.customer.datatable'
            ]);
            Route::get('info/{id}', [
                'uses' => 'MasterData\CustomerController@info',
                'as' => 'masterData.customer.info'
            ]);
            Route::post('/populateState', [
                'uses' => 'MasterData\CustomerController@populateState',
                'as' => 'masterData.customer.populateState'
            ]);
        });

        Route::group(['prefix' => 'state'], function () {
            Route::get('/', [
                'uses' => 'MasterData\StateController@index',
                'as' => 'masterData.state.index'
            ]);
            Route::get('/create', [
                'uses' => 'MasterData\StateController@create',
                'as' => 'masterData.state.create'
            ]);
            Route::post('/store', [
                'uses' => 'MasterData\StateController@store',
                'as' => 'masterData.state.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'MasterData\StateController@edit',
                'as' => 'masterData.state.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'MasterData\StateController@update',
                'as' => 'masterData.state.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'MasterData\StateController@delete',
                'as' => 'masterData.state.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'MasterData\StateController@datatable',
                'as' => 'masterData.state.datatable'
            ]);
        });

        Route::group(['prefix' => 'country'], function () {
            Route::get('/', [
                'uses' => 'MasterData\CountryController@index',
                'as' => 'masterData.country.index'
            ]);
            Route::get('/create', [
                'uses' => 'MasterData\CountryController@create',
                'as' => 'masterData.country.create'
            ]);
            Route::post('/store', [
                'uses' => 'MasterData\CountryController@store',
                'as' => 'masterData.country.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'MasterData\CountryController@edit',
                'as' => 'masterData.country.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'MasterData\CountryController@update',
                'as' => 'masterData.country.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'MasterData\CountryController@delete',
                'as' => 'masterData.country.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'MasterData\CountryController@datatable',
                'as' => 'masterData.country.datatable'
            ]);
        });

        // Embassy
        ROute::group(['prefix' => 'embassy'], function () {
            Route::get('/', [
                'uses' => 'MasterData\EmbassyController@index',
                'as' => 'masterData.embassy.index'
            ]);
            Route::get('/create', [
                'uses' => 'MasterData\EmbassyController@create',
                'as' => 'masterData.embassy.create'
            ]);
            Route::post('/store', [
                'uses' => 'MasterData\EmbassyController@store',
                'as' => 'masterData.embassy.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'MasterData\EmbassyController@edit',
                'as' => 'masterData.embassy.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'MasterData\EmbassyController@update',
                'as' => 'masterData.embassy.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'MasterData\EmbassyController@delete',
                'as' => 'masterData.embassy.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'MasterData\EmbassyController@datatable',
                'as' => 'masterData.embassy.datatable'
            ]);
        });

        // Embassy Staff
        Route::group(['prefix' => 'staff'], function () {
            Route::get('/', [
                'uses' => 'MasterData\StaffController@index',
                'as' => 'masterData.staff.index'
            ]);
            Route::get('/create', [
                'uses' => 'MasterData\StaffController@create',
                'as' => 'masterData.staff.create'
            ]);
            Route::post('/store', [
                'uses' => 'MasterData\StaffController@store',
                'as' => 'masterData.staff.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'MasterData\StaffController@edit',
                'as' => 'masterData.staff.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'MasterData\StaffController@update',
                'as' => 'masterData.staff.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'MasterData\StaffController@destroy',
                'as' => 'masterData.staff.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'MasterData\StaffController@datatable',
                'as' => 'masterData.staff.datatable'
            ]);
            Route::get('/createEmbassyAdmin', [
                'uses' => 'MasterData\StaffController@createEmbassyAdmin',
                'as' => 'masterData.staff.createEmbassyAdmin'
            ]);
            Route::post('/storeEmbassyAdmin', [
                'uses' => 'MasterData\StaffController@storeEmbassyAdmin',
                'as' => 'masterData.staff.storeEmbassyAdmin'
            ]);
            Route::get('/editEmbassyAdmin/{id}', [
                'uses' => 'MasterData\StaffController@editEmbassyAdmin',
                'as' => 'masterData.staff.editEmbassyAdmin'
            ]);
            Route::post('/updateEmbassyAdmin/{id}', [
                'uses' => 'MasterData\StaffController@updateEmbassyAdmin',
                'as' => 'masterData.staff.updateEmbassyAdmin'
            ]);
        });
    });

    Route::group(['prefix' => 'transaction'], function () {

        Route::group(['prefix' => 'serviceApplication'], function () {
            Route::get('/', [
                'uses' => 'Transaction\ServiceApplicationController@index',
                'as' => 'trans.serviceApplication.index'
            ]);
            /*Route::get('/create', [
					'uses' => 'Transaction\ServiceApplicationController@create',
					'as' => 'trans.serviceApplication.create'
				]);
				Route::post('/store', [
					'uses' => 'Transaction\ServiceApplicationController@store',
					'as' => 'trans.serviceApplication.store'
				]);*/
            Route::post('/validation', [
                'uses' => 'Transaction\ServiceApplicationController@validation',
                'as'   => 'trans.serviceApplication.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Transaction\ServiceApplicationController@datatable',
                'as' => 'trans.serviceApplication.datatable'
            ]);
            Route::get('/exportPDF/{id}', [
                'uses' => 'Transaction\ServiceApplicationController@exportPDF',
                'as'   => 'trans.serviceApplication.exportPDF'
            ]);

            Route::group(['prefix' => 'birthRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Transaction\BirthRegistrationController@edit',
                    'as' => 'trans.serviceApplication.birthRegistration.edit'
                ]);
                Route::post('/verify/{id}', [
                    'uses' => 'Transaction\BirthRegistrationController@verify',
                    'as' => 'trans.serviceApplication.birthRegistration.verify'
                ]);
                Route::post('/process/{id}', [
                    'uses' => 'Transaction\BirthRegistrationController@process',
                    'as' => 'trans.serviceApplication.birthRegistration.process'
                ]);
                Route::post('/reject/{id}', [
                    'uses' => 'Transaction\BirthRegistrationController@reject',
                    'as' => 'trans.serviceApplication.birthRegistration.reject'
                ]);
            });

            Route::group(['prefix' => 'marriageRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Transaction\MarriageRegistrationController@edit',
                    'as' => 'trans.serviceApplication.marriageRegistration.edit'
                ]);
                Route::post('/verify/{id}', [
                    'uses' => 'Transaction\MarriageRegistrationController@verify',
                    'as' => 'trans.serviceApplication.marriageRegistration.verify'
                ]);
                Route::post('/process/{id}', [
                    'uses' => 'Transaction\MarriageRegistrationController@process',
                    'as' => 'trans.serviceApplication.marriageRegistration.process'
                ]);
                Route::post('/reject/{id}', [
                    'uses' => 'Transaction\MarriageRegistrationController@reject',
                    'as' => 'trans.serviceApplication.marriageRegistration.reject'
                ]);
            });

            Route::group(['prefix' => 'deathRegistration'], function () {
                Route::get('/edit/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@edit',
                    'as' => 'trans.serviceApplication.deathRegistration.edit'
                ]);
                Route::post('/verify/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@verify',
                    'as' => 'trans.serviceApplication.deathRegistration.verify'
                ]);
                Route::post('/process/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@process',
                    'as' => 'trans.serviceApplication.deathRegistration.process'
                ]);
                Route::post('/reject/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@reject',
                    'as' => 'trans.serviceApplication.deathRegistration.reject'
                ]);

                // Submit content of documents related to death registration
                Route::post('/content/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@contentDoc',
                    'as' => 'trans.serviceApplication.deathRegistration.contentDoc'
                ]);

                // Export PDF of death registration
                Route::post('/exportPDF/{id}', [
                    'uses' => 'Transaction\DeathRegistrationController@pdf',
                    'as' => 'trans.serviceApplication.deathRegistration.pdf'
                ]);
            });

            Route::group(['prefix' => 'letterGoodConduct'], function () {
                // Edit
                Route::get('/edit/{id}', [
                    'uses' => 'Transaction\LetterGoodConductController@edit',
                    'as' => 'trans.serviceApplication.letterGoodConduct.edit'
                ]);

                // Verify
                Route::post('/verify/{id}', [
                    'uses' => 'Transaction\LetterGoodConductController@verify',
                    'as' => 'trans.serviceApplication.letterGoodConduct.verify'
                ]);

                // Process
                Route::post('/process/{id}', [
                    'uses' => 'Transaction\LetterGoodConductController@process',
                    'as' => 'trans.serviceApplication.letterGoodConduct.process'
                ]);

                // Reject
                Route::post('/reject/{id}', [
                    'uses' => 'Transaction\LetterGoodConductController@reject',
                    'as' => 'trans.serviceApplication.letterGoodConduct.reject'
                ]);
            });
        });

        Route::group(['prefix' => 'receipt'], function () {
            Route::get('/', [
                'uses' => 'Transaction\ReceiptController@index',
                'as' => 'trans.receipt.index'
            ]);
            Route::post('/validation', [
                'uses' => 'Transaction\ReceiptController@validation',
                'as'   => 'trans.receipt.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Transaction\ReceiptController@datatable',
                'as' => 'trans.receipt.datatable'
            ]);
            Route::get('/create/{id}', [
                'uses' => 'Transaction\ReceiptController@create',
                'as' => 'trans.receipt.create'
            ]);
            Route::post('/store', [
                'uses' => 'Transaction\ReceiptController@store',
                'as' => 'trans.receipt.store'
            ]);
            Route::get('/editReceipt/{id}', [
                'uses' => 'Transaction\ReceiptController@editReceipt',
                'as' => 'trans.receipt.editReceipt'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'Transaction\ReceiptController@edit',
                'as' => 'trans.receipt.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'Transaction\ReceiptController@update',
                'as' => 'trans.receipt.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'Transaction\ReceiptController@delete',
                'as' => 'trans.receipt.delete'
            ]);
            Route::post('/populateServiceApplication', [
                'uses' => 'Transaction\ReceiptController@populateServiceApplication',
                'as' => 'trans.receipt.populateServiceApplication'
            ]);
            Route::post('/printForm/{id}', [
                'uses' => 'Transaction\ReceiptController@printForm',
                'as'   => 'trans.receipt.printForm'
            ]);
        });
    });

    Route::group(['prefix' => 'setting'], function () {

        Route::group(['prefix' => 'user'], function () {
            Route::get('/', [
                'uses' => 'Setting\UserController@index',
                'as' => 'setting.user.index'
            ]);
            Route::get('/create', [
                'uses' => 'Setting\UserController@create',
                'as' => 'setting.user.create'
            ]);
            Route::post('/store', [
                'uses' => 'Setting\UserController@store',
                'as' => 'setting.user.store'
            ]);
            Route::get('/edit/{id}', [
                'uses' => 'Setting\UserController@edit',
                'as' => 'setting.user.edit'
            ]);
            Route::post('/update/{id}', [
                'uses' => 'Setting\UserController@update',
                'as' => 'setting.user.update'
            ]);
            Route::post('/delete/{id}', [
                'uses' => 'Setting\UserController@delete',
                'as' => 'setting.user.delete'
            ]);
            Route::post('/datatable', [
                'uses' => 'Setting\UserController@datatable',
                'as' => 'setting.user.datatable'
            ]);
        });

        Route::group(['prefix' => 'roles-and-permissions'], function () {
            Route::get('/', [
                'uses' => 'Setting\RolesController@index',
                'as' => 'setting.rolesAndPermissions.index'
            ]);
            Route::post('/create', [
                'uses'  => 'Setting\RolesController@create',
                'as'    => 'setting.rolesAndPermissions.create'
            ]);
            Route::post('/delete', [
                'uses'  => 'Setting\RolesController@delete',
                'as'    => 'setting.rolesAndPermissions.delete'
            ]);
            Route::post('/storePermission', [
                'uses'  => 'Setting\RolesController@storePermission',
                'as'    => 'setting.rolesAndPermissions.storePermission'
            ]);
        });
    });

    Route::group(['prefix' => 'inquiry'], function () {

        //Employer Info
        Route::group(['prefix' => 'employerInfo'], function () {
            Route::get('/', [
                'uses' => 'Inquiry\EmployerInfoController@index',
                'as' => 'inquiry.employerInfo.index'
            ]);
            Route::post('/validation', [
                'uses' => 'Inquiry\EmployerInfoController@validation',
                'as'   => 'inquiry.employerInfo.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Inquiry\EmployerInfoController@datatable',
                'as' => 'inquiry.employerInfo.datatable'
            ]);
            Route::post('/datatableEmployee', [
                'uses' => 'Inquiry\EmployerInfoController@datatableEmployee',
                'as' => 'inquiry.employerInfo.datatableEmployee'
            ]);
            Route::post('/datatableComplaintCase', [
                'uses' => 'Inquiry\EmployerInfoController@datatableComplaintCase',
                'as' => 'inquiry.employerInfo.datatableComplaintCase'
            ]);
            Route::get('info/{id}', [
                'uses' => 'Inquiry\EmployerInfoController@info',
                'as' => 'inquiry.employerInfo.info'
            ]);
        });

        //Employee Info
        Route::group(['prefix' => 'employeeInfo'], function () {
            Route::get('/', [
                'uses' => 'Inquiry\EmployeeInfoController@index',
                'as' => 'inquiry.employeeInfo.index'
            ]);
            Route::post('/validation', [
                'uses' => 'Inquiry\EmployeeInfoController@validation',
                'as'   => 'inquiry.employeeInfo.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Inquiry\EmployeeInfoController@datatable',
                'as' => 'inquiry.employeeInfo.datatable'
            ]);
            Route::post('/datatableComplaintCase', [
                'uses' => 'Inquiry\EmployeeInfoController@datatableComplaintCase',
                'as' => 'inquiry.employeeInfo.datatableComplaintCase'
            ]);
            Route::get('info/{id}', [
                'uses' => 'Inquiry\EmployeeInfoController@info',
                'as' => 'inquiry.employeeInfo.info'
            ]);
        });


        //Complaint Case Info
        Route::group(['prefix' => 'complaintCaseInfo'], function () {
            Route::get('/', [
                'uses' => 'Inquiry\ComplaintCaseInfoController@index',
                'as' => 'inquiry.complaintCaseInfo.index'
            ]);
            Route::post('/validation', [
                'uses' => 'Inquiry\ComplaintCaseInfoController@validation',
                'as'   => 'inquiry.complaintCaseInfo.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Inquiry\ComplaintCaseInfoController@datatable',
                'as' => 'inquiry.complaintCaseInfo.datatable'
            ]);
            Route::get('info/{id}', [
                'uses' => 'Inquiry\ComplaintCaseInfoController@info',
                'as' => 'inquiry.complaintCaseInfo.info'
            ]);
        });
    });

    Route::group(['prefix' => 'report'], function () {

        //Service Application Report
        Route::group(['prefix' => 'birthRegistration'], function () {
            Route::get('/', [
                'uses' => 'Report\BirthRegistrationController@index',
                'as' => 'report.birthRegistration.index'
            ]);
            Route::post('/validation', [
                'uses' => 'Report\BirthRegistrationController@validation',
                'as'   => 'report.birthRegistration.validation'
            ]);
            Route::post('/datatable', [
                'uses' => 'Report\BirthRegistrationController@datatable',
                'as' => 'report.birthRegistration.datatable'
            ]);
            Route::post('/exportPDF', [
                'uses' => 'Report\BirthRegistrationController@exportPDF',
                'as'   => 'report.birthRegistration.exportPDF'
            ]);
            Route::post('/exportExcel', [
                'uses' => 'Report\BirthRegistrationController@exportExcel',
                'as'   => 'report.birthRegistration.exportExcel'
            ]);
        });
    });

    Route::group(['prefix' => 'publicUser'], function () {

        Route::get('/signout', [
            'uses'  => 'PublicUser\Auth\LoginController@logout',
            'as'    => 'publicUser.signout'
        ]);

        Route::get('/homepage', [
            'uses'  => 'PublicUser\PublicUserController@index',
            'as'    => 'publicUser.index',
        ]);

        Route::post('/berita/datatable', [
            'uses' => 'PublicUser\PublicUserController@beritaDatatable',
            'as' => 'publicUser.homepage.beritaDatatable'
        ]);
		
        Route::get('/createPayment', [
            'uses'  => 'PublicUser\PaymentController@createPayment',
            'as'    => 'publicUser.createPayment',
        ]);

        Route::get('/updatePayment', [
            'uses'  => 'PublicUser\PaymentController@updatePayment',
            'as'    => 'publicUser.updatePayment',
        ]);

        Route::group(['prefix' => 'application'], function () {
            Route::get('/list', [
                'uses'  => 'PublicUser\Application\ApplicationController@index',
                'as'    => 'publicUser.application.index',
            ]);

            Route::get('/create', [
                'uses' => 'PublicUser\Application\ApplicationController@create',
                'as' => 'publicUser.application.create'
            ]);

            Route::get('/create/{id}', [
                'uses' => 'PublicUser\Application\ApplicationController@edit',
                'as' => 'publicUser.application.edit'
            ]);

            Route::post('/storeSSM/{id}', [
                'uses' => 'PublicUser\Application\ApplicationController@storeSSM',
                'as' => 'publicUser.application.storeSSM'
            ]);
        });

        Route::group(['prefix' => 'tender'], function () {
            Route::get('/list', [
                    'uses'  => 'PublicUser\Tender\TenderController@index',
                'as'    => 'publicUser.tender.index',
            ]);

            Route::get('/view/{id}', [
                'uses'  => 'PublicUser\Tender\TenderController@view',
                'as'    => 'publicUser.tender.view',
            ]);

            Route::get('/list-buy-doc', [
                'uses'  => 'PublicUser\Tender\TenderController@listBuyDoc',
                'as'    => 'publicUser.tender.listBuyDoc',
            ]);

            Route::get('/view-buy-doc/{id}', [
                'uses'  => 'PublicUser\Tender\TenderController@viewBuyDoc',
                'as'    => 'publicUser.tender.viewBuyDoc',
            ]);
        });

        Route::group(['prefix' => 'transaksi'], function () {
            Route::get('/list', [
                'uses'  => 'PublicUser\Transaksi\TransaksiController@index',
                'as'    => 'publicUser.transaksi.index',
            ]);

            Route::get('/checkout', [
                'uses'  => 'PublicUser\Transaksi\TransaksiController@checkout',
                'as'    => 'publicUser.transaksi.checkout',
            ]);
        });

        Route::group(['prefix' => 'profil'], function () {
            Route::group(['prefix' => 'syarikat'], function () {
                Route::get('/edit', [
                    'uses'  => 'PublicUser\Profil\ProfilController@editSyarikat',
                    'as'    => 'publicUser.profil.syarikat.index',
                ]);

                Route::post('/update', [
                    'uses'  => 'PublicUser\Profil\ProfilController@updateSyarikat',
                    'as'    => 'publicUser.profil.syarikat.update',
                ]);
            });

            Route::group(['prefix' => 'kkm'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listKKM',
                    'as'    => 'publicUser.profil.kkm.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createKKM',
                    'as'    => 'publicUser.profil.kkm.create',
                ]);
            });

            Route::group(['prefix' => 'ppk'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listPPK',
                    'as'    => 'publicUser.profil.ppk.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createPPK',
                    'as'    => 'publicUser.profil.ppk.create',
                ]);
            });

            Route::group(['prefix' => 'kakitangan'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listKakitangan',
                    'as'    => 'publicUser.profil.kakitangan.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createKakitangan',
                    'as'    => 'publicUser.profil.kakitangan.create',
                ]);
            });

            Route::group(['prefix' => 'saham'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listSaham',
                    'as'    => 'publicUser.profil.saham.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createSaham',
                    'as'    => 'publicUser.profil.saham.create',
                ]);
            });

            Route::group(['prefix' => 'kewangan'], function () {
                Route::get('/edit', [
                    'uses'  => 'PublicUser\Profil\ProfilController@editKewangan',
                    'as'    => 'publicUser.profil.kewangan.index',
                ]);
            });

            Route::group(['prefix' => 'aset'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listAset',
                    'as'    => 'publicUser.profil.aset.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createAset',
                    'as'    => 'publicUser.profil.aset.create',
                ]);
            });

            Route::group(['prefix' => 'projek'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listProjek',
                    'as'    => 'publicUser.profil.projek.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createProjek',
                    'as'    => 'publicUser.profil.projek.create',
                ]);
            });

            Route::group(['prefix' => 'produk'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listProduk',
                    'as'    => 'publicUser.profil.produk.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createProduk',
                    'as'    => 'publicUser.profil.produk.create',
                ]);
            });

            Route::group(['prefix' => 'anugerah'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listAnugerah',
                    'as'    => 'publicUser.profil.anugerah.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createAnugerah',
                    'as'    => 'publicUser.profil.anugerah.create',
                ]);
            });

            Route::group(['prefix' => 'lampiran'], function () {
                Route::get('/list', [
                    'uses'  => 'PublicUser\Profil\ProfilController@listLampiran',
                    'as'    => 'publicUser.profil.lampiran.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'PublicUser\Profil\ProfilController@createLampiran',
                    'as'    => 'publicUser.profil.lampiran.create',
                ]);
            });

        });
    });

    Route::group(['prefix' => 'osc'], function () {

        Route::get('/homepage', [
            'uses'  => 'Osc\OSCController@index',
            'as'    => 'osc.index',
        ]);

        Route::group(['prefix' => 'approval'], function () {
            Route::get('/list', [
                'uses' => 'Osc\Approval\ApprovalController@index',
                'as' => 'osc.approval.index'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Osc\Approval\ApprovalController@view',
                'as' => 'osc.approval.view'
            ]);
        });

        Route::group(['prefix' => 'application'], function () {
            Route::get('/list', [
                'uses' => 'Osc\Application\ApplicationController@index',
                'as' => 'osc.application.index'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Osc\Application\ApplicationController@view',
                'as' => 'osc.application.view'
            ]);
        });

    });

    Route::group(['prefix' => 'perolehan'], function () {

        Route::get('/homepage', [
            'uses'  => 'Perolehan\PerolehanController@index',
            'as'    => 'perolehan.index',
        ]);

        Route::group(['prefix' => 'application'], function () {
            Route::get('/list', [
                'uses'  => 'Perolehan\Application\ApplicationController@index',
                'as'    => 'perolehan.application.index',
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Perolehan\Application\ApplicationController@view',
                'as' => 'perolehan.application.view'
            ]);
        });

        Route::group(['prefix' => 'project'], function () {
            Route::get('/list/{id}', [
                'uses'  => 'Perolehan\Project\ProjectController@index',
                'as'    => 'perolehan.project.index',
            ]);

            Route::get('/view/{id}', [
                'uses'  => 'Perolehan\Project\ProjectController@view',
                'as'    => 'perolehan.project.view',
            ]);
        });

        Route::group(['prefix' => 'tender'], function () {
            Route::get('/list', [
                'uses'  => 'Perolehan\Tender\TenderController@index',
                'as'    => 'perolehan.tender.index',
            ]);

            Route::get('/create', [
                'uses'  => 'Perolehan\Tender\TenderController@create',
                'as'    => 'perolehan.tender.create',
            ]);

            Route::get('/view/{id}', [
                'uses'  => 'Perolehan\Tender\TenderController@view',
                'as'    => 'perolehan.tender.view',
            ]);

            Route::get('/check/{id}', [
                'uses'  => 'Perolehan\Tender\TenderController@check',
                'as'    => 'perolehan.tender.check',
            ]);

            Route::get('/list-v2', [
                'uses'  => 'Perolehan\Tender\TenderController@indexV2',
                'as'    => 'perolehan.tender.indexV2',
            ]);
        });

        Route::group(['prefix' => 'setting'], function () {
            Route::group(['prefix' => 'tender'], function () {
                Route::get('/list', [
                    'uses'  => 'Perolehan\Setting\TenderController@index',
                    'as'    => 'perolehan.setting.tender.index',
                ]);

                Route::get('/create', [
                    'uses'  => 'Perolehan\Setting\TenderController@create',
                    'as'    => 'perolehan.setting.tender.create',
                ]);
            });
        });
    });

    Route::group(['prefix' => 'pelaksana'], function () {
        Route::get('/homepage', [
            'uses'  => 'Pelaksana\PelaksanaController@index',
            'as'    => 'pelaksana.index',
        ]);

        Route::get('/notification/{id}', [
            'uses'  => 'Pelaksana\PelaksanaController@notification',
            'as'    => 'pelaksana.notification',
        ]);

        Route::get('/all-notification/{id}', [
            'uses'  => 'Pelaksana\PelaksanaController@allNotification',
            'as'    => 'pelaksana.all.notification',
        ]);

        Route::group(['prefix' => 'tender'], function () {
            Route::get('/list', [
                'uses'  => 'Pelaksana\Tender\TenderController@index',
                'as'    => 'pelaksana.tender.index',
            ]);

            Route::get('/list-v2', [
                'uses'  => 'Pelaksana\Tender\TenderController@indexV2',
                'as'    => 'pelaksana.tender.indexV2',
            ]);

            Route::get('/view/{id}', [
                'uses'  => 'Pelaksana\Tender\TenderController@view',
                'as'    => 'pelaksana.tender.view',
            ]);

            Route::get('/create', [
                'uses'  => 'Pelaksana\Tender\TenderController@create',
                'as'    => 'pelaksana.tender.create',
            ]);

            Route::get('/check/{id}', [
                'uses'  => 'Pelaksana\Tender\TenderController@check',
                'as'    => 'pelaksana.tender.check',
            ]);
        });

        Route::group(['prefix' => 'project'], function () {
            Route::get('/list', [
                'uses'  => 'Pelaksana\Project\ProjectController@index',
                'as'    => 'pelaksana.project.index',
            ]);

            Route::get('/view/{id}', [
                'uses'  => 'Pelaksana\Project\ProjectController@view',
                'as'    => 'pelaksana.project.view',
            ]);
        });

        Route::group(['prefix' => 'claim'], function () {
            Route::get('/list/{id}', [
                'uses' => 'Pelaksana\Claim\ClaimController@index',
                'as' => 'pelaksana.claim.index'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Pelaksana\Claim\ClaimController@view',
                'as' => 'pelaksana.claim.view'
            ]);
        });

        Route::group(['prefix' => 'meeting'], function () {
            Route::get('/list/{id}', [
                'uses' => 'Pelaksana\Meeting\MeetingController@index',
                'as' => 'pelaksana.meeting.index'
            ]);
        });
    });

    Route::group(['prefix' => 'contractor'], function () {

        Route::get('/homepage', [
            'uses'  => 'Contractor\ContractorController@index',
            'as'    => 'contractor.index',
        ]);

        Route::get('/notification/{id}', [
            'uses'  => 'Contractor\ContractorController@notification',
            'as'    => 'contractor.notification',
        ]);

        Route::get('/all-notification/{id}', [
            'uses'  => 'Contractor\ContractorController@allNotification',
            'as'    => 'contractor.all.notification',
        ]);

        Route::get('/test', [
            'uses'  => 'Contractor\ContractorController@test',
            'as'    => 'contractor.test',
        ]);

        Route::post('/testPost', [
            'uses'  => 'Contractor\ContractorController@testPost',
            'as'    => 'contractor.posttest',
        ]);

        Route::group(['prefix' => 'milestone'], function () {
            Route::get('/list/{id}', [
                'uses' => 'Contractor\Milestone\MilestoneController@index',
                'as' => 'contractor.milestone.index'
            ]);
            Route::get('/create/{id}', [
                'uses' => 'Contractor\Milestone\MilestoneController@create',
                'as' => 'contractor.milestone.create'
            ]);
            Route::post('/store', [
                'uses' => 'Contractor\Milestone\MilestoneController@store',
                'as' => 'contractor.milestone.store'
            ]);
        });

        Route::group(['prefix' => 'claim'], function () {
            Route::get('/create/{id}', [
                'uses' => 'Contractor\Claim\ClaimController@create',
                'as' => 'contractor.claim.create'
            ]);

            Route::get('/list/{id}', [
                'uses' => 'Contractor\Claim\ClaimController@index',
                'as' => 'contractor.claim.index'
            ]);
            Route::get('/addAttachment/{id}', [
                'uses' => 'Contractor\Claim\ClaimController@addAttachment',
                'as' => 'contractor.claim.add.attachment'
            ]);
        });
    });


});
