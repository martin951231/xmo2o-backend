<?php
namespace app\complaint\controller\platform;

use app\complaint\controller\platform\AuthBaseController;
use app\complaint\model\service\ComplaintService;

class ComplaintController extends AuthBaseController
{
    /**
     * 获取投诉列表
     * @return \json
     */
    public function getList(){
        $params = [];
        $params['type'] = request()->param('type', 0, 'trim,intval');
        $params['type_second'] = request()->param('type_second', 0, 'trim,intval');
        $params['keywords'] = request()->param('keywords', '', 'trim,string');
        $params['page'] = request()->param('page',1,'trim,intval');
        $params['status'] = request()->param('status',-1,'trim');
        $params['page_size'] = request()->param('page_size',10,'trim,intval');
        try {
            $data = (new ComplaintService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 获取投诉信息详情
     * @return \json
     */
    public function getDetail(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        try {
            $data = (new ComplaintService())->getDetail($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 获取业务类型
     * @return \json
     */
    public function getTypeList(){
        try {
            $data = (new ComplaintService())->getTypeList();
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 获取投诉信息修改状态
     * @return \json
     */
    public function changeStatus(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['status'] = request()->param('status', 0, 'trim,intval');
        try {
            $data = (new ComplaintService())->changeStatus($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 删除信息
     * @return \json
     */
    public function delete(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        try {
            $data = (new ComplaintService())->delete($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}