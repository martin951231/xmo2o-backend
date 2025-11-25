<?php
/**
 * 系统优惠券
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 15:19
 */

namespace app\common\model\service;

use app\common\model\db\SystemCoupon;
use app\common\model\service\UserService;

class SystemCouponService
{
    public $systemCouponObj = null;
    public function __construct()
    {
        $this->systemCouponObj = new SystemCoupon();
    }

    //领取方法
    //@param int $get_all 是否一次领取用户当前可领取的最大数量
    public function hadPull($coupon_id, $uid, $card_code = '', $get_all = 0)
    {
        $where['coupon_id'] = $coupon_id;
        $coupon = $this->getCoupon($coupon_id);

        $userService = new UserService();

        if (empty($coupon) || $coupon['status'] == 4) {
            return api_output(1, ['coupon' => $coupon]);
        } else if ($coupon['allow_new'] && !$userService->checkNew($uid, $coupon['cate_name'])) {
            return api_output(4, ['coupon' => $coupon]);
        } else if ($coupon['end_time'] < $_SERVER['REQUEST_TIME']) {
            $this->systemCouponObj->setField($where, 'status', 2);
            return api_output(2, ['coupon' => $coupon]);
        } else if ($coupon['status'] == 0) {
            return api_output(1, ['coupon' => $coupon]);
        } else if ($coupon['status'] == 2) {
            return api_output(2, ['coupon' => $coupon]);
        } else if ($coupon['status'] == 4) {
            return api_output(2, ['coupon' => $coupon]);
        } else if ($coupon['num'] <= $coupon['had_pull'] || $coupon['status'] == 3) {
            $this->systemCouponObj->setField($where, 'status', 3);
            return api_output(3, ['coupon' => $coupon]);
        } else {
            //判断用户城市
            if ($coupon['city_id']) {

                $now_user = $userService->getUser($uid);
                if ($now_user['city_id'] && $now_user['city_id'] != $coupon['city_id']) {
                    return api_output(6, ['coupon' => $coupon]);
                }
            }

            $hadpull = M('System_coupon_hadpull');
            $hadpull_count = $hadpull->where(array('uid' => $uid, 'coupon_id' => $coupon_id))->count();
            if ($hadpull_count < $coupon['limit']) {
                $reduce_num = 1;
                if ($get_all == 1) {
                    //实际可领取的数量
                    $receive_num = $coupon['limit'] - $hadpull_count;
                    //剩余的数量
                    $after_num = $coupon['num'] - $coupon['had_pull'];
                    $reduce_num = min($receive_num, $after_num);
                }
                if ($this->where($where)->setInc('had_pull', $reduce_num)) {
                    $this->where($where)->setField('last_time', $_SERVER['REQUEST_TIME']);
                    $data_all = array();
                    for ($i = 0; $i < $reduce_num; $i++) {
                        $data = array();
                        $data['coupon_id'] = $coupon_id;
                        $data['num'] = 1;
                        $data['receive_time'] = $_SERVER['REQUEST_TIME'];
                        $data['status'] = 0;
                        if ($card_code) {
                            $data['wx_card_code'] = $card_code;
                        }
                        $data['uid'] = $uid;
                        $data_all[] = $data;
                    }
                    $coupon = $this->get_coupon($coupon_id);
                    if ($hadpull->addAll($data_all)) {
                        if ($now_user = M('User')->where(array('uid' => $uid))->find()) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $cate_platform = $this->cate_platform();
                            $model->sendTempMsg('TM00251', array('href' => C('config.site_url') . '/wap.php?c=My&a=card_list&coupon_type=system', 'wecha_id' => $now_user['openid'], 'first' => L_("您成功领取了X1优惠券", array("X1" => $cate_platform['category'][$coupon['cate_name']])), 'toName' => $now_user['nickname'], 'gift' => $coupon['name'], 'time' => date("Y年m月d日 H:i"), 'remark' => L_('有效期x1 至 x2', array('x1' => date("Y-m-d", $coupon['start_time']), 'x2' => date("Y-m-d", $coupon['end_time'])))));
                        }
                        $coupon['has_get'] = $hadpull_count + $reduce_num;
                        $coupon['reduce_num'] = $reduce_num;
                        return array('error_code' => 0, 'coupon' => $coupon);
                    }
                } else {
                    return array('error_code' => 1, 'coupon' => $coupon);
                }
            } else {
                return array('error_code' => 5, 'coupon' => $coupon);
            }
        }
    }


    /**
     * 获取优惠券信息
     * User: chenxiang
     * Date: 2020/5/29 17:50
     * @param $coupon_id
     * @return mixed
     */
    public function getCoupon($coupon_id){
        $coupon = $this->systemCouponObj->getCouponData(['coupon_id'=>$coupon_id], true);
        return $coupon;
    }

    /**
     * 减少卡券库存
     * User: chenxiang
     * Date: 2020/6/1 10:28
     * @param $add
     * @param $less
     * @param $coupon_id
     * @return mixed|void
     */
    public function decreaseSku($add, $less, $coupon_id)
    {
        $now_coupon = $this->systemCouponObj->getCouponData(['coupon_id' => $coupon_id]);
        if (!$now_coupon['is_wx_card']) {
            return;
        }
        import('ORG.Net.Http');
        $res = D('Access_token_expires')->get_access_token();
        //修改库存
        $wx_data['card_id'] = $now_coupon['wx_cardid'];
        $wx_data['increase_stock_value'] = $add;
        $wx_data['reduce_stock_value'] = $less;
        $update_wx_card = httpRequest('https://api.weixin.qq.com/card/modifystock?access_token=' . $res['access_token'], 'post', json_encode($wx_data, JSON_UNESCAPED_UNICODE));
        $update_wx_card = json_decode($update_wx_card[1], true);
        $errorms = $update_wx_card['errmsg'];
        return $errorms;
    }

    /**
     * 用过ids 获取优惠券信息
     * User: chenxiang
     * Date: 2020/6/1 15:46
     * @param $ids
     * @return \think\Collection
     */
    public function getCouponListByIds($ids)
    {
        $where['coupon_id'] = array('in', $ids);
        $res = $this->systemCouponObj->getCouponListByIds($where, 'coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,is_discount,discount_value,limit,platform');
        return $res;
    }

    /**
     * 优惠券累增
     * User: chenxiang
     * Date: 2020/6/1 19:52
     * @param array $where
     * @param string $field
     * @param string $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = '') {
        $result = $this->systemCouponObj->setInc($where, $field, $num);
        return $result;
    }


    /**
     * 更新优惠券
     * User: chenxiang
     * Date: 2020/6/1 19:59
     * @param array $where
     * @param string $field
     * @param string $value
     * @return SystemCoupon|bool
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->systemCouponObj->setField($where, $field, $value);
        return $result;
    }


}