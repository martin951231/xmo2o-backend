<?php
namespace app\community\model\db\Device;

use think\Model;

class GarbageCollectionMachineList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 设备类型
     * @var string[] 
     */
    public $type = [
        1=>'智能厨余处理设备（DTU）',
        2=>'智能垃圾分类箱（DTU）',
        3=>'智能厨余处理+垃圾分类箱（DTU）',
        4=>'集中处理厨余设备',
        5=>'智能垃圾分类箱',
        7=>'智能垃圾房',
        8=>'智能厨余处理设备（屏幕版）',
        9=>'智能厨余处理+垃圾分类箱（屏幕版）',
        10=>'单桶控制垃圾箱',
        11=>'RFID垃圾中转站',
        12=>'手持终端设备',
        13=>'智能巡检台',
        14=>'AI智能识别垃圾分类箱',
        15=>'车载RFID巡检台',
        16=>'单桶控制垃圾箱（DTU）',
        17=>'混投垃圾箱',
        18=>'瓶类回收箱',
        19=>'混投+瓶类垃圾箱',
    ];
    /**
     * 设备状态
     * @var string[] 
     */
    public $status = [
        1=>'在线',
        2=>'离线',
        3=>'维修中',
        4=>'维修完成'
    ];
    
    public function getList($where,$field,$order,$paeSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('ml')
            ->join($prefix.'house_village v','v.village_id = ml.village_id','left');
        if($where){
            $list = $list->where($where)->field($field)->order($order);
        }else{
            $list = $list->field($field)->order($order);
        }
        if($paeSize){
            $list = $list->paginate($paeSize)->toArray();;
        }else{
            $list = $list->select();
        }
        return $list;
    }
    
    /**
     * 设备类型
     */
    public function getTypeText($type)
    {
        return $this->type[$type]??'';
    }
    
    /**
     * 设备状态
     */
    public function getStatusText($status)
    {
        return $this->status[$status]??'';
    }
    
    /**
     * 设备详情
     */
    public function getDetail($where,$field)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
}