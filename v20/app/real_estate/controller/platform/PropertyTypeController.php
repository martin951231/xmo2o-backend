<?php


namespace app\real_estate\controller\platform;

use app\real_estate\model\service\PropertyTypeService;

class PropertyTypeController extends AuthBaseController
{
    /**
     * 购房类型-列表
     * @return \json
     */
    public function getList(){
        $params = [];
        $params['page'] = request()->param('page', 1, 'trim,intval');
        $params['page_size'] = request()->param('page_size', 10, 'trim,intval');
        try {
            $data = (new PropertyTypeService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房类型-添加
     * @return \json
     */
    public function add(){
        $params = [];
        $params['name'] = request()->param('name', '', 'trim,string');
        try {
            $data = (new PropertyTypeService())->add($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房类型-编辑
     * @return \json
     */
    public function edit(){
        $params = [];
        $params['id'] = request()->param('id', '', 'trim,intval');
        $params['name'] = request()->param('name', '', 'trim,string');
        try {
            $data = (new PropertyTypeService())->edit($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房类型-单个信息详情
     * @return \json
     */
    public function show(){
        $params = [];
        $params['id'] = request()->param('id', 0, 'trim,intval');
        try {
            $data = (new PropertyTypeService())->show($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 购房类型-单个/批量删除
     * @return \json
     */
    public function delete(){
        $params = [];
        $params['id'] = request()->param('id');
        try {
            $data = (new PropertyTypeService())->delete($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}