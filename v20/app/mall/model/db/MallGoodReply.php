<?php
/**
 * MallGoodReply.php
 * 评论model
 * Create on 2020/9/9 16:01
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallGoodReply extends Model
{
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
//        $arr = $this->field(true)->where($where)->order('create_time DESC')->page($page, $pageSize)->select();
        $arr = $this->alias('a')
            ->join('merchant_store b','a.store_id = b.store_id')
            ->field('a.*')
            ->where($where)
            ->order('create_time DESC')
            ->page($page, $pageSize)
            ->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    public function getAll($where, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $obj = $this->alias('a')
            ->join($prefix . 'merchant_store' . ' m1', 'm1.store_id = a.store_id')
            ->join($prefix . 'mall_goods' . ' m2', 'm2.goods_id = a.goods_id')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = a.mer_id')
            ->field('a.rpl_id,a.goods_id,a.goods_score,a.service_score,a.logistics_score,a.comment,a.create_time,a.goods_sku_dec,a.status,a.reply_pic,a.reply_mv,a.merchant_reply_content,a.merchant_reply_time,a.is_show,m1.name as store_name,m2.name as goods_name,m2.image as goods_image,m3.name as mer_name')
            ->where($where)
            ->order('a.create_time desc');
        
        if($page >0 && $pageSize > 0){
            $arr = $obj->page($page, $pageSize)->select();
        }else{
            $arr = $obj->select();
        }
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
            ->join($prefix . 'merchant_store' . ' m1', 'm1.store_id = a.store_id')
            ->join($prefix . 'mall_goods' . ' m2', 'm2.goods_id = a.goods_id')
            ->join($prefix . 'merchant' . ' m3', 'm3.mer_id = a.mer_id')
            ->field('a.rpl_id,a.goods_id,a.goods_score,a.comment,a.create_time,a.goods_sku_dec,a.status,a.reply_pic,a.reply_mv,a.merchant_reply_content,a.merchant_reply_time,m1.name as store_name,m2.name as goods_name,m2.image as goods_image,m3.name as mer_name')
            ->where($where)
            ->count('rpl_id');
        return $count;
    }

    /**
     * 返回各种总数
     * @param $where
     * @return int
     */
    public function getCountByCondition($where)
    { 
        $count = $this->where($where)->where('is_show', 1)->where('is_del',0)->count('rpl_id');
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
        $field = 'ms.name AS store_name,a.rpl_id,a.comment,a.quality_reviews,a.show_home_page,a.create_time,a.goods_score,a.goods_sku_dec,a.status,a.reply_pic,a.reply_mv,m.name,m.image';
        $arr = $this->alias('a')
            ->join($prefix . 'mall_goods m', 'a.goods_id = m.goods_id')
            ->join($prefix . 'merchant_store ms', 'ms.store_id = a.store_id','LEFT')
            ->field($field)
            ->where($where)
            ->order('a.create_time DESC')
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
        $field = 'a.rpl_id,a.comment,a.goods_sku_dec,a.status,a.reply_pic,a.reply_mv,m.name,m.image';
        $arr = $this->alias('a')
            ->join($prefix . 'mall_goods m', 'a.goods_id = m.goods_id')
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
    public function delReply($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    //获得评价列表
    public function getReplyList($goods_id)
    {

        $where = [
            ['goods_id', '=', $goods_id],
            ['is_del', '=', 0],
        ];
        $result = $this->where($where)->order('create_time desc');
        $count = $result->count();
        $return['comments'] = $count;


        $where1 = [
            ['goods_id', '=', $goods_id],
            ['is_del', '=', 0],
            ['score', '>=', 4],
        ];

        $result1 = $this->where($where1)->order('create_time desc');
        $count1 = $result1->count();
        if ($count == 0) {
            $return['per'] = 0;
        } else {
            $return['per'] = round($count1 / $count * 100) . "%";
        }
        $return['comments_two'] = $this->where($where)->order('create_time desc')->limit(0, 2)->select()->toArray();

        return $return;
    }

    //获得不同评价列表
    public function getReplyLists($goods_id, $serch_type, $page, $sku_info)
    {
        if ($sku_info) {
            $where[] = [['goods_sku', 'like', '%' . $sku_info . '%']];
            $where1[] = [['goods_sku', 'like', '%' . $sku_info . '%']];
        }
        //评价筛选标题
        $arr = [0, 1, 2, 3, 4];
        foreach ($arr as $key => $val) {
            if ($val == 0) {
                //全部
                $where1[] = [
                    ['goods_id', '=', $goods_id],
                    ['is_del', '=', 0],
                ];
                $count = $this->where($where1)->order('create_time desc')->count();
                $return[] = ["totals" => $count];
            } elseif ($val == 1) {
                //有图视频
                $where1[] = [
                    ['goods_id', '=', $goods_id],
                    ['is_del', '=', 0],
                    ['image_status', '=', 1],
                ];
                $count = $this->where($where1)->order('create_time desc')->count();
                $return[] = ["has_image" => $count];
            } elseif ($val == 2) {
                //好评
                $where1[] = [
                    ['goods_id', '=', $goods_id],
                    ['is_del', '=', 0],
                    ['score', '>=', 4],
                ];
                $count = $this->where($where1)->order('create_time desc')->count();
                $return[] = ["good_comment" => $count];
            } elseif ($val == 3) {
                //中评
                $where1[] = [
                    ['goods_id', '=', $goods_id],
                    ['is_del', '=', 0],
                    ['score', '=', 3],
                ];
                $count = $this->where($where1)->order('create_time desc')->count();
                $return[] = ["mid_comment" => $count];
            } else {
                //差评
                $where1[] = [
                    ['goods_id', '=', $goods_id],
                    ['is_del', '=', 0],
                    ['score', '<=', 2],
                ];
                $count = $this->where($where1)->order('create_time desc')->count();
                $return[] = ["bad_comment" => $count];
            }
        }

        if ($serch_type == 0) {
            $where[] = [
                ['goods_id', '=', $goods_id],
                ['is_del', '=', 0],
            ];
        } elseif ($serch_type == 1) {
            //有图
            $where[] = [
                ['goods_id', '=', $goods_id],
                ['is_del', '=', 0],
                ['image_status', '=', 1],
            ];
        } elseif ($serch_type == 2) {
            //好评
            $where[] = [
                ['goods_id', '=', $goods_id],
                ['is_del', '=', 0],
                ['score', '>=', 4],
            ];
        } elseif ($serch_type == 3) {
            //中评
            $where[] = [
                ['goods_id', '=', $goods_id],
                ['is_del', '=', 0],
                ['score', '=', 3],
            ];
        } else {
            //差评
            $where[] = [
                ['goods_id', '=', $goods_id],
                ['is_del', '=', 0],
                ['score', '<=', 2],
            ];
        }

        $field = 'uid,user_image,user_name,goods_sku_dec,comment,image_status,reply_pic,reply_mv';
        $result = $this->where($where)->field($field)->order('create_time desc');

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
    public function getReplyLists1($goods_id, $serch_type, $page)
    {
        $where = [
            ['goods_id', '=', $goods_id],
            ['is_del', '=', 0],
        ];
        $result = $this->where($where)->order('create_time desc');
        $count = $result->count();
        $return['comments'] = $count;


        $where = [
            ['goods_id', '=', $goods_id],
            ['is_del', '=', 0],
            ['score', '>=', 4],
        ];
        $result1 = $this->where($where)->order('create_time desc');
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
        $goods_score = $this->where($where)->sum('goods_score');
        $service_score = $this->where($where)->sum('service_score');
        $sum = ($goods_score + $service_score) / 2;
        return $sum;
    }

    /**
     * 展示主页、优质评论
     * @param $where
     * @return MallGoodReply
     */
    public function getQualityHome($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }
}