<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/11/29.
 * Time: 11:20.
 */

/**
 * Class User_behavior_classifyModel用户行为分析（分类信息）
 */
class User_behavior_classifyModel extends Model{

    public $from_type_config = array(
        '0'=>'wap端',
        '1'=>'安卓',
        '2'=>'ios',
        '3'=>'小程序',
        '4'=>'pc端',
        '5'=>'其他',
        '6'=>'移动端'
    );

    /**
     * @desc 记录用户行为（预约服务）只记录已成功发布的分类信息记录
     * @param $classify_id:发布的分类信息id
     * @param $uid:用户id
     * @param $title:发布的分类信息标题
     * @param $description:发布的分类信息内容
     * @param $classify_time:发布分类信息的时间
     * @param $from_type：来源：0wap端，1安卓,2ios，3小程序，4pc端，5其他
     * @param string $add_ip:用户id
     * @return bool
     */
    public function add_record($classify_id,$uid,$title,$description,$classify_time,$from_type,$add_ip=''){
        $data = [
            'classify_id' => $classify_id,
            'uid' => $uid,
            'title' => $title,
            'description' => $description,
            'classify_time' => $classify_time,
            'from_type' => $from_type,
            'add_ip' =>$add_ip?ip2long($add_ip): ip2long($_SERVER["REMOTE_ADDR"]),
            'add_time' =>time(),
        ];
        $r = $this->data($data)->add();
        fdump(M()->getLastSql(),'User_behavior_classify_log',1);//记录下插入日志
        return  $r? true : false;
    }
}

?>