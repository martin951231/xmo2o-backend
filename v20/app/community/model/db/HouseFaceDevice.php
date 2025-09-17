<?php
/**
 * 门禁设备
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;

use think\Model;
use think\Db;
class HouseFaceDevice extends Model
{

    public $device_type_info = array(
        0 => array(
            'device_type' => 1,
            'name' => 'A1智能门禁机'
        ),
        1 => array(
            'device_type' => 2,
            'name' => 'A2智能门禁机'
        ),
        2 => array(
            'device_type' => 3,
            'name' => 'A3智能门禁机'
        ),
        3 => array(
            'device_type' => 4,
            'name' => 'A4智能门禁机'
        ),
        4 => array(
            'device_type' => 5,
            'name' => 'A5智能门禁机'
        ),
        5 => array(
            'device_type' => 6,
            'name' => 'A6智能门禁机'
        ),
        6 => array(
            'device_type' => 7,
            'name' => 'A7智能门禁机'
        ),
        7 => array(
            'device_type' => 61,
            'name' => 'A185智能门禁机'
        ),
        8 => array(
            'device_type' => 21,
            'name' => 'D1智能门禁机'
        ),
        9 =>array(
            'device_type' => 22,
            'name' => 'D2智能门禁机'
        ),
        10 => array(
            'device_type' => 23,
            'name' => 'D3智能门禁机'
        ),
        11 => array(
            'device_type' => 24,
            'name' => 'D4智能门禁机'
        ),
        12 => array(
            'device_type' => 29,
            'name' => '朵普智能门禁机'
        ),
        13 =>array(
            'device_type' => 30,
            'name' => '260云智能门禁'
        ),
        14 =>array(
            'device_type' => 31,
            'name' => '西函智能门禁'
        ),
        15 => array(
            'device_type' => 88,
            'name' => '大华人脸门禁'
        ),
    );

    public $device_brand_arr = array
    (
        'brand_haikang' => array
        (
            'key' => 'brand_haikang',
            'title' => '海康',
            'sub_title' => '海康威视',
        ),
        'brand_dahua' => array
        (
            'key' => 'brand_dahua',
            'title' => '大华',
            'sub_title' => '浙江大华技术股份有限公司',
        ),
        'brand_fastwhale' => array
        (
            'key' => 'brand_fastwhale',
            'title' => '快鲸',
            'sub_title' => '快鲸',
        ),
        'brand_kumike' => array
        (
            'key' => 'brand_kumike',
            'title' => 'kumike',
            'sub_title' => '酷蜜客',
        ),
        'brand_taishou' => array
        (
            'key' => 'brand_taishou',
            'title' => '泰首',
            'sub_title' => '泰首',
        ),
        'brand_zghl' => array
        (
            'key' => 'brand_zghl',
            'title' => '智国互联',
            'sub_title' => '智国互联',
        ),
        'brand_hasx' => array
        (
            'key' => 'brand_hasx',
            'title' => '华安视讯',
            'sub_title' => '华安视讯',
        ),
        'brand_lanniu' => array
        (
            'key' => 'brand_lanniu',
            'title' => '蓝牛',
            'sub_title' => '蓝牛',
        ),
        'brand_cloudwalk' => array
        (
            'key' => 'brand_cloudwalk',
            'title' => '云丛科技',
            'sub_title' => '云丛科技',
        ),
        'brand_anake' => array
        (
            'key' => 'brand_anake',
            'title' => '狄耐克',
            'sub_title' => '厦门狄耐克智能科技股份有限公司',
        ),
        'brand_duopu' => array
        (
            'key' => 'brand_duopu',
            'title' => '朵普',
            'sub_title' => '朵普',
        ),
        'brand_lucc' => array
        (
            'key' => 'brand_lucc',
            'title' => '驴充充',
            'sub_title' => '驴充充',
        ),
        'brand_atc' => array
        (
            'key' => 'brand_atc',
            'title' => '艾特充',
            'sub_title' => '艾特充',
        ),
        'brand_xihan' => array
        (
            'key' => 'brand_xihan',
            'title' => '西涵',
            'sub_title' => '西涵',
        ),
    );


    public function getWhereList($where = [], $field = true, $order = true, $page = 0, $listRows = 0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if ($listRows) {
            $sql->page($page, $listRows);
        }
        $result = $sql->select();
        return $result;
    }
    
    /**
     * Notes: 获取设备列表
     * @param $where
     * @param $field
     * @param string $group
     * @return
     * @author: weili
     * @datetime: 2020/8/3 13:44
     */
    public function getList($where,$field,$group='device_type',$order='device_id desc')
    {
        if($group == 1){
            $list = $this->where($where)->field($field)->order($order)->select();
        }else{
            $list = $this->where($where)->field($field)->group($group)->order($order)->select();
        }
        return $list;
    }

    /**
     * Notes: 获取数量
     * @author: weili
     * @datetime: 2020/8/3 14:00
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    //todo 统计门禁和小区的数据
    public function getDeviceList($where,$group,$field='*',$order='d.village_id desc',$page=0,$limit=10){
        $list = $this->alias('d')
            ->leftJoin('house_village v','v.village_id = d.village_id')
            ->where($where)
            ->field($field)
            ->group($group)
            ->order($order);
        if($page)
        {
            $list->page($page,$limit);
        }
        $list = $list->select();
        return $list;
    }

    //todo 统计门禁和小区的总数
    public function getDeviceCount($where,$group){
        $list = $this->alias('d')
            ->leftJoin('house_village v','v.village_id = d.village_id')
            ->where($where)
            ->group($group)
            ->count();
        return $list;
    }

    /**
     * 查询数据
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getLists($where,$field=true,$order='d.device_id desc',$page=0,$limit=10)
    {
        $sql = $this->alias('d')
            ->leftJoin('house_village v','v.village_id = d.village_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @return mixed
     */
    public function getCounts($where)
    {
        $count = $this->alias('d')
            ->leftJoin('house_village v','v.village_id = d.village_id')
            ->where($where)->count();
        return $count;
    }


    /**
     * 获取单条数据
     * @author: liukezhu
     * @date : 2021/11/29
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 插入一条数据
     * @param $data
     * @return int|string
     */
    public function addData($data){
        return $this->insertGetId($data);
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveData($where,$data){
        return $this->where($where)->save($data);
    }

    public function getColumn($where,$field, string $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
    
    public function getDeviceLists($where,$field='*',$order='d.village_id desc',$limit=10,$whereRaw=''){
        $list = $this->where($where)->field($field);
        if($whereRaw){
            $list = $list->whereRaw($whereRaw);
        }
        if($limit){
            $list = $list->order($order)->paginate($limit)->toArray();
        }else{
            $list = $list->order($order)->select();
            $list = $list ? $list->toArray() : [];
        }
        return $list;
    }
    public function getDeviceListsColumn($where,$column,$order='d.village_id desc',$whereRaw=''){
        $list = $this->where($where);
        if($whereRaw){
            $list = $list->whereRaw($whereRaw);
        }
        $list = $list->order($order)->column($column);
        return $list;
    }
    public function getDeviceListCount($where,$whereRaw=''){
        $list = $this->where($where);
        if($whereRaw){
            $list = $list->whereRaw($whereRaw);
        }
        $list = $list->count();
        return $list;
    }
}