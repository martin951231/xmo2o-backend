<?php

declare (strict_types=1);

namespace app\appoint\validate;

use think\Validate;

class AppointDecorate extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'id'           => 'require',
        'name'         => 'require',
        'adver_id'     => 'require',
        'slider_id'    => 'require',
        'appoint_id'   => 'require',
        'status'       => 'require|in:0,1',
        'config_name'  => 'require|checkConfig',
        'config_value' => 'require|checkConfig',
        'url'          => 'require',
        'pic'          => 'require',
        'province_id'  => 'require',
        'city_id'      => 'require',
        'appoint_ids'  => 'require',
        'sort'         => 'require',

        'type'        => 'require',
        'wap_title'   => 'require',
        'style'       => 'require',
        'category_id' => 'require',
        'now_city'    => 'require',
        
        
        'title|活动名称'    => 'require',
        'show_time_type|显示时长'    => 'require',
        'template_id|模板ID'    => 'require',
        
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'name.require'         => '名称不可为空！',
        'adver_id.require'     => '广告ID不可为空！',
        'mer_id.require'       => '商家ID不可为空！',
        'appoint_id.require'   => '预约ID不可为空！',
        'slider_id.require'    => '导航ID不可为空！',
        'status.require'       => '状态值不可为空！',
        'status.in'            => '状态值必须在 0,1 范围内！',
        'config_name.require'  => '预约配置不可为空！',
        'config_value.require' => '预约配置不可为空！',
        'url.require'          => '跳转链接不可为空！',
        'pic.require'          => '图片不可为空！',
        'province_id.require'  => '地区不可为空！',
        'city_id.require'      => '地区不可为空！',
        'now_city.require'     => '地区不可为空！',
        'appoint_ids.require'  => '预约商品不可为空！',
        'sort.require'         => '排序值不可为空！',

        'type.require'        => '类型不可为空！',
        'wap_title.require'   => '标题不可为空！',
        'style.require'       => '样式不可为空！',
        'category_id.require' => '分类ID不可为空！',
        'ids.require'         => '活动ID不可为空！',
    ];

    /**
     * @var array
     */
    protected $scene = [
        'appoint_advertisement_add_or_edit' => ['name', 'url', 'pic', 'province_id', 'city_id', 'status', 'sort'],
        'appoint_advertisement_del'         => ['adver_id'],
        'appoint_advertisement_detail'      => ['adver_id'],

        'appoint_slider_add_or_edit' => ['name', 'url', 'pic', 'province_id', 'city_id', 'status', 'sort'],
        'appoint_slider_del'         => ['slider_id'],
        'appoint_slider_detail'      => ['slider_id'],

        'appoint_decorate_index_set' => ['config_name'],
        'appoint_recommend_add'      => ['appoint_ids'],
        'appoint_recommend_edit'     => ['appoint_id', 'sort'],
        'appoint_recommend_del'      => ['appoint_ids'],

        'appoint_category_edit' => ['status', 'wap_title', 'sort', 'style', 'category_id'],

        'api_category_appoint_list'  => ['category_id', 'city_id'],
        'api_recommend_appoint_list' => ['now_city'],
        'api_appoint_store_list'     => ['appoint_id', 'mer_id'],
        'save_activity_list'         => ['title', 'show_time_type', 'template_id'],
        'del_activity'               => ['ids'],
    ];

    // 自定义验证规则
    protected function checkConfig($value, $rule, $data = [])
    {
        $configName = [
            'appoint_style', 'appoint_bottom_style',
            'appoint_show_tab', 'appoint_index_activity'
        ];
        if (!in_array($data['config_name'], $configName)) {
            return '配置名称错误！';
        }
        if ($data['config_name'] == 'appoint_style' && !in_array($data['config_value'], ['normal', 'hot'])) {
            return '样式设置错误！';
        }
        if ($data['config_name'] == 'appoint_bottom_style' && !in_array($data['config_value'], ['normal', 'float'])) {
            return '底部样式设置错误！';
        }
        return true;
    }

}
