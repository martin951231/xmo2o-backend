<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/6/18 15:35
 */
class HouseSmartAction extends BaseAction{
    /**
     * Notes: 获取对应充电桩二维码信息
     * User: wanzy
     * DateTime: 2020/6/18 15:43
     */
    public function smartChargeInfo() {
        $id = I('id');
        if (!$id) {
            $this->returnCodeError('请上传对应参数');
        }
        $database_house_smart_charging  = D('House_smart_charging');
        $info = $database_house_smart_charging->where(['id'=>$id])->find();
        if(!$info) {
            $this->returnCodeError('获取对象不存在！');
        }
        $res =[
            'info' => $info
        ];
        $this->returnCode(0,$res);
    }
}