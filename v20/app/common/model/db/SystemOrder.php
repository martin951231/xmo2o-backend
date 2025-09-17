<?php
/**
 * 系统订单汇总表
 */

namespace app\common\model\db;

use think\Model;
class SystemOrder extends Model{
	use \app\common\model\db\db_trait\CommonFunc;

    //修改订单状态
    public function change_status($order_id, $status,$type='foodshop')
    {
        $where['order_id'] = $order_id;
        $data['status'] = $status;
        /*更新平台订单状态*/
        $this->save_order_info($type,$order_id,$data);
        if ($this->where($where)->update($data)) {
            return true;
        } else {
            return false;
        }
    }

    //更新订单状态
    public function save_order_info($type,$order_id,$data=null,$add_sales=0){
        if(!is_array($data)){
            return false;
        }
        $system_id=$this->get_system_id($type,$order_id);
        $where['id']=$data['id']=$system_id;
        if(isset($data['status'])){
            /*if($type=='shop' || $type=='mall'){*/
                $data['system_status']=$data['status'];
          /*  }*/
        }

        $res=$this->where($where)->update($data);
        if($res){
            return array('error'=>0,'msg'=>L_('更新成功！'));
        }else{
            return array('error'=>1,'msg'=>L_('更新失败！'));
        }
    }

    /*获取不同类型订单的平台订单号*/
    private function get_system_id($type,$order_id){
        $where=array();
        $where['type']=$type;
        $where['order_id']=$order_id;

        $system_id=$this->getOne($where,$field="id");
        return $system_id;
    }

    /**
     * 统计某个字段
     * @param  $where array 查询条件
     * @param  $field string 需要统计的字段
     * @author hengtingmei
     * @return string
     */
    public function getTotalByCondition($where,$field){
       if(empty($where) || empty($field)){
           return false;
       }

        $res = $this->where($where)
                    ->field('sum('.$field.') as total,GROUP_CONCAT(IF(`type`="appoint",order_id,0)) as appoint_orderids')
                    ->find();
        //这里涉及到预约订单尾款没统计，额外计算加上吧
        if ($res->appoint_orderids) {
            $orderids = array_filter(explode(',', $res->appoint_orderids));
            if ($orderids) {
                $res->total += \app\common\model\db\AppointOrder::whereIn('order_id', $orderids)->where('product_price', '>', 0)->where('product_real_balace_pay_time', '>', 0)->sum('product_real_balace_price');
            }
        }
        return $res;
    }

    /**
     * 统计商家排行榜
     * @param  $where array 查询条件
     * @param  $field string 需要排名的字段
     * @author hengtingmei
     * @return string
     */
    public function getMerchantRanking($where,$field,$limit=10){
        if(empty($where) || empty($field)){
           return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $res = $this->where($where)
                    ->field($field.' as total , m.name, m.mer_id')
                    ->alias('o')
                    ->join($prefix.'merchant m','m.mer_id = o.mer_id')
                    ->leftJoin($prefix.'appoint_order app','app.order_id = o.order_id AND app.product_real_balace_pay_time > 0')
                    ->limit($limit)
                    ->order(['total'=>'DESC'])
                    ->group('o.mer_id')
                    ->select();
        return $res;
    }

    /**
     * @param $where
     * @return int
     * 获得销量
     */
    public function getNums($where,$field){
        $count = $this
            ->where($where)
            ->sum($field);
        return $count;
    }
    
    public static function changeAddressStatus($type, $orderId, $status)
    {
        //change_address_status '修改地址状态  0 未修改  1 等待商家审核 2 商家拒绝修改 3 骑手接单，修改地址自动撤销'
        return self::where(['type' => $type, 'order_id' => $orderId])->save(['change_address_status' => $status]);
    }
}