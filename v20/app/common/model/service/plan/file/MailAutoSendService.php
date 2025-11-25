<?php


namespace app\common\model\service\plan\file;


use app\common\model\db\ProcessPlanMsg;
use app\common\model\service\user\MailService;
use app\common\model\service\user\UserMsgService;
use app\common\model\service\UserService;
use think\facade\Db;

class MailAutoSendService
{
    /**
     * @param $order_id
     * @author zhumengqun
     * 站内信自动存表计划任务
     */
    public function runTask()
    {
        $where = [['send_status', '=', 0]];
        $list = (new MailService())->getSome($where);
        // 启动事务
        Db::startTrans();
        try {
            if (!empty($list)) {
                $where_user = array();
                foreach ($list as $key => $val) {
                    if ($val['users'] == 1) {//指定用户
                        if (!empty($val['users_label'])) {
                            $labels = unserialize($val['users_label']);
                            if (!empty($labels['area_sel'])) { //选择区域
                                $where_user = [['province_id', '=', $labels['sel_areas'][0]], ['city_id', '=', $labels['sel_areas'][1]]];
                            }
                            if (!empty($labels['level_sel'])) { //选择会员等级
                                array_push($where_user, ['level', '=', $labels['level']]);
                            }
                            if (!empty($labels['label_sel'])) { //选择用户标签
                                array_push($where_user, ['label_id', '=', implode(",", $labels['label_id'])]);
                            }
                        }

                    }
                    $user_list = (new UserService())->getSome($where_user);//符合条件的用户

                    if ($val['set_send_time'] == 0 || ($val['set_send_time'] = time() || time() > $val['set_send_time'])) {//立即发送
                        if ($val['type'] == 0 || $val['type'] == 2) {//消息中心加记录
                            foreach ($user_list as $k => $v) {
                                $data['uid'] = $v['uid'];
                                $data['title'] = $val['title'];
                                if ($val['content_type'] == 1) {
                                    $data['is_link'] = 1;
                                    $data['link_url'] = $val['content'];
                                } else {
                                    $data['is_link'] = 0;
                                    $data['link_url'] = '';
                                }
                                $data['content'] = $val['content'];
                                $data['is_read'] = 0;
                                $data['is_del'] = 0;
                                $data['type'] = 3;
                                $data['mail_id'] = $val['id'];
                                $data['img'] = $val['img'];
                                $ret=(new UserMsgService())->addData($data);
                                if($ret){
                                    throw new \think\Exception("站内信往用户中心添加失败");
                                }
                            }
                        }
                        if ($val['type'] == 0 || $val['type'] == 1) {//手机系统推送
                            if ($val['send_port'] == 0 || $val['send_port'] == 1) {//公众号
                                $msg['content'] = $val['title'];
                                $msg['type'] = 2;
                                $msg['add_time'] = time();
                                $msg['send_time'] = time();
                                $msg['status'] = 0;
                                $msg['from'] ='mail';
                                $ret=(new ProcessPlanMsg())->add($msg);
                                if($ret){
                                    throw new \think\Exception("添加信息推送计划任务失败");
                                }
                            }

                            if ($val['send_port'] == 0 || $val['send_port'] == 2) {//app
                                $msg['content'] = $val['title'];
                                $msg['type'] = 4;
                                $msg['add_time'] = time();
                                $msg['send_time'] = time();
                                $msg['status'] = 1;
                                $msg['from'] ='mail';
                                $ret=(new ProcessPlanMsg())->add($msg);
                                if($ret){
                                    throw new \think\Exception("添加信息推送计划任务失败");
                                }
                            }

                        }
                    }
                    $up['id']=$val['id'];
                    $up['send_status']=1;
                    $up['send_usernums']=count($user_list);
                    $ret=(new MailService())->saveData($up);//更新站内信表
                    if($ret){
                        throw new \think\Exception("更新站内信表失败");
                    }
                }
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            Db::rollback();
        }
    }
}