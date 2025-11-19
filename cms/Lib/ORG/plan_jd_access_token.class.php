<?php

class plan_jd_access_token extends plan_base{
    public function runTask(){
        $client_id = C('config.client_id');
        $client_secret = C('config.client_secret');
        $username = C('config.jd_username');
        $password = md5(C('config.jd_password'));
        import('ORG.Net.Http');
        $time = date('Y-m-d H:i:s',time());
        $sign = strtoupper(md5($client_secret.$time.$client_id.$username.$password.'access_token'.$client_secret));
        $url = "https://bizapi.jd.com/oauth2/accessToken";
        $data = array(
            'username'=>$username,
            'password'=>$password,
            'client_id'=>$client_id,
            'client_secret'=>$client_secret,
            'grant_type'=>'access_token',
            'timestamp'=>$time,
            'sign'=>$sign
        );
        $return = Http::curlPost($url,$data);
        if($return['success']==true){
            M('Config')->where(array('name'=>'jd_access_token'))->save(array('value'=>$return['result']['access_token']));
        }else{
            $arr = json_encode($return);
            fdump_sql($arr,'jd_access_token');
        }
        return true;
    }
}
?>