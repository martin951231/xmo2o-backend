<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 10:56:06
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:04:04
 */

namespace app\mall\model\db;

use think\Model;

class MallGoodsSpecVal extends Model
{

    //获取规格信息列表
    public function getSpecValList($where)
    {
        $arr = $this->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delSome($where)
    {
        return $this->where($where)->delete();

    }

    /**
     * @param $data
     * @return int|string
     * 添加
     */
    public function addOne($data)
    {
        return $this->insert($data);
    }

    /**
     * 根据规格id获取规格值
     * @param $sid
     */
    public function getSpecValueBySid($sid)
    {
        if (empty($sid)) {
            return false;
        }

        $where[] = [
            "spec_id", 'in', $sid
        ];
        $field = 'name,id,spec_id as sid';
        $arr = $this->field($field)->where($where)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

}