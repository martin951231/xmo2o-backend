<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsReply;
use app\mall\model\db\MallGoodReply;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\MerchantService;
use app\mall\model\service\MerchantStoreService;

class LifeToolsReplyService
{
    public function __construct()
    {
        $this->LifeToolsReplyModel = new LifeToolsReply();
    }

    /**
     * 根据条件查询service
     * @param $mer_id
     * @param $store_id
     * @param $goods_name
     * @param $begin_time
     * @param $end_time
     * @param $page
     * @param $pageSize
     * @return array|mixed
     */
    public function searchReply($type, $content, $begin_time, $end_time, $status,$page, $pageSize,$mer_id=0)
    {
        //$where = [['a.is_del', '=', 0]];
        $where=[];
        if (!empty($begin_time) && !empty($end_time)) {
            if($begin_time==$end_time){
                $arr = [['a.add_time', '>=', strtotime($begin_time." 00:00:00")], ['a.add_time', '<=', strtotime($end_time." 23:59:59")]];
                $where = array_merge($where, $arr);
            }else{
                $arr = [['a.add_time', '>=', strtotime($begin_time)], ['a.add_time', '<=', strtotime($end_time." 23:59:59")]];
                $where = array_merge($where, $arr);
            }
        }
        //0是未选择 1景区 2 场馆 3 课程
        if ($type == 1) {//1景区
            //按照店铺搜索
            $where[] = ['a.type', '=', 'scenic'];
            if (!empty($content)) {
                $where[] = ['m2.title|m3.name', 'like', '%' . $content . '%'];
            }
        } else if ($type == 2) {//场馆
            //按照商家搜索
            if(empty($content)){
                $arr = [['a.type', '=', 'stadium']];
            }else{
                $arr = [['a.type', '=', 'stadium'],['m2.title', 'like', '%' . $content . '%']];
            }
            $where = array_merge($where, $arr);
        } else if ($type == 3) {//3课程
            //按照商品搜索
            if(empty($content)){
                $arr = [['a.type', '=', 'course']];
            }else{
                $arr = [['a.type', '=', 'course'],['m2.title', 'like', '%' . $content . '%']];
            }
            $where = array_merge($where, $arr);
        }else if ($type == 4) {//4商家
            //按照商品搜索
            $where[] = ['a.type', '<>', 'scenic'];
            if (!empty($content)) {
                $where[] = ['m3.name', 'like', '%' . $content . '%'];
            }
        }else{//0的话是体育课程
            if(empty($content)){
                $arr = [['a.type', 'in', ['stadium','course']]];
            }else{
                $arr = [['a.type', 'in', ['stadium','course']],['m2.title', 'like', '%' . $content . '%']];
            }
            $where = array_merge($where, $arr);
        }
        if($mer_id){
            $where = array_merge($where, [['m3.mer_id','=',$mer_id]]);
        }
        if ($status != 2) {
            $arr = [['a.status', '=', $status]];
            $where = array_merge($where, $arr);
        }
        $list = $this->LifeToolsReplyModel->getAll($where, $page, $pageSize);
        if (!empty($list)) {
            //处理图片和视频
            foreach ($list as $key => $val) {
                //评论图片多张以英文分号隔开
                if (!empty($val['reply_pic'])) {
                    $reply_pic = [];
                    $reply_pic_arr = explode(",",$val['reply_pic']);
                    foreach ($reply_pic_arr as $val1) {
                        $reply_pic[] =  replace_file_domain($val1);
                    }
                    $list[$key]['reply_pic']  = $reply_pic;
                } else {
                    $list[$key]['reply_pic'] = [];
                }
                if (!empty($list[$key]['reply_mv'])) {
                    $reply_mv = explode(',', $val['reply_mv']);
                    unset( $list[$key]['reply_mv']);
                    $list[$key]['reply_mv_nums'] = 1;//标识评论是否有视频
                    $list[$key]['playerOption'] = [
                        'playbackRates' => [0.7, 1.0, 1.5, 2.0],
                        'aspectRatio' => '16:9',
                        'sources' => ['type' => 'video/mp4', 'src' => replace_file_domain($reply_mv[1])],
                        'poster' =>  replace_file_domain($reply_mv[0]),//你的封面地址
                    ];
                } else {
                    $list[$key]['reply_mv_nums'] = 2;//标识评论是否有视频
                    $list[$key]['playerOption'] = [
                        'playbackRates' => [0.7, 1.0, 1.5, 2.0],
                        'aspectRatio' => '16:9',
                        'sources' => ['type' => 'video/mp4', 'src' => ''],
                        'poster' =>  '',//你的封面地址
                    ];
                }
                $goods_imsge=explode(",",$val['goods_image']);
                if(!empty($goods_imsge)){
                    $list[$key]['goods_image'] = replace_file_domain($goods_imsge[0]);
                }else{
                    $list[$key]['goods_image'] = "";
                }
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $list[$key]['goods_sku_dec'] =  '';
            }

            //获取总数
            $list1['list'] = $list;
            $list1['count'] = $this->LifeToolsReplyModel->getReplyCount($where);
            return $list1;
        } else {
            return [];
        }
    }

