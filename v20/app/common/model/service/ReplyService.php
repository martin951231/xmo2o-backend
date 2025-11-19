<?php

namespace app\common\model\service;

use app\common\model\db\Reply;
use think\facade\Db;

class ReplyService
{
    public $replyMod = null;

    public function __construct()
    {
        $this->replyMod = new Reply();
    }

    public function getSome($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        return (new Reply)->getSome($where, $field, $order, $page, $limit);
    }

    public function decrZan($id){
        $reply = $this->replyMod->getOne([['pigcms_id','=',$id]]);
        return $this->updateThis([['pigcms_id','=',$id]], ['zan' => $reply['zan']-1]);
    }
    public function incrZan($id){
        $reply = $this->replyMod->getOne([['pigcms_id','=',$id]]);
        return $this->updateThis([['pigcms_id','=',$id]], ['zan' => $reply['zan']+1]);
    }

    public function updateThis($where, $data)
    {
        return (new Reply)->updateThis($where, $data);
    }

    public function getReplyLists($where, $order = '', $limit = 0)
    {
        $fields = 'r.anonymous,r.pigcms_id,r.score,r.is_good,r.add_time,r.comment,r.reply_pic,r.pic,r.order_type,u.avatar,u.nickname,r.merchant_reply_content,r.merchant_reply_time,r.user_reply_merchant,r.user_reply_merchant_time,r.mer_id,r.uid';
        $totalCount = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->where($where)->count();

        $mod = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->field($fields)->where($where);
        $order && $mod->order($order);
        $limit > 0 && $mod->limit($limit);
        $lists = $mod->select()->toArray();

        if ($lists) {
            $lists = ResourceService::replyModel($lists);
        }
        return ['count' => $totalCount, 'lists' => $lists];
    }


    public function getStoreReplyLists($params)
    {
        $where = [['r.is_del', '=', 0], ['r.status', '<>', 2]];
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 1;
        $groupId = $params['group_id'] ?? 0;
        $storeId = $params['store_id'] ?? 0;
        if ($storeId > 0) {
            $where[] = ['r.store_id', '=', $params['store_id']];
        }
        if ($groupId > 0) {
            $where[] = ['o.group_id', '=', $groupId];
        }
        if (isset($params['score_type'])) {
            switch ($params['score_type']) {
                case 'high':
                    $where[] = ['r.score', '>=', 3];
                    break;
                case 'low':
                    $where[] = ['r.score', '<', 3];
                    break;
                case 'withpic':
                    $where[] = [Db::raw("concat(r.`pic`,r.`reply_pic`)"), '<>', ''];
                    break;
                    
            }
        }


        if (cfg('open_shop_reply_check')) { //开启快店评论审核功能
            $where[] = Db::raw("(r.`order_type` = 3 AND r.`check`=2) or (r.`order_type` != 3)");
        }
        if (cfg('open_mall_reply_check')) { //开启商城评论审核功能
            $where[] = Db::raw("(r.`order_type` = 6 AND r.`check`=2) or (r.`order_type` != 6)");
        }

        $fields = 'r.anonymous,r.pigcms_id,r.score,r.is_good,r.add_time,r.comment,r.reply_pic,r.pic,r.order_type,u.avatar,u.nickname,r.merchant_reply_content,r.merchant_reply_time,r.user_reply_merchant,r.user_reply_merchant_time,r.mer_id,r.uid';
        $totalCount = $this->replyMod->alias('r')
            ->join('user u', 'r.uid = u.uid')
            ->leftJoin('group_order o', 'o.order_id=r.order_id')
            ->where($where)
            ->count();

        if ($totalCount > 0) {
            $lists = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')
                ->leftJoin('group_order o', 'o.order_id=r.order_id')
                ->field($fields)->where($where)->page($page, $pageSize)
                ->order('r.pigcms_id', 'DESC')
                ->select()
                ->toArray();
            $lists = ResourceService::replyModel($lists);
        } else {
            $lists = [];
        }
        if(!empty($params['uid'])){
            //是否可以回复评论
            foreach ($lists as $key => $val) {
                $can_reply = 0;
                if($val['uid'] == $params['uid'] && is_null($val['user_reply']) && !is_null($val['merchant_reply'])){
                    $can_reply = 1;
                }
                $lists[$key]['can_reply'] = $can_reply;
            }
        }
        return ['count' => $totalCount, 'total_page' => ceil($totalCount / $pageSize), 'lists' => $lists];
    }

    public function getStoreScoreLevelCount($param)
    {
        $storeId = $param['store_id'] ?? 0;
        $groupId = $param['group_id'] ?? 0;

        $where = [['r.is_del', '=', 0], ['r.status', '<>', 2]];
        if ($storeId) {
            $where[] = ['r.store_id', '=', $storeId];
        }
        if ($groupId > 0) {
            $where[] = ['o.group_id', '=', $groupId];
        }
        if (cfg('open_shop_reply_check')) { //开启快店评论审核功能
            $where[] = Db::raw("(r.`order_type` = 3 AND r.`check`=2) or (r.`order_type` != 3)");
        }
        if (cfg('open_mall_reply_check')) { //开启商城评论审核功能
            $where[] = Db::raw("(r.`order_type` = 6 AND r.`check`=2) or (r.`order_type` != 6)");
        }

        $totalCount = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->leftJoin('group_order o', 'o.order_id=r.order_id')
            ->where($where)
            ->count();
        $highCount = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->leftJoin('group_order o', 'o.order_id=r.order_id')
            ->where(array_merge($where, [['r.score', '>=', 3]]))
            ->count();
        $lowCount = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->leftJoin('group_order o', 'o.order_id=r.order_id')
            ->where(array_merge($where, [['r.score', '<', 3]]))
            ->count();
        $withPicCount = $this->replyMod->alias('r')->join('user u', 'r.uid = u.uid')->leftJoin('group_order o', 'o.order_id=r.order_id')
            ->where(array_merge($where, [[Db::raw("concat(r.`pic`,r.`reply_pic`)"), '<>', '']]))
            ->count();
        return ['high' => $highCount, 'total' => $totalCount, 'low' => $lowCount, 'withpic' => $withPicCount];
    }

    /**
     * 用户回复商家
     */
    public function userReplyMerchant($rpl_id, $content, $uid)
    {
        if(empty($content)){
            throw new \think\Exception('内容不能为空！');
        }
        $condition = [];
        $condition[] = ['uid', '=', $uid];
        $condition[] = ['pigcms_id', '=', $rpl_id];
        $reply = $this->replyMod->where($condition)->find();
        if(!$reply){
            throw new \think\Exception('评论不存在！');
        }
        if(!empty($reply->user_reply_merchant)){
            throw new \think\Exception('已回复，请勿重复操作！');
        }
        $reply->user_reply_merchant = $content;
        $reply->user_reply_merchant_time = time();
        return $reply->save();
    }
}