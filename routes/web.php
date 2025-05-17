<?php

use Illuminate\Support\Facades\Route;

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

Route::get('lang/{locale}', 'LocalizationController@index');

Route::get('complete', 'RegisterController@getComplete');
Route::post('complete', 'RegisterController@postComplete');

Route::get('register', 'RegisterController@getRegister');
Route::post('register', 'RegisterController@postRegister');

Route::middleware(['auth'])->group(function () {
    Route::get('/', 'HomeController@getIndex');

    Route::get('/setsex', 'UserInfoController@getSetSex');
    Route::post('/setsex', 'UserInfoController@postSetSex');

    Route::get('/terms', 'UserInfoController@getTerms');
    Route::post('/terms', 'UserInfoController@postTerms');

    Route::prefix('user')->group(function () {
        Route::get('{uid}/info', 'UserInfoController@getShow');

        Route::prefix('info')->group(function () {
            Route::get('edit', 'UserInfoController@getEdit');
            Route::get('password', 'UserInfoController@getPassword');
        });
    });

    Route::get('profiles', 'ProfileController@getList');

    Route::prefix('profile')->group(function () {
        Route::get('mock/{page_id}', 'ProfileController@getProfileMock');

        Route::get('/', 'ProfileController@getProfileSelf');

        Route::get('page/{page_id}', 'ProfileController@getProfileSelf');
        Route::get('{id}/page/{page_id}', 'ProfileController@getProfile');

        Route::post('{id}/finish', 'ProfileController@postFinish');

        Route::post('{id}/unlock', 'ProfileController@postUnlock');

        Route::post('{id}/value', 'ProfileController@postValue');
    });

    Route::prefix('statement')->group(function () {
        Route::get('mock/results', 'StatementController@getResultsMock');
        Route::get('mock/satisfied', 'StatementController@getSatisfiedMock');
        Route::get('mock/goals', 'StatementController@getGoalsMock');
        Route::get('mock/plan', 'StatementController@getPlanMock');
        Route::get('mock/plan/{results?}', 'StatementController@getPlanMock');

        Route::get('/', 'StatementController@getResultsSelf');

        Route::get('results', 'StatementController@getResultsSelf');
        Route::get('{id}/results', 'StatementController@getResults');
        Route::post('{id}/results', 'StatementController@postResults');

        Route::get('satisfied', 'StatementController@getSatisfiedSelf');
        Route::get('{id}/satisfied', 'StatementController@getSatisfied');

        Route::get('goals', 'StatementController@getGoalsSelf');
        Route::get('{id}/goals', 'StatementController@getGoals');

        Route::get('plan', 'StatementController@getPlanSelf');
        Route::get('{id}/plan', 'StatementController@getPlan');

        Route::post('{id}/finish', 'StatementController@postFinish');

        Route::get('{id}/factors/{page_id}', 'StatementController@getFactors');
        Route::post('{id}/improve', 'StatementController@postImprove');
        Route::post('{id}/satisfied', 'StatementController@postSatisfied');
        Route::post('{id}/target', 'StatementController@postTarget');
        Route::post('{id}/vision', 'StatementController@postVision');
        Route::post('{id}/text', 'StatementController@postText');

        Route::get('compare', 'StatementController@getCompareSelf');
        Route::get('{id}/compare/{other_id?}', 'StatementController@getCompare');
    });

    Route::prefix('admin')->group(function () {
        Route::group(['prefix' => 'questionnaire'], function () {
            Route::group(['prefix' => 'pages'], function () {
                Route::get('/', 'QuestionnairePageController@getList');
                Route::get('new', 'QuestionnairePageController@getNew');
                Route::post('new', 'QuestionnairePageController@postNew');
                Route::get('{id}', 'QuestionnairePageController@getContents');
                Route::post('{id}', 'QuestionnairePageController@postContents');
                Route::get('{id}/edit', 'QuestionnairePageController@getEdit');
                Route::post('{id}/edit', 'QuestionnairePageController@postEdit');
                Route::get('{id}/delete', 'QuestionnairePageController@getDelete');
                Route::post('{id}/delete', 'QuestionnairePageController@postDelete');
            });

            Route::group(['prefix' => 'groups'], function () {
                Route::get('new/{id}', 'QuestionnaireGroupsController@getNew');
                Route::post('new/{id}', 'QuestionnaireGroupsController@postNew');
                Route::get('{id}/edit', 'QuestionnaireGroupsController@getEdit');
                Route::post('{id}/edit', 'QuestionnaireGroupsController@postEdit');
                Route::get('{id}/delete', 'QuestionnaireGroupsController@getDelete');
                Route::post('{id}/delete', 'QuestionnaireGroupsController@postDelete');
            });
        });

        Route::group(['prefix' => 'units'], function () {
            Route::get('/', 'UnitController@getList');

            Route::get('new', 'UnitController@getNew');
            Route::post('new', 'UnitController@postNew');

            Route::get('{id}/edit', 'UnitController@getEdit');
            Route::post('{id}/edit', 'UnitController@postEdit');

            Route::get('{id}/delete', 'UnitController@getDelete');
            Route::post('{id}/delete', 'UnitController@postDelete');

            Route::get('{unit_id}/staff/new', 'StaffController@getNew');
            Route::post('{unit_id}/staff/new', 'StaffController@postNew');

            Route::get('{unit_id}/sections/new', 'SectionController@getNew');
            Route::post('{unit_id}/sections/new', 'SectionController@postNew');
        });

        Route::group(['prefix' => 'staff'], function () {
            Route::get('/', 'StaffController@getList');

            Route::get('{id}/edit', 'StaffController@getEdit');
            Route::post('{id}/edit', 'StaffController@postEdit');

            Route::get('{id}/delete', 'StaffController@getDelete');
            Route::post('{id}/delete', 'StaffController@postDelete');

            Route::post('{user}/deactivate', 'StaffController@postDeactivate');
            Route::post('{user}/activate', 'StaffController@postActivate');
        });

        Route::group(['prefix' => 'sections'], function () {
            Route::get('/', 'SectionController@getList');

            Route::get('{id}/edit', 'SectionController@getEdit');
            Route::post('{id}/edit', 'SectionController@postEdit');

            Route::get('{id}/delete', 'SectionController@getDelete');
            Route::post('{id}/delete', 'SectionController@postDelete');

            Route::get('{id}/archive', 'SectionController@getArchive');
            Route::post('{id}/archive', 'SectionController@postArchive');

            Route::get('{id}/qr', 'SectionController@getQr');

            Route::get('{section_id}/reginfo', 'StudentController@getRegInfoSection');

            Route::get('{section_id}/students/add', 'StudentController@getNewMultiple');
            Route::post('{section_id}/students/add', 'StudentController@postNewMultiple');

            Route::get('{section_id}/students/import', 'StudentController@getImport');
            Route::post('{section_id}/students/import', 'StudentController@postImport');
            Route::post('{section_id}/students/import/complete', 'StudentController@postCompleteImport');
        });

        Route::group(['prefix' => 'students'], function () {
            Route::get('/', 'StudentController@getList');

            Route::get('{id}/reginfo', 'StudentController@getRegInfo');

            Route::get('{id}/edit', 'StudentController@getEdit');
            Route::post('{id}/edit', 'StudentController@postEdit');

            Route::get('{id}/delete', 'StudentController@getDelete');
            Route::post('{id}/delete', 'StudentController@postDelete');
        });

        Route::group(['prefix' => 'samples'], function () {
            Route::get('/', 'SamplesController@getList');

            Route::get('new', 'SamplesController@getNew');
            Route::post('new', 'SamplesController@postNew');

            Route::get('{id}/edit', 'SamplesController@getEdit');
            Route::post('{id}/edit', 'SamplesController@postEdit');

            Route::get('{id}/members', 'SamplesController@getMembers');

            Route::get('{id}/members/add', 'SamplesController@getAddMember');
            Route::post('{id}/members/add', 'SamplesController@postAddMember');

            Route::get('{id}/members/remove/{userId}', 'SamplesController@getRemoveMember');
            Route::post('{id}/members/remove/{userId}', 'SamplesController@postRemoveMember');

            Route::get('{id}/delete', 'SamplesController@getDelete');
            Route::post('{id}/delete', 'SamplesController@postDelete');
        });
    }); // admin

    Route::prefix('statistics')->group(function () {
        Route::get('/', 'StatsController@getIndex');

        Route::get('filter/{name}', 'StatsController@ajaxGetFilter');
        Route::post('filter/set', 'StatsController@ajaxSetFilter');
        Route::post('filter/save', 'StatsController@ajaxSaveFilter');
        Route::post('filter/remove', 'StatsController@ajaxRemoveFilter');
        Route::post('compare', 'StatsController@ajaxCompare');
        Route::get('selection/{cacheId}', 'StatsController@getSelection');
        Route::get('feedback', 'StatsController@ajaxGetFeedback');
        Route::get('chart', 'StatsController@ajaxGetChart');
    });
});


// first show all companies in employees page and upon selection allow the user to select any department
