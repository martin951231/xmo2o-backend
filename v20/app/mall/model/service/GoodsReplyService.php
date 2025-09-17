<?php
/**
 * GoodsReplyService.php
 * 商品评价service
 * Create on 2020/9/8 15:21
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\service\MerchantStoreShopService;
use app\common\model\service\order\SystemOrderService;
use app\mall\model\db\MallGoodReply;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\UserService;
use app\mall\model\service\MallOrderService;
use app\mall\model\service\MallOrderDetailService;
use think\Exception;
use think\facade\Db;

class GoodsReplyService
{
    /**
     * 展示列表
     * @param $goods_id
     * @param $goods_sku
     * @param $goods_sku_dec
     * @param $mark
     * @param $page
     * @param $pageSize
     * @return array
     * @throws \think\Exception
     */
    public function getCommentList($goods_id, $goods_sku, $goods_sku_dec, $mark, $page, $pageSize)
    {
        if (empty($goods_id)) {
            throw new \think\Exception('缺少goods_id参数');
        }
        if ($goods_sku && $goods_sku_dec) {
            //基本条件
            $where = [
                ['a.goods_id', '=', $goods_id],
                ['a.is_del', '=', 0],
                ['a.is_show', '=', 1],
                ['a.goods_sku', '=', $goods_sku],
                ['a.goods_sku_dec', '=', $goods_sku_dec]
            ];
        } else {
            //基本条件
            $where = [
                ['a.goods_id', '=', $goods_id],
                ['a.is_del', '=', 0],
                ['a.is_show', '=', 1]
            ];
        }
        //标签条件
        switch ($mark) {
            case 0:
                break;   //全部
            case 1:
                array_push($where, ['a.image_status', '=', 1]);
                break;   //有视频/图片
            case 2:
                array_push($where, ['a.goods_score', '>=', 4], ['a.goods_score', '<=', 5]);
                break;
            case 3:
                array_push($where, ['a.goods_score', '=', 3]);
                break;
            case 4:
                array_push($where, ['a.goods_score', '>=', 1], ['a.goods_score', '<=', 2]);
                break;
        }
        $arr1 = array();
        $reply = new MallGoodReply();
        $where[] = ['b.have_mall','=',1];
        $arr = $reply->getList($where, $page, $pageSize);
        //根据uid获取每一位用户的头像和姓名存入数组中
        $userService = new UserService();
        if (!empty($arr)) {
            foreach ($arr as $key => $val) {
                if ($val['uid']) {
                    $user = $userService->getUser($val['uid']);
                    if ($val['anonymous'] == 1 && $user) {
                        $user['user_logo'] = cfg('site_url') . '/static/images/user_avatar.jpg';
                        $user['nickname'] = L_('匿名用户');
                    }
                    if ($user) {
                        $arr[$key]['user_image'] = $user['user_logo'] ? replace_file_domain($user['user_logo']) : cfg('site_url') . '/static/images/user_avatar.jpg';
                        $arr[$key]['user_name'] = $user['nickname'] ?: '';
                        //评论图片多张以英文分号隔开
                        if ($arr[$key]['reply_pic']) {
                            $arr[$key]['reply_pic'] = explode(';', $val['reply_pic']);
                            foreach ($arr[$key]['reply_pic'] as $key1 => $val1) {
                                $arr[$key]['reply_pic'][$key1] = $val1 ? replace_file_domain($val1) : '';
                            }
                        } else {
                            $arr[$key]['reply_pic'] = [];
                        }
                        if ($arr[$key]['reply_mv']) {
                            $arr[$key]['reply_mv'] = explode(';', $val['reply_mv']);
                            $arr[$key]['reply_mv']['image'] = isset($arr[$key]['reply_mv'][0]) ? replace_file_domain($arr[$key]['reply_mv'][0]) : '';
                            $arr[$key]['reply_mv']['url'] = isset($arr[$key]['reply_mv'][1]) ? replace_file_domain($arr[$key]['reply_mv'][1]) : '';
                            $arr[$key]['reply_mv']['vtime'] = isset($arr[$key]['reply_mv'][2]) ? replace_file_domain($arr[$key]['reply_mv'][2]) : '';
                        } else {
                            $arr[$key]['reply_mv'] = (object)array();
                        }
                        $arr[$key]['goods_sku'] = $val['goods_sku'] ?: '';
                        $arr[$key]['goods_sku_dec'] = $val['goods_sku_dec'] ?: '';

                        //处理时间返回形式
						$arr[$key]['create_day'] = date('Y-m-d', $arr[$key]['create_time']);
                        $arr[$key]['create_time'] = date('Y-m-d H:m:s', $arr[$key]['create_time']);
                        
                        $arr1[] = $arr[$key];
                    }
                } else {
                    //throw new \think\Exception('存在 用户已不存在的 商品评价');
                    continue;
                }
            }
            //返回各种数和各种率
            $count_arr = $this->getCounts($goods_id);
            $list['list'] = $arr1;
            $list = array_merge($list, $count_arr);
        } else {
            //返回各种数和各种率
            $count_arr = $this->getCounts($goods_id);
            $list['list'] = [];
            $list = array_merge($list, $count_arr);
        }
        return $list;
    }

    /**
     * 添加评论
     * @param $arr
     * @return int|string
     * @throws \think\Exception
     */
    public function addGoodsComment($arr)
    {
        if (empty($arr['uid'])) {
            throw new \think\Exception("请传递用户UID参数", 1002);
        }
        $shopService = new MerchantStoreShopService();
        //处理视频
        $reply_mv = [];
        if (!empty($arr['reply_mv']['image']) && !empty($arr['reply_mv']['url'])) {
            $reply_mv[0] = explode('/upload/', $arr['reply_mv']['image']) ? '/upload/' . explode('/upload/', $arr['reply_mv']['image'])[1] : '';
            $reply_mv[1] = explode('/upload/', $arr['reply_mv']['url']) ? '/upload/' . explode('/upload/', $arr['reply_mv']['url'])[1] : '';
            $reply_mv[2] = isset($arr['reply_mv']['vtime']) ? $arr['reply_mv']['vtime'] : 0;
        }
        $arr['reply_mv'] = implode(';', $reply_mv);
        //处理图片
        $reply_pic = [];
        if (!empty($arr['reply_pic'])) {
            foreach ($arr['reply_pic'] as $k => $v) {
                $reply_pic[$k] = explode('/upload/', $v) ? '/upload/' . explode('/upload/', $v)[1] : '';
            }
        }
        $arr['reply_pic'] = $reply_pic ? implode(';', $reply_pic) : ''; //以分号隔开

        //判断是否有图片和视频
        if ($arr['reply_mv'] || $arr['reply_pic']) {
            $arr['image_status'] = 1;
        } else {
            $arr['image_status'] = 0;
        }

        //根据order_id 获得mer_id,store_id,
        $orderService = new MallOrderService();
        $ids = $orderService->getOne($arr['order_id']);
        if ($ids) {
            $arr['mer_id'] = $ids['mer_id'];
            $arr['store_id'] = $ids['store_id'];
        } else {
            throw new \think\Exception('没有该订单信息');
        }

        //根据order_detail_id获取goods_id
        $orderDetailService = new MallOrderDetailService();
        $detail_ids = $orderDetailService->getByDetailId($arr['order_detail_id']);
        if ($detail_ids) {
            $arr['goods_id'] = $detail_ids['goods_id'];
            $arr['goods_sku'] = $detail_ids['sku_id'];
            $arr['goods_sku_dec'] = $detail_ids['sku_info'];
        } else {
            throw new \think\Exception('没有该订单详细信息');
        }

        //是否匿名 现在显示为头像显示原头像 姓名以a****b的显示 所以先默认为1
        $arr['anonymous'] = $arr['anonymous'] ?? 1;
        //产生时间
        $arr['create_time'] = time();
        //是否删除 0=未删除
        $arr['is_del'] = 0;
        Db::StartTrans();
        try {
            //入库
            $reply = new MallGoodReply();
            //没写文字给默认评价
            if (empty($arr['comment']) || !isset($arr['comment'])) {
                $arr['comment'] = '此用户没有填写评价。';
            }
            $reply->addConment($arr);
            //评论成功后更新店铺评分 总评分 总评论数
            $shop_data['score_all'] = $reply->getStoreSumScore(['store_id' => $arr['store_id']]);
            $shop_data['reply_count'] = $reply->getCountByCondition(['store_id' => $arr['store_id']]);
            if ($shop_data['reply_count']) {
                $shop_data['score_mean'] = $shop_data['score_all'] / $shop_data['reply_count'];
                $shop_data['score_mean'] = number_format((float)$shop_data['score_mean'], 1);
                if (stripos($shop_data['score_mean'], '.5') === false) {
                    $shop_data['score_mean'] = round($shop_data['score_mean']);
                }
            } else {
                $shop_data['score_all'] = 0;
                $shop_data['reply_count'] = 0;
                $shop_data['score_mean'] = 0;
            }
            //更新评分
            $shopService->updateOne(['store_id' => $arr['store_id']], $shop_data);
            //修改订单状态
            (new MallOrderService())->changeOrderStatus($arr['order_id'], 40, '用户已评价，该笔订单已完成');
            //调用卢敏的service修正订单列表页面
            (new SystemOrderService())->commentOrder('mall3', $arr['order_id']);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 获取各种数和各种率
     * @return array
     */
    public function getCounts($goods_id)
    {
        $count_arr = [];
        $reply = new MallGoodReply();

        //全部评论数
        $count_arr['all_comments'] = $reply->getCountByCondition(['goods_id' => $goods_id]);

        //有图片
        $count_arr['with_image'] = $reply->getCountByCondition(['image_status' => 1, 'goods_id' => $goods_id]);

        //好评
        $count_arr['good_comments'] = $reply->getCountByCondition([['goods_score', '>=', 4], ['goods_score', '<=', 5], ['goods_id', '=', $goods_id]]);

        //中评
        $count_arr['medium_comments'] = $reply->getCountByCondition(['goods_score' => 3, 'goods_id' => $goods_id]);

        //差评
        $count_arr['poor_comments'] = $reply->getCountByCondition([['goods_score', '>=', 1], ['goods_score', '<=', 2], ['goods_id', '=', $goods_id]]);

        //好评率
        $count_arr['favorable_rate'] = sprintf("%.2f", $count_arr['good_comments'] / ($count_arr['all_comments'] == 0 ? 1 : $count_arr['all_comments'])) * 100 . '%';
        return $count_arr;
    }

    /**
     * 去评论
     * @param $info 传入的商品id和skuid组成的数组
     * @return array
     * @throws \think\Exception
     */
    public function goToComment($info)
    {
        if (empty($info)) {
            throw new \think\Exception('参数缺失');
        }
        $arr = array();
        $goods = new MallGoodsService();
        $sku = new MallGoodsSkuService();
        //循环获取商品信息 一般情况下只有一个
        foreach ($info as $key => $val) {
            //查出商品名称和头像
            $goods_info = $goods->getOne($val[0], 1);
            //查出sku信息
            $sku_info = $sku->getSkuById($val[1]);

            $arr[] = [
                'name' => isset($goods_info['name']) ? $goods_info['name'] : '',
                'image' => isset($goods_info['image']) ? replace_file_domain($goods_info['image']) : '',
                'sku_info' => isset($sku_info['sku_info']) ? $sku_info['sku_info'] : '',
                'sku_str' => isset($sku_info['sku_str']) ? $sku_info['sku_str'] : ''
            ];
        }
        return $arr;

    }

    /**
     * 用户回复商家评论
     */
    public function userReplyMerchant($rpl_id, $content, $uid)
    {
        if(empty($content)){
            throw new \think\Exception('内容不能为空！');
        }
        $condition = [];
        $condition[] = ['uid', '=', $uid];
        $condition[] = ['rpl_id', '=', $rpl_id];
        $reply = MallGoodReply::where($condition)->find();
        if(!$reply){
            throw new \think\Exception('评论不存在！');
        }
        if(!empty($reply->user_reply_merchant)){
            throw new \think\Exception('已回复，请勿重复操作！');
        }
        $reply->user_reply_merchant = $content;
        $reply->user_reply_merchant_time = time();
        return $reply->save();
    }


}