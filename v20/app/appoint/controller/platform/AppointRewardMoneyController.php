<?php


namespace app\appoint\controller\platform;


use app\appoint\model\service\AppointRewardMoneyService;
use app\common\controller\platform\AuthBaseController;

class AppointRewardMoneyController extends AuthBaseController
{
    /**
     * 列表
     * @return \think\response\Json
     */
    public function getList(){
        try {
            $res = (new AppointRewardMoneyService())->getList();
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 添加
     * @return \think\response\Json
     */
    public function add(){
        $param['name'] = $this->request->param('name','','trim,string');
        $param['pic'] = $this->request->param('pic','','trim,string');
        $param['status'] = $this->request->param('status',0,'trim,intval');
        $param['sort'] = $this->request->param('sort',0,'trim,intval');
        $param['money'] = $this->request->param('money',0,'trim');
        $param['from_type'] = 1;
        try {
            $res = (new AppointRewardMoneyService())->saveData($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function edit(){
        $param['name'] = $this->request->param('name','','trim,string');
        $param['pic'] = $this->request->param('pic','','trim,string');
        $param['status'] = $this->request->param('status',0,'trim,intval');
        $param['sort'] = $this->request->param('sort',0,'trim,intval');
        $param['money'] = $this->request->param('money',0,'trim');
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['from_type'] = 2;
        try {
            $res = (new AppointRewardMoneyService())->saveData($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 删除
     * @return \think\response\Json
     */
    public function del(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new AppointRewardMoneyService())->del($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function changeStatus(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['status'] = $this->request->param('status',0,'trim,intval');
        $param['from_type'] = 1;
        try {
            $res = (new AppointRewardMoneyService())->changeInfo($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function changeSort(){
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['sort'] = $this->request->param('sort',0,'trim,intval');
        $param['from_type'] = 2;
        try {
            $res = (new AppointRewardMoneyService())->changeInfo($param);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}