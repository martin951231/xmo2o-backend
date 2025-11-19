<?php
/**
 * 商家后台寄存商品类型
 * Author: fenglei
 * Date Time: 2021/11/04 10:46
 */
namespace app\merchant\controller\merchant\deposit;

use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\card\CardNewDepositGoodsSortService;

class DepositGoodsSortController extends AuthBaseController
{
    /**
     * 商品类型列表
     */
    public function getGoodsSortList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['page_size'] = $this->request->post('pageSize', 10);
        try {
            $data = (new CardNewDepositGoodsSortService)->getGoodsSortList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 添加修改
     */
    public function handleGoodsSort()
    {
        $request = $this->request;
        $params = array();
        $params['sort_id'] = $request->post('sort_id', 0, 'trim');
        $params['name'] = $request->post('name', '', 'trim');
        $params['describe'] = $request->post('describe', '', 'trim');
        $params['sort'] = $request->post('sort', 0, 'trim');
        $params['mer_id'] = $this->merId;
      
        try {
            (new CardNewDepositGoodsSortService)->handleGoodsSort($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, [], '操作成功');
    }

    /**
     * 获取详情
     */
    public function getGoodsSortInfo()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['sort_id'] = $this->request->post('sort_id', 0, 'trim');
      
        try {
            $data = (new CardNewDepositGoodsSortService)->getGoodsSortInfo($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, '');
    }

    /**
     * 获取类型列表
     */
    public function getGoodsSortSelect()
    {
        try {
            $data = (new CardNewDepositGoodsSortService)->getGoodsSortSelect($this->merId);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data, '');
    }

    /**
     * 删除商品类型
     */
    public function delGoodsSort()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['sort_id'] = $this->request->post('sort_id', 0, 'trim');
        try {
            (new CardNewDepositGoodsSortService)->delGoodsSort($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, [], '操作成功！');
    }
}