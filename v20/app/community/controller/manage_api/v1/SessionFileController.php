<?php


namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\SessionFileService;
use think\facade\Request;
class SessionFileController extends BaseController
{
    public function index()
    {
        $input_post = file_get_contents('php://input');
        $data = $input_post;
        if(!$data){
            return api_output_error(1001,'必传参数缺失');
        }
        $data = json_decode($data,true);
        $serviceSessionFile = new SessionFileService();
        $serviceSessionFile->storeRecord($data);
        $msg = [
            'error' => 0,
            'data' => []
        ];
        $return = json_encode($msg,JSON_UNESCAPED_UNICODE);
        echo $return;exit();
    }
}