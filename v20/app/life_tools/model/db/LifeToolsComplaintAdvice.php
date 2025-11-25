<?php

/**
 * 投诉建议model
 */

namespace app\life_tools\model\db;

use think\Model;

class LifeToolsComplaintAdvice extends Model
{
    protected $pk = 'pigcms_id';

    /**
     * 关联user表
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    /**
     * 关联pigcms_life_tools表
     */
    public function tools()
    {
        return $this->belongsTo(LifeTools::class, 'tools_id', 'tools_id');
    }

    public function getContAttr($value, $data)
    {
        $content = $data['content'];
        if($content){
            //显示的字数
            $num = 30;
            $content = htmlspecialchars_decode($content); 
            $content = preg_replace("/\s+/", '', $content); 
            $content = strip_tags($content);
            $content = mb_strlen($content, 'utf-8') > $num ? mb_substr($content, 0, $num, "utf-8").'...' : mb_substr($content, 0, $num, "utf-8");
        } 
        return $content;
    }

    public function getCreateTimeAttr($value, $data)
    {
        return date('Y-m-d H:i:s', $data['add_time']);
    }

    public function getImagesArrAttr($images)
    {
        $imagesArr = [];
        if($images){
            $images = explode(',', $images);
            foreach ($images as $image) {
                $imagesArr[] = replace_file_domain($image);
            }
        }
        return $imagesArr;
    }

    public function getImageArrAttr($value, $data)
    {
        $imagesArr = [];
        if($data['images']){
            $images = explode(',', $data['images']);
            foreach ($images as $image) {
                $imagesArr[] = replace_file_domain($image);
            }
        }
        return $imagesArr;
    }

    /**
     * @param $where
     */
    public function getList($where = [], $limit = 20, $field = 'r.*,u.nickname,u.avatar', $order = 'r.add_time desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $list = $this->alias('r')
                ->field($field)
                ->join($prefix . 'user u', 'r.uid = u.uid')
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix . 'user u', 'r.uid = u.uid')
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
            $list = [
                'data' => []
            ];
            if (!empty($arr)) {
                $list['data'] = $arr->toArray();
            }
        }
        return $list;
    }

    /**
     * @param $where
     */
    public function getDetail($where = [], $field = 'r.*,u.nickname,u.avatar')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('r')
            ->field($field)
            ->join($prefix . 'user u', 'r.uid = u.uid')
            ->where($where)
            ->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

}