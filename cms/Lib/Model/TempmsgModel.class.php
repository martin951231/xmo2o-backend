<?php
/**
 * 微信公众号模板消息
 */
class TempmsgModel extends Model
{
    /**
     * 发送模板消息，供v20系统调用
     */
    public function sendTempMsg($tempKey, $dataArr, $mer_id = 0,$property_id = 0,$type=0)
    {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $model->sendTempMsg($tempKey, $dataArr, $mer_id, $property_id, $type);
    }
	
}
?>