    /**
     * 查看商品详情service
     * @param $rpl_id
     * @return array
     * @throws \think\Exception
     */
    public function getReplyDetails($rpl_id)
    {
        if (empty($rpl_id)) {
            throw new \think\Exception('rpl_id参数缺失');
        }
        $where = ['reply_id' => $rpl_id];
        $arr = $this->LifeToolsReplyModel->getByCondition($where);

        if (!empty($arr)) {
            //店铺名
            $store_name="";
            //商品名
            $goods_id = $arr['tools_id'];
            $goods_name =(new LifeTools())->getOne(['tools_id'=>$goods_id]);
            //商家名称
            $mer_id = $goods_name['mer_id'];
            $merService = new MerchantService();
            $merName = $merService->getByMerId($mer_id)['name'];

            //评论图片
            if($arr['images']){
                $arr['images'] = explode(',', $arr['images']);
                foreach ($arr['images'] as $key => $val) {
                    $arr['images'][$key] = replace_file_domain($val);
                }
            }

            $arr['video_url'] = empty($arr['video_url']) ? [] : explode(',', $arr['video_url']);
            if (!empty($arr['reply_mv']) && isset($arr['reply_mv'][0]) && !empty($arr['reply_mv'][0])) {
                $arr['reply_mv_nums'] = 1;//标识评论是否有视频
                $arr['playerOption'] = [
                    'playbackRates' => [0.7, 1.0, 1.5, 2.0],
                    'aspectRatio' => '16:9',
                    'sources' => ['type' => 'video/mp4', 'src' => replace_file_domain($arr['reply_mv'][1])],
                    'poster' =>  replace_file_domain($arr['reply_mv'][0]),//你的封面地址
                ];
            } else {
                $arr['reply_mv_nums'] = 2;//标识评论是否有视频
                $arr['playerOption'] = [
                    'playbackRates' => [0.7, 1.0, 1.5, 2.0],
                    'aspectRatio' => '16:9',
                    'sources' => ['type' => 'video/mp4', 'src' => ''],
                    'poster' =>  '',//你的封面地址
                ];
            }

            $details = [
                'mer_name' => $merName,
                'store_name' => $store_name,
                'reply_time' => empty($arr['add_time']) ? '' : date('Y-m-d H:i:s', $arr['add_time']),
                'goods_name' => $goods_name['title'],
                'goods_sku_dec' => '',
                'service_score' => $arr['score'],
                'goods_score' => $arr['score'],
                'logistics_score' => $arr['score'],
                'comment' => $arr['content'],
                'reply_pic' => $arr['images'] ?: [],
                // 'reply_mv' => $arr['reply_mv'] ?: '',
                'reply_mv_nums' => $arr['reply_mv_nums'],
                'playerOption' => $arr['playerOption'],
                'merchant_reply_content' => $arr['replys_content'],
                'merchant_reply_time' => empty($arr['replys_time']) ? '' : date('Y-m-d H:i:s', $arr['replys_time'])
            ];
            return $details;
        } else {
            return [];
        }
    }

    /**
     * 删除评价
     * @param $rpl_id
     * @return MallGoodReply
     * @throws \think\Exception
     */
    public function delReply($rpl_id)
    {
        if (empty($rpl_id)) {
            throw new \think\Exception('rpl_id参数缺失');
        }
        $where = ['reply_id' => $rpl_id];
        $data = [
            'status' => 0
        ];
        $result = $this->LifeToolsReplyModel->delReply($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    /**
     * 展示/不展示评价
     * @param $rpl_id
     */
    public function isShowReply($rpl_id)
    {
        if (empty($rpl_id)) {
            throw new \think\Exception('rpl_id参数缺失');
        }
        $reply = $this->LifeToolsReplyModel->where('reply_id', $rpl_id)->find();
        $reply->status = $reply->status == 1 ? 0 : 1;
        $result = $reply->save();
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return ['is_show' => $reply->status];
    }


    /**
     * 获得评价回复内容
     */
    public function getReplyContent($id)
    {
        return $this->LifeToolsReplyModel->getVal(['reply_id'=>$id],'replys_content as reply_content');
    }

    /**
     * 回复评价
     */
    public function subReply($where,$data)
    {
        return $this->LifeToolsReplyModel->updateThis($where,$data);
    }

}