<?php


namespace app\complaint\controller\platform;


use app\complaint\model\service\ComplaintGroupTypeService;

class ComplaintGroupTypeController extends AuthBaseController
{
    /**
     * 获取投诉列表
     * @return \json
     */
    public function getList(){
        $params['page'] = request()->param('page',1,'trim,intval');
        $params['pageSize'] = request()->param('pageSize',10,'trim,intval');
        $params['gid'] = request()->param('gid',0,'trim,intval');
        try {
            $data = (new ComplaintGroupTypeService())->getList($params);
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
        $params['sort'] = request()->param('sort',0,'trim,intval');
        $params['gid'] = request()->param('gid',0,'trim,intval');
        try {
            $data = (new ComplaintGroupTypeService())->saveData($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function edit(){
        $params['name'] = request()->param('name','','trim,string');
        $params['sort'] = request()->param('sort',0,'trim,intval');
        $params['gid'] = request()->param('gid',0,'trim,intval');
        $params['id'] = request()->param('id',0,'trim,intval');
        try {
            $data = (new ComplaintGroupTypeService())->saveData($params);
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
            $data = (new ComplaintGroupTypeService())->del($params);
            return api_output(0, $data);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
    }
}