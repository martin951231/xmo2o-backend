<?php


namespace app\life_tools\controller\merchant;


use app\life_tools\model\service\LifeToolsWifiService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeToolsWifiController extends AuthBaseController
{

    /**
     * 商家后台-wifi-列表
     * @return \json
     */
    public function wifiList(){
        $params = [];
        $params['page'] = $this->request->param('page', 1, 'trim,intval');
        $params['page_size'] = $this->request->param('page_size', 10, 'trim,intval');
        $params['keywords'] = $this->request->param('keywords', '', 'trim');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsWifiService())->getWifiList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 商家后台-wifi-添加/编辑
     * @return \json
     */
    public function wifiAdd(){
        $params = [];
        $params['id'] = $this->request->param('id', '', 'trim,intval');
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['wifi_pass'] = $this->request->param('wifi_pass', '', 'trim');
        $params['long'] = $this->request->param('long', '', 'trim');
        $params['lat'] = $this->request->param('lat', '', 'trim');
        $params['effective_range'] = $this->request->param('lat', '', 'trim,intval');
        $params['issued_by'] = $this->request->param('issued_by', '', 'trim');
        $params['status'] = $this->request->param('status', 0, 'trim,intval');
        $params['start_time'] = $this->request->param('start_time', '', 'trim');
        $params['end_time'] = $this->request->param('end_time', '', 'trim');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsWifiService())->wifiAdd($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 商家后台-wifi-详情
     * @return \json
     */
    public function wifiShow(){
        $param['id'] = $this->request->param('id','','trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsWifiService())->wifiShow($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 商家后台-wifi-状态修改
     */
    public function wifiStatusChange(){
        $param['id'] = $this->request->param('id','','trim,intval');
        $param['status'] = $this->request->param('status',0,'trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsWifiService())->wifiStatusChange($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

    /**
     * 商家后台-wifi-删除
     * @return \json
     */
    public function wifiDelete(){
        $param['id'] = $this->request->param('id','','trim,intval');
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsWifiService())->wifiDelete($param);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $arr, 'success');
    }

}