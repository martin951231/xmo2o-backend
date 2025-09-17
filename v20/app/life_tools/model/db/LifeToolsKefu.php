<?php
 
namespace app\life_tools\model\db;

use think\Model;

class LifeToolsKefu extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';


    public function getWorkTextAttr($value, $data)
    {
        $work = ['1'=>'投诉', '2'=>'寻人求助'];
        $workText = $data['work'];
        foreach($work as $key => $val){
            $workText = str_replace($key, $val, $workText);
        }
        return $workText;
    }

    public function getWorkDateTextAttr($value, $data)
    {
        $workDate = ['天', '一', '二', '三', '四', '五', '六'];
        $workDateText = $data['work_date'];
        foreach($workDate as $key => $val){
            $workDateText = str_replace($key, '星期'.$val, $workDateText);
        }
        return $workDateText;
    }

    public function getWorkArrAttr($value, $data)
    {
        $work = $data['work'] != '' ? explode(',', $data['work']) : [];
        foreach($work as $key => $val){
            $work[$key] = intval($val);
        }
        return $work; 
    }

    public function getWorkDateArrAttr($value, $data)
    {
        $work_date = $data['work_date'] != '' ? explode(',', $data['work_date']) : [];
        foreach($work_date as $key => $val){
            $work_date[$key] = intval($val);
        }
        return $work_date; 
    }

}