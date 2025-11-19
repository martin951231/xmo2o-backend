<?php


namespace app\merchant\model\service;


use app\common\model\db\CardNewCouponUseList;
use app\merchant\model\db\CardNewCouponHadpull;
use app\merchant\model\db\MerchantStoreGiveCouponLog;

class BuyOrderGiveCouponService
{
    public function getList($param){
        $page = $param['page']??0;
        $pageSize = $param['pageSize']??10;
        $search_type = $param['search_type']; //1-用户名，2-手机号
        $keywords = $param['keywords'];
        $date_type = $param['date_type']; //1-领取时间
        $start_date = $param['start_date'];
        $end_date = $param['end_date'];
        $mer_id = $param['mer_id'];
        $where[] = ['l.mer_id','=',$mer_id];
        if($keywords){
            switch ($search_type){
                case 1:
                    $where[] = ['u.nickname','like','%'.$keywords.'%'];
                    break;
                case 2:
                    $where[] = ['u.phone','=',$keywords];
                    break;
            }
        }
        if($start_date&&$end_date){
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);
            if($date_type==1){
                $where[] = ['l.add_time','between',[$start_date,$end_date]];
            }
        }
        $store_give_log_model = new MerchantStoreGiveCouponLog();
        $list = $store_give_log_model->getList($where,'',$page,$pageSize);
        if($page>0){
            $data = $list['data'];
        }else{
            $data = $list;
        }
        foreach ($data as $key=>$item){
            $data[$key]['receive_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $data[$key]['order_money'] = $item['store_money'];
            $data[$key]['status_txt'] = '未使用';
            //查询优惠券使用情况
            $coupon_use_total = (new CardNewCouponUseList())->where(['coupon_id'=>$item['coupon_id'],'type'=>0])->count();
            if($coupon_use_total&&$coupon_use_total<$item['num']){
                $data[$key]['status_txt'] = '部分使用';
            }elseif($coupon_use_total&&$coupon_use_total>=$item['num']){
                $data[$key]['status_txt'] = '已使用';
            }
        }
        if($page>0){
            $list['data'] = $data;
            $list['statistics_list'] = [
                'fqze_total' => [
                    'all_txt' => '发券总金额',
                    'note' => '',
                    'all_money' => 0,
                    'week_data' => 0,
                    'day_data' => 0,
                    'day_txt' => '今日发券金额',
                    'day_per' => 0,
                    'day_per_type' => 1, //1-金额，2-数量
                ],
                'fqsl_total' => [
                    'all_txt' => '发券数量',
                    'note' => '',
                    'all_money' => 0,
                    'week_data' => 0,
                    'day_data' => 0,
                    'day_txt' => '今日发券数量',
                    'day_per' => 0,
                    'day_per_type' => 2, //1-金额，2-数量
                ],
                'ysyyhqje_total' => [
                    'all_txt' => '已使用优惠券金额',
                    'note' => '',
                    'all_money' => 0,
                    'week_data' => 0,
                    'day_data' => 0,
                    'day_txt' => '今日使用金额',
                    'day_per' => 0,
                    'day_per_type' => 1, //1-金额，2-数量
                ],
                'ysyyhqsl_total' => [
                    'all_txt' => '已使用优惠券数量',
                    'note' => '',
                    'all_money' => 0,
                    'week_data' => 0,
                    'day_data' => 0,
                    'day_txt' => '今日使用数量',
                    'day_per' => 0,
                    'day_per_type' => 2, //1-金额，2-数量
                ],
            ];

            //周同比
            $first_week_start = strtotime(date('Y-m-d',strtotime('-6 days')));
            $first_week_end = strtotime(date('Y-m-d').' 23:59:59');
            $second_week_start = strtotime(date('Y-m-d',strtotime('-13 days')));
            //日同比
            $first_day_start = strtotime(date('Y-m-d'));
            $first_day_end = strtotime(date('Y-m-d').' 23:59:59');
            $second_day_start = strtotime(date('Y-m-d',strtotime('-1 days')));
            $where_count[] = ['l.mer_id','=',$mer_id];
            //发券统计
            $mer_coupon_field = 'sum(l.num) as num,sum(c.discount*l.num) as discount';
            $mer_coupon_total = $store_give_log_model->userCouponCount($where,$mer_coupon_field);
            $list['statistics_list']['fqze_total']['all_money'] = $mer_coupon_total['discount']?getFormatNumber($mer_coupon_total['discount']):0;
            $list['statistics_list']['fqsl_total']['all_money'] = $mer_coupon_total['num']?:0;

            //发券周统计
            $where_count_first_week = $where_count_send_week = $where_count;
            $where_count_first_week[] = ['l.add_time','between',[$first_week_start,$first_week_end]];
            $where_count_send_week[] = ['l.add_time','between',[$first_week_end,$second_day_start]];
            $mer_coupon_first_week_total = $store_give_log_model->userCouponCount($where_count_first_week,$mer_coupon_field);
            $mer_coupon_send_week_total = $store_give_log_model->userCouponCount($where_count_send_week,$mer_coupon_field);
            $list['statistics_list']['fqze_total']['week_data'] = $this->getCompare($mer_coupon_first_week_total['discount']?:0,$mer_coupon_send_week_total['discount']?:0);
            $list['statistics_list']['fqsl_total']['week_data'] = $this->getCompare($mer_coupon_first_week_total['num']?:0,$mer_coupon_send_week_total['num']?:0);

            //发券日统计
            $where_count_first_day = $where_count_send_day = $where_count;
            $where_count_first_day[] = ['l.add_time','between',[$first_day_start,$first_day_end]];
            $where_count_send_day[] = ['l.add_time','between',[$first_day_end,$second_day_start]];
            $mer_coupon_first_day_total = $store_give_log_model->userCouponCount($where_count_first_day,$mer_coupon_field);
            $mer_coupon_send_day_total = $store_give_log_model->userCouponCount($where_count_send_day,$mer_coupon_field);
            $list['statistics_list']['fqze_total']['day_data'] = $this->getCompare($mer_coupon_first_day_total['discount']?:0,$mer_coupon_send_day_total['discount']?:0);
            $list['statistics_list']['fqsl_total']['day_data'] = $this->getCompare($mer_coupon_first_day_total['num']?:0,$mer_coupon_send_day_total['num']?:0);
            
            //发券今日金额、数量
            $list['statistics_list']['fqze_total']['day_per'] = $mer_coupon_first_day_total['discount']?getFormatNumber($mer_coupon_first_day_total['discount']):0;
            $list['statistics_list']['fqsl_total']['day_per'] = $mer_coupon_first_day_total['num']?:0;
            
            //已使用优惠券统计
            $use_coupon_field = 'sum(ul.num) as num,sum(c.discount*ul.num) as discount';
            $use_coupon_total = $store_give_log_model->userUseCouponCount($where,$use_coupon_field);
            $list['statistics_list']['ysyyhqje_total']['all_money'] = $use_coupon_total['discount']?getFormatNumber($use_coupon_total['discount']):0;
            $list['statistics_list']['ysyyhqsl_total']['all_money'] = $use_coupon_total['num']?:0;
            
            //发券周统计
            $use_coupon_first_week_total = $store_give_log_model->userUseCouponCount($where_count_first_week,$use_coupon_field);
            $use_coupon_send_week_total = $store_give_log_model->userUseCouponCount($where_count_send_week,$use_coupon_field);
            $list['statistics_list']['ysyyhqje_total']['week_data'] = $this->getCompare($use_coupon_first_week_total['discount']?:0,$use_coupon_send_week_total['discount']?:0);
            $list['statistics_list']['ysyyhqsl_total']['week_data'] = $this->getCompare($use_coupon_first_week_total['num']?:0,$use_coupon_send_week_total['num']?:0);

            //发券日统计
            $use_coupon_first_day_total = $store_give_log_model->userUseCouponCount($where_count_first_day,$use_coupon_field);
            $use_coupon_send_day_total = $store_give_log_model->userUseCouponCount($where_count_send_day,$use_coupon_field);
            $list['statistics_list']['ysyyhqje_total']['day_data'] = $this->getCompare($use_coupon_first_day_total['discount']?:0,$use_coupon_send_day_total['discount']?:0);
            $list['statistics_list']['ysyyhqsl_total']['day_data'] = $this->getCompare($use_coupon_first_day_total['num']?:0,$use_coupon_send_day_total['num']?:0);

            //发券今日金额、数量
            $list['statistics_list']['ysyyhqje_total']['day_per'] = $use_coupon_first_day_total['discount']?getFormatNumber($use_coupon_first_day_total['discount']):0;
            $list['statistics_list']['ysyyhqsl_total']['day_per'] = $use_coupon_first_day_total['num']?:0;
            $list['statistics_list'] = array_values($list['statistics_list']);
            return $list;
        }else{
            return $data;
        }
    }

