<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResumeLog extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 操作记录列表
     */
    public function recruitResumeLogList($where, $fields){
        $result =  $this->where($where)->order('add_time desc')->field($fields)->select()->toArray();
        return $result;
    }

    /**
     * 操作记录保存
     */
    public function recruitResumeLogAdd($params){
        // 判断是否存在，存在则更新添加时间
        // $count = (new NewRecruitResumeLog())->where($params)->count();
        // if($count > 0){
        //     $result = $this->where($params)->update(['add_time'=>time()]);
        // }else{
            $result = $this->add($params);
        // }
        return $result;
    }
}