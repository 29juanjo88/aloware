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

Route::get('/post/1/comments', 'App\Http\Controllers\CommentsController@index');
Route::post('/post/1/comments', 'App\Http\Controllers\CommentsController@store');
Route::get('/post/1/comments/{comments}', 'App\Http\Controllers\CommentsController@show');
Route::put('/post/1/comments/{id}', 'App\Http\Controllers\CommentsController@update');
Route::delete('/post/1/comments/{comments}', 'App\Http\Controllers\CommentsController@destroy');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
