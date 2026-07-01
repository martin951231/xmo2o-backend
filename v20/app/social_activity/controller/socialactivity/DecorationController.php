<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\model\service\DecorationService;

class DecorationController extends AuthBaseController
{
    /**
     * 获取装修页面渲染的信息
     */
    public function getUrlAndRecSwitch()
    {
        try {
            $res = (new DecorationService())->getUrlAndRecSwitch();
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取广告列表
     */
    public function getAdAdverList()
    {
        try {
            $params = [
                'cat_key' => $this->request->param('cat_key', '', 'trim,string'),
            ];
            
            $res = (new DecorationService())->getAdverList($params);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取广告详情
     */
    public function getAdAdverDetail()
    {
        try {
            $id = $this->request->param('id', 0, 'trim,intval');
            
            $res = (new DecorationService())->getAdverDetail($id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 保存广告
     */
    public function saveAdAdver()
    {
        try {
            $data = [
                'cat_key' => $this->request->param('cat_key', '', 'trim,string'),
                'name' => $this->request->param('name', '', 'trim,string'),
                'pic' => $this->request->param('pic', '', 'trim,string'),
                'select_pic' => $this->request->param('select_pic', '', 'trim,string'),
                'url' => $this->request->param('url', '', 'trim,string'),
                'sort' => $this->request->param('sort', 0, 'trim,intval'),
                'status' => $this->request->param('status', 0, 'trim,intval'),
                'id' => $this->request->param('id', 0, 'trim,intval'),
            ];
            
            $res = (new DecorationService())->saveAdver($data);
            return api_output(0, ['adver_id' => $res], '保存成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除广告
     */
    public function deleteAdAdver()
    {
        try {
            $id = $this->request->param('id', 0, 'trim,intval');
            
            (new DecorationService())->deleteAdver($id);
            return api_output(0, [], '删除成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 修改可视化设置
     */
    public function changeSelect(){
        try {
            $params = [
                'name' => $this->request->param('name', '', 'trim,string'),
                'value' => $this->request->param('value', '', 'trim,string'),
            ];

            (new DecorationService())->changeSelect($params);
            return api_output(0, [], '修改成功');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
