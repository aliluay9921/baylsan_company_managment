<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\WoekerController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

route::post("login", [AuthController::class, "login"]);


route::middleware("auth:api")->group(function () {
    route::get("get_employees", [EmployeeController::class, "getEmployees"]);
    route::post("add_employee", [EmployeeController::class, "addEmployee"]);
    route::put("edit_employee", [EmployeeController::class, "editEmployee"]);
    route::delete("delete_employee", [EmployeeController::class, "deleteEmployee"]);

    route::get("get_workers", [WoekerController::class, "getWorkers"]);
    route::post("add_worker", [WoekerController::class, "addWorker"]);
    route::put("edit_worker", [WoekerController::class, "editWorker"]);
    route::delete("delete_worker", [WoekerController::class, "deleteWorker"]);
});