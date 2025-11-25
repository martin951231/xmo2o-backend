<?php
/**
 * 智能充电桩
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/6/18 17:30
 */
class HouseSmartAction extends BaseAction{

    /**
     * Notes: 针对智能充电桩的页面跳转处理
     * User: wanzy
     * DateTime: 2020/6/18 17:34
     */
    public function index() {
        $id = I('id');
        if(!$id){
            $this->error_tips('非法访问');
        }
        // 数据重定向
        $database_house_smart_charging  = D('House_smart_charging');
        $info = $database_house_smart_charging->where(['id'=>$id])->find();
        if (!$info) {
            $this->error_tips('访问对象不存在');
        }
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'AIcharge',
            'isAll' => true,
            'urlParam' => 'id='.$id,
        ];
        $AIchargeUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
        redirect($AIchargeUrl);
    }
}