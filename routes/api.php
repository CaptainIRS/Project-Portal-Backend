<?php

use Illuminate\Support\Facades\Route;

//auth
Route::post('auth/login/', 'AuthController@login');
Route::post('auth/register/', 'AuthController@register');
Route::post('auth/forgot_password/', 'AuthController@forgot_password')->middleware('guest');
Route::post('auth/reset_password/', 'AuthController@reset_password')->middleware('guest');

//dashboard
Route::get('dashboard/', 'DashboardController@show')->middleware('auth:api');
Route::get('filter/options/', 'DashboardController@filterOptions')->middleware('auth:api');

//project
Route::prefix('projects')->middleware('auth:api')->group(function () {
    Route::get('all/', 'ProjectController@all');
    Route::get('{project}/', 'ProjectController@show');
    Route::post('add/', 'ProjectController@add');
    Route::post('{project}/edit/', 'ProjectController@edit');
    Route::post('{project}/delete/', 'ProjectController@delete');
});

//stacks
Route::prefix('stacks')->middleware('auth:api')->group(function () {
    Route::get('all/', 'StackController@all');
    Route::post('add/', 'StackController@add');
});

//filters
Route::prefix('projects')->middleware('auth:api')->group(function () {
    Route::get('filter/user/{user_id}/', 'FilterController@user_filter');
    Route::get('filter/stack/{stack_id}/', 'FilterController@stack_filter');
    Route::get('filter/type/{type_id}/', 'FilterController@type_filter');
});
