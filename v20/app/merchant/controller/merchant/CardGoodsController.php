<?php

namespace app\merchant\controller\merchant;

use app\common\model\service\coupon\MerchantCouponService;
use app\merchant\model\service\card\CardGoodsService;

class CardGoodsController extends AuthBaseController
{
    /**
     * 商品类型列表
     */
    public function goodsTypeList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['page'] = $this->request->post('page', 0);
        $params['page_size'] = $this->request->post('pageSize', 10);
        $params['key'] = $this->request->post('key', '');
        try {
            $data = (new CardGoodsService())->goodsTypeList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品类型添加
     */
    public function goodsTypeAdd()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['name'] = $this->request->post('name', '');
        $params['sort'] = $this->request->post('sort', 0);
        try {
            $data = (new CardGoodsService())->goodsTypeAdd($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品类型编辑
     */
    public function goodsTypeEdit()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['id'] = $this->request->post('id', 0);
        $params['name'] = $this->request->post('name', '');
        $params['sort'] = $this->request->post('sort', 0);
        try {
            $data = (new CardGoodsService())->goodsTypeEdit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品类型批量删除
     */
    public function goodsTypeDel()
    {
        $params = array();
        $params['id'] = $this->request->post('id', []);
        $params['mer_id'] = $this->merId;
        try {
            $data = (new CardGoodsService())->goodsTypeDel($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品列表
     */
    public function goodsList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['page'] = $this->request->post('page', 1);
        $params['page_size'] = $this->request->post('pageSize', 10);
        $params['key'] = $this->request->post('key', '');
        $params['type'] = $this->request->post('type', 0);
        try {
            $data = (new CardGoodsService())->goodsList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商家优惠券列表
     */
    public function couponList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        try {
            $data = (new CardGoodsService())->couponList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品添加
     */
    public function goodsAdd()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['type'] = $this->request->post('type', '');
        $params['name'] = $this->request->post('name', '');
        $params['goods_type'] = $this->request->post('goods_type', 1);
        $params['points'] = $this->request->post('points', 0);
        $params['num'] = $this->request->post('num', 0);
        $params['address'] = $this->request->post('address', '');
        $params['long'] = $this->request->post('long', '');
        $params['lat'] = $this->request->post('lat', '');
        $params['effective_days'] = $this->request->post('effective_days', 0);
        $params['image'] = $this->request->post('image', '');
        $params['coupon_id'] = $this->request->post('coupon_id', '');
        $params['sort'] = $this->request->post('sort', 0);
        $params['content'] = $this->request->post('content', '');
        try {
            $data = (new CardGoodsService())->goodsAdd($params);
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
        $params['mer_id'] = $this->merId;
        $params['id'] = $this->request->post('id', 0);
        try {
            $data = (new CardGoodsService())->goodsDetail($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品编辑
     */
    public function goodsEdit()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['id'] = $this->request->post('id', 0);
        $params['edit_type'] = $this->request->post('edit_type', 0);//（0：修改基本信息，1：修改排序）
        $params['type'] = $this->request->post('type', '');
        $params['name'] = $this->request->post('name', '');
        $params['goods_type'] = $this->request->post('goods_type', 1);
        $params['points'] = $this->request->post('points', 0);
        $params['num'] = $this->request->post('num', 0);
        $params['address'] = $this->request->post('address', '');
        $params['long'] = $this->request->post('long', '');
        $params['lat'] = $this->request->post('lat', '');
        $params['effective_days'] = $this->request->post('effective_days', 0);
        $params['image'] = $this->request->post('image', '');
        $params['coupon_id'] = $this->request->post('coupon_id', '');
        $params['sort'] = $this->request->post('sort', 0);
        $params['content'] = $this->request->post('content', '');
        try {
            $data = (new CardGoodsService())->goodsEdit($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 商品批量删除
     */
    public function goodsDel()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['id'] = $this->request->post('id', []);
        try {
            $data = (new CardGoodsService())->goodsDel($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
    /**
     * 兑换商品列表
     */
    public function goodsExchangeList()
    {
        $params = array();
        $params['mer_id'] = $this->merId;
        $params['page'] = $this->request->post('page', 1);
        $params['page_size'] = $this->request->post('pageSize', 10);
        $params['key'] = $this->request->post('key', '');
        $params['type'] = $this->request->post('type', 0);
        try {
            $data = (new CardGoodsService())->goodsExchangeList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
