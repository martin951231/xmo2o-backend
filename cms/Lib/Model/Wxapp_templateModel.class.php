<?php
/*
 * 微信小程序订消息
 * @author hengtingmei
 * @date 20201105
 */
    class Wxapp_templateModel extends Model{
        /**
         * 获得某个业务的可用的模板信息
         * @param $param array
         * @return array
         */
        public function getNormalWxappTemplate($param){

            $type = $param['type'] ?? '';
            $wxappOpenid = $param['wxapp_openid'] ?? '';

            $returnArr = [];
            $where['template_key'] = ['neq', ''];
            $where['status'] = 1;
            
            //  小程序最多支持一次同意三个订阅消息
            switch ($type){
                case 'shop_pay'://外卖支付成功后
                    $templateId = "3578,3115,14403,335,3209";
                    // 验证是否已经同意过，已经同意过的不再返回
                    $whereBind['t.template_id'] =  ['in',$templateId];
                    $whereBind['b.wxapp_openid'] =  $wxappOpenid;
                    $whereBind['b.status'] =  0;
                    $bindList = M('Wxapp_template_bind_user_list')->where($whereBind)->join(' as b join '.C('DB_PREFIX').'wxapp_template as t on t.template_key = b.template_key')->select();
                    if (empty($bindList)) {
                        $where['template_id'] =  ['in','3578,3115,14403'];
                    }else{
                        $templateIdArr = explode(',', $templateId);
                        $bindTemplateIds = array_column($bindList, 'template_id');
                        $templateIdArr = array_diff($templateIdArr, $bindTemplateIds);
                        $where['template_id']  = ['in',$templateIdArr];;
                    }
                    break;
            }

            $template = $this->where($where)->order('id ASC')->limit(3)->select();
            $returnArr['template_list'] =  $template;
            return $returnArr;
        }

        /**
         * 获得单个模板信息
         * @param $param array
         * @return array
         */
        public function getOneNormalWxappTemplate($templateId){
            $where['template_key'] = ['neq', ''];
            $where['template_id'] =  $templateId;
            $where['status'] = 1;
            $template = $this->where($where)->find();
            return $template;
        }

        /**
         * 发送小程序订阅消息
         * @param $param array [
         *                          'type'=> '',//必传，发送类型，shop_pay_success-外卖支付成功
         *                          'wxapp_openid'=> '',//必传，用户公众号模板id
         *                          'page'=> '',//点击跳转页面，例子：pages/foodshop/order/orderDetail?order_id=1
         *                      ]
         * @return array
         */
        public function sendWxappMessage($param){
            $type = $param['type'] ?? 0;
            $wxappOpenid = $param['wxapp_openid'] ?? '';
            if(empty($wxappOpenid) || empty($type)){
                return false;
            }

            $dataInfo = $param['data_info'];
            if (empty($dataInfo)){
                return false;
            }

            // 获得模板id 并初步验证
            $templateId = '';
            switch ($type){
                case 'pay_success'://外卖支付成功
                    $templateId = '3578';
                    break;
                case 'order_status_change'://订单状态变更提醒
                    $templateId = '3115';
                    break;
                case 'add_spread_money'://佣金到账通知
                    $templateId = '14403';
                    break;
                case 'add_score'://积分到账提醒
                    $templateId = '335';
                    break;
                case 'send_coupon'://优惠券到账提醒
                    $templateId = '3209';
                    break;
            }

            // 验证模板是否可用
            $template = $this->getOneNormalWxappTemplate($templateId);
            if(empty($template)){
                return false;
            }

            // 验证用户是否授权过
            $where['template_key'] = $template['template_key'];
            $where['wxapp_openid'] = $wxappOpenid;
            $bind = D('Wxapp_template_bind_user_list')->getTemplateRole($where);
            if(empty($bind)){
                return false;
            }

            // 发送消息
            switch ($type){
                case 'pay_success'://支付成功通知
                    // 订单编号 123456790234235454353534
                    // 商品信息 百事可乐（罐）
                    // 支付金额 2.00元
                    // 支付时间 14:42 2019-12-07
                    // 备注 抢购下单

                    // 发送模板消息
                    $msgData = [
                        'page' => $param['page'],
                        'wecha_id' => $wxappOpenid,
                        'character_string3' => $dataInfo['real_orderid'],//订单编号
                        'thing1' => $dataInfo['goods_desc'],//商品信息描述
                        'amount2' => $dataInfo['price'].C('config.Currency_txt'),//支付金额
                        'time4' => date("Y年m月d日 H:i", $dataInfo['pay_time']),//支付时间
                        'thing6' => $dataInfo['note'],//备注
                    ];
                    break;
                case 'order_status_change'://订单状态变更提醒
                    // 商品名称 洗衣液 500ml
                    // 订单编号 SYU89798798798799
                    // 状态 已接单
                    // 订单金额 2元
                    // 备注信息 用户主动取消订单

                    // 发送模板消息
                    $msgData = [
                        'page' => $param['page'],
                        'wecha_id' => $wxappOpenid,
                        'thing3' => $dataInfo['goods_desc'],//商品信息描述
                        'character_string4' => $dataInfo['real_orderid'],//订单编号
                        'phrase9' => $dataInfo['status_str'],//状态
                        'amount1' => $dataInfo['price'].C('config.Currency_txt'),//支付金额
                        'thing2' => $dataInfo['note'],//备注信息
                    ];
                    break;
                case 'add_spread_money'://佣金到账通知
                    // 商品名称 家用电饭煲
                    // 获得佣金 18.15
                    // 到账时间 2019-12-07
                    // 佣金类型 推荐佣金

                    // 发送模板消息
                    $msgData = [
                        'page' => $param['page'],
                        'wecha_id' => $wxappOpenid,
//                        'thing2' => $dataInfo['goods_desc'],//商品名称
                        'amount4' => $dataInfo['price'].C('config.Currency_txt'),//获得佣金
                        'time1' => date("Y-m-d", $dataInfo['time']),//到账时间
                        'thing5' => $dataInfo['note'],//佣金类型
                    ];
                    break;
                case 'add_score'://积分到账提醒
                    // 订单编号 MYJ777777
                    // 获得积分 3
                    // 累计积分 2222
                    // 到账原因 注册送积分
                    // 交易时间 2019-10-10 14：33

                    // 发送模板消息
                    $msgData = [
                        'page' => $param['page'],
                        'wecha_id' => $wxappOpenid,
                        'character_string2' => $dataInfo['real_orderid'],//订单编号
                        'number5' => $dataInfo['score'],//获得积分
                        'number6' => $dataInfo['score_count'],//累计积分
                        'phrase10' => $dataInfo['note'],//到账原因
                        'date7' => date("Y-m-d H:i", $dataInfo['time']),//交易时间
                    ];
                    break;
                case 'send_coupon'://优惠券到账提醒
                    // 券名称 【优惠券】100-20
                    // 券类型 代金券
                    // 获取时间 2019-12-12 12:21:12
                    // 过期日期 2019-12-13 12:21:12
                    // 备注 领券成功！请立即使用

                    // 发送模板消息
                    $msgData = [
                        'page' => $param['page'],
                        'wecha_id' => $wxappOpenid,
                        'thing1' => $dataInfo['name'],//券名称
                        'phrase2' => $dataInfo['type'],//券类型
                        'time3' => date("Y-m-d", $dataInfo['get_time']),//获取时间
                        'time4' => date("Y-m-d", $dataInfo['pay_time']),//过期日期
                        'thing5' => $dataInfo['note'],//备注
                    ];
                    break;
            }

            $res = $this->sendTempMsg($template['template_key'], $msgData);
            fdump($res,'sendTempMsg',1);

            // 发送完成，修改授权状态
            $where = [
                'id' => $bind['id']
            ];
            if($res){
                // 发送成功
                M('Wxapp_template_bind_user_list')->where($where)->save(['status'=>1]);
            }else{
                // 发送失败，修改授权状态
                M('Wxapp_template_bind_user_list')->where($where)->save(['status'=>2]);
            }
            return $res;
        }

        /**
         * 发送小程序订阅消息
         * @param $tempKey 模板ID
         * @param array $dataArr 参数
         * @return bool
         * @author 衡婷妹
         * @date 2020/10/27
         */
        public function sendTempMsg($tempKey, $dataArr = [])
        {
            $model = '\app\common\model\service\weixin\WxappTemplateService/sendTempMsg';
            $params = [
                'tempKey' => $tempKey,
                'dataArr' => $dataArr,
            ];
            $res = invoke_v20_service($model,$params);
            if(empty($res) || $res['error_no']){
                return false;
            }
            return true;
        }
    }
?>
