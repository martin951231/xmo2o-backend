<?php


namespace app\life_tools\model\db;


use app\mall\model\db\Config;
use app\mall\model\db\MallGoodReply;
use think\Model;

class LifeToolsReply extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 添加评论
     * @param $arr
     * @return int|string
     */
    public function addConment($arr)
    {
        $result = $this->insert($arr);
        return $result;

    }

    /**
     * 获取列表
     * @param $where
     * @param $page
     * @param $pageSize
     * @return array
     */
    public function getList($where, $page, $pageSize)
    {
        $arr = $this->field(true)->where($where)->order('add_time DESC')->page($page, $pageSize)->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    public function getAll($where, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'life_tools' . ' m2', 'm2.tools_id = a.tools_id')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = m2.mer_id')
            ->field('a.reply_id as rpl_id,a.tools_id,a.score as goods_score,a.content as comment,a.add_time as create_time,a.status,a.video_image,a.video_url,a.replys_content,a.replys_time,m2.title as goods_name,m2.type,m2.images as goods_image,m3.name as mer_name')
            ->where($where)
            ->order('a.add_time desc')
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 按照条件查出来的总数
     * @param $where
     * @return mixed
     */
    public function getReplyCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'life_tools' . ' m2', 'm2.tools_id = a.tools_id')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = m2.mer_id')
            ->field('a.reply_id')
            ->where($where)
            ->count('a.reply_id');
        return $count;
    }

    /**
     * 返回各种总数
     * @param $where
     * @return int
     */
    public function getCountByCondition($where)
    {
        $count = $this->where($where)->where('status', 1)->count('rpl_id');
        return $count;
    }

    /**
     * 返回平均数
     * @param $where
     * @return int
     */
    public function getAvg($where,$field)
    {
        $count = $this->where($where)->avg($field);
        return $count;
    }
    /**
     * 根据条件查询
     * @param $where
     * @return array
     */
    public function getByCondition($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 根据搜索条件联合查询
     * @param $where
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getRplByGoodsName($where, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='a.reply_id as rpl_id,a.tools_id,a.score as goods_score,a.content,a.add_time as create_time,a.status,
        a.video_image as reply_pic,a.video_url as reply_mv,a.replys_content,a.replys_time,m2.title as name,m2.images as image';
        $arr = $this->alias('a')
            ->join($prefix . 'life_tools' . ' m2', 'm2.tools_id = a.tools_id')
            ->field($field)
            ->where($where)
            ->order('a.add_time DESC')
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 根据搜索条件联合查询
     * @param $where
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getRplByGoodsNameCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='a.reply_id';
        $arr = $this->alias('a')
            ->join($prefix . 'life_tools' . ' m2', 'm2.tools_id = a.tools_id')
            ->field($field)
            ->where($where)
            ->count();
        return $arr;
    }

    /**
     * 商家回复添加
     * @param $where
     * @param $data
     * @return MallGoodReply
     */
    public function addMerComment($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 删除评价
     * @param $where
     * @return MallGoodReply
     */
    public function delReply($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }

    //获得评价列表
    public function getReplyList($tools_id)
    {

        $where = [
            ['tools_id', '=', $tools_id],
        ];
        $result = $this->where($where)->order('add_time desc');
        $count = $result->count();
        $return['comments'] = $count;
        $where1 = [
            ['tools_id', '=', $tools_id],
            ['score', '>=', 4],
        ];

        $result1 = $this->where($where1)->order('add_time desc');
        $count1 = $result1->count();
        if ($count == 0) {
            $return['per'] = 0;
        } else {
            $return['per'] = round($count1 / $count * 100) . "%";
        }
        $return['comments_two'] = $this->where($where)->order('add_time desc')->limit(0, 2)->select()->toArray();

        return $return;
    }

    //获得不同评价列表
    public function getReplyLists($tools_id, $serch_type, $page, $sku_info)
    {
       /* if ($sku_info) {
            $where[] = [['goods_sku', 'like', '%' . $sku_info . '%']];
            $where1[] = [['goods_sku', 'like', '%' . $sku_info . '%']];
        }*/
        //评价筛选标题
        $arr = [0, 1, 2, 3, 4];
        foreach ($arr as $key => $val) {
            if ($val == 0) {
                //全部
                $where1[] = [
                    ['tools_id', '=', $tools_id],
                ];
                $count = $this->where($where1)->order('add_time desc')->count();
                $return[] = ["totals" => $count];
            } elseif ($val == 1) {
                //有图视频
                $where1[] = [
                    ['tools_id', '=', $tools_id],
                    ['status', '=', 1],
                    ['images', '<>', ""],
                ];
                $count = $this->where($where1)->order('add_time desc')->count();
                $return[] = ["has_image" => $count];
            } elseif ($val == 2) {
                //好评
                $where1[] = [
                    ['tools_id', '=', $tools_id],
                    ['score', '>=', 4],
                ];
                $count = $this->where($where1)->order('add_time desc')->count();
                $return[] = ["good_comment" => $count];
            } elseif ($val == 3) {
                //中评
                $where1[] = [
                    ['tools_id', '=', $tools_id],
                    ['score', '=', 3],
                ];
                $count = $this->where($where1)->order('add_time desc')->count();
                $return[] = ["mid_comment" => $count];
            } else {
                //差评
                $where1[] = [
                    ['tools_id', '=', $tools_id],
                    ['score', '<=', 2],
                ];
                $count = $this->where($where1)->order('add_time desc')->count();
                $return[] = ["bad_comment" => $count];
            }
        }

        if ($serch_type == 0) {
            $where[] = [
                ['tools_id', '=', $tools_id],
            ];
        } elseif ($serch_type == 1) {
            //有图
            $where[] = [
                ['tools_id', '=', $tools_id],
                ['status', '=', 1],
                ['images', '<>', ""],
            ];
        } elseif ($serch_type == 2) {
            //好评
            $where[] = [
                ['tools_id', '=', $tools_id],
                ['score', '>=', 4],
            ];
        } elseif ($serch_type == 3) {
            //中评
            $where[] = [
                ['tools_id', '=', $tools_id],
                ['score', '=', 3],
            ];
        } else {
            //差评
            $where[] = [
                ['tools_id', '=', $tools_id],
                ['score', '<=', 2],
            ];
        }

        $field = 'uid,content,images as reply_pic,video_url as reply_mv';
        $result = $this->where($where)->field($field)->order('add_time desc');

        $count = $result->count();

        $list = $result->page($page, Config::get('api.page_size'))
            ->select()->toArray();
        //var_dump($this->getLastSql());
        $return[] = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / Config::get('api.page_size'))),
            'now_page' => $page,
            'list' => $list
        ];
        return $return;
    }

    //获得不同评价列表
    public function getReplyLists1($tools_id, $serch_type, $page)
    {
        $where = [
            ['tools_id', '=', $tools_id],
            ['status', '=', 1],
        ];
        $result = $this->where($where)->order('add_time desc');
        $count = $result->count();
        $return['comments'] = $count;


        $where = [
            ['tools_id', '=', $tools_id],
            ['status', '=',1],
            ['score', '>=', 4],
        ];
        $result1 = $this->where($where)->order('add_time desc');
        $count1 = $result1->count();
        $return['per'] = round($count / $count1 * 100) . "%";
        return $return;
    }

    /**
     * @param $where
     * @return float|int
     * 店铺总评分
     */
    public function getStoreSumScore($where)
    {
        $goods_score = $this->where($where)->sum('score');
        return $goods_score;
    }

    /**
     * 获取内容
     */
    public function getVal($where,$field){
        return $this->where($where)->value($field);
    }

    public function getImagesArr($images) {
        $imagesArr = [];
        if ($images) {
            $images = explode(',', $images);
            foreach ($images as $image) {
                $imagesArr[] = replace_file_domain($image);
            }
        }
        return $imagesArr;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

}