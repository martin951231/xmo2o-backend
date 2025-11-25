<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\MemberGoodsService;

class MemberGoodsController extends AuthBaseController
{
    /**
     * 会员价商品列表
     * @return \json
     */
    public function goodsList(){
        $param['mer_id'] = $this->merId;
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['page_size'] = $this->request->param('page_size',10,'trim,intval');
        $param['type'] = $this->request->param('type','','trim,string');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        try {
            $data = (new MemberGoodsService())->goodsList($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取业务类型
     * @return \json
     */
    public function getGoodsType(){
        try {
            $data = (new MemberGoodsService())->getGoodsType();
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 会员价商品-删除
     * @return \json
     */
    public function goodsDel(){
        $param['mer_id'] = $this->merId;
        $param['id'] = $this->request->param('id',0,'trim,intval');
        try {
            $data = (new MemberGoodsService())->goodsDel($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 会员价商品-修改价格
     * @return \json
     */
    public function goodsChangePrice(){
        $param['mer_id'] = $this->merId;
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['new_price'] = $this->request->param('new_price',0,'trim');
        $param['new_price_info'] = $this->request->param('new_price_info');
        $param['stock'] = $this->request->param('stock',0,'trim,intval');
        try {
            $data = (new MemberGoodsService())->goodsChangePrice($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 会员价商品-修改排序
     * @return \json
     */
    public function goodsChangeSort(){
        $param['mer_id'] = $this->merId;
        $param['id'] = $this->request->param('id',0,'trim,intval');
        $param['sort'] = $this->request->param('sort',0,'trim,intval');
        try {
            $data = (new MemberGoodsService())->goodsChangeSort($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 商品列表
     * @return \json
     */
    public function getGoodsList(){
        $param['mer_id'] = $this->merId;
        $param['type'] = $this->request->param('type','shop','trim,string');
        $param['search_type'] = $this->request->param('search_type',1,'trim,intval');
        $param['keywords'] = $this->request->param('keywords','','trim,string');
        $param['page'] = $this->request->param('page',1,'trim,intval');
        $param['page_size'] = $this->request->param('page_size',10,'trim,intval');
        try {
            $data = (new MemberGoodsService())->getGoodsList($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 添加商品
     * @return \json
     */
    public function saveGoods(){
        $param['mer_id'] = $this->merId;
        $param['type'] = $this->request->param('type','','trim,string');
        $param['goods_ids'] = $this->request->param('goods_ids');
        try {
            $data = (new MemberGoodsService())->saveGoods($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $data);
    }
}