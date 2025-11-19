<?php

/**
 * 抖音hook
 */
class DouyinAction extends CommonAction
{
    public function callback()
    {
        fdump_sql([file_get_contents("php://input"), $_POST], 'douyin_hook');
        $arr = json_decode(file_get_contents("php://input"), true);

        if ($arr['event'] == 'create_video') {
            $shareId = $arr['content']['share_id'];
            if ($shareId) {
                $shareMod = D('Douyin_activity_shareid');
                $activityMod = D('Douyin_activity');
                $publishRecordMod = D('Douyin_publish_record');
                $userRecordMod = D('Douyin_activity_user_record');

                $shareRecord = $shareMod->where(['share_id' => $shareId])->find();
                if ($shareRecord) {
                    $uid = $shareRecord['uid'];
                    $activityId = $shareRecord['activity_id'];
                    $materialId = $shareRecord['material_id'];

                    //更新用户领券次数
                    $activity = $activityMod->where(['id' => $activityId, 'is_del' => 0, 'status' => 1])->find();
                    if ($activity && $activity['video_num'] > 0) {
                        $userRecord = $userRecordMod->where(['uid' => $uid, 'activity_id' => $activityId])->find();
                        if ($userRecord) {
                            $userRecordMod->where(['id' => $userRecord['id'], 'total_times' => ['lt', $activity['video_num']]])->setInc('total_times', 1);
                        } else {
                            $userRecordMod->add(['uid' => $uid, 'activity_id' => $activityId, 'total_times' => 1, 'create_time' => time()]);
                        }
                    }

                    //记录活动分享日志
                    $publish = [
                        'uid' => $uid,
                        'material_id' => $materialId,
                        'activity_id' => $activityId,
                        'share_id' => $shareId,
                        'create_time' => time()
                    ];
                    $publishRecordMod->add($publish);
                }
            }
        }
        $this->ajaxReturn(['challenge' => $arr['content']['challenge']]);
    }
}