<?php


namespace app\merchant\controller\api;

use app\merchant\model\service\card\CardNewDepositGoodsService;
use app\merchant\model\service\deposit\DepositGoodsService;

class DepositGoodsListController extends ApiBaseController
{
    /**
     * @return \json
     * 根据状态查找商品列表
     */
      public function getGoodsList(){
          $param['status']= $this->request->param("status", 0, "intval");
          $param['mer_id']= $this->request->param("mer_id", 0, "intval");
          $param['page']= $this->request->param("page", 1, "intval");
          $param['pageSize']= $this->request->param("pageSize", 10, "intval");
          $param['uid']= $this->_uid??0;
          if(empty($param['uid'])){
              return api_output(1002, '', '请登录！');
          }
          $ret=(new DepositGoodsService())->getGoodsListByStatus($param);
          return api_output(0, $ret);
      }


      /**
       * 获取商品详情
       */
    public function getGoodsDetail()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['bind_id'] = $this->request->post('bind_id', 0, 'trim,intval'); 
        if(empty($params['uid'])){
            return api_output(1002, '', '请登录！');
        }
        try {
            $data = (new CardNewDepositGoodsService)->getDepositGoodsDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
     /**
     * 核销状态
     */
    public function getVerificationStatus()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['bind_id'] = $this->request->post('bind_id', 0, 'trim,intval'); 
        $params['has_num'] = $this->request->post('has_num', 0, 'trim,intval'); 
        if(empty($params['uid'])){
            return api_output(1002, '', '请登录！');
        }
        try {
            $data = (new CardNewDepositGoodsService)->getVerificationStatus($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}