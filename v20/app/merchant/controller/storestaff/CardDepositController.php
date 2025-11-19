<?php
/**
 * 店员后台会员卡寄存功能
 * Author: hengtingmei
 * Date Time: 2021/11/03 17:15
 */

namespace app\merchant\controller\storestaff;
use app\merchant\model\service\deposit\DepositGoodsService;
use app\storestaff\controller\storestaff\AuthBaseController;
use app\merchant\model\service\card\CardUserlistService;

class CardDepositController extends AuthBaseController
{
    /**
     * 会员列表
     */
    public function getCardUserList()
    {
        $param['store_id'] = $this->staffUser['store_id'];
        $param['keyword'] = $this->request->param("keyword", "", "trim");
        $param['page'] = $this->request->param("page", "1", "intval");
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");

        // 是否绑定用户
        $param['is_bind'] = 1;

        // 显示寄存商品数量
        $param['is_deposit'] = 1;
      
        // 获得列表
        $list = (new CardUserlistService())->getCardUserList($param);
        return api_output(0, $list);
    }

    /**
     * @return mixed
     * 添加的商品列表
     */
    public function getGoodsMenu(){
        $param['mer_id']=$this->merId;
        $data=(new DepositGoodsService())->getGoodsMenuList($param);
        return api_output(0, $data);
    }

    /**
     * 添加商品
     */
    public function addGoods(){
        $param['mer_id']=$this->merId;
        $param['card_id'] = $this->request->param("card_id", "", "intval");//会员ID，pigcms_card_userlist表主键id
        $param['staff_id'] = $this->staffId;
        $param['store_id'] =$this->staffUser['store_id'];//店铺id
        $param['use_num'] = 0;//已使用商品数量
        $param['create_time'] = time();//创建时间
        $param['goods_list'] =$this->request->param('goods_list');
        if(empty($param['goods_list'])){
            return api_output_error(1003, '需要上传保存商品！');
        }
        $data=(new DepositGoodsService())->addGoods($param);
        if($data){
            return api_output(1000,[],'保存成功!');
        }else{
            return api_output_error(1003, '保存失败！');
        }
    }

    /**
     * 编辑商品
     */
    public function editGoods(){
        $param['id'] = $this->request->param("bind_id", "", "intval");//ID，pigcms_card_new_deposit_goods_bind_use主键id
        $param['staff_id'] = $this->staffId;
        $data=(new DepositGoodsService())->editGoods($param);
        return api_output(1000,$data,'获取成功!');
    }

  /**
   * 保存商品
   */
    public function saveGoods()
    {
        $param['id'] = $this->request->param("bind_id", "", "intval");//ID，pigcms_card_new_deposit_goods_bind_use主键id
        $param['num'] = $this->request->param("num", "", "intval");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['staff_id'] = $this->staffId;
        $data=(new DepositGoodsService())->saveGoods($param);
        if(empty($param['num'])){
            return api_output_error(1003, '缺少数量！');
        }
        if($data){
            return api_output(1000,[],'修改成功!');
        }else{
            return api_output_error(1003, '修改失败！');
        }
    }

    /**
     * @return \json
     * 删除商品
     */
    public function deleteGoods(){
        $param['id'] = $this->request->param("bind_id", "", "intval");//ID，pigcms_card_new_deposit_goods_bind_use主键id
        $param['staff_id'] = $this->staffId;
        $data=(new DepositGoodsService())->deleteGoods($param);
        if($data){
            return api_output(1000,[],'修改成功!');
        }else{
            return api_output_error(1003, '修改失败！');
        }
    }

    /**
     * @return \json
     * 根据状态查找商品列表
     */
    public function getGoodsList(){
        $param['status']= $this->request->param("status", 0, "intval");
        $param['page']= $this->request->param("page", 1, "intval");
        $param['pageSize']= $this->request->param("pageSize", 10, "intval");
        $param['card_id']= $this->request->param("card_id", 0, "intval");
        $param['staff_id'] = $this->staffId;
        if(empty($param['staff_id'])){
            return api_output(1002, '', '请登录！');
        }
        $param['store_id'] =$this->staffUser['store_id'];//店铺id
        $param['mer_id']=$this->merId;
        $ret=(new DepositGoodsService())->getGoodsListByStatus($param);
        return api_output(0, $ret);
    }
}
