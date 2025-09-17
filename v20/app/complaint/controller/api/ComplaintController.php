<?php
namespace app\complaint\controller\api;

use app\complaint\model\service\ComplaintService;

class ComplaintController extends ApiBaseController
{

    /**
     * 获取投诉类型
     * @return \json
     */
    public function getType(){
        $params = [];
        $params['type'] = request()->param('type', '', 'trim,string');
        try {
            $data = (new ComplaintService())->getType($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 添加投诉信息
     * @return \json
     */
    public function complainSave(){
        $params = [];
        $params['type'] = request()->param('type', 0, 'trim,string');
        $params['other_type'] = request()->param('other_type', 0, 'trim,intval');
        $params['phone'] = request()->param('phone', '', 'trim,string');
        $params['body'] = request()->param('body', '', 'trim,string');
        $params['img'] = request()->param('img','');
        $params['mer_id'] = request()->param('mer_id', 0, 'trim,intval');
        $params['store_id'] = request()->param('store_id', 0, 'trim,intval');
        $params['uid'] = $this->_uid;
        try {
            $data = (new ComplaintService())->complainSave($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}