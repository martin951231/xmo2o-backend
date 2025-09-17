<?php
namespace app\life_tools\controller\platform\house;

use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\house\HouseUnitService;

class HouseUnitController extends AuthBaseController
{
    /**
     * 获取列表
     * @return \think\response\Json
     */
    public function getList(){
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['house_id'] = $this->request->param('house_id',0,'trim,intval');
        $params['pid'] = $this->request->param('pid',0,'trim,intval');
        try {
            $res = (new HouseUnitService())->getList($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 状态修改
     * @return \think\response\Json
     */
    public function changeStatus(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['status'] = $this->request->param('status',0,'trim,intval');
        try {
            $res = (new HouseUnitService())->changeStatus($params);
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
        $params['name'] = $this->request->param('name','','trim,string');
        $params['sort'] = $this->request->param('sort',0,'trim,intval');
        $params['status'] = $this->request->param('status',0,'trim,intval');
        $params['house_id'] = $this->request->param('house_id',0,'trim,intval');
        $params['pid'] = $this->request->param('pid',0,'trim,intval');
        $params['serial_number'] = $this->request->param('serial_number',0,'trim,intval');
        try {
            $res = (new HouseUnitService())->saveInfo($params);
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
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['name'] = $this->request->param('name','','trim,string');
        $params['sort'] = $this->request->param('sort',0,'trim,intval');
        $params['status'] = $this->request->param('status',0,'trim,intval');
        $params['serial_number'] = $this->request->param('serial_number',0,'trim,intval');
        try {
            $res = (new HouseUnitService())->saveInfo($params);
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
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new HouseUnitService())->del($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
    
}