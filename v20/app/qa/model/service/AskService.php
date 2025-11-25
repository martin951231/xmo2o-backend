<?php

/**
 * 问答service
 */

namespace app\qa\model\service;

use app\common\model\service\UserService;
use app\merchant\model\service\MerchantStoreService;
use app\qa\model\db\Ask;
use app\qa\model\db\AskLabel;
use think\Exception;

class AskService
{
    public $askMod = null;

    public $labelMod = null;

    public function __construct()
    {
        $this->askMod = new Ask();
        $this->labelMod = new AskLabel();
    }

    public function getLabelsByMerId($merId)
    {
        if ($merId < 1) {
            return [];
        }
        $label = $this->labelMod->where('mer_id', $merId)->field('label_name,label_id')->select()->toArray();
        return $label;
    }

    public function getLabelsByStoreId($storeId)
    {
        if ($storeId < 1) {
            return [];
        }
        $default = [['label_name' => '全部', 'label_id' => 0]];
        $labelIds = $this->askMod->where('store_id', '=', $storeId)->where('is_del', '=', 0)->where('label_id', '>', 0)->column('label_id');
        if ($labelIds) {
            $label = $this->labelMod->whereIn('label_id', $labelIds)->field('label_name,label_id')->select()->toArray();
        } else {
            $label = [];
        }
        return array_merge($default, $label);
    }

    public function getAskList($params, $order = [], $addLastReply = true)
    {
        $params['is_del'] = 0;
        $params['fid'] = 0;
        $result = $this->askMod->getAskLists($params, $order);
        list($count, $lists) = [$result['count'], $result['lists']];

        //处理成前端所需接口格式
        $rs = [];
        $userService = new UserService();
        foreach ($lists as $l) {
            $data = [
                'id' => $l['id'],
                'nickname' => $l['nickname'],
                'avatar' => $userService->userAvatarDisplay($l['avatar']),
                'create_time' => date('m-d', $l['create_time']),
                'reply_count' => $l['reply_count'],
                'content' => $l['content'],
                'images' => $this->askMod->formateImage($l['image']),
            ];
            if ($addLastReply) {
                $lastReply = $this->askMod->getLastReplyByAskId($l['id']);
                if ($lastReply) {
                    $lastReply['is_show'] = true;
                    $lastReply['avatar'] = $userService->userAvatarDisplay($lastReply['avatar']);
                } else {
                    $lastReply['is_show'] = false;
                }
                $data['last_reply'] = $lastReply;
            }
            $rs[] = $data;
        }
        return ['count' => $count, 'lists' => $rs];
    }

