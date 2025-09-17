<?php

/**
 * 微信模板消息
 */

namespace app\common\model\service\weixin;
class TemplateNewsService
{

    /**
     * 发送模板消息
     * @param string $tempKey 模板ID
     * @param array $dataArr 参数
     * @param int $mer_id 商家ID
     * @return bool
     * @author 张涛
     * @date 2020/06/22
     */
    public function sendTempMsg($tempKey, array $dataArr, $merId = 0, $propertyId = 0, $type=0)
    {
        $param = [
            'tempKey' => $tempKey,
            'dataArr' => $dataArr,
            'mer_id' => $merId,
            'property_id' => $propertyId,
            'type' => $type,
        ];
        $res = invoke_cms_model('Tempmsg/sendTempMsg', $param);
        fdump_api([$param,$res],'sendTempMsg',1);
        return true;
    }
}
