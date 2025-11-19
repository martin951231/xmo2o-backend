<?php
/**
 * 微信登录相关
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 11:26
 */

namespace app\common\model\service\weixin;
use app\common\model\db\LoginQrcode as LoginQrcodeModel;
use think\facade\Env;
class LoginQrcodeService  {
    public $loginQrcodeModel = null;
    public function __construct()
    {
        $this->loginQrcodeModel = new LoginQrcodeModel();
    }

    /**
     * 根据条件删除数据
     * @param $where
     * @return bool
     */
    // $field,$op,$value
    public function del($where){

        $result = $this->loginQrcodeModel->delete($where);
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
            $result = $this->loginQrcodeModel->insertGetId($data);
        }catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return bool
     */
	public function updateThis($where,$data){
        if (!$where || !$data) {
            return false;
		}

		try {
			$result = $this->loginQrcodeModel->updateThis($where,$data);
        }catch (\Exception $e) {
			return false;
        }

		return $result;
    }



    /**
     * 根据条件获取一条记录
     * @param $where array
     * @author 衡婷妹
     * @date 2020/07/04
     * @return array
     */
    public function getOne($where) {
        if(empty($where)){
            return [];
        }

        $detail = $this->loginQrcodeModel->getOne($where);
        if(!$detail) {
            return [];
        }

        return $detail->toArray();
    }


    /**
     * 根据条件获取多条记录
     * @param $where array
     * @author 衡婷妹
     * @date 2020/07/04
     * @return array
     */
    public function getSome($where) {
        if(empty($where)){
            return [];
        }

        $list = $this->loginQrcodeModel->getSome($where);
        if(!$list) {
            return [];
        }

        return $list->toArray();
    }
    
}