<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;


class BaseController extends Controller
{
    protected $route;
    const DEFAULT_PER_PAGE = 30;

    public function getEditBtn($id, $frame = false)
    {
        $link = config('admin.route.prefix') . $this->route . "/edit?modify=" . $id;
        if ($frame === true) {
            $btn = "<button class=\"btn btn-primary\" onclick=\"layer.open({
                                                                                type: 2, 
                                                                                title: ['编辑', false], 
                                                                                area: ['860px', '640px'], 
                                                                                shadeClose: true,
                                                                                scrollbar: false,
                                                                                content: '" . $link . "',
                                                                                end: function(index, layero){ 
                                                                                  window.location.reload();
                                                                                  return false; 
                                                                                }  
                                                                            })\">编辑</button>";

            return $btn;
        }
        else return "<a class='btn btn-primary' href='" . $link . "'>编辑</a>";
    }

    public function getStatusBtn($id, $changeStatus, $statusText)
    {
        return '<button class="btn btn-default" onclick="layer.confirm( \'确定' . $statusText . '吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . $this->route . "/edit?status=" . $changeStatus . "&id=" . $id . '\'})">' . $statusText . '</button>';
    }

    public function getDeleteBtn($id)
    {
        return '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . $this->route . "/edit?delete=" . $id . '\'})">删除</button>';
    }
}