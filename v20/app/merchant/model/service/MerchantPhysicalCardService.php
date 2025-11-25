<?php


namespace app\merchant\model\service;


use app\merchant\model\db\MerchantPhysicalCard;
use app\merchant\model\db\MerchantPhysicalCardOrder;

class MerchantPhysicalCardService
{
    /**
     * 实体卡-列表
     * @param $param
     * @return array|mixed
     */
    public function getList($param){
        $page = $param['page']??0;
        $pageSize = $param['pageSize']??10;
        $search_type = $param['search_type']; //1-id,2-实体卡号，3-总金额，4-卡密，5-绑定用户
        $keywords = $param['keywords'];
        $date_type = $param['date_type']; //1-建卡时间，2-兑换时间
        $start_date = $param['start_date'];
        $end_date = $param['end_date'];
        $mer_id = $param['mer_id'];
        $where[] = ['c.mer_id','=',$mer_id];
        $where[] = ['c.is_del','=',0];
        if($keywords){
            switch ($search_type){
                case 1:
                    $where[] = ['c.id','=',$keywords];
                    break;
                case 2:
                    $where[] = ['c.card_no','=',$keywords];
                    break;
                case 3:
                    $where[] = ['c.money','=',$keywords];
                    break;
                case 4:
                    $where[] = ['c.carmi','=',$keywords];
                    break;
                case 5:
                    $where[] = ['u.nickname','like','%'.$keywords.'%'];
                    break;
            }
        }
        if($start_date&&$end_date){
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);
            if($date_type==1){
                $where[] = ['c.add_time','between',[$start_date,$end_date]];
            }elseif($date_type==2){
                $where[] = ['c.exchange_time','between',[$start_date,$end_date]];
            }
        }
        
