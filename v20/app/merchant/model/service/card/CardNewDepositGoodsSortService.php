<?php
/**
 * 商家寄存商品类型service
 * Author: fenglei
 * Date Time: 2021/11/04 11:32
 */

namespace app\merchant\model\service\card;

use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsSort;

class CardNewDepositGoodsSortService 
{
    public $depositGoodsSort = null;
    public $depositGoodsModel = null;

    public function __construct()
    {
        $this->depositGoodsSort = new CardNewDepositGoodsSort();
        $this->depositGoodsModel = new CardNewDepositGoods();
    }
    /**
     * 获取列表
     */
    public function getGoodsSortList($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['is_del', '=', 0];
        return $this->depositGoodsSort->where($condition)->order('sort desc')->paginate($params['page_size']);
    }
    /**
     * 添加修改
     */
    public function handleGoodsSort($params)
    {
        if(empty($params['mer_id']) || empty($params['name'])){
            throw new \Exception('请输入完整参数');
        }
        //添加
        if(empty($params['sort_id']))
        {
            $condition = array();
            $condition[] = ['name', '=', $params['name']];
            $condition[] = ['is_del', '=', 0];
            $condition[] = ['mer_id', '=', $params['mer_id']];
            if($this->depositGoodsSort->where($condition)->count()){
                throw new \Exception('类型已存在！');
            }
            $params['is_del'] = 0;
            $params['create_time'] = time();
            $this->depositGoodsSort->save($params);
        }else{//修改
            $osrt = $this->depositGoodsSort->where('sort_id', $params['sort_id'])->find();
            $osrt->name = $params['name'];
            $osrt->describe = $params['describe'];
            $osrt->sort = $params['sort'];
            $osrt->save();
        }
    }
    /**
     * 获取一条详情
     */
    public function getGoodsSortInfo($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['sort_id', '=', $params['sort_id']];
        $info = $this->depositGoodsSort->where($condition)->find();
        if(!$info){
            throw new \Exception('内容不存在！');
        }
        return $info;
    }

    /**
     * 获取类型列表
     */
    public function getGoodsSortSelect($mer_id)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $mer_id];
        $condition[] = ['is_del', '=', 0];
        return $this->depositGoodsSort->where($condition)->order('sort desc')->select();
    }

    public function delGoodsSort($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['sort_id', '=', $params['sort_id']];
        $condition[] = ['is_del', '=', 0];
        $info = $this->depositGoodsSort->where($condition)->find();
        if(!$info){
            throw new \Exception('内容不存在！');
        }
        //
        $this->depositGoodsModel->where('sort_id', $params['sort_id'])->save(['sort_id'=>0]);


        $info->is_del = 1;
        return $info->save();
    }
}