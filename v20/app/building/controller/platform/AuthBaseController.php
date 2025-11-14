<?php


namespace app\building\controller\platform;


use app\building\model\ShoppingMall;

class AuthBaseController extends \app\common\controller\platform\AuthBaseController
{
    public $mall_id; //商场id

    public function initialize()
    {
        parent::initialize();
        $this->mall_id = input('mall_id', 0, 'trim,intval');
        if (!$this->mall_id) {
            throw new \think\Exception("无效商场！", 1003);
        }
        if ($this->systemUser['level'] != 2) {
            $shopping_mall = (new ShoppingMall())->alias('m')->field('m.*')
                ->join('shopping_mall_bind_admin u', 'u.mall_id=m.id')
                ->where([
                    ['m.id', '=', $this->mall_id],
                    ['u.admin_id', '=', $this->_uid]
                ])->findOrEmpty()->toArray();
            if (!$shopping_mall) {
                throw new \think\Exception("您不是该商场管理员！", 1003);
            }
            if (!$shopping_mall['is_self']) {
                throw new \think\Exception("该商场不支持此项操作！", 1003);
            }
        }

    }
}