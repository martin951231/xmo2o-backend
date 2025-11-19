<?php
/**
 *工单操作记录
 * @author weili
 * @date 2020/10/23
 */
namespace app\community\model\service;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseVillageRepairList;
use app\community\model\db\HouseVillageRepairLog;
use app\community\model\db\User;
class HouseVillageRepairLogService
{
    /**
     * Notes:操作记录
     * @param $param
     * @return bool|int|string
     * @author: weili
     * @datetime: 2020/10/23 14:04
     */
    public function addLog($param)
    {
        if (empty($param['repair_id'])){
            return false;
        }
        $dbHouseVillageRepairList = new HouseVillageRepairList();
        $dbHouseVillageRepairLog = new HouseVillageRepairLog();

        $where[] = ['r.pigcms_id','=',$param['repair_id']];
        $repair = $dbHouseVillageRepairList->getFind($where,'r.*');
        if ($repair) {
            $data['dateline'] = time();
            $data['repair_id'] = intval($param['repair_id']);
            $data['status'] = isset($param['status']) ? intval($param['status']) : 0;
            $data['worker_phone'] = isset($param['phone']) ? $param['phone'] : '';
            $data['worker_name'] = isset($param['name']) ? $param['name'] : '';

            if ($data['status'] == 0 || $data['status'] == 4) {
                $this->sendWork($repair, $data['status']);
                $this->sendSystem($repair, $data['status']);
            } elseif ($data['status'] == 1) {
                $this->sendWork($repair, $data['status']);
                $this->sendUser($repair, $data['status']);
            } else {
                $this->sendUser($repair, $data['status']);
                $this->sendSystem($repair, $data['status']);
            }
            $res = $dbHouseVillageRepairLog->addData($data);
            return $res;
        }

    }

