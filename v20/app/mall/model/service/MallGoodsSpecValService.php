<?php

/**
 * @Author: jjc
 * @Date:   2020-06-12 13:32:42
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-12 14:04:51
 */

namespace app\mall\model\service;

use app\mall\model\db\MallGoodsSpecVal as MallGoodsSpecValModel;

class MallGoodsSpecValService
{

    public $MallGoodsSpecValModel = null;

    public function __construct()
    {
        $this->MallGoodsSpecValModel = new MallGoodsSpecValModel();
    }

    public function getList($where)
    {
        $list = $this->MallGoodsSpecValModel->getSpecValList($where);
        return $this->dealList($list);
    }

    private function dealList($list)
    {
        $return = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $return[$val['spec_id']][] = $val;
            }
        }
        return $return;
    }

    /**
     * @return mixed
     * 删除
     */
    public function delSome($where)
    {
        $res = $this->MallGoodsSpecValModel->delSome($where);
        return $res;
    }

    /**
     * @return mixed
     * 添加
     */
    public function addOne($data)
    {
        $res = $this->MallGoodsSpecValModel->addOne($data);
        return $res;
    }


    public function insertGetId($data)
    {
        return $this->MallGoodsSpecValModel->insertGetId($data);
    }

}