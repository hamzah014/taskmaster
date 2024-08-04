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

Route::get('/testemail', [
    'uses'  => 'GeneralController@testemail',
    'as'    => 'testemail.index'
]);

Route::get('/welcome', [
    'uses'  => 'GeneralController@welcome',
    'as'    => 'welcome.index'
]);

Route::get('/file/{fileGuid}', [
    'uses'  => 'FileController@viewFile',
    'as'    => 'file.view'
]);

Route::get('/download/{fileGuid}', [
    'uses'  => 'FileController@download',
    'as'    => 'file.download'
]);

Route::get('/delete/{fileGuid}', [
    'uses'  => 'FileController@delete',
    'as'    => 'file.delete'
]);

Route::get('/files', 'FileController@viewEncryptFile');

Route::get('/viewFile/{fileGuid}', [
    'uses'  => 'FileController@getFile',
]);

Route::get('/viewFileRefNo/{refNo}', [
    'uses'  => 'FileController@getFileRefNo',
    'as'    => 'file.view.refNo'
]);

Route::get('/viewFileRefNoFileType/{refNo}/{fileType}', [
    'uses'  => 'FileController@getFileRefNoFileType',
    'as'    => 'file.view.refNoFileType'
]);


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

Route::post('/register', [
    'uses'  => 'Auth\RegisterController@create',
    'as'    => 'register.create',
]);

Route::get('/user/activation', [
    'uses'  => 'Auth\RegisterController@activateUser',
    'as'    => 'user.activate'
]);

Route::get('/password/forgot', [
    'uses'  => 'Auth\ForgotPasswordController@index',
    'as'    => 'forgotPassword.index'
]);

Route::post('/password/sendLink', [
    'uses'  => 'Auth\ForgotPasswordController@sendLink',
    'as'    => 'forgotPassword.sendLink'
]);

