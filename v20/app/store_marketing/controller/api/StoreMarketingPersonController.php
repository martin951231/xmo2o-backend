<?php
namespace app\store_marketing\controller\api;

use app\store_marketing\model\service\StoreMarketingPersonService;

class StoreMarketingPersonController extends ApiBaseController
{
    /**
     * @return \json
     * 业务员改价
     */
  public function setPrice(){
      try{
          $data['share_id'] = $this->request->param("share_id", 0, "intval");
          $data['goods_id'] = $this->request->param("goods_id", 0, "intval");
          $data['goods_type'] = $this->request->param("goods_type", 2, "intval");
          $data['objective'] = $this->request->param("objective",'detail', "trim");
          $data['specs_id'] = $this->request->param("specs_id");
          $data['price'] = $this->request->param("price", '0', "trim");
          $data['uid']=$this->_uid??0;
          if(empty($data['uid'])){
              return api_output_error(1002, "获取用户信息失败,请重新登录");
          }
          if(empty($data['goods_id'])){
              return api_output_error(1003, "缺少商品信息");
          }
          $ret=(new StoreMarketingPersonService())->setPrice($data);
          if($ret['status']){
              return api_output(0, $ret, $ret['msg']);
          }else{
              return api_output_error(1003, $ret['msg']);
          }
      }catch (\Exception $e) {
          return api_output_error(1003,$e->getMessage());
      }

  }

    /**
     * @return \json
     * 佣金记录
     */
  public function storeMarketingRecord(){
      $data['uid']=$this->_uid??0;
      $data['page'] = $this->request->param("page", 0, "intval");
      $data['pageSize'] = $this->request->param("pageSize", 0, "intval");
      if(empty($data['uid'])){
          return api_output_error(1002, "获取用户信息失败,请重新登录");
      }
      $ret=(new StoreMarketingPersonService())->storeMarketingRecord($data['uid'],$data['page'],$data['pageSize']);
      if($ret['status']){
          return api_output(0, $ret, '获取成功');
      }else{
          return api_output_error(1003, $ret['msg']);
      }
  }

    /**
     * 分享海报
     */
  public function share(){
      // web 网页二维码   mini 小程序二维码
      $param['goods_id'] = $this->request->param("goods_id", 0, "intval");//商品id
      $param['share_code'] = $this->request->param("share_code",'', "trim");//分享码
      $param['goods_type'] = $this->request->param("goods_type", 0, "intval");//商品类型
      $param['person_id'] = $this->request->param("person_id", 0, "intval");//业务员id
      $param['origin'] = $this->request->param("origin", 'web', "trim");
      /*$param['goods_id'] = 2396;//商品id
      $param['goods_type'] = 2;//商品类型
      $param['person_id'] = 10;//业务员id
      $param['share_code'] ="0GBATn";
      $param['origin'] = 'web';*/
      $param['uid']=$this->_uid??0;
      if(empty($param['uid'])){
          return api_output_error(1002, "获取用户信息失败,请重新登录");
      }
      if(empty($param['share_code'])){
          return api_output_error(1002, "缺少分享码");
      }
      if(empty($param['goods_id'])){
          return api_output_error(1003, "缺少商品id");
      }

      if(empty($param['goods_type'])){
          return api_output_error(1003, "缺少商品类型");
      }

      if(empty($param['person_id'])){
          return api_output_error(1003, "业务员信息");
      }
      $ret=(new StoreMarketingPersonService())->share($param);
      if($ret['status']){
          return api_output(0, $ret['data'], '获取成功');
      }else{
          return api_output_error(1003, $ret['msg']);
      }

  }
}
