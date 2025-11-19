<?php


namespace app\life_tools\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\life_tools\model\service\IndexRecommendService;

class IndexRecommendController extends AuthBaseController
{
    /**
     * ticket文旅sport体育shop快店mall商城
     */
   public function getRecList(){
       $param['goods_type'] = $this->request->param('goods_type', 'ticket', 'trim');
       $list=(new IndexRecommendService())->getList($param);
       return api_output(0, $list, 'success');
   }

    /**
     * @return \json
     * 更新推荐
     */
   public function updateRec(){
       $param['goods_type'] = $this->request->param('goods_type', 'ticket', 'trim');
       $param['is_show'] = $this->request->param('is_show', '1', 'intval');
       $param['title'] = $this->request->param('title', '', 'trim');
       $param['sort'] = $this->request->param('sort', '0', 'intval');
       $param['id'] = $this->request->param('id', '0', 'intval');
       $param['column_num'] = $this->request->param('column_num', '3', 'intval');
       if(empty($param['id'])){
           return api_output_error(1003, "缺少必要参数");
       }
       $ret=(new IndexRecommendService())->updateRec($param);
       if($ret){
           return api_output(0, [], 'success');
       }else{
           return api_output_error(1003, "更新失败");
       }
   }

    /**
     * @return \json
     * 更新商品
     */
    public function updateRecGoods(){
        $param['sort'] = $this->request->param('sort', '0', 'intval');
        $param['id'] = $this->request->param('id', '0', 'intval');
        if(empty($param['id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        $ret=(new IndexRecommendService())->updateRecGoods($param);
        if($ret){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "更新失败");
        }
    }

    /**
     * 商品列表
     */
   public function getGoodsList(){
       $param['goods_type'] = $this->request->param('goods_type', 'ticket', 'trim');
       $param['page'] = $this->request->param('page', '1', 'intval');
       $param['pageSize'] = $this->request->param('pageSize', '5', 'intval');
       $param['keyWords'] = $this->request->param('keyWords', '', 'trim');
       $list=(new IndexRecommendService())->getGoodsList($param);
       return api_output(0, $list, 'success');
   }

    /**
     * 添加商品
     */
    public function addRecGoods(){
        $param['selectedRowKeys'] = $this->request->param('selectedRowKeys', '', 'trim');
        $param['goods_type'] = $this->request->param('goods_type', 'ticket', 'trim');
        $param['recommend_id'] = $this->request->param('recommend_id', '0', 'intval');
        /*$param['recommend_id'] = $this->request->param('recommend_id', '0', 'intval');
        $param['goods_id'] = $this->request->param('goods_id', '0', 'intval');
        $param['sort'] = $this->request->param('sort', '0', 'intval');
        $param['store_id'] = $this->request->param('store_id', '0', 'intval');
        $param['mer_id'] = $this->request->param('mer_id', '0', 'intval');
        if(!($param['recommend_id'] && $param['goods_id'] && $param['mer_id'])){
            return api_output_error(1003, "缺少参数");
        }*/
        if(empty($param['selectedRowKeys'])){
            return api_output_error(1003, "选择商品");
        }
        if(empty($param['recommend_id'])){
            return api_output_error(1003, "缺少必要参数");
        }
        try{
            $ret=(new IndexRecommendService())->addRecGoods($param);
            if($ret){
                return api_output(0, [], 'success');
            }else{
                return api_output_error(1003, "添加失败");
            }
        }catch (\Exception $e){
            dd($e);
        }
    }

    /**
     * 删除商品
     */
    public function delRecGoods()
    {
        $param['id'] = $this->request->param('id');
        if(empty($param['id'])){
            return api_output_error(1003, "缺少参数");
        }
        $ret=(new IndexRecommendService())->delRecGoods($param);
        if($ret){
            return api_output(0, [], 'success');
        }else{
            return api_output_error(1003, "添加失败");
        }
    }
}