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

class ActionController extends Controller
{
    use SendResponse, Pagination;

    public function getLogs()
    {
        $logs = Log::select("*");
        if (isset($_GET['query'])) {
            $logs->where(function ($q) {
                $columns = Schema::getColumnListing('logs');
                $q->whereHas('worker', function ($q) {
                    $q->where('full_name', 'LIKE', $_GET['query']);
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
    public function getSaleProcessWorkers()
    {
        $imports = Import::with("worker");
        if (isset($_GET['query'])) {
            $imports->where(function ($q) {
                $columns = Schema::getColumnListing('imports');
                $q->whereHas('worker', function ($q) {
                    $q->where('full_name', 'LIKE', $_GET['query']);
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
            "received_price" => $request["price"] - $request["remainder_price"],
            "status" => 0
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
            "note" => "تم عملية شراء عاملة"
        ]);

        $worker->update([
            "status" => 1
        ]);
        return $this->send_response(200, "تم عملية بيع عاملة بنجاح", [], Import::find($import->id));
    }
    public function withdrawFromBox(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "type" => "required",
            "employee_id" => $request["type"] == 0 ? "required|exists:employees,id" : "",
            "value" => "required",
            "note" => $request["type"] == 1 ? "required" : ""
        ], [
            "type.required" => "يجب تحديد نوع عملية السحب",
            "employee_id.exists" => "الموظف الذي قمت بأختياره غير متوفر",
            "employee_id.required" => "يجب أدخال الموظف",
            "value.required" => "يجب ادخال قيمة السحب",
            "note.required" => "يجب ادخال تفاصيل عملية السحب"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "value" => $request["value"],
            "note" => $request["type"] == 0 ? "سحب اموال موظف" : $request["note"],
            "log_type" => 1,
            "target_id" => $request["type"] == 0 ? $request["employee_id"] : null
        ];
        // if ($request["type"] == 0) {
        //     $request["target_id"] = $request["employee_id"];
        // }
        $log = Log::create($data);
        return $this->send_response(200, "تم عملية  سحب أموال بنجاح", [], Log::find($log->id));
    }

    public function workerRecovery(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "worker_id" => "required|exists:workers,id",
            "value" => "required",
            "note" => "required",
        ], [
            "worker_id.required" => "يجب أدخال العاملة المراد استرجاعها",
            "worker_id.exists" => "العاملة التي قمت بأرجاعها غير متوفرة",
            "value.required" => "يجب تحديد قيمة المبلغ المسترد",
            "note.required" => "يجب أدخال سبب الاسترداد"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $worker = Worker::find($request["worker_id"]);
        if ($worker->status == 0) {
            return $this->send_response(400, "يجب أختيار عاملة مستأجرة", [], []);
        }
        $worker->update(["status" => 0]);
        $data = [
            "value" => $request["value"],
            "note" => $request["note"],
            "log_type" => 1,
            "target_id" => $request["worker_id"]
        ];
        $log = Log::create($data);
        return $this->send_response(200, "تم استرجاع العاملة", [], Log::find($log->id));
    }
}