<?php
namespace app\community\model\db\Device;

use think\Model;
use think\facade\Db;

class GarbageCollectionWarnList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;    
    
    /**
     * 预警类型
     * @var string[]
     */
    public $type = [
        1=>'箱门异常',
        2=>'满溢报警',
        3=>'称重异常',
        4=>'烟雾报警',
        5=>'离线报警',
        6=>'风机故障',
        7=>'搅拌故障',
        8=>'舱体超温',
        9=>'加热超温',
        10=>'开仓故障',
        11=>'关仓故障',
        12=>'开门故障',
        13=>'关门故障',
        14=>'PLC 主站通讯故障',
        15=>'PLC 从站通讯故障',
        20=>'翻斗故障',
        21=>'回斗故障',
        22=>'影像采集异常',
        23=>'电磁锁异常',
        24=>'门锁未关闭',
        25=>'超温报警',
        26=>'低温报警',
        27=>'无桶预警',
        28=>'跳闸报警',
    ];

    /**
     * 预警状态
     * @var string[] 
     */
    public $status = [
        1=>'预警中',
        2=>'已解除'
    ];
    /**
     * 预警类型
     */
    public function getTypeText($type)
    {
        return $this->type[$type]??'';
    }
    
    public function getList($where,$field,$order,$pageSize)
    {
        $list = $this->where($where)->field($field)->order($order)->paginate($pageSize)->toArray();
        return $list;
    }
    
    /**
     * 预警状态
     */
    public function getStatusText($status)
    {
        return $this->status[$status]??'';
    }
    
    /**
     * 获取预警记录详情
     */
    public function getDetail($where,$field)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
}