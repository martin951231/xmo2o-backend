<?php
/**
 * 商家model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:38
 */

namespace app\merchant\model\db;
use think\Model;
class NewMerchantMenu extends Model {
    
    use \app\common\model\db\db_trait\CommonFunc;

    public function getMenuList($where, $order){
        $this->name = _view($this->name);
        $result = $this ->where($where)
                        ->order($order)
                        ->select();
        return $result;
    }
    
}