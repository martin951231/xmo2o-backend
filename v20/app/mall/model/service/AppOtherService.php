<?php
/**
 * AppOtherService.php
 * 其他IOSAPP列表service
 * Create on 2020/10/21 10:01
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\AppOther;

class AppOtherService
{
    public function __construct()
    {
        $this->appOtherModel = new AppOther();
    }

    /**
     * 获取所有的iosapp列表
     * @return array
     */
    public function getAll()
    {
        $arr = $this->appOtherModel->getAll();
        return $arr;
    }
}