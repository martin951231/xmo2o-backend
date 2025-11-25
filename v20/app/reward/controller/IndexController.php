<?php
declare (strict_types = 1);

namespace app\reward\controller;
use app\BaseController;
class IndexController extends BaseController
{
    public function index()
    {
    	return  cfg("site_url");
    }
}
