<?php
/**
 * 导入的用户service
 * Author: chenxiang
 * Date Time: 2020/5/25 18:20
 */

namespace app\common\model\service;

use app\common\model\db\UserImport;

class UserImportService {
    public $userImportObj = null;
    public function __construct()
    {
        $this->userImportObj = new UserImport();
    }

    /**
     * 获取导入的用户信息
     * User: chenxiang
     * Date: 2020/5/25 18:44
     * @param array $where
     * @return array|\think\Model|null
     */
    public function getUserFromUserImport($where = []) {
        $result = $this->userImportObj->getUserImport($where);
        return $result;
    }

    /**
     * 更新导入用户信息
     * User: chenxiang
     * Date: 2020/5/26 17:55
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveUserImport($where = [], $data = []) {
        $result = $this->userImportObj->saveUserImport($where, $data);
        return $result;
    }
}