        $list = (new MerchantPhysicalCard())->getList($where,'',$page,$pageSize);
        if($page>0){
            $data = $list['data'];
        }else{
            $data = $list;
        }
        foreach ($data as $key=>$item){
            $data[$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $data[$key]['exchange_time'] = $item['exchange_time']?date('Y-m-d H:i:s',$item['exchange_time']):'';
            $data[$key]['qr_code'] = cfg('site_url').'/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon='.urlencode(cfg('site_url').'/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id='.$mer_id.'&is_exchange_card=1&cardmi='.$item['carmi']);
        }
        if($page>0){
            $list['data'] = $data;
            //实体卡消费金额统计
            $list['total_money'] = [
                'all_txt' => '实体卡消费总金额',
                'all_money' => 0,
                'week_data' => 0,
                'day_data' => 0,
                'day_txt' => '日均销售额',
                'day_per' => 0,
                'note' => '',
                'day_per_type' => 1, //1-金额，2-数量
            ];
            //实体卡总数统计
            $list['total_card'] = [
                'all_txt' => '实体卡总数',
                'all_money' => 0,
                'week_data' => 0,
                'day_data' => 0,
                'day_txt' => '今天建卡',
                'day_per' => 0,
                'note' => '',
                'day_per_type' => 2, //1-金额，2-数量
            ];
            //实体卡统计
            $total = (new MerchantPhysicalCard())->getList($where,'count(id) as id_num,sum(use_money) as user_money_num',0);
            $list['total_money']['all_money'] = $total[0]['user_money_num']?:0;
            $list['total_card']['all_money'] = $total[0]['id_num'];
            //获取总销售额
            $all_total = (new MerchantPhysicalCard())->where(['mer_id'=>$mer_id])->order('id asc')->sum('use_money');
            //获取第一次消费时间
            $first_time = (new MerchantPhysicalCardOrder())->where(['mer_id'=>$mer_id])->order('id asc')->value('add_time');
            $day_num = $first_time?ceil((time()-$first_time)/(3600*24)):0;
            $list['total_money']['day_per'] = $day_num?round($all_total/$day_num,2):0;
            //消费周同比
            $first_week_start = strtotime(date('Y-m-d',strtotime('-6 days')));
            $first_week_end = strtotime(date('Y-m-d').' 23:59:59');
            $second_week_start = strtotime(date('Y-m-d',strtotime('-13 days')));
            $first_week_info = (new MerchantPhysicalCard())->getTotal([['c.is_del','=',0],['c.mer_id','=',$mer_id],['co.add_time','between',[$first_week_start,$first_week_end]]],'sum(co.money) as money');
            $second_week_info = (new MerchantPhysicalCard())->getTotal([['c.is_del','=',0],['c.mer_id','=',$mer_id],['co.add_time','between',[$second_week_start,$first_week_start]]],'sum(co.money) as money');
            $list['total_money']['week_data'] = $this->getCompare($first_week_info['money']?:0,$second_week_info['money']?:0);
            //消费日同比
            $first_day_start = strtotime(date('Y-m-d'));
            $first_day_end = strtotime(date('Y-m-d').' 23:59:59');
            $second_day_start = strtotime(date('Y-m-d',strtotime('-1 days')));
            $first_day_info = (new MerchantPhysicalCard())->getTotal([['c.is_del','=',0],['c.mer_id','=',$mer_id],['co.add_time','between',[$first_day_start,$first_day_end]]],'sum(co.money) as money');
            $second_day_info = (new MerchantPhysicalCard())->getTotal([['c.is_del','=',0],['c.mer_id','=',$mer_id],['co.add_time','between',[$second_day_start,$first_day_start]]],'sum(co.money) as money');
            $list['total_money']['day_data'] = $this->getCompare($first_day_info['money']?:0,$second_day_info['money']?:0);
            //实体卡周同比
            $first_week_card = (new MerchantPhysicalCard())->where([['is_del','=',0],['mer_id','=',$mer_id],['add_time','between',[$first_week_start,$first_week_end]]])->count();
            $second_week_card = (new MerchantPhysicalCard())->where([['is_del','=',0],['mer_id','=',$mer_id],['add_time','between',[$second_week_start,$first_week_start]]])->count();
            $list['total_card']['week_data'] = $this->getCompare($first_week_card,$second_week_card);
            //实体卡日同比
            $first_day_card = (new MerchantPhysicalCard())->where([['is_del','=',0],['mer_id','=',$mer_id],['add_time','between',[$first_day_start,$first_day_end]]])->count();
            $second_day_card = (new MerchantPhysicalCard())->where([['is_del','=',0],['mer_id','=',$mer_id],['add_time','between',[$second_day_start,$first_day_start]]])->count();
            $list['total_card']['day_data'] = $this->getCompare($first_day_card,$second_day_card);
            //今日建卡
            $list['total_card']['day_per'] = $first_day_card;
            $list['statistics_list'] = array_merge([$list['total_money']],[$list['total_card']]);
            return $list;
        }else{
            return $data;
        }
    }

