<?php
/**
 * 小区应用
 * author by ****
 */
namespace app\community\controller;

use app\BaseController;
use think\facade\Request;
class Demo extends BaseController{
    /**
     * desc: 测试样例
     * $param1: *****
     * return :string
     */
    public function index(){

        return "获取当前浏览器环境:".request()->agent;
    }
}