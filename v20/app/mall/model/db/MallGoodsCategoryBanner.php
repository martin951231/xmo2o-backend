<?php
/**
 * MallGoodsCategoryBanner.php
 * 平台后台-分类列表-轮播图
 * Create on 2020/9/14 10:23
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallGoodsCategoryBanner extends Model
{
    /**
     * 获取轮播图
     * @param $where
     * @param bool $field
     * @return array
     *
     */
    public function getBannerList($where, $field = true)
    {
        $arr = $this->field($field)->where($where)->order('sort DESC')->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }

    }

    /**
     * 更新轮播图
     * @param $arr
     * @param $where
     * @return MallGoodsCategoryBanner
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
        $result = $this->where($where)->delete();
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