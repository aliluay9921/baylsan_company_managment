<?php

use App\Http\Controllers\ActionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\GuaranteesController;
use App\Http\Controllers\WoekerController;

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

    route::get("get_workers", [WoekerController::class, "getWorkers"]);
    route::get("get_guarantees", [GuaranteesController::class, "getGuarantees"]);
    route::get("get_employees", [EmployeeController::class, "getEmployees"]);
    route::get("get_logs", [ActionController::class, "getLogs"]);
    route::get("get_sale_process_workers", [ActionController::class, "getSaleProcessWorkers"]);
    route::get("show_actions_worker", [WoekerController::class, "showActionsWorker"]);
    route::get("get_statistics",[ActionController::class,"getStatistics"]);

    route::post("sale_process_worker", [ActionController::class, "saleProcessWorker"]);
    route::post("withdraw_from_box", [ActionController::class, "withdrawFromBox"]);
    route::post("add_worker", [WoekerController::class, "addWorker"]);
    route::post("add_employee", [EmployeeController::class, "addEmployee"]);
    route::post("add_guarantees", [GuaranteesController::class, "addGuarantees"]);


    route::put("edit_worker", [WoekerController::class, "editWorker"]);
    route::put("edit_employee", [EmployeeController::class, "editEmployee"]);
    route::put("edit_sale", [ActionController::class, "editSale"]);
    route::put("worker_recovery", [ActionController::class, "workerRecovery"]);


    route::delete("delete_employee", [EmployeeController::class, "deleteEmployee"]);
    route::delete("delete_worker", [WoekerController::class, "deleteWorker"]);
});