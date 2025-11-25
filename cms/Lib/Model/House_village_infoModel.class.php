<?php
class House_village_infoModel extends Model{
    /**
     * 获取小区相关信息记录
     * User: zhanghan
     * Date: 2022/1/17
     * Time: 14:05
     * @param $where
     * @param bool $field
     * @return array|false|mixed|null
     */
    public function getVillageInfo($where,$field = true){
        return $this->field($field)->where($where)->find();
    }

    /**
     * 获取寻求帮助数据
     * @author: liukezhu
     * @date : 2022/3/25
     * @param $village_id
     * @return array
     */
    public function getHelpInfo($village_id){
        $village_info=$this->getVillageInfo(['village_id'=>$village_id],'help_info');
        if($village_info && $village_info['help_info']){
            $info=unserialize($village_info['help_info']);
        }else{
            $info=[
                'is_close' => 0,
                'type'      =>  [1,2],
                'phone'     =>  ['',''],
                'start'     =>  ['08:00','18:00'],
                'end'       =>  ['18:00','08:00'],
            ];
        }
//        $info=[
//            'type'      =>  [3,3],
//            'custom'    =>  ['啧啧啧','啊啊啊'],
//            'phone'     =>  ['111','222'],
//            'start'     =>  ['08:00','18:00'],
//            'end'       =>  ['18:00','08:00'],
//        ];
        $data=[];
        for ($x=0; $x<2; $x++) {
            foreach ($info as $k=>$v){
                if(isset($v[$x])){
                    $data[$x][$k]=$v[$x];
                }
            }
        }
        return $data;
    }

    //todo 寻求帮助
    public function getAskHelp($config,$village_id){
        $village_info=$this->getVillageInfo(['village_id'=>$village_id],'help_info');
        if(empty($village_info) || empty($village_info['help_info'])){
            return ['error'=>false,'data'=>'暂无联系电话，请跟工作人员联系！'];
        }
        $info=unserialize($village_info['help_info']);
        $data['title']='寻求帮助';
        $color=[
            $config['site_url'].'/static/images/house/ask_help/tel_bg_1.png',
            $config['site_url'].'/static/images/house/ask_help/tel_bg_2.png',
        ];
        $is_close='0';
        if(isset($info['is_close'])){
            $is_close=$info['is_close'];
        }
        for ($x=0; $x<2; $x++){
            $title='';
            if(isset($info['type'][$x])){
                if($info['type'][$x] == '1'){
                    $title='白班电话';
                }elseif ($info['type'][$x] == '2'){
                    $title='晚班电话';
                }elseif ($info['type'][$x] == '3'){
                    $title=isset($info['custom'][$x]) ? $info['custom'][$x] : '';
                }
            }
            if(empty($title)){
                continue;
            }
            $data['is_close']=$is_close;
            $phone=isset($info['phone'][$x]) ? $info['phone'][$x] : '';
            if($is_close==1 || $is_close=='1'){
                $phone='';
            }
            $data['info'][]=[
                'background'=>$color[$x],
                'phone'=>$phone,
                'title'=>$title
            ];
        }
        return ['error'=>true,'data'=>$data];
    }
}
?>