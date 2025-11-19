<?php


namespace app\community\model\service\Device;


use app\community\model\db\Device\GarbageCollectionWarnList;
use app\community\model\db\HouseVillage;

class GarbageCollectionWarnService
{
    /**
     * 获取预警类型
     */
    public function getWarnType()
    {
        $list = [];
        $typeInfo = (new GarbageCollectionWarnList())->type;
        foreach ($typeInfo as $key=>$type){
            $list[] = [
                'key'=>$key,
                'value'=>$type
            ];
        }
        return $list;
    }
    /**
     * 预警记录列表
     */
    public function warnList($param)
    {
        $where = [['status','=',1]];
        $field= 'id,device_no,name,address,bucke_no,type,warn_time,status,content,last_status,last_time';
        $order = 'add_time desc';
        if($param['status'] && in_array($param['status'],[1,2])){
            $where[] = ['last_status','=',$param['status']];
        }
        if($param['device_no']){
            $where[] = ['device_no','=',$param['device_no']];
        }
        if($param['name']){
            $where[] = ['name','like','%'.$param['name'].'%'];
        }
        if($param['type']){
            $where[] = ['type','=',$param['type']];
        }
        if($param['village_id']){
            $where[] = ['village_id','=',$param['village_id']];
        }
        $list = (new GarbageCollectionWarnList())->getList($where,$field,$order,$param['pageSize']);
        foreach ($list['data'] as &$item){
            if(!$item['warn_time']){
                $json = json_decode($item['content'],true);
                if(isset($json['dateWarn'])){
                    if(strlen($json['dateWarn'])==13){
                        $json['dateWarn'] = substr($json['dateWarn'], 0, -3);
                    }
                    $item['warn_time'] = $json['dateWarn'];
                }
            }
            $item['warn_time'] = $item['warn_time'] ? date('Y-m-d H:i:s',$item['warn_time']) : '';
            $item['last_time'] = $item['last_time'] ? date('Y-m-d H:i:s',$item['last_time']) : '';
            $item['type_str'] = (new GarbageCollectionWarnList())->getTypeText($item['type']);
            $item['status'] = $item['last_status'];
            $item['status_str'] = (new GarbageCollectionWarnList())->getStatusText($item['last_status']);
        }
        return $list;
    }
    /**
     * 预警详情
     */
    public function warnDetail($param)
    {
        if(!$param['id']){
            throw_exception(L_('查看对象不能为空'));
        }
        $info = (new GarbageCollectionWarnList())->getDetail(['id'=>$param['id']],'id,device_no,name,village_id,address,bucke_no,type,warn_time,status');
        if(!$info){
            throw_exception(L_('未查询到投放信息'));
        }
        $info['village_name'] = $info['village_id'] ? (new HouseVillage())->where('village_id',$info['village_id'])->value('village_name') : '';
        $info['warn_time'] = date('Y-m-d H:i:s',$info['warn_time']);
        $info['type_str'] = (new GarbageCollectionWarnList())->getTypeText($info['type']);
        $info['status_str'] = (new GarbageCollectionWarnList())->getStatusText($info['status']);
        return $info;
    }
}