<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2022/1/28
 * Time: 16:46
 *======================================================
 */

namespace app\community\model\service;


use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillageDetailRecord;
use app\community\model\db\HouseVillagePrintCustom;
use app\community\model\db\HouseVillagePrintCustomConfigure;
use app\community\model\db\HouseVillagePrintTemplate;
use app\community\model\db\HouseVillagePrintTemplateNumber;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\MaterielCheckout;
use app\community\model\db\HouseVillage;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;

class PrintTemplateService
{
    /**
     * 打印模板类型列表  后续添加更多类型时考虑放在数据库表中
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 9:40
     * @return array[]
     */
    public function templateType () {
        return [
            [
                'id' => 1,
                'name' => '模板一',
                'url' =>  cfg('site_url').'/static/images/house/print_template_1.png'
            ],
            [
                'id' => 2,
                'name' => '模板二',
                'url' =>  cfg('site_url').'/static/images/house/print_template_2.png'
            ],
            [
                'id' => 3,
                'name' => '模板三',
                'url' =>  cfg('site_url').'/static/images/house/print_template_3.png'
            ]
        ];
    }
    public function templateFontSize () {
        return [
            [
                'key' => 1,
                'name' => '12px',
                'value' =>  '12'
            ],
            [
                'key' => 2,
                'name' => '13px',
                'value' =>  '13'
            ],
            [
                'key' => 3,
                'name' => '14px',
                'value' =>  '14'
            ],
            [
                'key' => 4,
                'name' => '15px',
                'value' =>  '15'
            ],
            [
                'key' => 5,
                'name' => '16px',
                'value' =>  '16'
            ],
            [
                'key' => 6,
                'name' => '17px',
                'value' =>  '17'
            ],
            [
                'key' => 7,
                'name' => '18px',
                'value' =>  '18'
            ],
            [
                'key' => 8,
                'name' => '19px',
                'value' =>  '19'
            ],
            [
                'key' => 9,
                'name' => '20px',
                'value' =>  '20'
            ],
            [
                'key' => 10,
                'name' => '21px',
                'value' =>  '21'
            ],
            [
                'key' => 11,
                'name' => '23px',
                'value' =>  '23'
            ],
            [
                'key' => 12,
                'name' => '25px',
                'value' =>  '25'
            ],
            [
                'key' => 13,
                'name' => '26px',
                'value' =>  '26'
            ],
            [
                'key' => 14,
                'name' => '28px',
                'value' =>  '28'
            ],
            [
                'key' => 15,
                'name' => '30px',
                'value' =>  '30'
            ],
            [
                'key' => 16,
                'name' => '32px',
                'value' =>  '32'
            ],
            [
                'key' => 17,
                'name' => '35px',
                'value' =>  '35'
            ],
        ];
    }
    /**
     * 获取打印模板列表
     * User: zhanghan
     * Date: 2022/1/28
     * Time: 17:07
     * @param $where
     * @param $field
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPrintTemplateList($where,$field = true,$page = 0,$limit = 10){
        if (empty($where)){
            throw new Exception('查询条件不能为空');
        }
        $print_template = new HouseVillagePrintTemplate();
        $data = $print_template->getListByPage($where,$field,$page,$limit);
        foreach ($data['list'] as &$value){
            switch ($value['type']){
                case 3:
                    $value['type_txt'] = '模板三';
                    break;
                case 2:
                    $value['type_txt'] = '模板二';
                    break;
                default :
                    $value['type_txt'] = '模板一';
                    break;
            }
        }
        // 模板类型
        $data['templateTypeArr'] = $this->templateType();
        // 返回下可以进入的老板模板链接
        $data['oldPrintTemplateList'] = '/village/village.iframe/house_cashier_print_template_list';
        return $data;
    }

    /**
     * 打印模板详情
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 9:46
     * @param $where
     * @param $field
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTemplateDetail($where,$field = true){
        if (empty($where)){
            throw new Exception('查询条件不能为空');
        }
        $print_template = new HouseVillagePrintTemplate();
        $print_custom_configure = new HouseVillagePrintCustomConfigure;

        $data = $print_template->get_one($where);
        if($data && !$data->isEmpty()){
            $data = $data->toArray();
            // 模板字段
            $list = [];
            if(!empty($data['custom_field'])){
                $list = json_decode($data['custom_field'],1);
                unset($data['custom_field']);
            }
            // 获取打印模板中的字段
            $return['list'] = $list;

            // 关键词
            if(empty(Cache::get('template_key_word'))){
                $print_title_list = $print_custom_configure->getList([['is_hidden', '=', 0]],'title');
                $template_key_word = array_values(array_unique(array_column($print_title_list,'title')));
                Cache::set('template_key_word',$template_key_word);
            }else{
                $template_key_word = Cache::get('template_key_word');
            }
            $return['template_key_word'] = array_values(array_unique($template_key_word));
            $return['font_size']=$this->templateFontSize();
            $return['font_set']='';
            if(isset($data['font_set']) && !empty($data['font_set'])){
                $return['font_set']=json_decode($data['font_set'],1);
                unset($data['font_set']);
            }
            if(isset($data['extra_data']) && !empty($data['extra_data'])){
                $return['extra_data']=json_decode($data['extra_data'],1);
                unset($data['extra_data']);
            }else{
                $return['extra_data']='';
            }
            if(isset($data['seal_img']) && $data['seal_img']){
                $data['seal_img']= replace_file_domain( $data['seal_img']);
            }
            $return['data'] = $data;
            return $return;
        }else{
            throw new Exception('未查询到模板信息');
        }
    }

    /**
     * 保存打印模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 10:28
     * @param $template_id
     * @param $param
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function templateAdd($template_id,$param){
        if(empty($param) || empty($param['village_id'])){
            throw new Exception('保存参数或者小区不能为空');
        }
        $print_template = new HouseVillagePrintTemplate();
        if($template_id > 0){
            // 编辑
            $where = [];
            $where[] = ['template_id','=',$template_id];
            $where[] = ['village_id','=',$param['village_id']];

            $data = $print_template->get_one($where,'template_id');
            if($data && !$data->isEmpty()){
                $res = $print_template->templateSave($where,$param);
            }else{
                throw new Exception('参数错误，小区不存在该模板');
            }
        }else{
            // 新增 新版打印
            $param['is_new'] = 1;
            $param['time'] = time();
            $param['custom_field'] = '';
            if(!isset($param['uid'])){
                $param['uid'] = 0;
            }
            $res = $print_template->templateAdd($param);
        }
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除打印模板
     * User: zhanghan
     * Date: 2022/1/29
     * Time: 11:03
     * @param $template_id
     * @param $village_id
     * @return bool
     * @throws Exception
     */
    public function delTemplate($template_id,$village_id){
        if(empty($template_id) || empty($village_id)){
            throw new Exception('模板ID或小区ID不能为空');
        }
        $print_template = new HouseVillagePrintTemplate();
        $where = [];
        $where[] = ['template_id','=',$template_id];
        $where[] = ['village_id','=',$village_id];
        return $print_template->delTemplate($where);

    }