    private function sendWork($param, $status)
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseWorker  = new HouseWorker();
        $templateNewsService =new TemplateNewsService();
        $village = $dbHouseVillage->getOne($param['village_id']);
        if ($village) {
            if ($village['status']!=1) {
                // 如果小区状态不是正常的不予以发送模板消息
                return true;
            }
            $wechat_appid = cfg('wechat_appid');//appid
            $wechat_appsecret = cfg('wechat_appsecret');//appsecret

            $info = $this->getStatus($param['type']);
            $first = $info['first'];
            $type = $info['type'];
            if ($status == 0) {
                if ($village['handle_type']) {
                    $href = cfg('site_url').'/wap.php?c=Worker&a=detail&wxscan=1&pigcms_id=' . $param['pigcms_id'];
                    $worker_where[] = ['status','=',1];
                    $worker_where[] = ['village_id','=',$param['village_id']];
                    $worker_where[] = ['is_del','=',0];
                    $worker_where[] = ['type','=',$type];
                    $workers = $dbHouseWorker->getAll($worker_where);
                    foreach ($workers as $worker) {
                        $data = [
                            'tempKey' => 'OPENTM405462911',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $worker['openid'],
                                'first' => '您有一个' . $first . '需处理',
                                'keyword1' => $first,
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => '物业',
                                'remark' => '\n请点击查看详细信息！'
                            ]
                        ];
                        //调用微信模板消息  send_msg('work', $worker['wid'], $data, $token_info);
                        $templateNewsService->sendTempMsg($data['tempKey'],$data['dataArr']);

                    }
                }
            } else {
                $status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理人员完成', '业主已评价');
                $href = cfg('site_url').'/wap.php?c=Worker&a=detail&wxscan=1&pigcms_id=' . $param['pigcms_id'];
                $worker_where[] = ['status','=',1];
                $worker_where[] = ['village_id','=',$param['village_id']];
                $worker_where[] = ['is_del','=',0];
                $worker_where[] = ['wid','=',$param['wid']];
                $worker = $dbHouseWorker->getAll($worker_where);
                if ($worker && !$worker->isEmpty()){
                    foreach ($worker as $vv){
                        $data = [
                            'tempKey' => 'OPENTM405462911',
                            'dataArr' => [
                                'href' => $href,
                                'wecha_id' => $vv['openid'],
                                'first' => $status_all[$status],
                                'keyword1' => $first,
                                'keyword2' =>'已发送',
                                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                'keyword4' => '物业',
                                'remark' => '\n请点击查看详细信息！'
                            ]
                        ];
                        //调用发送微信模板消息   send_msg('work', $worker['wid'], $data, $token_info);
                        $templateNewsService->sendTempMsg($data['tempKey'],$data['dataArr']);
                    }
                }
            }
        }
    }

    /**
     * Notes: 状态
     * @param $type
     * @return mixed
     * @author: weili
     * @datetime: 2020/10/23 14:32
     */
    public function getStatus($type)
    {
        switch ($type){
            case '1':
                $data['first'] = '物业报修工单';
                $data['type'] = 1;
                return $data;
                break;
            case '2':
                $data['first'] = '水电煤工单';
                $data['type'] = 0;
                return $data;
                break;
            case '3':
                $data['first'] = '投诉建议工单';
                $data['type'] = 0;
                return $data;
                break;
            case '4':
                $data['first'] = '废品回收工单';
                $data['type'] = 1;
                return $data;
                break;
        }
    }
    private function sendSystem($param, $status)
    {
        $dbHouseVillage = new HouseVillage();
        $templateNewsService =new TemplateNewsService();
        $village = $dbHouseVillage->getOne($param['village_id']);
        if ($village) {
            if ($village['status']!=1) {
                // 如果小区状态不是正常的不予以发送模板消息
                return true;
            }
            $wechat_appid = cfg('wechat_appid');//appid
            $wechat_appsecret = cfg('wechat_appsecret');//appsecret

            $info = $this->getStatus($param['type']);
            $first = $info['first'];
            if ($status == 0) {
                if ($village['handle_type'] == 0) {
                    $href = cfg('site_url').'/wap.php?c=Customer&a=detail&pigcms_id=' . $param['pigcms_id'];
                    $tempKey = 'OPENTM405462911';
                    $arr = [
                        'href' => $href,
                        'wecha_id' => $village['openid'],
                        'first' => '您有一个' . $first . '需处理',
                        'keyword1' => $first,
                        'keyword2' =>'已发送',
                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                        'keyword4' => '物业',
                        'remark' => '\n请点击查看详细信息！'
                    ];
                    //调用微信模板消息
                    $templateNewsService->sendTempMsg($tempKey,$arr);
                }
            } else {
                $status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理人员完成', '业主已评价');
                $href = cfg('site_url').'/wap.php?c=Customer&a=detail&pigcms_id=' . $param['pigcms_id'];
                $tempKey = 'OPENTM405462911';
                $arr = [
                    'href' => $href,
                    'wecha_id' => $village['openid'],
                    'first' => $status_all[$status],
                    'keyword1' => $first,
                    'keyword2' =>'已发送',
                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                    'keyword4' => '物业',
                    'remark' => '\n请点击查看详细信息！'
                ];
                //调用微信模板消息
                $templateNewsService->sendTempMsg($tempKey,$arr);
            }
        }
    }
    private function sendUser($param, $status)
    {
        $dbHouseVillage = new HouseVillage();
        $dbHouseWorker  = new HouseWorker();
        $dbUser = new User();
        $templateNewsService =new TemplateNewsService();
        fdump_api(['给客户发送模板消息'.__LINE__,$param,$status],'sendUser',1);
        $village = $dbHouseVillage->getOne($param['village_id']);
        if ($village['status']!=1) {
            // 如果小区状态不是正常的不予以发送模板消息
            return true;
        }
        $user_where[] = ['uid','=',$param['uid']];
        $now_user =$dbUser->getOne($user_where);

        $service_house_village = new HouseVillageService();
        $app_version = isset($_POST['app_version'])&&$_POST['app_version']?intval($_POST['app_version']):0;
        $deviceId = isset($_POST['Device-Id'])&&$_POST['Device-Id']?trim($_POST['Device-Id']):0;
        $param0817 = [
            'pagePath' => 'pages/village/my/',
            'isAll' => true,
        ];
        $pagesMyUrl = $service_house_village->villagePagePath($app_version,$deviceId,$param0817);

        if ($now_user['openid']) {
            $status_all = array('下单成功', '物业已受理', '处理人员已受理', '处理完成', '已评价');
            if ($param['type'] == 1) {
                $first = '物业报修';
//                $href = cfg('site_url').'/wap.php?c=House&a=village_my_repair_detail&village_id='. $param['village_id'] . '&id=' . $param['pigcms_id'];
                $href = $pagesMyUrl . 'waterReportlist?type=1&title='.urlencode('物业报修'). '&id=' . $param['pigcms_id'].'&village_id=' . $param['village_id'];
                if (isset($param['bind_id']) && $param['bind_id']) {
                    $href .= '&pigcms_id='.$param['bind_id'];
                }
            }
            elseif ($param['type'] == 2) {
                $first = '水电煤上报';
//                $href = cfg('site_url').'/wap.php?c=House&a=village_my_utilitieslists&village_id='. $param['village_id'] . '&id=' . $param['pigcms_id'];
                $href = $pagesMyUrl . 'waterReportlist?type=2&title='.urlencode('水电煤上报'). '&id=' . $param['pigcms_id'].'&village_id=' . $param['village_id'];
                if (isset($param['bind_id']) && $param['bind_id']) {
                    $href .= '&pigcms_id='.$param['bind_id'];
                }
            }
            elseif ($param['type'] == 3) {
                $first = '投诉建议';
//                $href = cfg('site_url').'/wap.php?c=House&a=village_my_suggest_detail&village_id='. $param['village_id'] . '&id=' . $param['pigcms_id'];
                $href = $pagesMyUrl . 'waterReportlist?type=3&title='.urlencode('投诉建议'). '&id=' . $param['pigcms_id'].'&village_id=' . $param['village_id'];
                if (isset($param['bind_id']) && $param['bind_id']) {
                    $href .= '&pigcms_id='.$param['bind_id'];
                }
            }
            elseif ($param['type'] == 4) {
                $first = '废品回收';
                $href = cfg('site_url').'/wap.php?c=House&a=village_my_waste_recovery_detail&village_id='. $param['village_id'] . '&id=' . $param['pigcms_id'];
            }
            $where[] = ['status','=',1];
            $where[] = ['village_id','=',$param['village_id']];
            $where[] = ['wid','=',$param['wid']];
            $where[] = ['is_del','=',0];
            $worker = $dbHouseWorker->getOne($where);
            if ($status == 1) {
                $remark = '已经分配给:' . $worker['name'] . ',' . $worker['phone'] . '请您耐心等待！';
            } elseif ($status == 2) {
                $remark = '工作人员:' . $worker['name'] . ',' . $worker['phone'] . '已经接单，'. $param['msg'] .'请您耐心等待！';
            } elseif ($status == 3) {
                $remark = '工作人员:' . $worker['name'] . ',' . $worker['phone'] . '已处理，期待您对本次任务的评价！';
            }
            $tempKey = 'TM00017';
            $arr = [
                'href' => $href,
                'wecha_id' => $now_user['openid'],
                'first' => $first . '订单',
                'OrderSn' => $param['pigcms_id'],
                'OrderStatus' => $status_all[$status],
                'remark' => $remark
            ];
            $wechat_appid = cfg('wechat_appid');//appid
            $wechat_appsecret = cfg('wechat_appsecret');//appsecret
            fdump_api(['给客户发送模板消息'.__LINE__,$tempKey,$arr],'sendUser',1);
            //调用微信模板消息
            $templateNewsService->sendTempMsg($tempKey,$arr);
        }
    }
}