<?php

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

Route::group(['prefix' => 'connect'], function() {
    Route::get('error', function (Illuminate\Http\Request $request) {
        $type = $request->input('type');
        $error = $request->input('error');
        return "type: $type\nerror: $error\n";
    });
    Route::get('facebook', 'OAuthConnect@connectFacebook');
    Route::get('google', 'OAuthConnect@connectGoogle');
});

