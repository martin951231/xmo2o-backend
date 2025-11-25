<?php
/*
 * 企业付款日志
 * */
class CompanypayLogModel extends Model
{
    public function addLog($data,$result)
    {
        $data['admin_ip'] = get_client_ip();
        $data['create_time'] = time();
        $data['post_data'] = json_encode($result['post_data']);
        $data['result'] = json_encode($result['result']);
        $this->add($data);
    }
}
?>