<?php
class plan_sub_qywx_session_file extends plan_base{
    use customization;
    public function runTask($param){
        $property = D('House_property')->where(['status'=>['in',[0,1]]])->field('id')->select();
        fdump_api($property,'plan_sub_qywx_session_file',true);
       $isoa8003Customized= D('Config_customization')->getHzhouGrapefruitOrderJudge();
       $isAihorseCustomized =D('Config_customization')->getIsAihorseCustomized();
        foreach ($property as $val){
            $where = [];
            $where['bind_type'] = 0;
            $where['bind_id'] = $val['id'];
            $wxBind = D('House_enterprise_wx_bind')->where($where)->field('corpid')->find();
            if($wxBind['corpid']){
                $info = D('Work_msg_audit')->where(['corp_id'=>$wxBind['corpid']])->field('id,secret,status,seq')->find();
                if($info && $info['status'] == 1){
                    $cnfId = $val['id'];//物业id
                    $seq = $info['seq'];
                    //$url = "http://localhost:18080/msgaudit/run?cnfId=".$cnfId."&seq=".$seq;
                    $url = "http://auth.msgaudit.com:30164/msgaudit/run?cnfId=".$cnfId."&seq=".$seq;
                    if($isoa8003Customized || $isAihorseCustomized){
                        //小红马 oa.8003 这2个客户 维持之前的
                        $url = "http://localhost:18080/msgaudit/run?cnfId=".$cnfId."&seq=".$seq;
                    }
                    if ($this->hasCloudIntercom()) {
                        $url = "http://192.168.1.100:18080/msgaudit/run?cnfId=" . $cnfId . "&seq=" . $seq;
                    }
                    if (!function_exists("shell_exec")) {
                        fdump_api(['shell_exec'=>'no_function','url'=>$url],'plan_sub_qywx_session_file',true);
                        continue;
                    }else{
                        fdump_api(['url'=>$url,'info'=>$info],'plan_sub_qywx_session_file',true);
                        $msg = shell_exec("wget '".$url."'  -O '/dev/null'");
                    }

                }
            }

        }
        return true;
    }
}
