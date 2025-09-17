<?php


namespace app\roundabout\controller\platform;


use app\common\controller\platform\AuthBaseController;
use app\roundabout\model\service\AdverService;

class AdverController extends AuthBaseController
{
    /**
     * 广告列表
     * @return \think\response\Json
     */
    public function getList(){
        $params['page'] = $this->request->param('page',0,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['begin_time'] = $this->request->param('begin_time', '', 'trim');
        $params['end_time'] = $this->request->param('end_time', '', 'trim');
        $params['is_common'] = $this->request->param('is_common', -1, 'intval');
        $params['status'] = $this->request->param('status', -1, 'intval');
        $params['keyword'] = $this->request->param('keyword', '', 'trim');

        try {
            $res = (new AdverService())->getLisit($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 修改状态
     * @return \think\response\Json
     */
    public function changeStatus(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['status'] = $this->request->param('status',0,'trim,intval');
        $params['type'] = 1;
        try {
            $res = (new AdverService())->updataAdver($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 删除
     * @return \think\response\Json
     */
    public function delAdver(){
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['type'] = 2;
        try {
            $res = (new AdverService())->updataAdver($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 添加
     * @return \think\response\Json\
     */
    public function addAdver(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['pic'] = $this->request->param('pic','','trim,string');
        $params['show_position'] = $this->request->param('show_position',[]);
        $params['link_url'] = $this->request->param('link_url','','trim,string');
        $params['is_common'] = $this->request->param('is_common',1,'intval');
        try {
            $res = (new AdverService())->saveAdver($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return \think\response\Json
     */
    public function editAdver(){
        $params['name'] = $this->request->param('name','','trim,string');
        $params['start_time'] = $this->request->param('start_time','','trim,string');
        $params['end_time'] = $this->request->param('end_time','','trim,string');
        $params['pic'] = $this->request->param('pic','','trim,string');
        $params['show_position'] = $this->request->param('show_position',[]);
        $params['link_url'] = $this->request->param('link_url','','trim,string');
        $params['id'] = $this->request->param('id',0,'trim,intval');
        $params['is_common'] = $this->request->param('is_common',1,'intval');
        try {
            $res = (new AdverService())->saveAdver($params);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    //获取平台广告指定区域
    public function getAdverArea(){
        $id = $this->request->param('id',0,'intval');
        try {
            $res = (new AdverService())->getAdverArea($id);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }

    //保存指定区域
    public function adverAreaSet(){
        $id = $this->request->param('id',0,'intval');
        $areaIds = $this->request->param('city_ids','','trim');
        try {
            $res = (new AdverService())->adverAreaSet($id,$areaIds);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1003,$e->getMessage());
        }
    }
}