    /**
     * 保存提问
     * @param $param
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function saveAsk($param)
    {
        $storeId = $param['store_id'] ?? 0;
        $uid = $param['uid'] ?? 0;
        $fid = $param['fid'] ?? 0;
        $content = $param['content'];
        $image = $param['image'] ?? [];

        if (empty($content)) {
            throw new Exception(L_('提问内容不能为空'), 1001);
        }
        if ($storeId < 1) {
            throw new Exception(L_('店铺参数缺失'), 1001);
        }
        if ($uid < 1) {
            throw new Exception(L_('用户未登录'), 1001);
        }
        $storeInfo = (new MerchantStoreService())->getStoreByStoreId($storeId);
        if (empty($storeInfo)) {
            throw new Exception(L_('店铺不存在'), 1003);
        }
        if ($fid > 0 && $this->askMod->where('id', $fid)->findOrEmpty()->isEmpty()) {
            throw new Exception(L_('提问不存在'), 1003);
        }

        $data = [
            'fid' => $fid,
            'uid' => $uid,
            'store_id' => $storeId,
            'mer_id' => $storeInfo['mer_id'],
            'content' => $content,
            'image' => $image ? implode(';', $image) : '',
            'index_show' => 0,
            'label_id' => 0,
            'reply_count' => 0,
            'create_time' => time(),
            'is_del' => 0
        ];
        $id = $this->askMod->insertGetId($data);
        $fid > 0 && $this->syncReplyCount($fid);
        return $id;
    }

    public function syncReplyCount($id)
    {
        $count = $this->askMod->where('fid', $id)->where('is_del', 0)->count();
        $this->askMod->where('id', $id)->update(['reply_count' => $count]);
    }


    public function getLists($params)
    {
        $where = [];
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;

        isset($params['is_del']) && $where[] = ['a.is_del', '=', $params['is_del']];
        isset($params['id']) && $where[] = ['a.id', '=', $params['id']];
        isset($params['fid']) && $where[] = ['a.fid', '=', $params['fid']];
        if (isset($params['mer_id']) && $params['mer_id'] > 0) {
            $where[] = ['a.mer_id', '=', $params['mer_id']];
        }
        if (isset($params['store_id']) && $params['store_id'] > 0) {
            $where[] = ['a.store_id', '=', $params['store_id']];
        }
        if (isset($params['index_show']) && $params['index_show'] >= 0) {
            $where[] = ['a.index_show', '=', $params['index_show']];
        }
        if (isset($params['keyword']) && $params['keyword']) {
            $where[] = ['a.content', 'LIKE', '%' . $params['keyword'] . '%'];
        }

        $total = $this->askMod->alias('a')->where($where)->count();
        $lists = [];
        if ($total > 0) {
            $lists = $this->askMod->alias('a')
                ->join('merchant_store s', 's.store_id = a.store_id')
                ->leftJoin('ask_label l', 'l.label_id = a.label_id')
                ->field("a.*,FROM_UNIXTIME(a.create_time,'%Y-%m-%d %H:%i:%s') AS create_date,s.name as store_name,IFNULL(l.label_name,'') AS label_name")
                ->where($where)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
        }
        return ['list' => $lists, 'total' => $total];
    }

    public function setIndexShow($id, $indexShow)
    {
        if ($id < 1 || !in_array($indexShow, [0, 1])) {
            throw new Exception(L_('参数有误'), 1001);
        }
        return $this->askMod->where('id', $id)->update(['index_show' => $indexShow]);
    }

    public function saveLabels($merId, $labels)
    {
        $labels = array_filter(array_unique($labels));
        if ($merId < 1) {
            throw new Exception(L_('商家参数有误'), 1001);
        }
        if (!is_array($labels) || empty($labels)) {
//            throw new Exception(L_('标签参数有误'), 1001);
            return true;
        }

        $oldLabels = $this->labelMod->where('mer_id', $merId)->column('label_name');
        $newLabel = [];
        $deleteLabel = array_diff($oldLabels, $labels);
        foreach ($labels as $l) {
            if (!in_array($l, $oldLabels)) {
                $newLabel[] = [
                    'mer_id' => $merId,
                    'label_name' => $l
                ];
            }
        }
        $newLabel && $this->labelMod->insertAll($newLabel);
        $deleteLabel && $this->labelMod->where('mer_id', $merId)->whereIn('label_name', $deleteLabel)->delete();
        return true;
    }


    public function saveAskLabel($id, $labelId)
    {
        if ($id < 1 || $labelId < 0) {
            throw new Exception(L_('参数有误'), 1001);
        }
        return $this->askMod->where('id', $id)->update(['label_id' => $labelId]);
    }

    public function askDetail($id)
    {
        if ($id < 0) {
            throw new Exception(L_('参数有误'), 1001);
        }
        $result = $this->askMod->askDetail($id, []);
        $userService = new UserService();
        foreach ($result as &$r) {
            $r['avatar'] = $userService->userAvatarDisplay($r['avatar']);
        }
        return $result;
    }

    public function getAll($params)
    {
        $where = [];
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $askType = $params['ask_type'] ?? 0;

        isset($params['is_del']) && $where[] = ['a.is_del', '=', $params['is_del']];
        if (isset($params['mer_id']) && $params['mer_id'] > 0) {
            $where[] = ['m.mer_id', '=', $params['mer_id']];
        }
        if ($askType == 1) {
            $where[] = ['a.fid', '=', 0];
        } else if ($askType == 2) {
            $where[] = ['a.fid', '>', 0];
        }
        if (isset($params['keyword']) && $params['keyword']) {
            $where[] = ['a.content|s.name|m.name|u.nickname', 'LIKE', '%' . $params['keyword'] . '%'];
        }

        $total = $this->askMod->alias('a')
            ->join('merchant_store s', 's.store_id = a.store_id')
            ->join('merchant m', 'm.mer_id = s.mer_id')
            ->join('user u', 'u.uid = a.uid')
            ->where($where)
            ->count();

        $lists = [];
        if ($total > 0) {
            $lists = $this->askMod->alias('a')
                ->join('merchant_store s', 's.store_id = a.store_id')
                ->join('merchant m', 'm.mer_id = s.mer_id')
                ->join('user u', 'u.uid = a.uid')
                ->field("a.*,FROM_UNIXTIME(a.create_time,'%Y-%m-%d %H:%i:%s') AS create_date,s.name as store_name,m.name AS mer_name,u.nickname,u.phone")
                ->where($where)
                ->page($page, $pageSize)
                ->select()
                ->toArray();
        }
        return ['list' => $lists, 'total' => $total];
    }

    public function deleteByIds(array $ids)
    {
        if (empty($ids)) {
            throw new Exception(L_('参数有误'), 1001);
        }
        $this->askMod->whereIn('id', $ids)->update(['is_del' => 1]);
        $this->askMod->whereIn('fid', $ids)->update(['is_del' => 1]);
        return true;
    }
}
