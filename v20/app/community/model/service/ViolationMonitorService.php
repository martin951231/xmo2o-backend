<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/7 17:48
 */

namespace app\community\model\service;

use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseWorker;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\db\WorkMsgAuditInfoGroup;
use app\community\model\db\WorkMsgAuditInfoMarkdown;
use app\community\model\db\WorkMsgAuditInfoMonitor;
use app\community\model\db\WorkMsgAuditInfoText;
use app\community\model\db\WorkMsgAuditInfoSensitive;

class ViolationMonitorService
{
    /**
     * 添加敏感词
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:04
     */
    public function addSensitive($name,$village_id,$property_id)
    {
        $db_sensitive = new WorkMsgAuditInfoSensitive();
        $sensitive_info = $db_sensitive->getOne(['name' => $name]);
        if (!empty($sensitive_info)) {
            if ($sensitive_info['village_id']==$village_id||$sensitive_info['property_id']==$property_id){
                throw new \think\Exception('敏感词名称已存在，无法添加');
            }
        }
        $data = [
            'name' => $name,
            'village_id' => $village_id,
            'property_id' => $property_id,
            'status' => 1,
            'create_time' => time(),
        ];
        $data = $db_sensitive->addOne($data);

        return $data;

    }

    /**
     * 删除敏感词
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     */
    public function delSensitive($id)
    {
        $db_sensitive = new WorkMsgAuditInfoSensitive();
        if (is_array($id)) {
            $id_arr = [];
            foreach ($id as $va) {
                $id_arr[] = $va['id'];
            }
            $id = implode(',', $id_arr);
        } else {
            $db_sensitive = new WorkMsgAuditInfoSensitive();
            $sensitive_info = $db_sensitive->getOne([['id', 'in', $id]]);
            if (empty($sensitive_info)) {
                throw new \think\Exception('敏感词信息不存在，无法删除');
            }
        }

        $sensitive = $db_sensitive->delOne([['id', 'in', $id]]);
        return $sensitive;
    }


    /**
     * 修改敏感词状态
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:46
     */
    public function editSensitiveStatus($id, $status)
    {
        $db_sensitive = new WorkMsgAuditInfoSensitive();
        $sensitive_info = $db_sensitive->getOne(['id' => $id]);
        if (empty($sensitive_info)) {
            throw new \think\Exception('敏感词信息不存在，无法修改');
        }
        $sensitive = $db_sensitive->saveOne(['id' => $id], ['status' => $status]);
        return $sensitive;
    }


    /**
     * 查询敏感词列表
     * @author:zhubaodi
     * @date_time: 2021/5/7 18:59
     */
    public function getSensitiveList($village_id,$property_id,$name, $page, $limit)
    {
        $where = [];
        if (!empty($name)) {
            $where[] = ['name' ,'=',$name];
        }
        if (!empty($village_id)){
            $where[] = ['village_id' ,'=',$village_id];
        }
        if (!empty($property_id)){
            $where[] = ['property_id' ,'=',$property_id];
        }
        $db_sensitive = new WorkMsgAuditInfoSensitive();
        $count = $db_sensitive->getCount($where);
        $list = $db_sensitive->getList($where, $page, true, 'id DESC', $limit);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            foreach ($list as $k => $value) {
                $value['time'] = date('Y-m-d', $value['create_time']);
                $list[$k] = $value;
            }
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }


