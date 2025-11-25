<?php
/**
 * 团购优惠组合
 * Author: 衡婷妹
 * Date Time: 2020/11/17 09:54
 */

namespace app\group\model\service;

use app\group\model\db\GroupCombineActivityGoods;

use longLat;
class GroupCombineActivityGoodsService
{
    public $groupCombineActivityGoodsModel = null;

    public function __construct()
    {
        $this->groupCombineActivityGoodsModel = new GroupCombineActivityGoods();
    }

    public function getCombineCount($param = [])
    {
        $combine_id = $param['combine_id'] ?? [];
        $condition = [];

        //  组合ID
        if (!empty($combine_id)) {
            $condition[] = ['combine_id', 'in', $combine_id];
        }

        $condition[] = ['is_del', '=', 0];
        $field = 'combine_id,count(*) as nums';
        $list = $this->groupCombineActivityGoodsModel->getCombineCount($condition, $field);
        return $list;
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getBindList($param = [])
    {
        $combineId = $param['combine_id'] ?? 0;
        $page = $param['page'] ?? 0;
        $limit = $param['limit'] ?? 0;
        $lat = $param['lat'] ?? 0;//维度
        $lng = $param['lng'] ?? 0;//经度
        // 团购id
        $groupId = $param['group_id'] ?? 0;

        $condition = [];

        // 排序
        $order = [];
        if(isset($param['order']) && $param['order']){
            switch ($param['order']){
                case 'sale_count'://销量排序
                    $order['g.sale_count'] = 'DESC';
                    break;
                case 'distance'://距离排序
                    if($lat<=0 || $lng<=0){
                        $order['g.sale_count'] = 'DESC';
                    }
                    break;
            }
        }

        $order['b.id'] = 'ASC';

        $condition[] = [
            ['b.combine_id' ,'=', $combineId],
        ];

        $field = 'b.*,m.name as merchant_name,g.*';

//        var_dump($order);die;
        // 商品列表
        $list = $this->groupCombineActivityGoodsModel->getBindList($condition, $field, $order, $page, $limit);
        if(empty($list)){
            return [];
        }
        $list = $list->toArray();

        // 按距离排序
        if(isset($param['order']) && $param['order'] == 'distance'){
            if($lat>0 && $lng>0){
                $groupIds = array_column($list,'group_id');
                $where = [
                    'group_ids' => $groupIds,
                    'lat' => $lat,
                    'lng' => $lng,
                ];
                $storeList = (new StoreGroupService())->getStoreByGroup($where);
                $sortArr = [];
                foreach ($list as $key => &$group){
                    foreach ($storeList as $store){
                        if($group['group_id'] == $store['group_id']){
                            $group['juli'] = $store['juli'];
                            $sortArr[] = $store['juli'];
                            continue(2);
                        }
                    }
                }
                array_multisort($sortArr,SORT_ASC,$list );
            }
        }
        $nowGroup = [];
        $groupImage = new GroupImageService();
        foreach ($list as $key => &$_group){
            $tmp_pic_arr = explode(';', $_group['pic']);
            $_group['image'] = $groupImage->getImageByPath($tmp_pic_arr[0], 'm');
            if(isset($param['image_size']) && $param['image_size']){
                $_group['image'] = thumb_img($_group['image'],$param['image_size']['width'],$param['image_size']['height'],'fill');
            }else{
                $_group['image'] = thumb_img($_group['image'],'200','200','fill');
            }
            $_group['url'] = cfg('site_url').'/wap.php?c=Groupnew&a=detail&source=group_combine&group_id='.$_group['group_id'];
            $_group['price'] = get_format_number($_group['price']);
            $_group['old_price'] = get_format_number($_group['old_price']);

            $_group['status_str'] = (new GroupService())->getStatus($_group);

            if (isset($_group['juli'])&&$_group['juli']) {
                $_group['distance'] = get_range($_group['juli']);
            } else if($lng && $lat){
                $location2 = (new longLat())->gpsToBaidu($_group['lat'], $_group['long']);//转换腾讯坐标到百度坐标
                $jl = get_distance($location2['lat'], $location2['lng'], $lat, $lng);
                $_group['distance'] = get_range($jl);
            }else{
                $_group['distance'] = 0;
            }
            $_group['begin_time'] = date('Y-m-d H:i',$_group['begin_time'] );
            $_group['end_time'] = date('Y-m-d H:i',$_group['end_time'] );

            if($groupId>0 && $_group['group_id'] == $groupId){
                $nowGroup[] = $_group;
                unset($list[$key]);
            }
        }
//        var_dump($list);
        $list = array_merge($nowGroup,$list);
        return array_values($list);
    }

    
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivityGoodsModel->insertGetId($data);
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

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupCombineActivityGoodsModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityGoodsModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *删除
     * @param $where array
     * @return array
     */
    public function del($where){
        if(empty($where)){
            return false;
        }
        try {
            $result = $this->groupCombineActivityGoodsModel->where($where)->delete();
        }catch (\Exception $e) {
            return false;
        }

        return $result;
    }
    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupCombineActivityGoodsModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityGoodsModel->getSome($where);
        } catch (\Exception $e) {
            return false;
        }

        return $result->toArray();
    }

    /**
     *获取总数
     * @param $where array
     * @return array
     */
    public function getCount($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityGoodsModel->getCount($where);
        } catch (\Exception $e) {
            return 0;
        }

        return $result;
    }
}