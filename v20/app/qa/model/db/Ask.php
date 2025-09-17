<?php

/**
 * 问答表
 */

namespace app\qa\model\db;

use think\Model;

class Ask extends Model
{
    /**
     * 获取问答列表
     * @param $params
     * @param array $order
     * @return array
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function getAskLists($params, $order = [])
    {
        $where = [];
        $storeId = $params['store_id'] ?? 0;
        $labelId = $params['label_id'] ?? 0;
        $id = $params['id'] ?? 0;
        $page = $params['page'] ?? 0;
        $pageSize = $params['page_size'] ?? 0;

        if ($storeId > 0) {
            $where[] = ['a.store_id', '=', $storeId];
        }
        if ($labelId > 0) {
            $where[] = ['a.label_id', '=', $labelId];
        }
        if ($id > 0) {
            $where[] = ['a.id', '=', $id];
        }
        if (isset($params['is_del'])) {
            $where[] = ['a.is_del', '=', $params['is_del']];
        }
        if (isset($params['fid'])) {
            $where[] = ['a.fid', '=', intval($params['fid'])];
        }

        $count = $this->alias('a')->join('user u', 'u.uid = a.uid')->where($where)->order($order)->count();
        $lists = [];
        if ($count > 0) {
            $fields = 'a.*,u.nickname,u.avatar';
            $obj = $this->alias('a')->join('user u', 'u.uid = a.uid')->where($where)->order($order);
            if ($page > 0 && $pageSize > 0) {
                $obj->page($page, $pageSize);
            }
            $lists = $obj->field($fields)->select()->toArray();
        }
        return ['count' => $count, 'lists' => $lists];
    }

    /**
     * 获取最后回复的一条数据
     * @param $askId
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function getLastReplyByAskId($askId)
    {
        $fields = 'a.*,u.nickname,u.avatar';
        $where = [
            ['a.fid', '=', $askId],
            ['a.is_del', '=', 0],
        ];
        $lastReply = $this->alias('a')->join('user u', 'u.uid = a.uid')->where($where)->field($fields)->order('a.id', 'DESC')->findOrEmpty();

        $rs = [];
        if (!$lastReply->isEmpty()) {
            $rs = [
                'id' => $lastReply->id,
                'nickname' => $lastReply->nickname,
                'avatar' => replace_file_domain($lastReply->avatar),
                'create_time' => date('m-d', $lastReply->create_time),
                //'reply_count' => $lastReply->reply_count,
                'content' => $lastReply->content,
                'images' => $this->formateImage($lastReply->image)
            ];
        }
        return $rs;
    }

    public function formateImage($image)
    {
        $arr = array_filter(explode(';', $image));
        $img=array();
        foreach ($arr as $k=>$v){
            $img[]= replace_file_domain($v);
        }

        return $img;
    }

    public function askDetail($id, $order = [])
    {
        $asks = $this->alias('a')
            ->join('user u', 'u.uid = a.uid')
            ->where('a.is_del', '=', 0)
            ->whereRaw('a.id=:id OR a.fid=:fid', ['id' => $id, 'fid' => $id])
            ->field('a.*,u.nickname,u.avatar')
            ->order('a.id', 'asc')
            ->select();

        $rs = [];
        if (!$asks->isEmpty()) {
            foreach ($asks as $a) {
                $rs[] = [
                    'id' => $a->id,
                    'is_ask' => $a->fid == 0,
                    'nickname' => $a->nickname,
                    'avatar' => replace_file_domain($a->avatar),
                    'create_time' => date('Y-m-d H:i:s', $a->create_time),
                    'content' => $a->content,
                    'images' => $this->formateImage($a->image)
                ];
            }
        }
        return $rs;
    }
}
