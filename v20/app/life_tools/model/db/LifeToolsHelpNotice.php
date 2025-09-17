<?php

/**
 * 寻人求助model
 */

namespace app\life_tools\model\db;

use think\Model;

class LifeToolsHelpNotice extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    protected $pk = 'pigcms_id';
    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $pageSize
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     * 列表
     */
    public function helpList($where = [], $field = true,$order=true,$page=1,$pageSize=10){
        $limit = [
            'page' => $page ?? 1,
            'list_rows' => $pageSize ?? 10
        ];
        $arr = $this
            ->field($field)
            ->where($where)
            ->order($order)
            ->paginate($limit)->toArray();
        return $arr;
    }
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

    /**
     * @param $where
     * @param string $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 详情
     */
    public function getDetail($where, $field = '*')
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            $arr = $arr->toArray();
        } else {
            $arr = [];
        }
        return $arr;
    }

    public function getImageArrAttr($value, $data)
    {
        $imagesArr = [];
        if($data['images']){
            $imagesArr = unserialize($data['images']);
            // $images = explode(',', $data['images']);
            // foreach ($images as $image) {
            //     $imagesArr[] = replace_file_domain($image);
            // }
        }
        return $imagesArr;
    }
}