<?php
/**
 *用户行为分析（发表评论）service
 * Created by vscode.
 * Author: 钱大双
 * Date Time: 2020年12月3日10:09:05
 */

namespace app\common\model\service;

use app\common\model\db\UserBehaviorReply as UserBehaviorReplyModel;

class UserBehaviorReplyService
{
    public $type;

    public function __construct()
    {
        $this->type = [
            'meal' => 1,
            'group' => 2,
            'mall' => 3
        ];
    }

    /**
     * 添加用户行为分析（发表评论）
     * @param $data [
     *                          reply_id:1,//评论id
     *                          uid:12,//用户id
     *                          content:'小炒肉',//评论内容
     *                          business_type:'mall',//业务类型：快店：meal，团购：group， 新版商城：mall
     *                          comment_time:'',//评论时间
     *                          from_type:1,//来源：来源：0wap端，1安卓,2ios，3小程序，4pc端，5其他,6移动端
     * ]
     * @return bool
     */
    public function addUserBehaviorReply($data)
    {
        $data['add_time'] = time();
        $data['add_ip'] = get_client_ip(1);
        $data['business_type'] = $this->type[$data['business_type']];
        $userBehaviorReplyModel = new UserBehaviorReplyModel();
        
        return $userBehaviorReplyModel->save($data);
    }
}