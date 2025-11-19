<?php 

namespace app\douyin\controller\api;

use app\common\model\service\UserService;
use app\douyin\model\service\DouyinActivityService;
use douyin\Douyin;
use think\Exception;
use token\Token;

class DouyinActivityController extends ApiBaseController
{
    public function config()
    {
        $arr = [
            'client_key' => cfg('douyin_h5_client_key'),
            'scope' => 'user_info,h5.share,data.external.item'
        ];
        return api_output(0, $arr, 'success');
    }

    /**
     * 活动首页
     */
    public function activityIndex()
    {
        $actId = $this->request->param('activity_id', 0, 'intval');
        $uid         = $this->_uid;
        try {
            $data = (new DouyinActivityService())->activityIndex($actId, $uid);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 领取活动优惠券
     */
    public function getActivityCoupon()
    {
        $this->checkLogin();
        $param['activity_id'] = $this->request->param('activity_id', 0, 'intval');
        $param['coupon_id'] = $this->request->param('coupon_id', 0, 'intval');
        $param['uid']         = $this->_uid;
        try {
            $data = (new DouyinActivityService())->getActivityCoupon($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0, $data, 'success');
    }

    /**
     * 抖音H5应用授权登录
     *
     * @return void
     * @author: zt
     * @date: 2022/12/02
     */
    public function login()
    {
        $code = $this->request->param('code', '', 'trim');
        $deviceId = $this->request->param('Device-Id', '', 'trim');
        if($this->_uid){
            $user = (new \app\common\model\db\User())->where('uid', $this->_uid)->field('uid,nickname,phone,dy_openid,dy_unionid,status')->findOrEmpty()->toArray();
            if ($user['status'] != 1) {
                return api_output(1003, [], '用户已注销或未注册');
            }
            $return = [
                'device_id' => $deviceId,
                'v20_ticket' => Token::createToken($user['uid'], $deviceId),
                'user' => $user,
            ];
        }else{
            if (empty($code)) {
                return api_output(1001, [], '参数有误');
            }
            //获取用户信息
            $response = (new Douyin())->getUserInfoByCode($code);
            //$str='{"data":{"avatar":"https://p26.douyinpic.com/aweme/100x100/aweme-avatar/mosaic-legacy_2f7020001aa08e07787dd.jpeg?from=4010531038","avatar_larger":"https://p6.douyinpic.com/aweme/1080x1080/aweme-avatar/mosaic-legacy_2f7020001aa08e07787dd.jpeg?from=4010531038","captcha":"","city":"","client_key":"awkvgennnkwy79hg","country":"","desc_url":"","description":"","district":"","e_account_role":"","error_code":0,"gender":0,"log_id":"202212021512020101510661480D059F40","nickname":"新新爸爸","open_id":"_0003oSmQe9FNg2aKB6R0wzKqJqeMuhaJXLg","province":"","union_id":"e8b3d738-56ee-4677-aa9f-38e8267d0305"},"message":"success"}';
            //$response = json_decode($str,true);
            $userData = [
                'nickname' => $response['data']['nickname'],
                'dy_unionid' => $response['data']['union_id'],
                'avatar' => $response['data']['avatar_larger'],
                'province' => $response['data']['province'],
                'city' => $response['data']['city'],
                'sex' => $response['data']['gender'],
            ];
            $user = (new UserService())->autoReg($userData, false, true);
            $return = [
                'device_id' => $deviceId,
                'v20_ticket' => Token::createToken($user['uid'], $deviceId),
                'user' => $user,
            ];
        }
        return api_output(0, $return);
    }

    public function getShareVideo()
    {
        $this->checkLogin();
        $actId = $this->request->param('activity_id', 0, 'intval');
        $uid = $this->_uid;
        try {
            $data = (new DouyinActivityService())->getShareVideo($actId, $uid);
            return api_output(0, $data);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function couponDetail()
    {
        $hadpullId = $this->request->param('hadpull_id', 0, 'intval');
        $activityId = $this->request->param('activity_id', 0, 'intval');
        $uid = $this->_uid;
        try {
            $data = (new \app\common\model\service\coupon\MerchantCouponService())->showDouyinActivityCouponDetail($hadpullId, $activityId, $uid);
            return api_output(0, $data);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    public function jsSdkConfig()
    {
        $url = $this->request->param('url', '', 'trim');
        try {
            $data = (new DouyinActivityService())->jsSdkConfig($url);
            return api_output(0, $data);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}