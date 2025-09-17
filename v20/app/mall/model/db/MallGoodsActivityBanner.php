<?php
/**
 * 平台后台-活动推荐-轮播图
 * Create on 2020/10/16 10:23
 * Created by chenxiang
 */

namespace app\mall\model\db;

use think\Model;

class MallGoodsActivityBanner extends Model
{
    /**
     * 获取轮播图
     * @param $where
     * @param bool $field
     * @return array
     */
    public function getBannerList($where, $field = true)
    {
        $arr = $this->field($field)->where($where)->order('sort DESC')->select()->toArray();
        return $arr;
    }

    /**
     * 更新轮播图
     * @param $arr
     * @param $where
     */
    public function updateBanner($arr, $where)
    {
        $result = $this->where($where)->update($arr);
        return $result;
    }

    /**
     * 添加轮播图
     * @param $arr
     * @return int|string
     */
    public function addBanner($arr)
    {
        $result = $this->insert($arr);
        return $result;
    }

    /**
     * 删除轮播图
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delBanner($where)
    {
        $result = $this->where($where)->update(['is_del'=>1]);
        return $result;
    }

    /**
     * 点击次数累累计
     * @param $id  
     */
    public function clickNumberInc($id)
    {
        $banner = $this->find($id);
        if($banner){
            $banner->click_number ++;
            $banner->save();
            return $banner;
        }else{
            return [];
        }
    }
}