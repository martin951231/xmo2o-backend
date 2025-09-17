<?php

/**
 * @Author: jjc
 * @Date:   2020-06-15 11:13:17
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-15 16:19:21
 */

namespace app\mall\model\service;

use app\mall\model\db\MallSearchLog as MallSearchLogModel;

class MallSearchLogService
{

    public $MallSearchLogModel = null;

    public function __construct()
    {
        $this->MallSearchLogModel = new MallSearchLogModel();
    }

    //插入数据
    public function insertData($content, $uid = '')
    {
        return $this->MallSearchLogModel->insertData1111($content, $uid);
    }

//插入一条数据
    public function insertData2($content)
    {
        return $this->MallSearchLogModel->insertData2($content);
    }

    public function getAll($uid)
    {
        $where = [
            ['uid', '=', $uid]
        ];
        $list = $this->MallSearchLogModel->getAll($where, 'content');
        return array_column($list, 'content');
    }

    public function getContent($keyword)
    {
        if (empty($keyword)) {
            throw new \think\Exception("关键词缺失！");
        }
        $where = [
            ['content|content_transcription', 'like', '%' . $keyword . '%']
        ];
        $list = $this->MallSearchLogModel->getAll($where, 'content');

        return array_column($list, 'content');
    }

    /**
     * auth 朱梦群
     * time 2020.10.16
     * 获取搜索记录top
     */
    public function getHotRecord($where)
    {
        $list = $this->MallSearchLogModel->getHotRecord($where);
        if (!empty($list)) {
            $i = 1;
            foreach ($list as $key =>$val){
                $list[$key]['id'] = $i;
                $i++;
            }
            $arr['list'] = $list;
            return $arr;
        } else {
            return [];
        }

    }
}