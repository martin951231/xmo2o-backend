<?php
/**
 * 团购优惠组合订单商品
 * Author: 衡婷妹
 * Date Time: 2020/11/19 10:59
 */

namespace app\group\model\service\order;

use app\group\model\db\GroupCombineActivityOrderGoods;
use app\group\model\service\GroupImageService;
use app\group\model\service\StoreGroupService;

class GroupCombineActivityOrderGoodsService
{
    public $groupCombineActivityOrderGoodsModel = null;

    public function __construct()
    {
        $this->groupCombineActivityOrderGoodsModel = new GroupCombineActivityOrderGoods();
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getGoodsList($param = [])
    {
        $orderId = $param['order_id'] ?? 0;

        $condition = [];

        // 排序
        $order = [];
        $order['b.id'] = 'ASC';

        $condition[] = [
            ['b.order_id' ,'=', $orderId],
        ];

        $field = 'b.*,m.name as merchant_name,g.pic';

        // 商品列表
        $list = $this->groupCombineActivityOrderGoodsModel->getGoodsList($condition, $field, $order);
        if(empty($list)){
            return [];
        }
        $list = $list->toArray();


        $groupImage = new GroupImageService();

        $usedCount = 0;//已使用的次数
        foreach ($list as $key => &$_group){
            $tmp_pic_arr = explode(';', $_group['pic']);
            $_group['image'] = $groupImage->getImageByPath($tmp_pic_arr[0], 'm');
            if(isset($param['image_size']) && $param['image_size']){
                $_group['image'] = thumb_img($_group['image'],$param['image_size']['width'],$param['image_size']['height'],'fill');
            }else{
                $_group['image'] = thumb_img($_group['image'],'200','200','fill');
            }
            $_group['url'] = cfg('site_url').'/wap.php?c=Groupnew&a=detail&source=group_combine&group_id='.$_group['group_id'];
            $usedCount += $_group['used_count'];

            $_group['price'] = get_format_number($_group['price']);
            $_group['old_price'] = get_format_number($_group['old_price']);
        }

        $returnArr['list'] =  array_values($list);
        $returnArr['used_count'] =  $usedCount;
        return $returnArr;
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivityOrderGoodsModel->insertGetId($data);
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
            $result = $this->groupCombineActivityOrderGoodsModel->insertAll($data);
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
            $result = $this->groupCombineActivityOrderGoodsModel->where($where)->update($data);
        } catch (\Exception $e) {
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

        $result = $this->groupCombineActivityOrderGoodsModel->getOne($where, $order);
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
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupCombineActivityOrderGoodsModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}