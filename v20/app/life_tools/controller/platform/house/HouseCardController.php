<?php


namespace app\life_tools\controller\platform\house;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\house\HouseCardService;

class HouseCardController extends AuthBaseController
{
    /**
     * 活动列表
     * @return array|\think\response\Json
     */
    public function getList(){
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        try {
            $res = (new HouseCardService())->getList($params);
            return  api_output(1000,$res);
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
            $res = (new HouseCardService())->del($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 创建
     * @return \think\response\Json
     */
    public function add(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['house_ids'] = $this->request->param('house_ids',[]);
        try {
            $res = (new HouseCardService())->save($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function edit(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['house_ids'] = $this->request->param('house_ids',[]);
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new HouseCardService())->save($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 选择楼盘列表
     * @return array|\think\response\Json
     */
    public function getHouseList(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $res = (new HouseCardService())->getHouseList($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 认购用户
     * @return \think\response\Json
     */
    public function getCardUser(){
        $params['aid'] = $this->request->param('aid',0,'trim,intval');
        $params['search_type'] = $this->request->param('search_type',1,'trim,intval');
        $params['keywords'] = $this->request->param('keywords','','trim,string');
        $params['start_date'] = $this->request->param('start_date','','trim,string');
        $params['end_date'] = $this->request->param('end_date','','trim,string');
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['h_id'] = $this->request->param('h_id',0,'trim,intval');
        $params['b_id'] = $this->request->param('b_id',0,'trim,intval');
        $params['u_id'] = $this->request->param('u_id',0,'trim,intval');
        $params['f_id'] = $this->request->param('f_id',0,'trim,intval');
        $params['r_id'] = $this->request->param('r_id',0,'trim,intval');
        try {
            $res = (new HouseCardService())->getCardUser($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 认卡用户-修改状态
     * @return \think\response\Json
     */
    public function cardUserChangeStatus(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['type'] = $this->request->param('type',1,'trim,intval');
        $params['carmi'] = $this->request->param('carmi',0,'trim,intval');
        try {
            $res = (new HouseCardService())->changeStatus($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 认卡用户-导出
     * @return \think\response\Json
     */
    public function exportCardUser(){
        $params['aid'] = $this->request->param('aid',0,'trim,intval');
        $params['search_type'] = $this->request->param('page',1,'trim,intval');
        $params['keywords'] = $this->request->param('pageSize','','trim,string');
        $params['start_date'] = $this->request->param('start_date','','trim,string');
        $params['end_date'] = $this->request->param('end_date','','trim,string');
        $params['h_id'] = $this->request->param('h_id',0,'trim,intval');
        $params['b_id'] = $this->request->param('b_id',0,'trim,intval');
        $params['u_id'] = $this->request->param('u_id',0,'trim,intval');
        $params['f_id'] = $this->request->param('f_id',0,'trim,intval');
        $params['r_id'] = $this->request->param('r_id',0,'trim,intval');
        $params['rand_number'] = time();
        try {
            $res = (new HouseCardService())->exportCardUser($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 获取楼盘、楼栋、单元、楼层、房间信息
     */
    public function getHouseDetail(){
        $params['aid'] = $this->request->param('aid',0,'trim,intval');
        $params['rand_number'] = time();
        try {
            $res = (new HouseCardService())->getHouseDetail($params);
            return  api_output(1000,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}