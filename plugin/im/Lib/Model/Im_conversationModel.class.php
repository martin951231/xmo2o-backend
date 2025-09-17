<?php

class Im_conversationModel extends Model
{
    const TYPE_SINGLE = 3;

    const TYPE_GROUP = 4;

    public function getConversationByUsername($username, $relation='', $keyword = '', $limit = 100,$toUser = '')
    {
        if ($username) {
            $where = array('c.from_user' => $username, 'c.is_del' => 0);
            if($relation){
                $where['c.relation'] = $relation;
            }
            if($toUser){
                $where['c.to_user'] = $toUser;
            }
            if ($keyword) {
                $where['u.nickname'] = array('LIKE', '%' . $keyword . '%');
            }
            $lists = $this->alias('c')
                ->join(C('DB_PREFIX') . 'im_user AS u ON c.to_user = u.username')
                ->where($where)
                ->field('c.*,u.nickname,u.avatar')
                ->limit($limit)
                ->order('status DESC,last_msg_time DESC')
                ->select();
            $rs = array();

            $chatRecordMod = D('Im_chat_record');
            $resumeMod = D('New_recruit_resume');
            foreach ($lists as $l) {
                $companyName = '';
                list($prefix,$uuid) = Im_userModel::getUidAndPrefix($l['to_user']);
                if ($prefix == Im_userModel::PREFIX_HR) {
                    $company = D('New_recruit_hr')->alias('hr')
                        ->join(C('DB_PREFIX') . 'new_recruit_company as com ON hr.mer_id =com.mer_id')
                        ->join(C('DB_PREFIX') . 'merchant as m ON m.mer_id =com.mer_id')
                        ->field('com.name,com.short_name,m.logo')
                        ->where(['hr.uid' =>$uuid])
                        ->find();
                    $companyName = $company['name']?:'';
                }

                if ($l['relation'] == 'hr2user') {
                    $resume = $resumeMod->where(['uid' => $uuid, 'is_del' => 0])->find();
                }
                $index = Im_chat_recordModel::buildChatIndexKey($username, $l['to_user']);
                $textCount = $chatRecordMod->where(['index_key' => $index, 'msg_type' => 'text', 'show_rule' => ['IN', [0, 2]]])->count();
                $rs[] = array(
                    'key' => $l['id'],
                    'username' => $l['to_user'],
                    'nickname' => $resume['name'] ?: $l['nickname'],
                    'prefix' => $prefix,
                    'avatar' => replace_file_domain($resume['portrait'] ?: $l['avatar']),
                    'relation' => $l['relation'],
                    'status' => $l['status'],
                    'last_msg' => $l['last_msg'],
                    'last_msg_time' => $l['last_msg_time'] > 0 ? self::formateDate($l['last_msg_time']) : self::formateDate($l['create_time']),
                    'unread_msg_count' => $l['unread_msg_count'],
                    'company_name' => $companyName,
                    'label' => $textCount == 1 ? '新招呼' : '',
                    'label_color' => $textCount == 1 ? '#2973F0' : '',
                    'job_id' => $l['job_id'],
                    'resume_id' => $l['resume_id']
                );
            }
        }
        return isset($rs) ? $rs : array();
    }

    /**
     * 获取未读消息数
     */
    public function getUnreadMsgCount($username, $relation = '')
    {
        $where = [
            'c.from_user' => $username,
            'c.is_del' => 0
        ];
        if ($relation) {
            $where['c.relation'] = $relation;
        }
        $count = $this->alias('c')
            ->join(C('DB_PREFIX') . 'im_user AS u ON c.to_user = u.username')
            ->where($where)
            ->sum('unread_msg_count');
        return intval($count);
    }

    /**
     * 获取两个账号之间的关系
     */
    public static function getRelation($username1, $username2)
    {
        return Im_userModel::getUserPrefix($username1) . '2' . Im_userModel::getUserPrefix($username2);
    }

