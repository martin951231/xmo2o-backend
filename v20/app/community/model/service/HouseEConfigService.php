<?php

namespace app\community\model\service;
use app\community\model\db\HouseEConfig;

class HouseEConfigService
{
    public function getEConfig($where=[],$field=true)
    {
        $db_house_e_config = new HouseEConfig();
        $data = $db_house_e_config->getConfig($where,$field);
        return $data;
    }
}