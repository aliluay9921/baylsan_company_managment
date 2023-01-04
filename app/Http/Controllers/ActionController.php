<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Log;
use App\Models\Import;
use App\Models\Worker;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class ActionController extends Controller
{
    use SendResponse, Pagination;

    public function getLogs()
    {
        $logs = Log::select("*");
        if (isset($_GET['query'])) {
            $logs->where(function ($q) {
                $columns = Schema::getColumnListing('logs');
                $q->orwhereHas('worker', function ($q) {
                    $q->where('full_name', 'LIKE', '%' . $_GET['query']  . '%');
                });
                $q->orwhereHas('employee', function ($q) {
                    $q->where('full_name', 'LIKE', '%' . $_GET['query']  . '%');
                });
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $logs->where($filter->name, 'LIKE', '%' . $filter->value . '%');
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $logs->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($logs->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب النشاطات  بنجاح', [], $res["model"], null, $res["count"]);
    }

    public function getStatistics(){
       $result=[];
       $workers_available=Worker::where("status",0)->count();
       $buys_workers=Worker::where("status",1)->count();
       $sales_day=Log::where("log_type",0)->where("date",Carbon::now()->format('Y-m-d'))->sum("value");
       $sales_month=Log::where("log_type",0)->whereBetween("date", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum("value");
       $withdraw_day=Log::where("log_type",1)->where("date",Carbon::now()->format('Y-m-d'))->sum("value");
       $withdraw_month=Log::where("log_type",1)->whereBetween("date", [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum("value");
       $result=[
           "worker_available"=> $workers_available,
           "buys_workers"=> $buys_workers,
           "sales_day"=> $sales_day,
           "sales_month"=> $sales_month,
           "withdraw_day"=> $withdraw_day,
           "withdraw_month"=> $withdraw_month,
       ]; 
    return $this->send_response(200, 'تم جلب الاحصائيات بنجاح', [], $result);

    }
    public function getSaleProcessWorkers()
    {
        $imports = Import::with("worker");
        if (isset($_GET['query'])) {
            $imports->where(function ($q) {
                $columns = Schema::getColumnListing('imports');
                $q->whereHas('worker', function ($q) {
                    $q->where('full_name', 'LIKE', '%' . $_GET['query']  . '%');
                });
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }

        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $imports->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($imports->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب المبيعات بنجاح', [], $res["model"], null, $res["count"]);
    }
    public function saleProcessWorker(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "customer_name" => "required",
            "worker_id" => "required|exists:workers,id",
            "request_type" => "required",
            "price" => "required",
            "remainder_price" => "required",
            "received_price" => "required",
        ], [
            "customer_name.required" => "يجب أدخال اسم الزبون",
            "worker_id.required" => "يجب أدخال العاملةالمراد بيعها",
            "worker_id.exists" => "العاملة التي قمت بأدخالها غير متوفرة",
            "request_type.required" => "يجب تحديد نوع العملية ",
            "price.required" => "يجب تحديد سعر البيع",
            "remainder_price.required" => "يجب ادخال السعر الواصل ",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "customer_name" => $request["customer_name"],
            "worker_id" => $request["worker_id"],
            "request_type" => $request["request_type"], // daily ,weekly ,monthly ,yearly
            "price" => $request["price"],
            "remainder_price" => $request["remainder_price"],
            "received_price" => $request["received_price"],
            "status" => 0,
            "note" => $request["note"] ?? null,
        ];
        $worker =  Worker::find($request["worker_id"]);
        if ($worker->status == 1) {
            return $this->send_response(400, 'العاملة غير متوفرة حالياً تم تأجيرها', $validator->errors(), []);
        }
        $import = Import::create($data);
        Log::create([
            "target_id" => $request["worker_id"],
            "log_type" => 0,
            "value" => $request["remainder_price"],
            "note" => "تم عملية شراء عاملة",
            "date"=>Carbon::now()->format('Y-m-d')
        ]);

        $worker->update([
            "status" => 1
        ]);
        return $this->send_response(200, "تم عملية بيع عاملة بنجاح", [], Import::with('worker')->find($import->id));
    }
    public function withdrawFromBox(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "type" => "required",
            "employee_id" => $request["type"] == 0 ? "required|exists:employees,id" : "",
            "worker_id" => $request["type"] == 1 ? "required|exists:workers,id" : "",
            "value" => "required",
            "note" => $request["type"] == 2 ? "required" : "",
            "date" => "required"
        ], [
            "type.required" => "يجب تحديد نوع عملية السحب",
            "employee_id.exists" => "الموظف الذي قمت بأختياره غير متوفر",
            "employee_id.required" => "يجب أدخال الموظف",
            "worker_id.exists" => "العاملة الذي قمت بأختياره غير متوفر",
            "worker_id.required" => "يجب أدخال العاملة",
            "value.required" => "يجب ادخال قيمة السحب",
            "note.required" => "يجب ادخال تفاصيل عملية السحب",
            "date.required" => "يجب ادخال تأريخ عملية السحب"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "value" => $request["value"],
            "note" => $request["note"] ?? null,
            "log_type" => 1,
            "date" => $request["date"]
        ];
        if ($request["type"] == 0) {
            $data["target_id"] = $request["employee_id"];
        } else if ($request["type"] == 1) {
            $data["target_id"] = $request["worker_id"];
        }

        $log = Log::create($data);
        return $this->send_response(200, "تم عملية  سحب أموال بنجاح", [], Log::find($log->id));
    }
    public function workerRecovery(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "sale_id" => "required|exists:imports,id",
            "worker_id" => "required|exists:workers,id",
            "value" => "required",
            "note" => "required",
        ], [
            "sale_id.required" => "يجب أدخال العملية المراد استرجاعها",
            "sale_id.exists" => "العملية التي قمت بأرجاعها غير متوفرة",

            "worker_id.required" => "يجب أدخال العاملة المراد استرجاعها",
            "worker_id.exists" => "العاملة التي قمت بأرجاعها غير متوفرة",
            "value.required" => "يجب تحديد قيمة المبلغ المسترد",
            "note.required" => "يجب أدخال سبب الاسترداد"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $worker = Worker::find($request["worker_id"]);
        $sale = Import::find($request["sale_id"]);
        if ($worker->status == 0) {
            return $this->send_response(400, "يجب أختيار عاملة مستأجرة", [], []);
        }
        if ($sale->status == 1) {
            return $this->send_response(400, "يجب تحديد طلب عند الزبون ليتم ارجاعه", [], []);
        }
        $worker->update(["status" => 0]);
        $sale->update(["status" => 1]);
        $data = [
            "value" => $request["value"],
            "note" => $request["note"],
            "log_type" => 1,
            "target_id" => $request["worker_id"],
             "date"=>Carbon::now()->format('Y-m-d')
        ];
        $log = Log::create($data);
        return $this->send_response(200, "تم استرجاع العاملة", [], Import::with("worker")->find($request["sale_id"]));
    }
    public function editSale(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "sale_id" => "required|exists:imports,id",
            "customer_name" => "required",
            "worker_id" => "required|exists:workers,id",
            "request_type" => "required",
            "price" => "required",
            "remainder_price" => "required",
        ], [
            "customer_name.required" => "يجب أدخال اسم الزبون",
            "worker_id.required" => "يجب أدخال العاملةالمراد بيعها",
            "worker_id.exists" => "العاملة التي قمت بأدخالها غير متوفرة",
            "request_type.required" => "يجب تحديد نوع العملية ",
            "price.required" => "يجب تحديد سعر البيع",
            "remainder_price.required" => "يجب ادخال السعر الواصل ",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $sale = Import::find($request["sale_id"]);
         Log::create([
            "target_id" => $request["worker_id"],
            "log_type" => 0,
            "value" =>  $request["remainder_price"] - $sale->remainder_price,
            "note" => "تم التعديل على عملية شراء عاملة",
            "date"=>Carbon::now()->format('Y-m-d')
        ]);
        $sale->update([
            "customer_name" => $request["customer_name"],
            "worker_id" => $request["worker_id"],
            "request_type" => $request["request_type"], // daily ,weekly ,monthly ,yearly
            "price" => $request["price"],
            "remainder_price" => $request["remainder_price"], // القيمة المستلمة
            "received_price" => $request["received_price"],
            "status" => 0,
            "note" => $request["note"] ?? null,
        ]);
        
        return $this->send_response(200, 'تم تعديل معلومات البيع  بنجاح', [], Import::with('worker')->find($sale->id));
    }
}