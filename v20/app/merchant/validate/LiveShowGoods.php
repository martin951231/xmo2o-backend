<?php
declare (strict_types=1);

namespace app\merchant\validate;

use think\Validate;

class LiveShowGoods extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'id'         => 'require',
        'percent'    => 'require',
        'goods_type' => 'require',
        'selected'   => 'require',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [

    ];

    /**
     * @var array
     */
    protected $scene = [
        'select_goods_list'        => ['goods_type'],
        'add_live_show_goods'      => ['goods_type', 'selected'],
        'live_show_goods_list'     => ['goods_type'],
        'change_live_show_percent' => ['goods_type', 'id', 'percent'],
        'del_live_show_goods'      => ['goods_type', 'id'],
        'live_show_order'          => ['goods_type'],
    ];
}