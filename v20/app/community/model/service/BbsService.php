<?php

namespace app\community\model\service;

use app\community\model\db\BbsAricle;

class BbsService
{
    public $bbsModel = '';
    public function __construct()
    {
        $this->bbsModel = new BbsAricle();
    }

    /**
     * 获取文章数量
     * @author lijie
     * @date_time 2020/08/03 15:50
     * @param $where
     * @return int
     */
    public function getArticleCount($where)
    {
        $count = $this->bbsModel->get_bbs_aricle_num($where);
        return $count;
    }
}