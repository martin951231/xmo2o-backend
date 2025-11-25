<?php
namespace app\community\model\db\Device;

use think\Model;
use think\facade\Db;

class GarbageCollectionPutList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 投放方式
     * @var string[]
     */
    public $type = [
        2=>'微信小程序',
        4=>'刷卡',
        5=>'传统无用户',
        6=>'人脸识别',
        7=>'二维码',
        8=>'rfid 标签投递'
    ];
    /**
     * 垃圾类型
     * @var string[] 
     */
    public $garbageType = [
        1=>'可回收垃圾',
        4=>'有害垃圾',
        5=>'厨余垃圾',
        6=>'其他垃圾'
    ];
    public function getList($where,$field,$order,$pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $data = $this->alias('p')
            ->join($prefix.'user u','u.uid = p.uid')
            ->where($where)->field($field)->order($order)->paginate($pageSize)->toArray();
        return $data;
    }
    
    public function getClntTypeText($type)
    {
        return $this->type[$type]??'';
    }
    
    public function getDetail($where,$field)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
    
    public function getGarbageType($type)
    {
        return $this->garbageType[$type]??'';
    }
}