    /**
     * 获取配置区选择字段列表
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 13:05
     * @param $where
     * @param bool $field
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPrintCustomConfigureList($template_id,$type,$field = true){
        if(empty($type) || empty($template_id)){
            throw new Exception('类型或者模板ID不能为空');
        }
        $print_template = new HouseVillagePrintTemplate();

        $where = [];
        $where[] = ['template_id','=',$template_id];
        
        $res = $print_template->get_one($where,'type');
        if(empty($res)){
            throw new Exception('模板ID参数错误');
        }

        $where_config = [];
        $where_config[] = ['type','=',$type];
        $where_config[] = ['is_hidden','<',1];
        $print_custom_configure = new HouseVillagePrintCustomConfigure;
        $list = $print_custom_configure->getList($where_config,$field);
        if(empty($list)){
            throw new Exception('未查询到相关数据');
        }
        return $list;
    }

    /**
     * 保存模板配置详情
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 15:03
     * @param $village_id
     * @param $template_id
     * @param $ids
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addPrintTemplateCustom($village_id,$template_id,$ids,$font_set=array(),$extra_data=array()){
        $print_custom = new HouseVillagePrintCustom();
        $print_template = new HouseVillagePrintTemplate();

        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['template_id','=',$template_id];
        $type=1;
        $print_extra_data=array();
        $data = $print_template->get_one($where);
        if(!$data || $data->isEmpty()){
            throw new Exception('未查询到模板信息');
        }else{
            $data=$data->toArray();
            $type=$data['type'];   //模板类型
            if(isset($data['extra_data']) && !empty($data['extra_data'])){
                $print_extra_data=json_decode($data['extra_data'],1);
            }
        }
        unset($data);
        // 开启事务
        Db::startTrans();
        //删除之前的
        $print_custom->delTemplateCustom($where);
        if (is_array($ids) && $ids) {
            $data = [];
            foreach ($ids as $key => $value) {
                if(is_numeric($value['id'])){ // 非自定义字段
                    $temp = array(
                        'template_id' => $template_id,
                        'village_id' => $village_id,
                        'configure_id' => intval($value['id']),
                        'uid'=>0,
                        'time' => time(),
                    );
                    $data[] = $temp;
                }
            }
            $res = $print_custom->addPrintTemplateCustom($data);
            if(!$res){
                // 回滚
                Db::rollback();
                throw new Exception('保存失败');
            }
            $js_ids = json_encode($ids,JSON_UNESCAPED_UNICODE);
        }else{
            $js_ids = '';
        }
        // 保存 转json 保存
        $saveArr=array('custom_field' => $js_ids);
        if($type==3 && !empty($font_set)){
            $saveArr['font_set']=json_encode($font_set,JSON_UNESCAPED_UNICODE);
        }
        if($type==3 && !empty($extra_data)){
            $print_extra_data=array_merge($print_extra_data,$extra_data);
            $saveArr['extra_data']=json_encode($print_extra_data,JSON_UNESCAPED_UNICODE);
        }
        $print_template->templateSave($where,$saveArr);
        // 提交
        Db::commit();
        return [];
    }

    /**
     * 获取模板打印编号并保存
     * User: zhanghan
     * Date: 2022/2/7
     * Time: 17:05
     * @param $village_id
     * @param $print_template_id
     * @param $order_ids
     * print_special 大于0的值表示不同的定制需求
     * @return mixed|string
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printTemplateNumber($print_template_id, $order_ids,$village_id=0,$print_special=0){
        if(empty($print_template_id) || empty($order_ids) || empty($village_id)){
            throw new Exception('模板ID或订单ID或小区ID不能为空');
        }
        sort($order_ids,SORT_NUMERIC);
        $print_template_number = new HouseVillagePrintTemplateNumber();
        $print_template = new HouseVillagePrintTemplate();

        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['template_id','=',$print_template_id];
        $template_info = $print_template->get_one($where,'type');
        if(!$template_info || $template_info->isEmpty()){
            throw new Exception('未查询到模板信息');
        }
        $db_house_new_pay_order = new HouseNewPayOrder();
        $is_notpay_print=0;
        $one_order_obj=$db_house_new_pay_order->get_one(array('order_id'=>$order_ids['0']),'order_id,village_id,is_paid,pay_time');
        if($one_order_obj && !$one_order_obj->isEmpty()){
            $one_order=$one_order_obj->toArray();
            if($one_order && $one_order['pay_time']<10){
                $is_notpay_print=1;
            }
        }
        $village_code='';
        if($print_special==1){
            $houseVillageDb=new HouseVillage();
            $villageTmp =$houseVillageDb->getInfo(array('village_id'=>$village_id),'village_code');
            if($villageTmp && isset($villageTmp['village_code']) && $villageTmp['village_code']){
                $village_code=trim($villageTmp['village_code']);
            }
        }
        
        // 获取一条数据
        $where = [];
        $where[] = ['print_template_id','=',$print_template_id];
        $where[] = ['order_ids','=',implode(',',$order_ids)];
        if($print_special>0){
            $where[] = ['print_special','=',$print_special];
        }else{
            $where[] = ['print_special','<',1];
        }
        $data = $print_template_number->getOne($where,'*','id DESC');
        if(!empty($data)){
             $print_number_tmp=sprintf('%07s',$data['print_number']);
             if($print_special==1){
                 $print_index=$data['print_index'];
                 if($print_index<1000){
                     $print_index=str_pad($print_index,4,'0',STR_PAD_LEFT);
                 }
                 $print_number_tmp=$village_code.$data['print_date'].$print_index;
             }
            return $print_number_tmp;
        }else{
            // 最新一条数据
            $whereArr=array(['id','>',0]);
            $print_date=date('Ymd');
            $print_index=1;
            $orderby='id DESC';
            if($print_special==1){
                $whereArr[]=array('village_id','=',$village_id);
                $whereArr[]=array('print_date','=',$print_date);
                $orderby='print_index DESC';
            }
            $first_new = $print_template_number->getOne($whereArr,'*',$orderby);
            $id_indx=1;
             if($first_new && $first_new['id']){
                $print_index=$first_new['print_index']>0 ? $first_new['print_index']+1:1;
                $id_indx= $first_new['id']+1;
            }
            // 不存在，则添加打印模板编号记录
            $data = [
                'village_id' => $village_id,
                'print_template_id' => $print_template_id,
                'order_ids' => implode(',',$order_ids),
            ];
            $data['create_at']= time();
            if($print_special==1){
                $data['print_number']= 0;
                $data['print_date']= $print_date;
                $data['print_index']= $print_index;
                $data['print_special']= 1;
            }else{
                $data['print_number']= $id_indx;
            }
            if($is_notpay_print>0){
                $data['print_type']=1;
            }
            $print_template_number->addOne($data);
            $print_number_tmp=sprintf('%07s',$id_indx);
            if($print_special==1){
                if($print_index<1000){
                    $print_index=str_pad($print_index,4,'0',STR_PAD_LEFT);
                }
                $print_number_tmp=$village_code.$print_date.$print_index;
            }
            return $print_number_tmp;
        }
    }

    public function getOrderPrintNumber($village_id=0,$order_id=0){
        if($village_id<1 || $order_id<1){
            return '';
        }
        $print_template_number = new HouseVillagePrintTemplateNumber();
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['order_ids','FIND IN SET',$order_id];
        $tmplists = $print_template_number->getList($where,'print_number','id ASC');
        return $tmplists;
    }
    /**
     * 更改打印订单开票状态
     * User: zhanghan
     * Date: 2022/2/10
     * Time: 11:01
     * @param $order_id
     * @param int $pigcms_id
     * @param array $choice_ids
     * @return bool
     * @throws Exception
     */
    public function printRecordUrl($order_id, $pigcms_id = 0, $choice_ids = [], $printTempLateType = '')
    {
        if (empty($order_id) && empty($choice_ids)) {
            throw new \think\Exception("订单id不能为空！");
        }

        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_detail_record = new HouseVillageDetailRecord();

        if ($printTempLateType == 'materiel') {
            if (empty($choice_ids)) {
                throw new \think\Exception("缺少打印对象！");
            }
            $dbMaterielCheckout = new MaterielCheckout();
            $whereCheckout = [];
            $whereCheckout[] = ['id', 'in', $choice_ids];
            $checkouts = $dbMaterielCheckout->getList($whereCheckout);
            if ($checkouts && !is_array($checkouts)) {
                $checkouts = $checkouts->toArray();
            }
            if (empty($checkouts)) {
                throw new \think\Exception("打印对象不存在！");
            }
            return true;
        } 
        elseif (!empty($choice_ids)) {
            $arr2 = array_unique(array_column($choice_ids, 'pigcms_id'));
            $orderid2 = array_unique(array_column($choice_ids, 'orderid'));
            if(empty($arr2) ){
                throw new \think\Exception("查询数据不存在！");
            }
            $pigcms_id_count=count($arr2);
            if($pigcms_id_count==2 && in_array(0,$arr2)){
                //如果是两个数据 有一个是0 则算一个人
            }else if($pigcms_id_count > 1 ){
                throw new \think\Exception("当前仅支持同一个缴费人进行批量打印已缴账单！");
            }
            //$user_info = $db_house_village_user_bind->getOne(['pigcms_id' => $arr2[0]]);
            $where[] = ['order_id','in',$orderid2];
        }
        else{
            $where[] = ['order_id', '=', $order_id];
            //$user_info = $db_house_village_user_bind->getOne(['pigcms_id' => $pigcms_id]);
        }
        $field='o.order_id,o.village_id,o.order_name,o.unit_price,o.pay_time';
        $orderList = $db_house_new_pay_order->getOrderList($where,$field);
        if($orderList && !$orderList->isEmpty()){
            $orderList = $orderList->toArray();
        }else{
            throw new \think\Exception("订单不存在！");
        }

        $recordData = []; // 开票数据 新版打印 打印后，订单开票状态为已开票  无法监听浏览器打印机打印事件，程序默认查询数据后就打印了
        foreach ($orderList as $k => $v) {
            if ($v['pay_time'] > 10) {  //已缴费账单

                // 获取订单开票记录
                $res = $db_house_village_detail_record->getOne([['order_id', '=', $v['order_id']]]);
                if ($res && !$res->isEmpty()) {
                    $db_house_village_detail_record->incUpdate([['order_id', '=', $v['order_id']]], 'print_num');
                    $db_house_new_pay_order->saveOne(['order_id'=> $v['order_id']],['invoice_status'=>1]);
                    continue;
                }
                // 新版打印 更新订单为开票状态 无法监听浏览器打印机打印事件，程序默认查询数据后就打印了
                $recordData[] = [
                    'order_id' => $v['order_id'],
                    'property_type' => $v['order_name'],
                    'price' => $v['unit_price'],
                    'village_id' => $v['village_id'],
                    'create_time' => time(),
                    'order_type' => 2,
                    'print_num' => 1,
                ];
                $db_house_new_pay_order->saveOne(['order_id'=> $v['order_id']],['invoice_status'=>1]);
            }
        }
        //打印后，订单开票状态为已开票  无法监听浏览器打印机打印事件，程序默认查询数据后就打印了
        if(!empty($recordData)){
            $db_house_village_detail_record->addEDetailRecord($recordData);
        }
        return true;
    }

}