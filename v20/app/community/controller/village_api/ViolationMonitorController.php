<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/7 17:33
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\FixedAssetsService;
use app\community\model\service\SessionFileService;
use app\community\model\service\ViolationMonitorService;
use think\facade\Request;

class ViolationMonitorController extends CommunityBaseController
{

    /**
     * 查询监控列表
     * @author:zhubaodi
     * @date_time: 2021/5/7 17:34
     */
    public function monitorList()
    {
        $data['village_id'] = $this->adminUser['village_id'];
        $data['property_id']=0;
        if (empty($data['village_id'])){
            $data['property_id']=$this->adminUser['property_id'];
        }
        $data['time'] = $this->request->param('time', '', 'trim');
        $data['user_arr'] = $this->request->param('user_arr', '', 'trim');
        $data['group_arr'] = $this->request->param('group_arr', '', 'trim');
        $data['page'] = empty($this->request->param('page', '', 'trim'))?1:$this->request->param('page', '', 'trim');
        $data['limit'] = 20;
        if ($data['time']){
            $data['start_date'] =strtotime(date('Y-m-d 00:00:00',strtotime($data['time'][0]))) ;
            $data['end_date'] = strtotime(date('Y-m-d 23:59:59',strtotime($data['time'][1])));
        }else{
            $data['start_date'] = 0;
            $data['end_date'] = 0;
        }

       //  print_r($data);exit;

        $serviceViolationMonitor = new ViolationMonitorService();
        try {
            $list = $serviceViolationMonitor->getMonitorList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }


    /**
     * 查询敏感词
     * @author:zhubaodi
     * @date_time: 2021/5/7 17:34
     */
    public function sensitiveList()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id=$this->adminUser['property_id'];
        $name = $this->request->param('title', '', 'trim');
        $page = $this->request->param('page', '', 'trim');
        $limit = 20;
        $serviceViolationMonitor = new ViolationMonitorService();
        try {
            $list = $serviceViolationMonitor->getSensitiveList($village_id,$property_id,$name, $page, $limit);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 添加敏感词
     * @author:zhubaodi
     * @date_time: 2021/5/7 17:34
     */
    public function addSensitive()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id=$this->adminUser['property_id'];
        $name = $this->request->param('name', '', 'trim');
        if (empty($name)) {
            return api_output(1001, [], '敏感词名称不能为空！');
        }
        if (mb_strlen($name) > 6) {
            return api_output(1001, [], '敏感词名称不得超过六个字！');
        }
        $serviceViolationMonitor = new ViolationMonitorService();

        try {
            $id = $serviceViolationMonitor->addSensitive($name,$village_id,$property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($id < 1) {
            return api_output_error(1001, '添加失败');
        } else {
            return api_output(0, $id);
        }

    }

    /**
     * 修改敏感词状态
     * @author:zhubaodi
     * @date_time: 2021/5/7 17:34
     */
    public function editSensitiveStatus()
    {
        $id = $this->request->param('id', '', 'intval');
        if (empty($id)) {
            return api_output(1001, [], '敏感词id不能为空！');
        }
        $status = $this->request->param('status', '', 'intval');
        if (empty($id)) {
            return api_output(1001, [], '状态不能为空！');
        }
        $serviceViolationMonitor = new ViolationMonitorService();
        try {
            $id = $serviceViolationMonitor->editSensitiveStatus($id, $status);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if ($id < 1) {
            return api_output_error(1001, '修改失败');
        } else {
            return api_output(0, $id);
        }
    }


    /**
     * 删除敏感词
     * @author:zhubaodi
     * @date_time: 2021/5/7 17:34
     */
    public function delSensitive()
    {
        $id = $this->request->param('id', '', 'trim');
        if (empty($id)) {
            return api_output(1001, [], '敏感词id不能为空！');
        }
        $serviceViolationMonitor = new ViolationMonitorService();
        try {
            $id = $serviceViolationMonitor->delSensitive($id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());


        }
        if ($id < 1) {
            return api_output_error(1001, '删除失败');
        } else {
            return api_output(0, $id);
        }
    }

    /**
     * 查询群聊信息
     * @author:zhubaodi
     * @date_time: 2021/5/11 20:13
     */
    public function groupChatList(){
        $serviceViolationMonitor = new ViolationMonitorService();
        try{
            $list = $serviceViolationMonitor->getGroupChatList();
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}