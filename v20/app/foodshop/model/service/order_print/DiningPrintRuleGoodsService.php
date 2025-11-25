<?php
/**
 * 餐饮打印规则绑定的分类或商品service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/21 15:02
 */

namespace app\foodshop\model\service\order_print;
use app\foodshop\model\db\DiningPrintRuleGoods;
class DiningPrintRuleGoodsService {
    public $diningPrintRuleGoodsModel = null;
    public function __construct()
    {
        $this->diningPrintRuleGoodsModel = new DiningPrintRuleGoods();
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
            $result = $this->diningPrintRuleGoodsModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->diningPrintRuleGoodsModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array
     */
    public function getCount($where = []){
        $res = $this->diningPrintRuleGoodsModel->getCount($where);
        if(!$res) {
            return 0;
        }
        return $res;
    }

    /**
     * 根据条件获取列表
     * @param $where
     * @author 衡婷妹
     * @date 2020/09/21
     */
    public function getSome($where = [], $field = true, $order=true, $page=0, $limit=0)
    {
        $res = $this->diningPrintRuleGoodsModel->getSome($where, $field, $order, $page, $limit);

        if(!$res){
            return [];
        }

        return $res->toArray();
    }

    /**
     * 删除
     * @author 衡婷妹
     */
    public function delthis($where)
    {
        if (empty($where)) {
            return false;
        }

        $rs = $this->diningPrintRuleGoodsModel->where($where)->delete();
        if (!$rs) {
            return false;
        }
        return true;
    }

}