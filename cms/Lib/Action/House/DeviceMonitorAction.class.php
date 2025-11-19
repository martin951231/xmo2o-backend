<?php

/**
 * 大华摄像机
 * 目前实际调试设备类型 DH-SD4423-D-I 网络球型摄像机
 * Class DeviceMonitorAction
 */
class DeviceMonitorAction extends BaseAction{

    public function list() {
        $where = [];
        $where['village_id'] = $this->house_session['village_id'];
        $page = I('page',1);
        $page_count	=	I('page_count',20);
        $list = D('House_village_monitor_log')->getList($where,$page,$page_count);
        $this->assign('list',$list['list']);
        $this->assign('pagebar',$list['pagebar']);
        $this->display();
    }

    public function videoRecorderAdd() {
        $logId = I('logId');
        if (IS_POST) {
            try {
                $param = $_POST;
                $village_id = $this->house_session['village_id'];
                $property_id = $this->house_session['property_id'];
                $param['logId'] = $logId;
                $param['village_id'] = $village_id;
                $param['property_id'] = $property_id;
                D('House_village_monitor_log')->videoRecorderAdd($param);
            } catch (\Exception $e) {
                $this->error($e->getMessage());exit;
            }
            $this->success('操作成功！',U('list'));exit;
        } elseif ($logId) {
            if ($logId) {
                $where = [];
                $where['logId'] = $logId;
                $monitorLog = D('House_village_monitor_log')->where($where)->find();
            } else {
                $monitorLog = [];
            }
            $this->assign('monitorLog',$monitorLog);
        }
        $this->display();
    }

    public function channelList() {
        $where = [];
        $logId = I('logId');
        $where['village_id'] = $this->house_session['village_id'];
        if ($logId) {
            $where['logId'] = $logId;
        }
        $page = I('page',1);
        $page_count	=	I('page_count',20);
        $list = D('House_village_monitor_log')->getChannelList($where,$page,$page_count);
        $this->assign('list',$list['list']);
        $this->assign('pagebar',$list['pagebar']);
        $this->display();
    }

    public function lookVideo() {
        $camera_id = I('camera_id');
        if ($camera_id) {
            $where = [];
            $where['camera_id'] = $camera_id;
            $monitor = D('House_village_monitor')->where($where)->find();
            $changeIpUrl = C('config.changeIpUrl')?C('config.changeIpUrl'):'http://192.168.0.241:8808';
            $changeIpPort = C('config.cameraChangeIpPort')?C('config.cameraChangeIpPort'):'1935';
            if (isset($monitor['channelNum'])&&$monitor['channelNum']) {
                $monitor['videoTxt'] = "{$changeIpUrl}/live?port={$changeIpPort}&app=myapp&stream=mystream_{$monitor['channelNum']}";
            }
        } else {
            $monitor = [];
        }
        $this->assign('monitor',$monitor);
        // cdn flv资源地址
        $flvMinJs = D('House_village_monitor_log')->flvMinJs;
        $this->assign('flvMinJs',$flvMinJs);
        $this->display();
    }

    public function videoEdit() {
        $camera_id = I('camera_id');
        if (IS_POST) {
            try {
                $param = $_POST;
                $village_id = $this->house_session['village_id'];
                $property_id = $this->house_session['property_id'];
                $param['camera_id'] = $camera_id;
                $param['village_id'] = $village_id;
                $param['property_id'] = $property_id;
                D('House_village_monitor_log')->videoEdit($param);
            } catch (\Exception $e) {
                $this->error($e->getMessage());exit;
            }
            $this->success('操作成功！',U('channelList',$_GET));exit;
        } elseif ($camera_id) {
            if ($camera_id) {
                $where = [];
                $where['camera_id'] = $camera_id;
                $monitor = D('House_village_monitor')->where($where)->find();
                $changeIpUrl = C('config.changeIpUrl')?C('config.changeIpUrl'):'http://192.168.0.241:8808';
                $changeIpPort = C('config.cameraChangeIpPort')?C('config.cameraChangeIpPort'):'1935';
                if (isset($monitor['channelNum'])&&$monitor['channelNum']) {
                    $monitor['videoTxt'] = "{$changeIpUrl}/live?port={$changeIpPort}&app=myapp&stream=mystream_{$monitor['channelNum']}";
                }
            } else {
                $monitor = [];
            }
            $this->assign('monitor',$monitor);
        }
        $this->display();
    }

    public function repairVideo() {
        $camera_id = I('camera_id');
        import('@.ORG.plan.plan_sub_monitor_change_video');
        $param = [
            'type' => 'daHuaRtmpChangeSingle',
            'camera_id' => $camera_id,
        ];
        $plan_sub_monitor_change_video = new plan_sub_monitor_change_video();
        $data = $plan_sub_monitor_change_video->runTask($param);
        exit(json_encode(array('error' => 0, 'msg' => '修复成功，页面在10秒后自动刷新')));
    }
}