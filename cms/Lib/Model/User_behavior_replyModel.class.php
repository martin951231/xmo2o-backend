<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/11/29.
 * Time: 11:21.
 */

/**
 * Class User_behavior_replyModel用户行为分析（发布评论）
 */
class User_behavior_replyModel extends Model{

    public $from_type_config = array(
        '0'=>'wap端',
        '1'=>'安卓',
        '2'=>'ios',
        '3'=>'小程序',
        '4'=>'pc端',
        '5'=>'其他',
        '6'=>'移动端',
    );
    public $business_type_config = array(
        '0'=>'老版商城',
        '3'=>'新版商城',
        '1'=>'快店',
        '2'=>'团购',
        '4'=>'餐饮',
    );
    /**
     * @desc 记录用户行为（发布评论）
     * @param int $reply_id ：评论id
     * @param int $uid :用户id
     * @param string $content :评论内容
     * @param int $business_type ：业务类型：0商城，1快店，2团购，3新版商城，4，餐饮
     * @param string $comment_time ：发布评论的时间
     * @param string $from_type ：来源：0wap端，1ios,2安卓，3小程序，4pc端，5其他 6系统自动评价
     * @param string $add_ip ：用户ip
     * @return bool
     */
    public function add_record($reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip=''){
        $data = [
            'reply_id' => $reply_id,
            'uid' => $uid,
            'content' => $content,
            'business_type' => $business_type,
            'comment_time' => $comment_time,
            'from_type' => $from_type,
            'add_ip' =>$add_ip?ip2long($add_ip): ip2long($_SERVER["REMOTE_ADDR"]),
            'add_time' =>time(),
        ];
        $r = $this->data($data)->add();
        fdump(M()->getLastSql(),'User_behavior_reply_log',1);//记录下插入日志
        return  $r? true : false;
    }
}

?>