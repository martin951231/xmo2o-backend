<?php


namespace app\building\model;


use think\Model;

class ShoppingMallFloor extends Model
{
    public function getList($where, $field)
    {
        $list = $this->where($where)->field($field)->order('id asc')->select()->toArray();
        return $list;
    }

    public function getCoorListAttr($value)
    {
        $coor_list = $value ? json_decode($value, true) : [];
        return $coor_list;
    }

    public function getBgImgAttr($value)
    {
        $bg_img = $value ? replace_file_domain($value) : '';
        if ($bg_img && strpos($bg_img, 'http') === false) {
            $bg_img = cfg('site_url') . $bg_img;
        }
        return $bg_img;
    }
}