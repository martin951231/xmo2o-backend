<?php
/**
 * AppointCategory.php
 * 文件描述
 * Create on 2020/11/18 15:33
 * Created by zhumengqun
 */

namespace app\common\model\db;

use app\appoint\model\db\Appoint;
use \think\Model;

class AppointCategory extends Model
{
    /**
     * @return array
     * 获取全部分类
     */
    public function getCategory()
    {
        $arr = $this->field(true)->where(['cat_status' => 1])->order('cat_sort DESC')->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return array
     * 根据条件获取一个
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    public function searchIdsAttr($query, $value)
    {
        !empty($value) && is_array($value) && $query->where('cat_id','IN', $value);
    }
    
    public function getAppointNumAttr($value,$data)
    {
        $appointCount = 0;
        if(!empty($data['cat_id'])){
           $appointCount = Appoint::where('cat_fid', $data['cat_id'])
               ->where([
                   'appoint_status' => 0,
                   'check_status' => 1
               ])
               ->where('end_time', '>', time())
               ->count();
        }
        return $appointCount;
    }
}