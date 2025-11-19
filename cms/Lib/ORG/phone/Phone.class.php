<?php
/*
 * 号码隐私保护对接
 * author 衡婷妹
 * date 20200803
 * */
final class Phone
{
    /*
     * 绑定隐私号码
     * */
    public function axxBind($data){
        if(C('config.protect_dongxin_appkey')&&C('config.protect_dongxin_appsecret')){
            import('@.ORG.phone.DongXinPhone');
            $phoneObj = new DongXinPhone();
            $result = $phoneObj->axxBind($data);
        }else{
            $result = [
                'code'=>1,
                'message'=>'缺少配置'
            ];
        }
        return $result;
    }
}
?>