    /**
     * 优惠买单返券记录
     * @param $param
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\Exception
     */
    public function export($param){
        $param['select_all'] = 1;
        $data = $this->getList($param);
        $title = '优惠买单返券记录';
        $xlsCell = [
            ['id', '领取id'],
            ['nickname', '用户名称'],
            ['phone', '用户手机号'],
            ['discount', '优惠券金额'],
            ['num', '数量'],
            ['order_money', '优惠买单金额'],
            ['status_txt', '状态'],
            ['receive_time', '领取时间'],
        ];
        $fileName = date("Y-m-d", time()) . '-' . $param['rand_number'];
        $exportResult = export_excel($title, $xlsCell, $data, $fileName);
        return ['file_url' => $exportResult['url']];
    }


    public function getCompare($number,$numberCompare,$is_percent=1){
        if ($is_percent) {
            // 符号
            $diff = $number-$numberCompare;
            if ($diff>=0) {
                $fuhao = 1;
            }elseif ($diff<0) {
                $fuhao = -1;
            }

            if ($numberCompare!=0&&$number!=0) {
                $compare = $fuhao * abs(getFormatNumber(($number/$numberCompare)-1))*100;
            }elseif (($numberCompare!=0&&$number==0) || ($numberCompare==0&&$number!=0)) {
                $compare = $fuhao * 100;
            }else{
                $compare = '0';
            }
        }else{
            $compare = getFormatNumber($number-$numberCompare);
        }

        return $compare;
    }
}