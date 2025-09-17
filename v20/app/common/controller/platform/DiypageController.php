<?php


namespace app\common\controller\platform;


use app\common\controller\CommonBaseController;
use app\common\model\service\diypage\DiypageModelService;

class DiypageController extends CommonBaseController
{
    /**
     * @return \json
     * 获得装修组件
     */
    public function getDiypageModel()
    {
        $diypageModel = new DiypageModelService();
        $source = $this->request->param('source', 'category', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        try {
            $ret = $diypageModel->getDiypageModel($source, $source_id);
            return api_output(1000, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获得装修详情
     */
    public function getDiypageDetail()
    {
        try {
            $diypageModel = new DiypageModelService();
            $source = $this->request->param('source', 'category', 'trim');
            $source_id = $this->request->param('source_id', 0, 'intval');
            $now_city = $this->request->param('now_city', 0, 'intval');
            $ret = $diypageModel->getDiypageDetail($source, $source_id,$now_city);
            return api_output(1000, $ret);
        } catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 保存装修数据
     */
    public function saveDiypage(){
        try {
            $diypageModel = new DiypageModelService();
            $data['source'] = $this->request->param('source', 'category', 'trim');
            $data['source_id'] = $this->request->param('source_id', 0, 'intval');
            $data['custom'] = $this->request->param('custom', '', 'trim');
            $ret=$diypageModel->saveDiypage($data);
            if($ret!==false){
                return api_output(1000, L_("保存成功"));
            }else{
                return api_output(1003, L_("保存失败"));
            }
        }catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获得热搜词列表
     */
    public function getSearchHotList(){
        try {
            $diypageModel = new DiypageModelService();
            $source = $this->request->param('source', 'category', 'trim');
            $source_id = $this->request->param('source_id', 0, 'intval');
            $ret=$diypageModel->getSearchHotList($source,$source_id);
            return api_output(1000, $ret);
        }catch (\Exception $e) {
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获得店铺子分类列表
     */
    public function getMerchantCategoryChildList(){
        try{
            $diypageModel = new DiypageModelService();
            $cat_id = $this->request->param('cat_id', '', 'intval');
            $ret=$diypageModel->getMerchantCategoryChildList($cat_id);
            return api_output(1000, $ret);
        }catch (\Exception $e){
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json|mixed
     * 用户端主分类装修获得店铺列表
     */
    public function getFeedStoreList(){
        try{
            $data = $this->request->param();
            $diypageModel = new DiypageModelService();
            if(!isset($data['category_id'])){
                return api_output(1003, L_("缺少分类参数"));
            }
            $list=$diypageModel->getFeedStoreList($data);
            return api_output(1000, $list);
        }catch (\Exception $e){
            dd($e);
            //return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json|mixed
     * 用户端子分类装修获得店铺列表
     */
    public function getCategoryStoreList(){
        try{
            $data = $this->request->param();
            if(!isset($data['cat_id'])){
                return api_output(1003, L_("缺少分类参数"));
            }
            $list=(new DiypageModelService())->getCategoryStoreList($data);
            return api_output(1000, $list);
        }catch (\Exception $e){
            return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获得feed流导航分类列表
     */
    public function getFeedCategoryList(){
        try {
            $cat_id = $this->request->param('cat_id', '', 'intval');
            if(empty($cat_id)){
                return api_output(1003, L_("缺少分类参数"));
            }
            $list=(new DiypageModelService())->getFeedCategoryList($cat_id);
            return api_output(1000, $list);
        }catch (\Exception $e){
            return api_output(1003, $e->getMessage());
        }

    }

}