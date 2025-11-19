<?php


namespace app\complaint\controller\platform;


use app\complaint\model\service\ComplaintGroupService;

class ComplaintGroupController extends AuthBaseController
{
    /**
     * 获取投诉列表
     * @return \json
     */
    public function getList(){
        $params['page'] = request()->param('page',1,'trim,intval');
        $params['pageSize'] = request()->param('pageSize',10,'trim,intval');
        try {
            $data = (new ComplaintGroupService())->getList($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 添加
     * @return \think\response\Json
     */
    public function add(){
        $params['name'] = request()->param('name','','trim,string');
        $params['type'] = request()->param('type','','trim,string');
        $params['sort'] = request()->param('sort',0,'trim,intval');
        try {
            $data = (new ComplaintGroupService())->saveData($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 添加
     * @return \think\response\Json
     */
    public function edit(){
        $params['name'] = request()->param('name','','trim,string');
        $params['type'] = request()->param('type','','trim,string');
        $params['sort'] = request()->param('sort',0,'trim,intval');
        $params['id'] = request()->param('id',0,'trim,intval');
        try {
            $data = (new ComplaintGroupService())->saveData($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 删除
     * @return \think\response\Json
     */
    public function del(){
        $params['id'] = request()->param('id',0,'trim,intval');
        try {
            $data = (new ComplaintGroupService())->del($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}