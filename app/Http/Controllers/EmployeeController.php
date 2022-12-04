<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    use SendResponse, Pagination;


    public function getEmployees()
    {
        $employees = Employee::select("*");
        if (isset($_GET['query'])) {
            $employees->where(function ($q) {
                $columns = Schema::getColumnListing('employees');
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
                    $employees->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($employees->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الموضفين بنجاح', [], $res["model"], null, $res["count"]);
    }

    public function addEmployee(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "full_name" => 'required',
            'age' => 'required',
            'salary' => 'required',
            'carrer_title' => 'required',
        ], [
            'full_name.required' => ' يرجى ادخال اسم الموضف ',
            'age.required' => 'يرجى أدخال عمر الموضف',
            'salary.required' => 'يرجى أدخال راتب الموضف',
            'carrer_title.required' => 'يرجى أدخال العنوان الوضيفي ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $employee = Employee::create([
            "full_name" => $request["full_name"],
            "age" => $request["age"],
            "salary" => $request["salary"],
            "carrer_title" => $request["carrer_title"],
        ]);
        return $this->send_response(200, 'تم أضافة موضف جديد بنجاح', [], Employee::find($employee->id));
    }

    public function editEmployee(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "employee_id" => "required|exists:employees,id",
            "full_name" => 'required',
            'age' => 'required',
            'salary' => 'required',
            'carrer_title' => 'required',
        ], [
            "employee_id.required" => 'يجب اختيار الموضف المراد التعديل عليه',
            "employee_id.exists" => 'الموضف الذي قمت بأدخاله غير متوفر او تم حذفه',
            'full_name.required' => ' يرجى ادخال اسم الموضف ',
            'age.required' => 'يرجى أدخال عمر الموضف',
            'salary.required' => 'يرجى أدخال راتب الموضف',
            'carrer_title.required' => 'يرجى أدخال العنوان الوضيفي ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $employee = Employee::find($request["employee_id"]);
        $employee->update([
            "full_name" => $request["full_name"],
            "age" => $request["age"],
            "salary" => $request["salary"],
            "carrer_title" => $request["carrer_title"],
        ]);
        return $this->send_response(200, 'تم تعديل موضف جديد بنجاح', [], Employee::find($employee->id));
    }

    public function deleteEmployee(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "employee_id" => "required|exists:employees,id",
        ], [
            "employee_id.required" => 'يجب اختيار الموضف المراد  حذفه',
            "employee_id.exists" => 'الموضف الذي قمت بأدخاله غير متوفر او تم حذفه',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حصل خطأ في المدخلات', $validator->errors(), []);
        }
        $employee = Employee::find($request["employee_id"]);
        $employee->delete();
        return $this->send_response(200, 'تم حذف الموضف بنجاح', [], []);
    }
}