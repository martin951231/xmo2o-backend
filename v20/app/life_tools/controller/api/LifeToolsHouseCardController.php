<?php


namespace app\life_tools\controller\api;


use app\life_tools\model\service\house\HouseCardService;

class LifeToolsHouseCardController extends ApiBaseController
{
    /**
     * @return \json
     * 详情
     */
    public function getHouseInfo(){
        $this->checkLogin();
        $params['houses_id'] = $this->request->param('houses_id', '0', 'intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->getHouseInfo($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 预约排卡
     */
    public function BookingCard(){
        $this->checkLogin();
        $params['name'] = $this->request->param('name','','trim,string');
        $params['phone'] = $this->request->param('phone','','trim,string');
        $params['house_id'] = $this->request->param('house_id',0,'trim,intval');
        $params['b_number'] = $this->request->param('b_number',0,'trim,intval');
        $params['u_number'] = $this->request->param('u_number',0,'trim,intval');
        $params['f_number'] = $this->request->param('f_number',0,'trim,intval');
        $params['r_number'] = $this->request->param('r_number',0,'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->BookingCard($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 我的排卡楼盘列表
     * @return \think\response\Json
     */
    public function myBookHouseList(){
        $this->checkLogin();
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->myBookHouseList($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 我的排卡楼盘信息
     */
    public function getMyCardHouseInfo(){
        $this->checkLogin();
        $params['house_id'] = $this->request->param('house_id', 0, 'intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->getMyCardHouseInfo($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 我的排卡-排卡详情
     */
    public function getMyCardDetail(){
        $this->checkLogin();
        $params['house_id'] = $this->request->param('house_id', 0, 'intval');
        $params['b_number'] = $this->request->param('b_number',0,'trim,intval');
        $params['u_number'] = $this->request->param('u_number',0,'trim,intval');
        $params['f_number'] = $this->request->param('f_number',0,'trim,intval');
        $params['r_number'] = $this->request->param('r_number',0,'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->getMyCardDetail($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 我的排卡-取消排卡
     */
    public function myCardCancel(){
        $this->checkLogin();
        $params['house_id'] = $this->request->param('house_id', 0, 'intval');
        $params['b_number'] = $this->request->param('b_number',0,'trim,intval');
        $params['u_number'] = $this->request->param('u_number',0,'trim,intval');
        $params['f_number'] = $this->request->param('f_number',0,'trim,intval');
        $params['r_number'] = $this->request->param('r_number',0,'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $res = (new HouseCardService())->myCardCancel($params);
            return api_output(0, $res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}