<?php


namespace app\classify\model\db;


use app\common\model\db\User;
use think\Model;

class ClassifyUserInput extends Model
{
    protected $name='classify_userinput';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
    
    public function getImgsAttr($value)
    {
        return $value ? unserialize($value) : [];
    }
    
    public function getContentAttr($value)
    {
        return $value ? unserialize($value) : [];
    }

    public function getAddtimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
    
    public function getReleaseTimeAttr($value, $data)
    {
        $time = time() - $data['addtime'];
        if ($time > 60*60*24*30){
            $timeText = bcdiv($time, 60*60*24*30).'月';
        }elseif ($time > 60*60*24){
            $timeText = bcdiv($time, 60*60*24).'天';
        }elseif ($time > 60*60){
            $timeText = bcdiv($time, 60*60).'小时';
        }elseif($time > 60){
            $timeText = bcdiv($time, 60).'分钟';
        }
        return $timeText.'前';
    }
}