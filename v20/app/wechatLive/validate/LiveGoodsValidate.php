<?php
declare (strict_types=1);

namespace app\wechatLive\validate;


use think\Validate;

class LiveGoodsValidate extends Validate
{
    protected $rule = [
        'mer_id'          => 'require|number',
        'store_id'        => 'require|number',
        'goods_id'        => 'require|number',
        'goods_name'      => 'require|max:14|min:1',
        'goods_type'      => 'require|checkType',
        'goods_image'     => 'require',
        'price'           => 'require',
        'platform_status' => 'require|in:1,2',
        'page'            => 'require|number',
        'page_size'       => 'require|number',
    ];

    protected $message = [
        'mer_id.require'      => '商家ID不可为空！',
        'store_id.require'    => '店铺ID不可为空！',
        'goods_name.require'  => '商品名称不可为空！',
        'goods_name.max'      => '商品名称最长14个汉字！',
        'goods_name.min'      => '商品名称最短5个汉字！',
        'goods_id.require'    => '商品ID不可为空！',
        'goods_type.require'  => '商品类型不可为空！',
        'goods_image.require' => '商品图片不可为空！',
        'price.require'       => '商品价格不可为空！',
        'page.require'        => '页码不可为空！',
        'page_size.require'   => '分页参数不可为空！',
        'status.in'           => '状态值必须在 1,2 范围内！',
    ];

    protected $scene = [
        'goods_list'                     => ['mer_id', 'goods_type', 'page', 'page_size'],
        'live_goods_add'                 => ['mer_id', 'store_id', 'goods_id', 'goods_name', 'goods_type', 'goods_image', 'price'],
        'live_goods_edit'                => ['mer_id', 'store_id', 'id', 'goods_id', 'price', 'goods_type'],
        'live_goods_list'                => ['page', 'page_sizes'],
        'live_goods_delete'              => ['mer_id', 'id', 'goods_id', 'store_id'],
        'live_goods_change_status'       => ['id', 'goods_id', 'store_id', 'status'],
        'live_goods_set_commission_rate' => ['id', 'goods_id', 'commission_rate'],

        'api_live_list' => ['page', 'page_size'],
    ];

    // 自定义验证规则
    protected function checkType($value)
    {
        $type = ['shop', 'mall', 'village_group','group'];

        if (!in_array($value, $type)) {
            return '商品类型不正确！';
        }
        return true;
    }
}