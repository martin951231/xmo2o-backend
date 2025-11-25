<?php
/**
 * 商家短信验证码
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/07/03 10:31
 */

namespace app\merchant\model\db;
use think\Model;
class MerchantSmsRecord extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得最后一条数据
     * @param $where array 条件
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public  function getLastOne($where){
        $result = $this->where($where)
                       ->order(['pigcms_id' => 'DESC'])
                       ->find();
        return $result;
    }
}