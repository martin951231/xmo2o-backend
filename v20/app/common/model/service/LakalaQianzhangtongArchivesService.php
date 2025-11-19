<?php
/**
 *拉卡拉钱账通
 */

namespace app\common\model\service;

use app\common\model\db\LakalaQianzhangtongArchives;

class LakalaQianzhangtongArchivesService
{

    public function __construct()
    {

    }

    public function saveData($where = [], $data = [])
    {
        $lakalaQianzhangtongArchives = new LakalaQianzhangtongArchives();
        $retObj = $lakalaQianzhangtongArchives->saveOneData($where, $data);
        return $retObj;
    }

    public function getOneData($where = [], $field = '*', $orderby = 'id DESC')
    {
        $lakalaQianzhangtongArchives = new LakalaQianzhangtongArchives();
        $retObj = $lakalaQianzhangtongArchives->getOneData($where, $field, $orderby);
        $retData = array();
        if ($retObj && !$retObj->isEmpty()) {
            $retData = $retObj->toArray();
        }
        return $retData;
    }

    public function addOneData($data = [])
    {
        $lakalaQianzhangtongArchives = new LakalaQianzhangtongArchives();
        $inserId = $lakalaQianzhangtongArchives->addOneData($data);
        return $inserId;
    }

}