Route::post('/resetPassword/sendOTPReset', [
    'uses'  => 'Auth\ResetPasswordController@sendOTPReset',
    'as'    => 'resetPassword.sendOTPReset'
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

Route::post('/requestOTPReset', [
    'uses'  => 'Auth\ResetPasswordController@requestOTPReset',
    'as'    => 'resetPassword.requestOTPReset'
]);

Route::get('/reset/password/success', [
    'uses'  => 'Auth\ResetPasswordController@resetSuccess',
    'as'    => 'resetPassword.success'
]);

Route::group(['prefix' => 'admin'], function () {

    Route::get('/login', [
        'uses' => 'Admin\Auth\LoginController@index',
        'as' => 'admin.login.index'
    ]);

    Route::post('/login', [
        'uses' => 'Admin\Auth\LoginController@login',
        'as' => 'admin.login.validate'
    ]);

});

Route::group(['middleware' => 'auth'], function () {

    Route::group(['prefix' => 'admin'], function () {

        Route::get('/home', [
            'uses' => 'Admin\AdminController@index',
            'as' => 'admin.dashboard'
        ]);

        Route::get('/role', [
            'uses' => 'Admin\Role\RoleController@index',
            'as' => 'admin.role.index'
        ]);

        Route::post('/userDatatable', [
            'uses' => 'Admin\Role\RoleController@userDatatable',
            'as' => 'admin.role.userDatatable'
        ]);

        Route::post('/checkUser', [
            'uses' => 'Admin\Role\RoleController@checkUser',
            'as' => 'admin.role.checkUser'
        ]);

        Route::post('/role/user', [
            'uses' => 'Admin\Role\RoleController@saveUserRole',
            'as' => 'admin.role.saveUserRole'
        ]);

    });

    Route::get('/signout', [
        'uses'  => 'Auth\LoginController@logout',
        'as'    => 'signout'
    ]);

    Route::group(['prefix' => 'dashboard'], function () {

        Route::get('/', [
            'uses' => 'Dashboard\DashboardController@index',
            'as' => 'dashboard.index'
        ]);

    });

    Route::group(['prefix' => 'profile'], function () {

        Route::get('/', [
            'uses' => 'ProfileController@index',
            'as' => 'profile.index'
        ]);

        Route::post('/update', [
            'uses' => 'ProfileController@update',
            'as' => 'profile.update'
        ]);

        Route::post('/resetPassword', [
            'uses' => 'ProfileController@resetPassword',
            'as' => 'profile.resetPassword'
        ]);
    });


    Route::group(['prefix' => 'project'], function () {

        Route::get('/', [
            'uses' => 'Project\ProjectController@index',
            'as' => 'project.index'
        ]);

        Route::get('/create', [
            'uses' => 'Project\ProjectController@create',
            'as' => 'project.create'
        ]);

        Route::post('/submitInfo', [
            'uses' => 'Project\ProjectController@submitInfo',
            'as' => 'project.submitInfo'
        ]);

        Route::post('/cancelProject', [
            'uses' => 'Project\ProjectController@cancelProject',
            'as' => 'project.cancelProject'
        ]);

        Route::post('/searchUser', [
            'uses' => 'Project\ProjectController@searchUser',
            'as' => 'project.searchUser'
        ]);

        Route::post('/storeMember', [
            'uses' => 'Project\ProjectController@storeMember',
            'as' => 'project.storeMember'
        ]);

        Route::post('/storeDocument', [
            'uses' => 'Project\ProjectController@storeDocument',
            'as' => 'project.storeDocument'
        ]);

        Route::post('/submitRole', [
            'uses' => 'Project\ProjectController@submitRole',
            'as' => 'project.submitRole'
        ]);

        Route::post('/submitIdea', [
            'uses' => 'Project\ProjectController@submitIdea',
            'as' => 'project.submitIdea'
        ]);

        Route::post('/projectDatatable', [
            'uses' => 'Project\ProjectController@projectDatatable',
            'as' => 'project.projectDatatable'
        ]);

        Route::get('/view/{id}', [
            'uses' => 'Project\ProjectController@edit',
            'as' => 'project.edit'
        ]);

        Route::post('/update/{id}', [
            'uses' => 'Project\ProjectController@updateInfo',
            'as' => 'project.updateInfo'
        ]);

        Route::post('/updateStatus', [
            'uses' => 'Project\ProjectController@updateStatus',
            'as' => 'project.updateStatus'
        ]);

        Route::group(['prefix' => 'idea'], function () {

            Route::get('/list', [
                'uses' => 'Project\ProjectIdeaController@index',
                'as' => 'project.idea.index'
            ]);

            Route::post('/projectIdeaDatatable', [
                'uses' => 'Project\ProjectIdeaController@projectIdeaDatatable',
                'as' => 'project.idea.projectIdeaDatatable'
            ]);

            Route::get('/edit/{id}', [
                'uses' => 'Project\ProjectIdeaController@edit',
                'as' => 'project.idea.edit'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Project\ProjectIdeaController@view',
                'as' => 'project.idea.view'
            ]);

            Route::post('/add', [
                'uses' => 'Project\ProjectIdeaController@add',
                'as' => 'project.idea.add'
            ]);

            Route::post('/ideaProjectDatatable', [
                'uses' => 'Project\ProjectIdeaController@ideaProjectDatatable',
                'as' => 'project.idea.ideaProjectDatatable'
            ]);

            Route::post('/update/status', [
                'uses' => 'Project\ProjectIdeaController@updateStatus',
                'as' => 'project.idea.updateStatus'
            ]);

            Route::group(['prefix' => 'analysis'], function () {

                Route::get('/list', [
                    'uses' => 'Project\IdeaAnalysisController@index',
                    'as' => 'project.idea.analysis.index'
                ]);

                Route::post('/ideaAnalysisDatatable', [
                    'uses' => 'Project\IdeaAnalysisController@ideaAnalysisDatatable',
                    'as' => 'project.idea.analysis.ideaAnalysisDatatable'
                ]);

                Route::get('/edit/{id}', [
                    'uses' => 'Project\IdeaAnalysisController@edit',
                    'as' => 'project.idea.analysis.edit'
                ]);

                Route::post('/ideaProjectDatatable', [
                    'uses' => 'Project\ProjectIdeaController@ideaProjectDatatable',
                    'as' => 'project.idea.analysis.ideaProjectDatatable'
                ]);

                Route::get('/requirement/{id}', [
                    'uses' => 'Project\IdeaAnalysisController@formRequirement',
                    'as' => 'project.idea.analysis.formRequirement'
                ]);

                Route::post('/requirement/submit', [
                    'uses' => 'Project\IdeaAnalysisController@submitRequirement',
                    'as' => 'project.idea.analysis.submitRequirement'
                ]);

                Route::post('/requirement/submit/all', [
                    'uses' => 'Project\IdeaAnalysisController@submitAllRequirement',
                    'as' => 'project.idea.analysis.submitAllRequirement'
                ]);



            });

            Route::group(['prefix' => 'scoring'], function () {

                Route::get('/list', [
                    'uses' => 'Project\IdeaScoringController@index',
                    'as' => 'project.idea.scoring.index'
                ]);

                Route::post('/ideaScoringDatatable', [
                    'uses' => 'Project\IdeaScoringController@ideaScoringDatatable',
                    'as' => 'project.idea.scoring.ideaScoringDatatable'
                ]);

                Route::get('/edit/{id}', [
                    'uses' => 'Project\IdeaScoringController@edit',
                    'as' => 'project.idea.scoring.edit'
                ]);

                Route::post('/ideaProjectDatatable', [
                    'uses' => 'Project\ProjectIdeaController@ideaProjectDatatable',
                    'as' => 'project.idea.scoring.ideaProjectDatatable'
                ]);

                Route::get('/scoring/{id}', [
                    'uses' => 'Project\IdeaScoringController@formScoring',
                    'as' => 'project.idea.scoring.formScoring'
                ]);

                Route::post('/scoring/submit', [
                    'uses' => 'Project\IdeaScoringController@submitScoring',
                    'as' => 'project.idea.scoring.submitScoring'
                ]);

                Route::post('/scoring/submit/all', [
                    'uses' => 'Project\IdeaScoringController@submitAllScoring',
                    'as' => 'project.idea.scoring.submitAllScoring'
                ]);


            });

        });

        Route::group(['prefix' => 'analysis'], function () {

            Route::get('/', [
                'uses' => 'Project\ProjectAnalysisController@index',
                'as' => 'project.analysis.index'
            ]);

            Route::post('/projectDatatable', [
                'uses' => 'Project\ProjectAnalysisController@projectDatatable',
                'as' => 'project.analysis.projectDatatable'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Project\ProjectAnalysisController@view',
                'as' => 'project.analysis.view'
            ]);

            Route::post('/projectAnalysisDatatable', [
                'uses' => 'Project\ProjectAnalysisController@projectAnalysisDatatable',
                'as' => 'project.analysis.projectAnalysisDatatable'
            ]);

            Route::post('/viewIdea', [
                'uses' => 'Project\ProjectAnalysisController@viewIdea',
                'as' => 'project.analysis.viewIdea'
            ]);

            Route::post('/submitAnalysis', [
                'uses' => 'Project\ProjectAnalysisController@submitAnalysis',
                'as' => 'project.analysis.submitAnalysis'
            ]);


        });

    });

    Route::group(['prefix' => 'risk'], function () {

        Route::get('/', [
            'uses' => 'Risk\RiskController@index',
            'as' => 'risk.index'
        ]);

        Route::post('/riskDatatable', [
            'uses' => 'Risk\RiskController@riskDatatable',
            'as' => 'risk.riskDatatable'
        ]);

        Route::get('/view/{id}', [
            'uses' => 'Risk\RiskController@view',
            'as' => 'risk.view'
        ]);

        Route::post('/submitRisk', [
            'uses' => 'Risk\RiskController@submitRisk',
            'as' => 'risk.submitRisk'
        ]);

        Route::post('/updateStatusRisk', [
            'uses' => 'Risk\RiskController@updateStatusRisk',
            'as' => 'risk.updateStatusRisk'
        ]);

    });

    Route::group(['prefix' => 'task'], function () {

        Route::get('/', [
            'uses' => 'Task\TaskController@index',
            'as' => 'task.index'
        ]);

        Route::get('/create', [
            'uses' => 'Task\TaskController@create',
            'as' => 'task.create'
        ]);

        Route::post('/add', [
            'uses' => 'Task\TaskController@add',
            'as' => 'task.add'
        ]);

        Route::post('/projectTaskDatatable', [
            'uses' => 'Task\TaskController@projectTaskDatatable',
            'as' => 'task.projectTaskDatatable'
        ]);

        Route::get('/list/{id}', [
            'uses' => 'Task\TaskController@listTask',
            'as' => 'task.listTask'
        ]);

        Route::post('/detail', [
            'uses' => 'Task\TaskController@detail',
            'as' => 'task.view.detail'
        ]);

        Route::post('/taskDatatable', [
            'uses' => 'Task\TaskController@taskDatatable',
            'as' => 'task.taskDatatable'
        ]);

        Route::get('/view/{id}', [
            'uses' => 'Task\TaskController@edit',
            'as' => 'task.edit'
        ]);


        Route::post('/update', [
            'uses' => 'Task\TaskController@update',
            'as' => 'task.update'
        ]);

        Route::group(['prefix' => 'user'], function () {

            Route::get('/list', [
                'uses' => 'Task\TaskController@indexUser',
                'as' => 'task.user.index'
            ]);

            Route::post('/taskUserDatatable', [
                'uses' => 'Task\TaskController@taskUserDatatable',
                'as' => 'task.user.taskUserDatatable'
            ]);

            Route::post('/myTaskDatatable', [
                'uses' => 'Task\TaskController@myTaskDatatable',
                'as' => 'task.user.myTaskDatatable'
            ]);

            Route::get('/view/{id}', [
                'uses' => 'Task\TaskController@viewUser',
                'as' => 'task.user.viewUser'
            ]);

            Route::post('/submitTask', [
                'uses' => 'Task\TaskController@submitTask',
                'as' => 'task.user.submitTask'
            ]);

            Route::post('/submitTaskLead', [
                'uses' => 'Task\TaskController@submitTaskLead',
                'as' => 'task.user.submitTaskLead'
            ]);

            Route::post('/completeTask', [
                'uses' => 'Task\TaskController@completeTask',
                'as' => 'task.user.completeTask'
            ]);

        });


    });

});

