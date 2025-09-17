<?php
/**
 * 用户积分兑换商品
 */
namespace app\merchant\controller\api;


use app\merchant\model\service\card\CardGoodsService;

class CardGoodsController extends ApiBaseController {
    /**
     * 积分兑换首页
     */
    public function mine()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['userInfo'] = $this->userInfo;
        $params['cardId'] = $this->request->param('card_id');
        if(empty($params['uid'])){
            return api_output(1002, '', '请登录！');
        }
        try {
            $data = (new CardGoodsService())->mine($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 根据类型分页获取商品列表
     */
    public function getGoods()
    {
        $params = array();
        $params['cardId'] = $this->request->param('card_id');
        $params['type'] = $this->request->param('type');
        $params['mer_id'] = $this->request->param('mer_id');
        $params['pageSize'] = $this->request->param('pageSize');
        $params['page'] = $this->request->param('page');
        try {
            $data = (new CardGoodsService())->getGoods($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 兑换商品
     */
    public function exchange()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['cardId'] = $this->request->param('card_id');
        $params['goods_id'] = $this->request->param('goods_id');
        try {
            $data = (new CardGoodsService())->exchange($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 兑换商品列表
     */
    public function exchangeList()
    {
        $params = array();
        $params['cardId'] = $this->request->param('card_id');
        $params['pageSize'] = $this->request->param('pageSize');
        $params['type'] = $this->request->param('type');
        $params['page'] = $this->request->param('page');
        try {
            $data = (new CardGoodsService())->exchangeList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 兑换记录详情
     */
    public function exchangeDetail()
    {
        $params = array();
        $params['id'] = $this->request->param('id');
        try {
            $data = (new CardGoodsService())->exchangeDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 积分记录
     */
    public function scoreList()
    {
        $params = array();
        $params['cardId'] = $this->request->param('card_id');
        $params['pageSize'] = $this->request->param('pageSize');
        $params['page'] = $this->request->param('page');
        try {
            $data = (new CardGoodsService())->scoreList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        $params = array();
        $params['uid'] = $this->_uid;
        $params['cardId'] = $this->request->param('card_id');
        $params['id'] = $this->request->post('id', 0);
        try {
            $data = (new CardGoodsService())->goodsDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}