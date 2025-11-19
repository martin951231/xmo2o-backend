<?php
/**
 * 微信授权相关
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 11:26
 */

namespace app\common\model\service\weixin;
use app\common\model\db\AdminQrcode as AdminQrcodeModel;
use think\facade\Env;
class AdminQrcodeService  {
    public $adminQrcodeModel = null;
    public function __construct()
    {
        $this->adminQrcodeModel = new AdminQrcodeModel();
    }

    /**
     * 添加自增属性
     * @param $data
     * @return bool
     */
    public function setAutoIncrement(){
        $prefix = config('database.connections.mysql.prefix');
        $dbName = config('database.connections.mysql.database');
        $sql = "SELECT AUTO_INCREMENT FROM information_schema.`TABLES` WHERE TABLE_SCHEMA = '" . $dbName . "' AND TABLE_NAME ='" . $prefix . "admin_qrcode' LIMIT 1";
        $autoIncrement = $this->adminQrcodeModel->query($sql);

        if (isset($autoIncrement[0]['AUTO_INCREMENT']) && intval($autoIncrement[0]['AUTO_INCREMENT']) < 1600000001) {
            $this->adminQrcodeModel->query('ALTER TABLE ' . $prefix . 'admin_qrcode AUTO_INCREMENT=' . 1600000001);
        }
    }

    /**
     * 根据条件删除数据
     * @param $where
     * @return bool
     */
    // $field,$op,$value
    public function del($where){
       
        $result = $this->adminQrcodeModel->del($where);
        if($result) {
            return true;
        }
        return false;
    }

    /**
     * 添加记录
     * @param $data
     * @return bool
     */
    public function save($data){
        if (!$data) {
            return false;
        }
        
        try {
            $result = $this->adminQrcodeModel->insertGetId($data);
        }catch (\Exception $e) {
            return false;
        }

        return $result;
    }
    
    /**
     * 更新数据
     * @param $id
     * @param $data
     * @return bool
     */
	public function updateById($id,$data){
        if (!$id || !$data) {
            return false;
		}
		
		try {
			$result = $this->adminQrcodeModel->updateById($id,$data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $result;
    }

    /**
     * 根据id返回数据
     * @param $id
     * @return array
     */
    public function getAdminQrcodeById($id) {
        $adminQrcode = $this->adminQrcodeModel->getAdminQrcodeById($id);
        if(!$adminQrcode) {
            return [];
        }
        return $adminQrcode->toArray();
    }

    
}