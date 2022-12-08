<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class WoekerController extends Controller
{
    use SendResponse, Pagination;

    public function showActionsWorker()
    {
        $worker = Worker::with('imports', 'logs')->find($_GET["worker_id"]);
        return $this->send_response(200, 'تم جلب نشاطات العاملة بنجاح', [], $worker);
    }

    public function getWorkers()
    {
        $workers = Worker::select("*");
        if (isset($_GET['query'])) {
            $workers->where(function ($q) {
                $columns = Schema::getColumnListing('workers');
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
                    $workers->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($workers->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب العاملات بنجاح', [], $res["model"], null, $res["count"]);
    }

    public function addWorker(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "full_name" => 'required',
            'age' => 'required',
            'passport_no' => 'required',
            'nationality' => 'required',
            'date_entry' => 'required',
            'date_issuance_visa' => 'required',
        ], [
            'full_name.required' => ' يرجى ادخال اسم العاملة ',
            'age.required' => 'يرجى أدخال عمر العاملة',
            'passport_no.required' => 'يرجى أدخال رقم الجواز الخاص بالعاملة',
            'nationality.required' => 'يرجى ادخال البلد الخاص بالعاملة',
            'date_entry.required' => 'يرجى أدخال تأريخ الدخول  ',
            'date_issuance_visa.required' => ' أدخال تأريخ صدور الفيزا  ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $worker = Worker::create([
            "full_name" => $request["full_name"],
            "age" => $request["age"],
            "passport_no" => $request["passport_no"],
            "nationality" => $request["nationality"],
            "date_entry" => $request["date_entry"],
            "date_issuance_visa" => $request["date_issuance_visa"],
            "status" => 0
        ]);
        return $this->send_response(200, 'تم أضافة عاملة جديد بنجاح', [], Worker::find($worker->id));
    }
    public function editWorker(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "worker_id" => "required|exists:workers,id",
            "full_name" => 'required',
            'age' => 'required',
            'passport_no' => 'required',
            'nationality' => 'required',
            'date_entry' => 'required',
            'date_issuance_visa' => 'required',
        ], [
            "worker_id.required" => 'يجب اختيار ألعاملة المراد التعديل عليه',
            "worker_id.exists" => 'ألعاملة الذي قمت بأدخاله غير متوفر او تم حذفه',
            'full_name.required' => ' يرجى ادخال اسم العاملة ',
            'age.required' => 'يرجى أدخال عمر العاملة',
            'passport_no.required' => 'يرجى أدخال رقم الجواز الخاص بالعاملة',
            'nationality.required' => 'يرجى ادخال البلد الخاص بالعاملة',
            'date_entry.required' => 'يرجى أدخال تأريخ الدخول  ',
            'date_issuance_visa.required' => ' أدخال تأريخ صدور الفيزا',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $worker = Worker::find($request["worker_id"]);
        $worker->update([
            "full_name" => $request["full_name"],
            "age" => $request["age"],
            "passport_no" => $request["passport_no"],
            "nationality" => $request["nationality"],
            "date_entry" => $request["date_entry"],
            "date_issuance_visa" => $request["date_issuance_visa"],
        ]);
        return $this->send_response(200, 'تم تعديل معلومات العاملة  بنجاح', [], Worker::find($worker->id));
    }
    public function deleteWorker(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "worker_id" => "required|exists:workers,id",
        ], [
            "worker_id.required" => 'يجب اختيار ألعاملة المراد التعديل عليه',
            "worker_id.exists" => 'ألعاملة الذي قمت بأدخاله غير متوفر او تم حذفه',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $worker = Worker::find($request["worker_id"]);
        $worker->delete();
        return $this->send_response(200, 'تم حذف العاملة بنجاح', [], []);
    }
}