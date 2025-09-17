<?php


namespace app\common\model\service\plan\file;

use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\db\MerchantStoreGiveCouponLog;

class StoreGiveCouponExpireNoticeService
{
    /**
     * 店铺优惠买单返券-优惠券即将过期提醒
     */
    public function runTask()
    {
        $time = time();
        $coupon_log_model = new MerchantStoreGiveCouponLog();
        $where[] = ['h.is_use','=',0];
        $where[] = ['l.end_time','>',$time];
        $where[] = ['u.openid','<>',''];
       
        //获取还有三天过期的优惠券
        $where_first = $where;
        $first_start = strtotime(date('Y-m-d',strtotime('+3 day')));
        $first_end = $first_start+3600*24;
        $where_first[] = ['l.end_time','between',[$first_start,$first_end]];
        $where_first[] = ['l.first_send','=',0];
        $list_first = $coupon_log_model->getExpireList($where_first);
        $this->sendMsg($list_first,'first_send');

        //获取还有一天过期的优惠券
        $where_second = $where;
        $second_start = strtotime(date('Y-m-d',strtotime('+1 day')));
        $second_end = $second_start+3600*24;
        $where_second[] = ['l.end_time','between',[$second_start,$second_end]];
        $where_second[] = ['l.second_send','=',0];
        $list_second = $coupon_log_model->getExpireList($where_second);
        $this->sendMsg($list_second,'second_send');
    }
    
    private function sendMsg($data,$send_time){
        $day_data = ['first_send'=>3,'second_send'=>1];
        $over_day = $day_data[$send_time];
        foreach ($data as $item){
            $msg = L_("X1还有X2天过期！",['X1'=>$item['coupon_name'],'X2'=>$over_day]);
            $msgDataWx = [
                'href' => cfg('site_url').'/packapp/plat/pages/coupon/myCoupon',
                'wecha_id' => $item['openid'],
                'keyword1' => L_('优惠券即将过期通知'),
                'keyword2' => L_($msg),
                'keyword3' => date("Y-m-d H:i"),
            ];
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            (new MerchantStoreGiveCouponLog())->where(['id'=>$item['id']])->save([$send_time=>1]);
        }
    }
}