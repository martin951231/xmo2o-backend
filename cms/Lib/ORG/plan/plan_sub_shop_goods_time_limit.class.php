<?php
/**
 * 定时更新外卖商品限时优惠状态
 * User: 衡婷妹.
 * Date: 2021/02/02.
 * Time: 16:15.
 */

/**
 * 子计划任务
 */
class plan_sub_shop_goods_time_limit extends plan_base{
    //主计划任务
    public function runTask($param){
		if(empty($param['store_id'])){
            return true;
        }
        
        $store_id = $param['store_id'];

        //查找店铺列表
        $where = array();
        $where['l.store_id'] = $store_id;
        $where['l.is_del'] = 0;

        $field = 'l.id,l.start_date,l.end_date,l.week,l.start_time,l.end_time,l.stock_type,l.is_spec,g.goods_id,g.name,g.price,g.image,g.sort_id,g.store_id,g.stock_num as goods_stock,g.status AS goods_status,g.spec_value,g.is_properties';
        $list = M('Time_limited_discount_goods')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on g.goods_id=l.goods_id')
                ->field($field)
                ->where($where)
                ->select();
       
        if(empty($list)){
            return true;
        }

        $now_time = time();
        $idArr = [];//记录已失效的活动id
        foreach($list as $key =>$goods){
            $this->keepThread();
            if(!$goods['goods_id']){
                $idArr[] =  $goods['id'];
                    continue;
            }

            if(strtotime($goods['end_date']. ' ' . $goods['end_time']) < time()){
                $idArr[] =  $goods['id'];
                continue;
            }

            if ($goods['is_spec'] == 1) {// 多规格
                $record['spec_list'] = D('Time_limited_discount_goods_spec')->where(['limit_id' => $goods['id']])->getField('spec_index,id,limit_id,stock,origin_stock,limit_price', true);

                //如果多规格变动，则限时优惠不生效
                $spec_arr = $record['spec_list'] ? array_unique(array_column($record['spec_list'], 'spec_index')) : [];
                $format_spec_list_keys = [];
                if ($goods['spec_value']) {
                    $spec_array = explode('#', $goods['spec_value']);
                    foreach ($spec_array as $v) {
                        $rowArray = explode('|', $v);
                        if ($rowArray[0]) {
                            $format_spec_list_keys[] = str_replace(':', '_', $rowArray[0]);
                        }
                    }
                }
                if (array_diff($spec_arr, $format_spec_list_keys)) {
                    // 规格不匹配
                    $idArr[] =  $goods['id'];
                    continue;
                }
            }else {
                //如果商品增加了规格，则限时优惠没有规格不生效
                if ($goods['spec_value']) {
                    // 规格不匹配
                    $idArr[] =  $goods['id'];
                    continue;
                }
            }

        }
        if($idArr){
            $where = [
                'id' => ['in',$idArr]
            ];
            $saveData = [
                'is_del' => 1
            ];
            M('Time_limited_discount_goods')->where($where)->save($saveData);
        }
        return true;
    }
}