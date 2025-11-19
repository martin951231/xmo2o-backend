<?php

declare (strict_types=1);

namespace app\appoint\validate;

use think\Validate;

class AppointNewsflash extends Validate
{
    protected $rule = [
        'title'           => 'require|max:255',
        'remark'          => 'require|max:255',
        'show_time_type'  => 'require|in:1,2',
        'start_show_time' => 'require|date',
        'end_show_time'   => 'require|date',
        'content'         => 'require',
        'status'          => 'require|in:-1,0,1',
        'views'           => 'require|integer',

        'newsflash_id' => 'require|integer',
        'appoint_id'   => 'require|integer',
        'sort'         => 'require|integer',
    ];

    protected $message = [
        'title.require'           => '资讯标题不能为空',
        'title.max'               => '资讯标题长度不能超过255个字符',
        'remark.require'          => '备注不能为空',
        'remark.max'              => '备注长度不能超过255个字符',
        'show_time_type.require'  => '显示时长类型不能为空',
        'show_time_type.in'       => '显示时长类型值不正确',
        'start_show_time.require' => '开始显示时间不能为空',
        'start_show_time.date'    => '开始显示时间格式不正确',
        'end_show_time.require'   => '结束显示时间不能为空',
        'end_show_time.date'      => '结束显示时间格式不正确',
        'content.require'         => '资讯内容不能为空',
        'status.require'          => '显示状态不能为空',
        'status.in'               => '显示状态值不正确',
        'views.require'           => '浏览量不能为空',
        'views.integer'           => '浏览量必须为整数',

        'newsflash_id.require' => '预约快报资讯ID不能为空',
        'newsflash_id.integer' => '预约快报资讯ID必须为整数',
        'appoint_id.require'   => '预约ID不能为空',
        'appoint_id.integer'   => '预约ID必须为整数',
        'sort.require'         => '排序值不能为空',
        'sort.integer'         => '排序值必须为整数',
    ];

    protected $scene = [
        'save_news'        => ['title', 'show_time_type', 'appoint_json', 'content', 'status'],
        'del_news'         => ['newsflash_id'],
        'news_add_appoint' => ['appoint_id', 'sort'],
        'news_detail'      => ['newsflash_id'],

        'api_news_detail' => ['newsflash_id'],
        'api_news_list'   => ['status'],
    ];

}
