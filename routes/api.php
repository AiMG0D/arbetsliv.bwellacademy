<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\ProfilesController;
use App\Http\Controllers\Api\QuestionsController;
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

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
});

// Temporarily removed auth for testing questions endpoints
Route::prefix('questions')->controller(QuestionsController::class)->group(function () {
    Route::get('work', 'workQuestions');
    Route::get('school', 'schoolQuestions');
    Route::get('life', 'lifeQuestions');
});

Route::get('feedback', [FeedbackController::class, 'index']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('profile')->controller(ProfilesController::class)->group(function () {
        Route::get('{profile}/values', 'profileValues');
        Route::post('{profile}/improve', 'updateImproveValue');
        Route::post('{profile}/value', 'updateProfileValue');
        Route::get('{profile}/satisfied', 'getSatisfaction');
        Route::post('{profile}/satisfied', 'updateSatisfaction');
        Route::get('{profile}/goals', 'getGoals');
        Route::post('{profile}/goals', 'updateGoals');
        Route::get('{profile}/plan', 'getPlannedGoals');
        Route::post('{profile}/plan', 'updatePlannedGoals');
        Route::post('create', 'create');
        Route::post('finish', 'finish');
    });

    Route::post('profile/{profile}/feedback', [FeedbackController::class, 'store']);
});
