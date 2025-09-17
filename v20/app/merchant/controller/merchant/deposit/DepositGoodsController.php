<?php
/**
 * 商家后台寄存商品
 * Author: fenglei
 * Date Time: 2021/11/04 10:23
 */
namespace app\merchant\controller\merchant\deposit;

use app\merchant\controller\merchant\AuthBaseController; 

use app\merchant\model\service\card\CardNewDepositGoodsService;

class DepositGoodsController extends AuthBaseController
{
    public function getGoodsList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['page_size'] = $this->request->post('pageSize', 10);
        try {
            $data = (new CardNewDepositGoodsService())->getGoodsList($params); 
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
        
    }

    /**
     * 添加修改商品
     */
    public function goodsEdit()
    {
        $request = $this->request;
        $params = array();
        $params['goods_id'] = $request->post('goods_id', 0, 'trim,intval');
        $params['name'] = $request->post('name', '', 'trim');
        $params['start_time'] = $request->post('start_time', 0, 'trim');
        $params['end_time'] = $request->post('end_time', 0, 'trim');
        $params['sort_id'] = $request->post('sort_id', 0, 'trim');
        $params['image'] = $request->post('image', '', 'trim');
        $params['stock_num'] = $request->post('stock_num', 0, 'trim');
        $params['mer_id'] = $this->merId;
        try {
            (new CardNewDepositGoodsService())->goodsEdit($params); 
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, []);
    }

    /**
     * 获取详情
     */
    public function getGoodsDetail()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['goods_id'] = $this->request->post('goods_id', 0 ,'trim,intval');
        try {
            $data = (new CardNewDepositGoodsService())->getGoodsDetail($params); 
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['goods_id'] = $this->request->post('goods_id', 0, 'trim,intval');
        try {
            (new CardNewDepositGoodsService)->delGoods($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, [], '操作成功！');
    }
}