    /**
     * 查询敏感词监控列表
     * @author:zhubaodi
     * @date_time: 2021/5/10 18:59
     */
    public function getMonitorList($data)
    {
        $where = [];
        $where1=[];
        if (!empty($data['village_id'])) {
            $where[] = ['village_id', '=', $data['village_id']];
        } else {
            $where[] = ['property_id', '=', $data['property_id']];
        }
        if ($data['start_date']) {
            $where[] = ['b.msgtime', '>=', $data['start_date']];
        }
        if ($data['end_date']) {
            $where[] = ['b.msgtime', '<=', $data['end_date']];
        }
        if ($data['user_arr']) {
            $user_id_arr=[];
            foreach ($data['user_arr'] as $value){
               $user_id=explode('-',$value);
                $user_id_arr[]=$user_id[0];
            }
            $user = implode(',', $user_id_arr);
            $where1 = 'b.user_id in ('.$user.') or b.to_user_id in ('.$user.')';
        }
        if ($data['group_arr']) {
            $group_id_arr=[];
            foreach ($data['group_arr'] as $value){
                $group_id=explode('-',$value);
                $group_id_arr[]=$group_id[2];
            }
            $group = implode(',', $group_id_arr);
            $where[] = ['b.roomid','in',$group];
        }

        $db_monitor = new WorkMsgAuditInfoMonitor();
        $db_info = new WorkMsgAuditInfo();
        $db_user = new HouseContactWayUser();
        $db_worker = new HouseWorker();
        $db_group = new WorkMsgAuditInfoGroup();
        $count = $db_monitor->getCount($where);
        $list = $db_monitor->getList($where, $data['page'], 'a.*,b.*', 'a.id DESC', $data['limit'], $where1);
        if (!empty($list)) {
            $list = $list->toArray();
        }
        if (!empty($list)) {
            foreach ($list as $k => $value) {
                if ($value['from_type'] == 1) {
                    $worker = $db_worker->get_one(['wid' => $value['user_id']]);
                    $value['from'] = '工作人员';
                    $value['from_name'] = $worker['name'];
                    $value['avatar'] = $worker['avatar'];

                }
                if ($value['from_type'] == 2) {
                    $user = $db_user->getFind(['customer_id' => $value['external_id']]);
                    if ($user['bind_id']) {
                        $value['from'] = '业主';
                    } else {
                        $value['from'] = ' ';
                    }
                    $value['from_name'] = $user['name'];
                    $value['avatar'] = $user['avatar'];
                }

                if (!empty($value['roomid'])) {
                    $group = $db_group->getFind(['group_id' => $value['roomid']]);
                    $value['to'] = '群聊';
                    $value['to_name'] = $group['roomname'];

                } else {
                    if ($value['to_type'] == 1) {
                        $worker = $db_worker->get_one(['wid' => $value['to_user_id']]);
                        $value['to'] = '工作人员';
                        $value['to_name'] = $worker['name'];
                    }
                    if ($value['to_type'] == 2) {
                        $user = $db_user->getFind(['customer_id' => $value['to_external_id']]);
                        if ($user['bind_id']) {
                            $value['to'] = '业主';
                        } else {
                            $value['to'] = '客户';
                        }

                        $value['to_name'] = $user['name'];
                    }
                }


                $msgtime = substr($value['msgtime'], 0, 10);
                $value['time'] = date('Y-m-d H:i:s', $msgtime);
                if (empty($value['to_name'])) {
                    $value['to_name'] = 'xxx';
                }
                if (empty($value['from_name'])) {
                    $value['from_name'] = 'xxx';
                }
                $value['content'] = str_replace($value['sensitive_name'], '<span style="color: #0a8ddf">' . $value['sensitive_name'] . '</span>', $value['content']);
                $value['title'] = $value['from'] . '【' . $value['from_name'] . '】 对 ' . $value['to'] . '【' . $value['to_name'] . '】 说';
                $list[$k] = $value;
            }
        }
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        return $data1;
    }


