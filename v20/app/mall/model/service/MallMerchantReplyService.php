<?php
/**
 * MallMerchantReplyService.php
 * 商家后台-商品评价service
 * Create on 2020/9/11 10:11
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\MallGoodReply;
use app\mall\model\service\MerchantStoreService;

class MallMerchantReplyService
{
    public function __construct()
    {
        $this->MallGoodsReplyModel = new MallGoodReply();
    }

    /**
     * 获取商家的所有店铺
     * @param $mer_id
     * @return array
     */
    public function getStores($mer_id)
    {
        if (empty($mer_id)) {
            throw new \think\Exception('mei_id参数缺失');
        }
        $merchantStore = new MerchantStoreService();
        $arr = $merchantStore->getStoreByMerId($mer_id);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
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
    public function searchReply($mer_id, $store_id, $goods_name, $begin_time, $end_time, $status,$page, $pageSize)
    {
        $where = [['a.mer_id', '=', $mer_id], ['a.is_del', '=', 0], ['m.is_del', '=', 0]];
        if (!empty($store_id)) {
            $arr = [['a.store_id', '=', $store_id]];
            $where = array_merge($where, $arr);
        }
        if (!empty($begin_time) && !empty($end_time)) {
            $arr = [['a.create_time', '>=', strtotime($begin_time)], ['a.create_time', '<=', strtotime($end_time)]];
            $where = array_merge($where, $arr);
        }
        if (!empty($goods_name)) {
            $arr = [['m.name', 'like', '%' . $goods_name . '%']];
            $where = array_merge($where, $arr);
        }
        if ($status != 2) {
            $arr = [['a.status', '=', $status]];
            $where = array_merge($where, $arr);
        }
        $list = $this->MallGoodsReplyModel->getRplByGoodsName($where, $page, $pageSize);
        if (!empty($list)) {
            //处理图片和视频
            foreach ($list as $key => $val) {
                //评论图片多张以英文分号隔开
                if (!empty($val['reply_pic'])) {
                    $reply_pic = [];
                    $reply_pic_arr = explode(";",$val['reply_pic']);
                    foreach ($reply_pic_arr as $val1) {
                        $reply_pic[] = replace_file_domain($val1);
                    }
                    $list[$key]['reply_pic']  = $reply_pic;
                } else {
                    $list[$key]['reply_pic'] = [];
                }
                if (!empty($list[$key]['reply_mv'])) {
                    $reply_mv = explode(';', $val['reply_mv']);
                    unset( $list[$key]['reply_mv']);
                    $list[$key]['reply_mv_nums'] = 1;//标识评论是否有视频
                    $list[$key]['playerOption'] = [
                        'playbackRates' => [0.7, 1.0, 1.5, 2.0],
                        'aspectRatio' => '16:9',
                        'sources' => ['type' => 'video/mp4', 'src' => replace_file_domain($reply_mv[1])],
                        'poster' => replace_file_domain($reply_mv[0]),//你的封面地址
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
                $list[$key]['goods_sku_dec'] = $val['goods_sku_dec'] ?: '';
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $list[$key]['image'] = $val['image'] ? replace_file_domain($val['image']) : '';
            }

            //获取总数
            $list1['count'] = $this->MallGoodsReplyModel->getRplByGoodsNameCount($where);
            $list1['list'] = $list;
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
        $where = ['rpl_id' => $rpl_id];
        $arr = $this->MallGoodsReplyModel->getByCondition($where);

        if (!empty($arr)) {
            //店铺名
            $store_id = $arr['store_id'];
            $merchantStore = new MerchantStoreService();
            $store_name = $merchantStore->getStoreByStoreId($store_id)?$merchantStore->getStoreByStoreId($store_id)[0]['name']:'';
            //商品名
            $goods_id = $arr['goods_id'];
            $merchantStore = new MallGoodsService();
            $goods_name = $merchantStore->getNameById($goods_id)['name']??'';
            if (!empty($arr['reply_pic'])) {
                $arr['reply_pic'] = explode(';', $arr['reply_pic']);
                foreach ($arr['reply_pic'] as $key => $val) {
                    $arr['reply_pic'][$key] = replace_file_domain($val);
                }
            } else {
                $arr['reply_pic'] = [];
            }
            $arr['reply_mv'] = empty($arr['reply_mv']) ? [] : explode(';', $arr['reply_mv']);
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
                'store_name' => $store_name,
                'reply_time' => date('Y-m-d H:i:s',$arr['create_time']),
                'goods_name' => $goods_name,
                'goods_sku_dec' => $arr['goods_sku_dec'],
                'service_score' => $arr['service_score'],
                'goods_score' => $arr['goods_score'],
                'logistics_score' => $arr['logistics_score'],
                'comment' => $arr['comment'],
                'reply_pic' => $arr['reply_pic'],
//                'reply_mv' => $arr['reply_mv'],
                'reply_mv_nums' => $arr['reply_mv_nums'],
                'playerOption' => $arr['playerOption'],
                'merchant_reply_content' => $arr['merchant_reply_content'],
                'merchant_reply_time' => $arr['merchant_reply_time'],
                'user_reply_merchant' => $arr['user_reply_merchant'],
                'user_reply_merchant_time' => $arr['user_reply_merchant_time'] ? date('Y-m-d H:i:s', $arr['user_reply_merchant_time']) : '暂无回复',
            ];
            return $details;
        } else {
            return [];
        }
    }

    /**
     * 商家回复
     * @param $rpl_id
     * @param $merchant_reply_content
     * @param $merchant_reply_time
     * @return MallGoodReply
     * @throws \think\Exception
     */
    public function merchantReply($rpl_id, $merchant_reply_content)
    {
        if (empty($rpl_id)) {
            throw new \think\Exception('rpl_id参数缺失');
        }
        if (empty($merchant_reply_content)) {
            throw new \think\Exception('商家回复内容不能为空');
        }
        $where = ['rpl_id' => $rpl_id];
        $data = [
            'merchant_reply_content' => $merchant_reply_content,
            'merchant_reply_time' => time(),
            'status' => 1
        ];
        $result = $this->MallGoodsReplyModel->addMerComment($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }

    /**
     * 展示主页、优质评论
     */
    public function getQualityHome($rpl_id, $type, $del=0)
    {
        if (empty($rpl_id)) {
            throw new \think\Exception('rpl_id参数缺失');
        }
        $where = ['rpl_id' => $rpl_id];
        if($del == 0){
            if($type == 1){
                // 设置展示主页
                $data = ['show_home_page' => 1,];
            }elseif($type ==2){
                // 设置优质评论
                $data = ['quality_reviews' => 1,];
            }
        }else{
            if($type == 1){
                // 取消展示主页
                $data = ['show_home_page' => 0,];
            }elseif($type ==2){
                // 取消优质评论
                $data = ['quality_reviews' => 0,];
            }
        }
        $result = $this->MallGoodsReplyModel->getQualityHome($where, $data);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return $result;
    }
}