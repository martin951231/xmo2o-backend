<?php
/**
 * MallPlatformReplyService.php
 * 平台后台-商品评价service
 * Create on 2020/9/11 17:35
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\MallGoodReply;
use app\mall\model\service\MerchantStoreService;

class MallPlatformReplyService
{
    public function __construct()
    {
        $this->MallGoodsReplyModel = new MallGoodReply();
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
    public function searchReply($type, $content, $begin_time, $end_time, $status,$page, $pageSize)
    {
        $where = [['a.is_del', '=', 0]];
        if (!empty($begin_time) && !empty($end_time)) {
            $arr = [['a.create_time', '>=', strtotime($begin_time)], ['a.create_time', '<=', strtotime($end_time)]];
            $where = array_merge($where, $arr);
        }
        if ($type == 1) {
            //按照店铺搜索
            $store_name = $content;
            $arr = [['m1.name', 'like', '%' . $store_name . '%']];
            $where = array_merge($where, $arr);
        } else if ($type == 2) {
            //按照商家搜索
            $merchant_name = $content;
            $arr = [['m3.name', 'like', '%' . $merchant_name . '%']];
            $where = array_merge($where, $arr);
        } else if ($type == 3) {
            //按照商品搜索
            $goods_name = $content;
            $arr = [['m2.name', 'like', '%' . $goods_name . '%']];
            $where = array_merge($where, $arr);
        }
        if ($status != 2) {
            $arr = [['a.status', '=', $status]];
            $where = array_merge($where, $arr);
        }

        $isExport = request()->param('is_export',0,'intval');
        if ($isExport) {
            $page = $pageSize = 0;
        }
        $list = $this->MallGoodsReplyModel->getAll($where, $page, $pageSize);

        if (!empty($list)) {
            //处理图片和视频
            foreach ($list as $key => $val) {
                //评论图片多张以英文分号隔开
                if (!empty($val['reply_pic'])) {
                    $reply_pic = [];
                    $reply_pic_arr = explode(";",$val['reply_pic']);
                    foreach ($reply_pic_arr as $val1) {
                        $reply_pic[] =  replace_file_domain($val1);
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
                        'sources' => ['type' => 'video/mp4', 'src' => replace_file_domain($reply_mv[1]??'')],
                        'poster' =>  replace_file_domain($reply_mv[0]??''),//你的封面地址
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
                $list[$key]['goods_image'] = replace_file_domain($val['goods_image']);
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $list[$key]['goods_sku_dec'] = $val['goods_sku_dec'] ?: '';
                $list[$key]['merchant_reply_datetime'] = $val['merchant_reply_time']?date('Y-m-d H:i:s', $val['merchant_reply_time']):'';
                $list[$key]['is_show_desc'] = $val['is_show'] == 1 ? '显示' : '隐藏';
                $list[$key]['status_desc'] = $val['status'] == 1 ? '已回复' : '未回复';
                $list[$key]['reply_pic_arr'] = $list[$key]['reply_pic'] ? implode(PHP_EOL, $list[$key]['reply_pic']) : '';
            }
           
            //导出excel
            if($isExport){
                $xlsCell = [
                    ['goods_name', '商品名称'],
                    ['mer_name', '商家名称'],
                    ['store_name', '店铺名称'],
                    ['comment', '评论内容'],
                    ['reply_pic_arr', '评论图片'],
                    ['goods_score', '商品评价(星级)'],
                    ['service_score', '服务态度(星级)'],
                    ['logistics_score', '物流服务(星级)'],
                    ['create_time', '评价时间'],
                    ['status_desc', '是否回复'],
                    ['merchant_reply_content', '商家回复内容'],
                    ['merchant_reply_datetime', '商家回复时间'],
                    ['is_show_desc', '是否展示'],
                ];
                $exportResult = export_excel('评论管理', $xlsCell, $list,  time());
                return $exportResult;
            }

            //获取总数
            $list1['list'] = $list;
            $list1['count'] = $this->MallGoodsReplyModel->getReplyCount($where);
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
            $store_name = $merchantStore->getStoreByStoreId($store_id)[0]['name'];
            //商品名
            $goods_id = $arr['goods_id'];
            $merchantStore = new MallGoodsService();
            $goods_name = $merchantStore->getNameById($goods_id)['name'];
            //商家名称
            $mer_id = $arr['mer_id'];
            $merService = new MerchantService();
            $merName = $merService->getByMerId($mer_id)['name'];
			
			//评论图片
			if($arr['reply_pic']){
				$arr['reply_pic'] = explode(';', $arr['reply_pic']);
				foreach ($arr['reply_pic'] as $key => $val) {
					$arr['reply_pic'][$key] = replace_file_domain($val);
				}
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
                'mer_name' => $merName,
                'store_name' => $store_name,
                'reply_time' => empty($arr['create_time']) ? '' : date('Y-m-d H:i:s', $arr['create_time']),
                'goods_name' => $goods_name,
                'goods_sku_dec' => $arr['goods_sku_dec'],
                'service_score' => $arr['service_score'],
                'goods_score' => $arr['goods_score'],
                'logistics_score' => $arr['logistics_score'],
                'comment' => $arr['comment'],
                'reply_pic' => $arr['reply_pic'] ?: [],
                // 'reply_mv' => $arr['reply_mv'] ?: '',
                'reply_mv_nums' => $arr['reply_mv_nums'],
                'playerOption' => $arr['playerOption'],
                'merchant_reply_content' => $arr['merchant_reply_content'],
                'merchant_reply_time' => empty($arr['merchant_reply_time']) ? '' : date('Y-m-d H:i:s', $arr['merchant_reply_time']),
                'user_reply_merchant' => $arr['user_reply_merchant'],
                'user_reply_merchant_time' => $arr['user_reply_merchant_time'] ? date('Y-m-d H:i:s', $arr['user_reply_merchant_time']) : '暂无回复',
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
        $where = ['rpl_id' => $rpl_id];
        $data = [
            'is_del' => 1
        ];
        $result = $this->MallGoodsReplyModel->delReply($where, $data);
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
        $reply = $this->MallGoodsReplyModel->where('rpl_id', $rpl_id)->find();
        $reply->is_show = $reply->is_show == 1 ? 0 : 1;
        $result = $reply->save(); 
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        }
        return ['is_show' => $reply->is_show];
    }
}