    /**
     * 添加敏感内容
     * @author:zhubaodi
     * @date_time: 2021/5/10 19:19
     */
    public function addMonitor($id)
    {
        if (empty($id)) {
            throw new \think\Exception('请上传id');
        }
        $db_text = new WorkMsgAuditInfoText();
        $db_info = new WorkMsgAuditInfo();
        $db_sensitive = new WorkMsgAuditInfoSensitive();
        $db_worker = new HouseWorker();
        $db_user = new HouseContactWayUser();
        $db_monitor=new WorkMsgAuditInfoMonitor();
        $textInfo = $db_text->getFind(['id' => $id]);
        if ($textInfo) {
            $audit_info=$db_info->getFind(['id'=>$textInfo['audit_info_id']]);
            if ($audit_info){
                if ($audit_info['from_type']==1){
                    $address=$db_worker->get_one(['wid'=>$audit_info['user_id']]);
                }
                if ($audit_info['from_type']==2){
                    $address=$db_user->getFind(['customer_id'=>$audit_info['external_id']]);
                }
                $where[]=['status','=',1];
                if ($address){
                    if (!empty($address['village_id'])){
                        $where[]=['village_id','=',$address['village_id']];
                    }else{
                        $where=['property_id','=',$address['property_id']];
                    }
                }
                $sensitive_list=$db_sensitive->getList($where);
                if (!empty($sensitive_list)){
                    $sensitive_list=$sensitive_list->toArray();
                    $sensitive_arr=[];
                    if (!empty($sensitive_list)){
                        foreach ($sensitive_list as $value){
                            $str= strstr($textInfo['content'],$value['name']);
                           if (!empty($str)){
                               $data_monitor=[
                                   'audit_info_id'=> $textInfo['audit_info_id'],
                                   'sensitive_name'=>$value['name'],
                                   'content'=>$textInfo['content'],
                                   'property_id'=>$address['property_id'],
                                   'village_id'=>$address['village_id'],
                                   'create_time'=>time()
                               ];
                               $db_monitor->insert($data_monitor);
                           }
                        }
                    }

                }
            }
            return true;
        }
        return false;

    }

    /**
     * 查询群聊信息
     * @author:zhubaodi
     * @date_time: 2021/5/11 20:14
     */
    public function getGroupChatList()
    {

        $dbHouseWorker = new HouseWorker();
        $dbWorkMsgAuditInfoGroup = new WorkMsgAuditInfoGroup();
        $dbWorkMsgAuditInfo = new WorkMsgAuditInfo();
        $dbHouseContactWayUser = new HouseContactWayUser();
        $group_where = [];
        $group_data = $dbWorkMsgAuditInfoGroup->getList($group_where, 'id as chat_id,roomname as chat,group_id as roomid,member_userid,member_external_id,owner_id,owner_type');
        foreach ($group_data as $key => &$val) {
            $member_userid = explode(',', $val['member_userid']);
            $member_external_id = explode(',', $val['member_external_id']);
            $owner_id = $val['owner_id'];
            $owner_type = $val['owner_type'];
            if ($owner_type == 1) {
                $owner_info = $dbHouseWorker->get_one(['wid' => $owner_id], 'avatar');
                $avatar[] = $owner_info['avatar'];
            } else {
                $owner_info = $dbHouseContactWayUser->getFind(['customer_id' => $owner_id], 'avatar');
                $avatar[] = $owner_info['avatar'];
            }
            $w_where = [];
            $w_where[] = ['wid', 'in', $member_userid];
            $worker_avatar = $dbHouseWorker->getColumn($w_where, 'avatar');
            $w_where = [];
            $w_where[] = ['customer_id', 'in', $member_external_id];
            $way_user_avatar = $dbHouseContactWayUser->getColumn($w_where, 'avatar');
            $avatarDatas = array_merge($avatar, $worker_avatar, $way_user_avatar);
            $avatarData = [];
            foreach ($avatarDatas as $vs) {
                if ($vs) {
                    $avatarData[] = $vs;
                }
            }
            $val['avatarData'] = $avatarData;
            unset($val['member_userid']);
            unset($val['member_external_id']);
            unset($val['owner_id']);
            unset($val['owner_type']);
            $sql_where = [];
            $sql_where[] = ['chat_id', '=', $val['chat_id']];
            $audit_info = $dbWorkMsgAuditInfo->getFind($sql_where, 'id,msgtime,content', 'id desc');

            $val['content'] = $audit_info['content'];
            $val['msgtime'] = $audit_info['msgtime'];
            $val['id'] = $audit_info['id'];
        }
        $data['list'] = $group_data->toArray();
        // print_r($data);exit;
        return $data;
    }
}
