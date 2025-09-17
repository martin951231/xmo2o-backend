<?php
/**
 * 用户消息提醒
 */

namespace app\common\model\db;

use think\Model;

class UserNotice extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getList($where,$field,$pageSize){
        $prefix = config('database.connections.mysql.prefix');
	    $data = $this->where($where)->where('id','IN',function ($query)use($where,$pageSize,$prefix){
            $query->table($prefix.'user_notice')->where($where)->field('max(id) as id')->group('order_id,business')->paginate($pageSize)->toArray();
        })->field($field)->order('id desc')->group('order_id,business')->paginate($pageSize)->toArray();
	    return $data;
    }
    
    public function getBusiness($business)
    {
        $businessMsg = '';
        switch ($business){
            case 'mall':
                $businessMsg = '商城';
                break;
            case 'shop':
                $businessMsg = '外卖';
                break;
            case 'group':
                $businessMsg = '团购';
                break;
            case 'village_group':
                $businessMsg = '社区团购';
                break;
            case 'appoint':
                $businessMsg = '预约';
                break;
            case 'scenic':
                $businessMsg = '景区';
                break;
            case 'classify':
                $businessMsg =  cfg('classify_name');
                break;
        }
        return $businessMsg;
    }
}