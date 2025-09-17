<?php
declare (strict_types=1);

namespace app\life_tools\validate\merchant;

use app\life_tools\model\db\LifeTools;
use \app\life_tools\model\db\LifeToolsScenicMap as MapModel;
use think\Validate;

class LifeToolsScenicMap extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
        'mer_id'               => 'require|number',
        'map_id'               => 'require|number|checkMap',
        'map_ids'              => 'require',
        'place_id'             => 'require',
        'place_ids'            => 'require',
        'name'                 => 'require',
        'status'               => 'require|in:0,1',
        'scenic_id'            => 'require|checkScenic',
        'category_id'          => 'require',
        'scenic_location_line' => 'require',
        'longitude'            => 'require',
        'latitude'             => 'require',
        'line_id'              => 'require',
    ];

    /**
     * 定义错误信息
     * @var array
     */
    protected $message = [
        'mer_id.require'      => '商家ID不可为空！',
        'map_id.require'      => '地图ID不可为空！',
        'name.require'        => '名称不可为空！',
        'status.in'           => '状态值必须在 0,1 范围内！',
        'scenic_id.require'   => '关联景区不可为空！',
        'category_id.require' => '分类不可为空！',
        'longitude.require'   => '经度不可为空！',
        'latitude.require'    => '纬度不可为空！',
    ];

    /**
     * @var array
     */
    protected $scene = [
        'map_list'        => ['mer_id'],
        'save_map'        => ['mer_id', 'name', 'scenic_id'],
        'save_map_status' => ['mer_id', 'map_id', 'status'],
        'map_del'         => ['mer_id', 'map_ids'],

        'map_place_list' => ['mer_id', 'map_id'],
        'map_place_del'  => ['mer_id', 'map_id', 'place_ids'],
        'save_map_place' => ['mer_id', 'map_id', 'name', 'category_id', 'longitude', 'latitude'],

        'map_line_list' => ['mer_id', 'map_id'],
        'save_map_line' => ['mer_id', 'map_id', 'name', 'location_ids', 'scenic_location_img', 'scenic_location_line'],
        'map_line_del'  => ['mer_id', 'map_id', 'line_id'],

        'category_list' => ['mer_id'],
        'save_category' => ['mer_id', 'category_name', 'sort'],
        'category_del'  => ['mer_id', 'category_id'],

        'position' => ['longitude', 'latitude'],

        'api_scenic_map_category'      => ['scenic_id'],
        'api_scenic_map_place'         => ['scenic_id', 'category_id'],
        'api_map_place_detail'         => ['scenic_id', 'place_id'],
        'api_scenic_map_line'          => ['scenic_id'],
        'api_scenic_map_line_distance' => ['scenic_id', 'line_id'],
    ];

    // 自定义验证规则
    protected function checkScenic($value, $rule, $data = [])
    {
        $where['tools_id'] = $data['scenic_id'];
        !empty($data['mer_id']) && $where['mer_id'] = $data['mer_id'];
        $map = LifeTools::where($where)->field('tools_id')->find();

        if (empty($map)) {
            return '景区不存在！';
        }
        return true;
    }

    protected function checkMap($value, $rule, $data = [])
    {
        $where['id'] = $data['map_id'];
        !empty($data['mer_id']) && $where['merchant_id'] = $data['mer_id'];
        $map = MapModel::where($where)->field('id')->find();

        if (empty($map)) {
            return '景区地图不存在！';
        }
        return true;
    }
}
