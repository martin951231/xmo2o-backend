<?php 
namespace app\employee\model\db;
 
use think\Model;

class EmployeeCardUserPayLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';

    public function addLog($PayCode, $status=1, $msg='')
    { 
        $condition = [];
        $condition[] = ['code', '=', $PayCode->code];
        $PayLog = $this->where($condition)->find();
        if($PayLog){
            return false;
        }else{
            $this->card_id = $PayCode->card_id;
            $this->user_id = $PayCode->user_id;
            $this->uid = $PayCode->uid;
            $this->mer_id = $PayCode->mer_id;
            $this->code = $PayCode->code;
            $this->status = $status;
            $this->msg = $msg;
            $this->add_time = time();
            return $this->save(); 
        } 
    }
}