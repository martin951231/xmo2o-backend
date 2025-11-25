<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\ShopSliderService;

class ShopSliderController extends AuthBaseController
{
    /**
     * 获取外卖导航列表
     * @return \think\response\Json
     */
    public function getSlider(){
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['pageSize'] = $this->request->post('pageSize', 10, 'intval');
        $params['page'] = $this->request->post('page', 0, 'intval');
        $params['store_id'] = $this->request->post('store_id', 0, 'intval');
        try {
            $data = (new ShopSliderService())->getSlider($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 外卖导航-添加
     * @return \think\response\Json
     */
    public function addSlider(){
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['name'] = $this->request->post('name', '', 'trim,string');
        $params['pic'] = $this->request->post('pic', '', 'trim,string');
        $params['url'] = $this->request->post('url', '', 'trim,string');
        $params['store_id'] = $this->request->post('store_id', 0, 'intval');
        $params['sort'] = $this->request->post('sort', 0, 'intval');
        $params['status'] = $this->request->post('status', 0, 'intval');
        try {
            $data = (new ShopSliderService())->saveSlider($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 外卖导航-编辑
     * @return \think\response\Json
     */
    public function editSlider(){
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['name'] = $this->request->post('name', '', 'trim,string');
        $params['pic'] = $this->request->post('pic', '', 'trim,string');
        $params['url'] = $this->request->post('url', '', 'trim,string');
        $params['store_id'] = $this->request->post('store_id', 0, 'intval');
        $params['sort'] = $this->request->post('sort', 0, 'intval');
        $params['status'] = $this->request->post('status', 0, 'intval');
        $params['id'] = $this->request->post('id', 0, 'intval');
        try {
            $data = (new ShopSliderService())->saveSlider($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 外卖导航-删除
     * @return \think\response\Json
     */
    public function delSlider(){
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        $params['store_id'] = $this->request->post('store_id', 0, 'intval');
        try {
            $data = (new ShopSliderService())->delSlider($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 外卖导航-详情
     * @return \think\response\Json
     */
    public function showSlider(){
        $params = [];
        $params['id'] = $this->request->post('id', 0, 'intval');
        $params['store_id'] = $this->request->post('store_id', 0, 'intval');
        try {
            $data = (new ShopSliderService())->showSlider($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}