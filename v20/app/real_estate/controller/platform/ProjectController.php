<?php


namespace app\real_estate\controller\platform;

use app\real_estate\model\service\ProjectService;

class ProjectController extends AuthBaseController
{
    /**
     * 项目列表-列表
     * @return \json
     */
    public function getList(){
        $params = [];
        $params['page'] = request()->param('page', 1, 'trim,intval');
        $params['page_size'] = request()->param('page_size', 10, 'trim,intval');
        try {
            $data = (new ProjectService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 项目列表-添加
     * @return \json
     */
    public function add(){
        $params = [];
        $params['name'] = request()->param('name', '', 'trim,string');
        $params['place'] = request()->param('place', '', 'trim,string');
        try {
            $data = (new ProjectService())->add($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 项目列表-编辑
     * @return \json
     */
    public function edit(){
        $params = [];
        $params['id'] = request()->param('id', '', 'trim,intval');
        $params['name'] = request()->param('name', '', 'trim,string');
        $params['place'] = request()->param('place', '', 'trim,string');
        try {
            $data = (new ProjectService())->edit($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 项目列表-单个信息详情
     * @return \json
     */
    public function show(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        try {
            $data = (new ProjectService())->show($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 项目列表-单个/批量删除
     * @return \json
     */
    public function delete(){
        $params = [];
        $params['id'] = request()->param('id');
        try {
            $data = (new ProjectService())->delete($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}