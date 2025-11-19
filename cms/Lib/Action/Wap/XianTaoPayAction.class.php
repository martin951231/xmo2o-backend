<?php
/*
 * 智慧仙桃支付
 * @author hengtingmei
 * @date 20210727
 */
class XianTaoPayAction extends BaseAction {

    
    //用户授权
    public function toAuthorization(){
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        
        try{

            import('@.ORG.pay.XianTaoCard');
            $pay = new XianTaoCard();

            // 账户授权
           $pay->toAuthorization($phone, $password);

        } catch(Exception $e){
            exit(json_encode(['error_code' => 1,'msg'=>$e->getMessage()]));
        }
        
        exit(json_encode(['error_code' => 0]));
    }

    //输入密码
    public function inputPwd(){
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        
        
        
        $this->display();
    }

    //保存密码
    public function savePassword(){
        $password = $_POST['password'];
        if($_GET['pay_param']){
            $pay_param = base64_decode(urldecode($_GET['pay_param']));
            $_POST = json_decode($pay_param,true);
           
        }
        import('@.ORG.Ticket');
        $password = ticket::create($password, 'xiantaopwd', true);

         $wherePwd = [
            'order_type' => $_POST['order_type'],
            'order_id' => $_POST['order_id'],
            'uid' => $this->user_session['uid'],
        ];
        $save = [
            'password' => $password['ticket'],
            'order_type' => $_POST['order_type'],
            'order_id' => $_POST['order_id'],
            'uid' => $this->user_session['uid'],
            'add_time' => time(),
        ];

        $oldpass = M('Xiantao_pay_pwd')->where($wherePwd)->find();
        if($oldpass){
            $res = M('Xiantao_pay_pwd')->where($wherePwd)->data($save)->save();
        }else{
            $res = M('Xiantao_pay_pwd')->add($save);
        }
        if($res === false){
            $this->error_tips('密码保存失败，请重试!');
        }

        exit(json_encode(['status' => 1,'url' => C('config.site_url').'/wap.php?c=Pay&a=go_pay&wxapp_error_handle=close_window&device_id=packapp&pay_param='.$_GET['pay_param']]));
    }

}