<?php
/**
 * 云音箱
 * Author: hengtingmei
 * Date Time: 2021/01/06 18:28
 */

namespace app\merchant\model\service;
use app\common\model\service\plan\PlanMsgService;
use app\merchant\model\db\VoiceBox;
class VoiceBoxService {
    public $voiceBoxModel = null;
    public function __construct()
    {
        $this->voiceBoxModel = new VoiceBox();
    }

    /**
     * 获得当前所在城市区域信息
     * @return array
     */
    public function sendMsgToVoiceBox($storeId, $msg, $beginWithShortName = true)
    {
        if ($storeId < 1 || !$msg) {
            return;
        }

        $siteShortName = cfg('site_short_name') ? cfg('site_short_name') : '';
        $beginWithShortName && $msg = $siteShortName . $msg;

        $where = [
            ['store_id', '=', $storeId],
            ['imei', '<>', ''],
        ];
        $voiceBox = $this->getSome($where);
        if ($voiceBox) {
            foreach ($voiceBox as $value) {
                //只能填数字，不填写或填写0则不播放，最多6次
                $voiceSecond = min($value['voice_number'], 6);
                if(!$voiceSecond){
                    continue;
                }

                $voiceMsg = str_repeat($msg . '。', $voiceSecond);

                if ($value['brand'] == 2) {
                    \net\Http::curlPostOwnWithHeader(cfg('site_url') . '/source/mqtt_pub.php', 'POST', ['message' => $voiceMsg, 'imei' => $value['imei']]);
                }else{
                    (new PlanMsgService())->addTask(['type' => '6', 'content' => ['message' => $voiceMsg, 'imei' => $value['imei']]]);
                }
            }
        }
    }


    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where,$field = true ){
        if(empty($where)){
            return false;
        }

        $result = $this->voiceBoxModel->getOne($where,$field);
        if(empty($result)){
            return [];
        }

        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where){
        try {
            $result = $this->voiceBoxModel->getSome($where);
        } catch (\Exception $e) {
            return [];
        }
        return $result->toArray();
    }

    /**
     *插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $id = $this->voiceBoxModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->voiceBoxModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}