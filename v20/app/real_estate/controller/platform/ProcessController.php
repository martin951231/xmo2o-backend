<?php


namespace app\real_estate\controller\platform;

use app\real_estate\model\service\ProcessService;

class ProcessController extends AuthBaseController
{
    /**
     * 购买流程-列表
     * @return \json
     */
    public function getList(){
        $params = [];
        $params['page'] = request()->param('page', 1, 'trim,intval');
        $params['page_size'] = request()->param('page_size', 10, 'trim,intval');
        try {
            $data = (new ProcessService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-添加
     * @return \json
     */
    public function add(){
        $params = [];
        $params['name'] = request()->param('name', '', 'trim,string');
        $params['sort'] = request()->param('sort', 0, 'trim,intval');
        $params['pay_type'] = request()->param('pay_type');
        $params['font_color'] = request()->param('font_color', '', 'trim,string');
        try {
            $data = (new ProcessService())->add($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-编辑
     * @return \json
     */
    public function edit(){
        $params = [];
        $params['id'] = request()->param('id', '', 'trim,intval');
        $params['name'] = request()->param('name', '', 'trim,string');
        $params['sort'] = request()->param('sort', 0, 'trim,intval');
        $params['pay_type'] = request()->param('pay_type');
        $params['font_color'] = request()->param('font_color', '', 'trim,string');
        try {
            $data = (new ProcessService())->edit($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-单个信息详情
     * @return \json
     */
    public function show(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        try {
            $data = (new ProcessService())->show($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-删除
     * @return \json
     */
    public function delete(){
        $params = [];
        $params['id'] = request()->param('id');
        try {
            $data = (new ProcessService())->delete($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-步骤值修改
     * @return \json
     */
    public function changeSort(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        $params['sort'] = request()->param('sort', 0, 'trim,intval');
        try {
            $data = (new ProcessService())->changeSort($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购买流程-付款类型
     * @return \json
     */
    public function payNameList(){
        try {
            $data = (new ProcessService())->payNameList();
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}