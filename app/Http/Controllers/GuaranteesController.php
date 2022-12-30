<?php

namespace App\Http\Controllers;

use App\Models\Guarantee;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class GuaranteesController extends Controller
{
    use SendResponse, Pagination;

    public function getGuarantees()
    {
        $guarantees = Guarantee::with("worker");
        if (isset($_GET['query'])) {
            $guarantees->where(function ($q) {
                $columns = Schema::getColumnListing('guarantees');
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
                    $guarantees->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($guarantees->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الكفالات بنجاح', [], $res["model"], null, $res["count"]);
    }
    public function addGuarantees(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "customer_name" => "required",
            "worker_id" => "required|exists:workers,id|unique:guarantees,worker_id",
            "finger_print_intelligence" => "required",
            "book_work" => "required",

        ], [
            "customer_name.required" => "يجب أدخال اسم الزبون",
            "finger_print_intelligence.required" => "يجب أدخال بصمة المخابرات ",
            "book_work.required" => "يجب أدخال دفترالعمل",
            "worker_id.required" => "يجب أدخال العاملةالمراد بيعها",
            "worker_id.exists" => "العاملة التي قمت بأدخالها غير متوفرة",
            "worker_id.unique" => "تم تكفل العاملة التي تم اختيارها مسبقاً",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "customer_name" => $request["customer_name"],
            "worker_id" => $request["worker_id"],
            "book_work" => $request["book_work"],
            "note" => $request["note"],
            "finger_print_intelligence" => $request["finger_print_intelligence"],
        ];
        $add = Guarantee::create($data);
        return $this->send_response(200, 'تم أضافة معلومات الكفالة', [], Guarantee::with("worker")->find($add->id));
    }
}