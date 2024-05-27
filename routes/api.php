<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

	Route::group(['middleware' => 'with_api_key'], function() {
                Route::post('/registerCert','Api\SignController@registerCert');
                Route::post('/revokeCert','Api\SignController@revokeCert');
                Route::post('/signPdf','Api\SignController@signPdf');
                Route::post('/multipleSignPdfRoamCert','Api\SignController@multipleSignPdfRoamCert');
	});
    
	Route::group(['middleware' => 'auth:api'], function() {

		/**** REFERENCE TABLE ***/
                Route::get('/listBanner', 'Api\BannerController@listBanner');
                Route::get('/listContractorAuthUser', 'Api\ContractorController@listContractorAuthUser');

                Route::post('/listApprovalAuthUser','Api\ApprovalController@listApprovalAuthUser');
                Route::post('/listApproval','Api\ApprovalController@listApproval');
                Route::post('/viewApproval','Api\ApprovalController@viewApproval');
                Route::post('/updateApproval','Api\ApprovalController@updateApproval');

                /****  NOTIFICATION ***/
                Route::get('/listNotification','Api\NotificationController@listNotification');
                Route::post('/removeNotification','Api\NotificationController@removeNotification');
                Route::post('/updateNotification','Api\NotificationController@updateNotification');

                /**** SETTING ***/
                Route::get('/viewProfile','Api\ProfileController@viewProfile');
                Route::post('/changePassword','Api\ProfileController@changePassword');
                Route::post('/changeEmail', 'Api\ProfileController@changeEmail');
                Route::post('/changeEmailOTP', 'Api\ProfileController@changeEmailOTP');
                Route::post('/updateProfile','Api\ProfileController@updateProfile');
                Route::post('/deleteProfile', 'Api\ProfileController@deleteProfile');
                Route::post('/logout','Api\AuthController@logout');

	});


//});
