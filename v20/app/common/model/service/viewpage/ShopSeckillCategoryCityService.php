<?php
/**
 * 系统后台可视化页面 外卖首页-限时秒杀功能 基础配置
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/21
 */

namespace app\common\model\service\viewpage;
use app\common\model\db\ShopSeckillCategoryCity;
use app\common\model\service\AreaService;

class ShopSeckillCategoryCityService {
    public $shopSeckillCategoryCityModel = null;
    public function __construct()
    {
        $this->shopSeckillCategoryCityModel = new ShopSeckillCategoryCity();
    }


    /**
     * 获得分类绑定的城市
     * @param $data array
     * @return array
     */
    public function getCityList($catInfo){
        if(empty($catInfo)){
            return [];
        }

        $returnArr = [];
        if($catInfo['is_all']){
            $returnArr[] = ['value'=>'all'];
        }else{
            $where = [
                [ 'cat_id', '=', $catInfo['cat_id']]
            ];
            $cityList = $this->getSome($where);
            foreach ($cityList as $_city){
                $returnArr[] = [
                    'value'=>$_city['province_id'].'-'.$_city['city_id']
                ];
            }
        }

        return $returnArr;
    }

    /**
     * 获得分类绑定的城市
     * @param $data array
     * @return array
     */
    public function getCityNameList($catInfo){
        if(empty($catInfo)){
            return [];
        }

        $returnArr = [];
        if($catInfo['is_all']){
            $returnArr[] = '全国';
        }else{
            $where = [
                [ 'cat_id', '=', $catInfo['cat_id']]
            ];
            $cityList = $this->getSome($where);
            $cityIds = array_column($cityList,'city_id');
            $where = [
                ['area_id','in',implode(',',$cityIds)]
            ];
            $returnArr = (new AreaService())->getAreaListByCondition($where);

        }

        return $returnArr;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();

        $id = $this->shopSeckillCategoryCityModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        $id = $this->shopSeckillCategoryCityModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *删除
     * @param $where array
     * @return array
     */
    public function del($where){
        $result = $this->shopSeckillCategoryCityModel->where($where)->delete();

        return $result;
    }

    /**
     *获取一条条数据
     * @param $where array
     * @return array
     */
    public function getOne($where){

        try {
            $result = $this->shopSeckillCategoryCityModel->getOne($where);
        } catch (\Exception $e) {
            return (object)[];
        }
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->shopSeckillCategoryCityModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->diningOrderRefundModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}