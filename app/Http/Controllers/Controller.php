<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function respData($data = [], $msg = '操作成功', $code = Response::HTTP_OK, $status = Response::HTTP_OK)
    {
        return $this->resp($data, $msg, true, $code, $status);
    }

    protected function respError($msg = '操作失败', $code = Response::HTTP_INTERNAL_SERVER_ERROR, $status = Response::HTTP_OK)
    {
        return $this->resp([], $msg, false, $code, $status);
    }

    private function resp($data = [], $msg = '操作成功', $success = true, $code = Response::HTTP_OK, $status = Response::HTTP_OK)
    {
        $result = [
            'code'      => $code,
            'success'   => $success,
            'data'      => $data,
            'msg'       => $msg
        ];
        return response()->json($result, $status);
    }

    protected function respFail($msg = '操作失败', $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        throw new \Exception($msg, $code);
    }


    protected function requestValidate($rules, $message = [])
    {
        $validate = Validator::make(Input::all(), $rules, $message);

        if ($validate->fails()) {
            throw new \Exception($validate->getMessageBag()->first(), Response::HTTP_NOT_ACCEPTABLE);
        }
    }
}