    /**
     * 实体卡-添加
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addCard($param){
        set_time_limit(0);
        $card_prefix = $param['card_prefix']?:'';
        $start_no = $param['start_no']?:0; //卡号位数
        //$end_no = $param['end_no'];
        $money = $param['money'];
        $total = $param['total'];
        $mer_id = $param['mer_id'];
        if(!$card_prefix){
            throw new \think\Exception(L_('卡前缀不能为空！'));
        }
        if(!$start_no||!is_numeric($start_no)){
            throw new \think\Exception(L_('请填写卡号位数！'));
        }
        if(!$money||$money<=0){
            throw new \think\Exception(L_('实体卡金额不能为空！'));
        }
        if(!$total){
            throw new \think\Exception(L_('数量不能为空！'));
        }
        $card_total = str_pad(1,($start_no+1),0,STR_PAD_RIGHT);
        if(($card_total-1)<$total){
            throw new \think\Exception(L_('数量超出卡号范围！'));
        }
        $time = time();
        $saveData = $card_no_arr = $carmi_arr = [];
        for ($i=0;$i<$total;$i++){
            $card_no = $this->checkCard($card_no_arr,$mer_id,1,$start_no,$card_prefix);
            $card_no_arr[] = $card_no;
            $carmi_no = $this->checkCard($carmi_arr,$mer_id,2,12,$card_prefix);
            $carmi_arr[] = $carmi_no;
            $saveData[] = [
                'card_no' => $card_no,
                'add_time' => $time,
                'mer_id' => $mer_id,
                'money' => $money,
                'carmi' => $carmi_no,
                'is_del' => 0
            ];
        }
        if(!$saveData){
            throw new \think\Exception(L_('卡号范围有误！'));
        }
        $res = (new MerchantPhysicalCard())->saveAll($saveData);
        if($res){
            return true;
        }else{
            throw new \think\Exception(L_('添加失败！'));
        }
    }

    /**
     * 生成不重复的卡号/卡密
     * @param $no
     * @param int $type
     */
    public function checkCard($arr=[],$mer_id,$type=1,$card_num=1,$card_prefix=''){
        $where['mer_id'] = $mer_id;
        if($type==1){
            //生成卡号
            $start_num = str_pad(1,$card_num,0,STR_PAD_RIGHT);
            $end_num = str_pad(1,($card_num+1),0,STR_PAD_RIGHT)-1;
            $card_no = $card_prefix.mt_rand($start_num,$end_num);
            $where['card_no'] = $card_no;
        }else{
            //生成卡密
            $start_num = str_pad(1,$card_num,0,STR_PAD_RIGHT);
            $end_num = str_pad(1,($card_num+1),0,STR_PAD_RIGHT)-1;
            $card_no = mt_rand($start_num,$end_num);
            $where['carmi'] = $card_no;
        }
        $card = (new MerchantPhysicalCard())->where($where)->findOrEmpty()->toArray();
        while (count($card)>0||in_array($card_no,$arr)){
            if($type==1){
                //生成卡号
                $card_no = $card_prefix.mt_rand($start_num,$end_num);
                $where['card_no'] = $card_no;
            }else{
                //生成卡密
                $card_no = mt_rand($start_num,$end_num);
                $where['carmi'] = $card_no;
            }
            $card = (new MerchantPhysicalCard())->where($where)->findOrEmpty()->toArray();
        }
        return $card_no;
    }

    /**
     * 实体卡-删除
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delCard($param){
        $id = $param['id']?:0;
        $mer_id = $param['mer_id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要删除的实体卡！'));
        }
        $card = (new MerchantPhysicalCard())->where(['mer_id'=>$mer_id,'id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$card){
            throw new \think\Exception(L_('实体卡不存在/已删除！'));
        }
        if((new MerchantPhysicalCard())->where(['mer_id'=>$mer_id,'id'=>$id,'is_del'=>0])->save(['is_del'=>1])){
            return true;
        }else{
            throw new \think\Exception(L_('删除失败！'));
        }
    }

    /**
     * 实体卡-消费记录
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getCardOrderList($param){
        $id = $param['id']?:0;
        $page = $param['page']?:0;
        $pageSize = $param['pageSize']?:0;
        $mer_id = $param['mer_id'];
        if(!$id){
            throw new \think\Exception(L_('请选择要查看的实体卡！'));
        }
        $card = (new MerchantPhysicalCard())->where(['mer_id'=>$mer_id,'id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$card){
            throw new \think\Exception(L_('实体卡不存在/已删除！'));
        }
        $where[] = ['co.mer_id','=',$mer_id];
        $where[] = ['co.card_id','=',$id];
        
        $list = (new MerchantPhysicalCardOrder())->getList($where,'',$page,$pageSize);
        $type_txt = ['store_staff'=>'店员收银','shop_offline'=>'外卖（线下零售）','merchant_card'=>'充值商家会员卡'];
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
            $list['data'][$key]['type_txt'] = $type_txt[$item['type']]??'';
        }
        return $list;
    }

    /**
     * 导出实体卡
     * @param $param
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\Exception
     */
    public function exportCard($param){
        $data = $this->getList($param);
        $title = '实体卡列表';
        $xlsCell = [
            ['id', 'ID'],
            ['card_no', '实体卡号'],
            ['use_money', '金额'],
            ['money', '总金额'],
            ['carmi', '卡密'],
            ['nickname', '用户昵称'],
            ['phone', '用户手机号'],
            ['exchange_time', '兑换时间'],
            ['add_time', '建卡时间'],
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