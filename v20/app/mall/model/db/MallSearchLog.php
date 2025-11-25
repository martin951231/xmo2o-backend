<?php

/**
 * @Author: jjc
 * @Date:   2020-06-15 10:52:28
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-15 18:41:01
 */

namespace app\mall\model\db;

use think\Model;

class MallSearchLog extends Model
{

    //插入数据
    public function insertData1111($content, $uid = '')
    {
        $data = [];
        $data = [
            'content' => $content,
            //'contentAssoicate' => $contentAssoicate,
            'create_time' => time(),
        ];
        if ($uid) {
            $data['uid'] = $uid;
        }
        $this->save($data);
    }


    public function insertData2($content)
    {
        return $this->insert($content);
    }

    public function getAll($where, $field = "*")
    {
        return $this->where($where)->field($field)->group('content')->select()->toArray();
    }

    /**
     * auth 朱梦群
     * time 2020.10.16
     * 获取搜索记录top
     */
    public function getHotRecord($where)
    {
        $list = $this->field('content, count(1) as times')->where($where)->group('content')->order('times desc')->select();
        if (!empty($list)) {
            return $list->toArray();
        } else {
            return [];
        }
    }

}