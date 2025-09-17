<?php
/**
 * 系统后台用户model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 09:01
 */

namespace app\common\model\db;
use think\Model;
class SystemMenu extends Model {
    
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据用户名获取后端用户表的数据
     * @param $account
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function geMenuList($where = [],$order=['sort DESC','fid ASC','id ASC']) {
        $result = $this->where($where)->order($order)->select();
        return $result;
    }

}