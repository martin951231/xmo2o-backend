<?php

class DeviceCustomizeAction extends BaseAction {

    public $deviceJdk='';
    public function __construct(){
        parent::__construct();

        import('@.ORG.DeviceCustomize');
        $this->deviceJdk =  new DeviceCustomize();

    }

    public function test(){
        $data=array(
            array(
                'guid'=>1,
                'url'=>'http://o2o-static.pigcms.com/upload/config/000/000/001/5df1b12e98f33264.jpg',
                'name'=>'测试0709',
                'endTime'=>'2021-09-09 08:56:10'
            )

        );
//        $token=$this->deviceJdk->queryDevice('84E0F424AACD06B2');
        $token=$this->deviceJdk->addPerson('84E0F424AACD06B2',$data);
        var_export($token);
    }


}
