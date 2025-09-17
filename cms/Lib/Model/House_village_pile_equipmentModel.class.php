<?php


class House_village_pile_equipmentModel extends Model
{
    public function getList($where=[], $pageSize = 15, $field=true,$order='id DESC')
    {
        $return = array();
        $count = $this->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = $this->field($field)->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($list) {
            $return['count'] = count($list);
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }

        return $return;
    }

    public function getType3EquipmentDetail($village_id=0, $equipment_num='', $equipment_id = 0)
    {
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi=new PileHaoKuaiChong($village_id);
        $apiRet=$pileHaoKuaiChongApi->getEquipmentDetail($equipment_num,$equipment_id);
        return $apiRet;
    }
    public function startType3Tocharge($village_id=0, $order_pile_id=0,$equipment_num=''){
        fdump_api(['village_id'=>$village_id,'order_pile_id'=>$order_pile_id,'equipment_num'=>$equipment_num],'haokuaichong/startType3Tocharge',1);
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi=new PileHaoKuaiChong($village_id);
        $apiRet=$pileHaoKuaiChongApi->startToCharge($order_pile_id);
        fdump_api(['village_id'=>$village_id,'order_pile_id'=>$order_pile_id,'apiRet'=>$apiRet],'haokuaichong/startType3Tocharge',1);
        return $apiRet;
    }

    public function stopType3Tocharge($village_id=0, $order_pile_id=0,$order_no=''){
        fdump_api(['village_id'=>$village_id,'order_pile_id'=>$order_pile_id,'order_no'=>$order_no],'haokuaichong/stopType3Tocharge',1);
        import('@.ORG.PileHaoKuaiChong');
        $pileHaoKuaiChongApi=new PileHaoKuaiChong($village_id);
        $apiRet=$pileHaoKuaiChongApi->stopToCharge($order_pile_id,$order_no);
        fdump_api(['village_id'=>$village_id,'order_pile_id'=>$order_pile_id,'apiRet'=>$apiRet],'haokuaichong/stopType3Tocharge',1);
        return $apiRet;
    }
    /*
     * village_pile_equipment 表 type字段值是3的设备的短短状态值
     */
    public function get_type3_port_state($state=0,$is_all=false){
        $port_state_info=array(
            '0'=>'空闲',
            '1'=>'充电中',
            '2'=>'有充电器但未充电（用 户未启动充电）',
            '3'=>'有充电器但未充电（已充满电）',
            '4'=>'端口有故障（短路或该路无法计量）',
            '5'=>'浮充',
            '6'=>'存储器损坏',
            '7'=>'门磁开关故障',
            '8'=>'接触不良或保险丝烧断故障',
            '170'=>'等待刷卡中',
        );
        if($is_all){
            return $port_state_info;
        }
        $state=strval($state);
        if(isset($port_state_info[$state])){
            return $port_state_info[$state];
        }
        return '其他';
    }
}