    /**
     * 格式化日期
     */
    public static function formateDate($unix)
    {
        $pre1day = strtotime(date('Y-m-d', strtotime('-1 day')));
        $year = strtotime(date('Y'));
        $today = $pre1day + 86400;
        if ($unix >= $today) {
            return date('H:i', $unix);
        } else if ($unix >= $pre1day && $unix < $today) {
            return '昨天' . date('H:i', $unix);
        } else if ($unix >= $year) {
            return date('m/d H:i', $unix);
        } else {
            return date('m/d H:i', $unix);
        }
    }

    /**
     * 创建会话
     *
     */
    public function build($from, $to,$type, $jobId = 0, $resumeId = 0)
    {
        $tm = time();
        list($fprefix,$fuid) = Im_userModel::getUidAndPrefix($from);
        list($tprefix,$tuid) = Im_userModel::getUidAndPrefix($to);
        $fuuid = $fprefix.'_'.$fuid;
        $tuuid = $tprefix.'_'.$tuid;
        $hasConversation = $this->where(array('from_uuid' => $fuuid, 'to_uuid' => $tuuid))->find();
        if (!$hasConversation) {
            //没有则创建会话
            $data = array(
                array(
                    'from_user' => $from,
                    'to_user' => $to,
                    'from_uuid' => $fuuid,
                    'to_uuid' => $tuuid,
                    'type' => $type,
                    'relation' => self::getRelation($from, $to),
                    'create_time' => $tm,
                    'job_id' => $jobId,
                    'resume_id' => $resumeId
                ),
                array(
                    'from_user' => $to,
                    'to_user' => $from,
                    'from_uuid' => $tuuid,
                    'to_uuid' => $fuuid,
                    'type' => $type,
                    'relation' => self::getRelation($to, $from),
                    'create_time' => $tm,
                    'job_id' => $jobId,
                    'resume_id' => $resumeId)
            );
            return $this->addAll($data);
        } else {
            if ($hasConversation['from_user'] == $from && $hasConversation['to_user'] == $to) {
                //当前对接客服是同一个，若会话标记为删除，则恢复，否则退出
                $update = array('status' => 1, 'is_del' => 0, 'job_id' => $jobId,'resume_id' => $resumeId);
                $rs1 = $this->where(array('id' => $hasConversation['id']))->save($update);
                $rs2 = $this->where(array('from_uuid' => $tuuid, 'to_uuid' => $fuuid))->save($update);
                //如果客服也删除了会话，则恢复会话同时连接数+1
                $inc = false;
                if (in_array($fprefix, [Im_userModel::PREFIX_PLAT, Im_userModel::PREFIX_STORE])) {
                    $inc = $hasConversation['from_user'] && $rs1;
                    $kf = $hasConversation['from_user'];
                } else if (in_array($tprefix, [Im_userModel::PREFIX_PLAT, Im_userModel::PREFIX_STORE])) {
                    $inc = $hasConversation['to_user'] && $rs2;
                    $kf = $hasConversation['to_user'];
                } else if(in_array($tprefix, [Im_userModel::PREFIX_VILLAGE])){
                    $kf = $hasConversation['to_user'];
                }
                //修正客服连接数
                $count = M('Im_conversation')->where(['from_user' => $kf, 'status' => 1])->count();
                D('Merchant_store_kefu')->where(['username' => $kf])->setField('cur_receive', $count ?: 0);
                return;
            } else {
                //不是同一个客服，则更新当前对接客服信息
                $this->where(array('id' => $hasConversation['id']))
                    ->save(array('from_uuid' => $fuuid, 'from_user' => $from, 'to_uuid' => $tuuid, 'to_user' => $to, 'status' => 1, 'job_id' => $jobId,'resume_id' => $resumeId));
                $this->where(array('from_uuid' => $tuuid, 'to_uuid' => $fuuid))
                    ->save(array('from_uuid' => $tuuid, 'from_user' => $to, 'to_uuid' => $fuuid, 'to_user' => $from, 'status' => 1, 'job_id' => $jobId,'resume_id' => $resumeId));
                return true;
            }
        }
    }
}