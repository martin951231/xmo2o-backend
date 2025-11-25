<?php

namespace app\community\model\service;

use app\community\model\db\SkycockpitInfo;
use app\community\model\service\AreaService;
use app\community\model\service\ConfigDataService;
use app\community\model\service\AockpitService;
use app\community\model\db\HouseCameraTip;
use app\community\model\db\HouseVillageNews;
use app\community\model\db\HouseCameraDevice;

class SkyCockpitInfoService
{

    public function getOneData($where,$field = true)
    {
        $skyCockpitInfo = new SkycockpitInfo();
        $infoObj = $skyCockpitInfo->getOne($where, $field);
        $user_name='超级管理员';
        $cockpitInfo = array('baseinfo' => array('xtype' => 0, 'screentitle' => '启天数据驾驶舱 ', 'bg_img' => '','user_name'=>$user_name,'weatherInfo'=>array('is_weather'=>0)));
        $cockpitInfo['info1data'] = array('xtype' => 1, 'navtitle' => '社区概况', 'nav_list' => array());
        $cockpitInfo['info2data'] = array('xtype' => 2, 'navtitle' => '社区安防', 'nav_list' => array(), 'tj_title' => '社区人口分布', 'tj_list' => array(),'links'=>array());
        $cockpitInfo['info3data'] = array('xtype' => 3, 'navtitle' => '社区消防', 'nav_list' => array(), 'tj_title' => '趋势图', 'chart_title' => array(), 'chart_x' => array(), 'chart_y' => array(),'colorArr'=>array());
        $cockpitInfo['info4data'] = array('xtype' => 4, 'navtitle' => '健康养老', 'nav_list' => array(), 'tj_title' => '社区老人服务优化比', 'chart_title' => array(), 'tj_list' => array(),'chart_x' => array(), 'chart_y' => array());
        $cockpitInfo['info5data'] = array('xtype' => 5, 'navtitle' => '社区环境', 'nav_list' => array(), 'tj_title' => '环境监测分类', 'tj_list' => array());
        $cockpitInfo['info6data'] = array('xtype' => 6, 'navtitle' => '事件列表', 'nav_list' => array(), 'tj_list' => array(),'nav_left'=>array('title'=>'智慧安防 ','value'=>'23456'));
        $cockpitInfo['info7data'] = array('xtype' => 7, 'navtitle' => '物联感知设备', 'nav_list' => array());
        $cockpitInfo['info8data'] = array('xtype' => 8, 'navtitle' => '视频监控', 'nav_list' => array(), 'v_list' => array(), 'faceimg_list' => array());
        $cockpitInfo['info9data'] = array('xtype' => 9, 'navtitle' => '社区公告', 'nav_list' => array());
        $cockpitInfo['bottomimg'] = array(
            array('title'=>'总览','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_1.png'),
            array('title'=>'智慧安防','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_2.png'),
            array('title'=>'智慧消防','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_3.png'),
            array('title'=>'环境监测','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_4.png'),
            array('title'=>'智慧物业','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_5.png'),
            array('title'=>'智慧养老','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_6.png'),
            array('title'=>'智慧健康','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_7.png'),
            array('title'=>'AR实验','icon'=>'https://hf.pigcms.com/static/wxapp/cockpitScreen/nav_item_icon_8.png'),

        );
        if ($infoObj && !$infoObj->isEmpty()) {
            $info = $infoObj->toArray();
            $weatherInfo='';
            if ($info && $info['baseinfo']) {
                $baseinfo = json_decode($info['baseinfo'], 1);
                if ($baseinfo && $baseinfo['screentitle']) {
                    $cockpitInfo['baseinfo']['screentitle'] = $baseinfo['screentitle'];
                }
                if ($baseinfo && $baseinfo['bg_img']) {
                    $cockpitInfo['baseinfo']['bg_img'] = $this->handleImg($baseinfo['bg_img']);
                }
                if($baseinfo && $baseinfo['city_id']){
                    $weatherInfo=$this->getWeatherInfo($baseinfo['city_id']);
                    if($weatherInfo && isset($weatherInfo['info'])){
                        $weatherInfo['is_weather']=1;
                        $weatherInfo['temperature_str']=$weatherInfo['temperature'].'℃';
                        if($weatherInfo['max']!=='' && $weatherInfo['max']!==null){
                            $weatherInfo['temperature_str']=$weatherInfo['min'].' ~ '.$weatherInfo['max'].'℃';
                        }
                        $cockpitInfo['baseinfo']['weatherInfo']=$weatherInfo;
                    }

                }
            }
            if ($info && $info['info1data']) {
                $info1data = json_decode($info['info1data'], 1);
                if ($info1data && $info1data['navtitle']) {
                    $cockpitInfo['info1data']['navtitle'] = $info1data['navtitle'];
                }
                if ($info1data && $info1data['navinfo']) {
                    foreach ($info1data['navinfo'] as $nvv) {
                        if (!empty($nvv['xname'])) {
                            $percentage = '0%';
                            if ($nvv['xv2'] > 0) {
                                $percentage = $nvv['xv2'] . '%';
                            }
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1'], 'status' => 0, 'percentage' => $percentage, 'icon' => "https://hf.pigcms.com/static/wxapp/cockpitScreen/percent_icon.png");
                            $cockpitInfo['info1data']['nav_list'][] = $itemArr;
                        }
                    }
                }
            }
            if ($info && $info['info2data']) {
                $info2data = json_decode($info['info2data'], 1);
                if ($info2data && $info2data['navtitle']) {
                    $cockpitInfo['info2data']['navtitle'] = $info2data['navtitle'];
                }
                if ($info2data && $info2data['navinfo']) {
                    foreach ($info2data['navinfo'] as $nvv) {
                        if (!empty($nvv['xname'])) {
                            $color = '#1ABFF1';
                            if (!is_numeric($nvv['xv1']) && is_string($nvv['xv1'])) {
                                $color = '#1AF17A';
                            }
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1'], 'color' => $color);
                            $cockpitInfo['info2data']['nav_list'][] = $itemArr;
                        }
                    }
                }
                if ($info2data && $info2data['tjtitle']) {
                    $cockpitInfo['info2data']['tj_title'] = $info2data['tjtitle'];
                }
                $idd=1;
                $cockpitInfo['info2data']['tj_list'][]=array("name"=>"", "id"=>strval($idd), "percent"=>"","symbolSize"=>10, "symbol"=>'circle');
                if ($info2data && $info2data['tjinfo']) {
                    foreach ($info2data['tjinfo'] as $tvv) {
                        if (!empty($tvv['xname'])) {
                            $idd++;
                            $percentage = '0%';
                            if ($tvv['xv1'] > 0) {
                                $percentage = $tvv['xv1'] . '%';
                            }
                            $itemArr = array('name' => $tvv['xname'], "id"=>strval($idd), 'percent' => $percentage, "symbolSize"=>3, "symbol"=>'circle');
                            $cockpitInfo['info2data']['tj_list'][] = $itemArr;
                            $cockpitInfo['info2data']['links'][] = array("source"=>"1","target"=>strval($idd));
                        }
                    }
                }
            }

            if ($info && $info['info3data']) {
                $info3data = json_decode($info['info3data'], 1);
                if ($info3data && $info3data['navtitle']) {
                    $cockpitInfo['info3data']['navtitle'] = $info3data['navtitle'];
                }
                if ($info3data && $info3data['navinfo']) {
                    foreach ($info3data['navinfo'] as $nvv) {
                        if (!empty($nvv['xname'])) {
                            $color = '#1ABFF1';
                            if (!is_numeric($nvv['xv1']) && is_string($nvv['xv1'])) {
                                $color = '#1AF17A';
                            }
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1'], 'color' => $color);
                            $cockpitInfo['info3data']['nav_list'][] = $itemArr;
                        }
                    }
                }

                if ($info3data && $info3data['tjtitle']) {
                    $cockpitInfo['info3data']['tj_title'] = $info3data['tjtitle'];
                }
                $colorArr = ['#177EED','#1ABFF1', '#1AF17A', '#c0ff00', '#FF6633', '#ffc000', '#c0a0ff', '#0099FF'];
                $cockpitInfo['info3data']['colorArr'] = $colorArr;
                $cockpitInfo['info3data']['chart_x'] = $info3data['xmonth'];
                if ($info3data && $info3data['tjinfo']) {
                    $indexk=0;
                    foreach ($info3data['tjinfo'] as $kkk => $tvv) {
                        if (!empty($tvv['xname'])) {
                            $cockpitInfo['info3data']['chart_title'][] = $tvv['xname'];
                            $cockpitInfo['info3data']['chart_y'][$kkk] = array();
                            $itemArr=array('name'=>$tvv['xname'],'data'=>array(),'color'=>$colorArr[$indexk]);
                            $indexk++;
                            $xv=array();
                            if ($tvv['xv']) {
                                foreach ($tvv['xv'] as $cvv) {
                                    $xv[]=$cvv['xv1'];
                                }
                            }
                            $itemArr['data']=$xv;
                            $cockpitInfo['info3data']['chart_y'][$kkk] = $itemArr;
                        }
                    }

                }
            }


            if ($info && $info['info4data']) {
                $info4data = json_decode($info['info4data'], 1);
                if ($info4data && $info4data['navtitle']) {
                    $cockpitInfo['info4data']['navtitle'] = $info4data['navtitle'];
                }
                if ($info4data && $info4data['navinfo']) {
                    foreach ($info4data['navinfo'] as $nvv) {
                        if (!empty($nvv['xname'])) {
                            $color = '#1ABFF1';
                            if (!is_numeric($nvv['xv1']) && is_string($nvv['xv1'])) {
                                $color = '#1AF17A';
                            }
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1'], 'color' => $color);
                            $cockpitInfo['info4data']['nav_list'][] = $itemArr;
                        }
                    }
                }
                if ($info4data && $info4data['tjtitle']) {
                    $cockpitInfo['info4data']['tj_title'] = $info4data['tjtitle'];
                }
                $maxV = 0;
                $colorArr = ['#1ABFF1', '#1AF17A', '#c0ff00', '#FF6633', '#ffc000', '#c0a0ff', '#0099FF'];
                $colorRGBArr = ['rgba(26,191,241,0.5)', 'rgba(26,241,122,0.5)', 'rgba(192,255,0,0.5)', 'rgba(255,102,51,0.5)', 'rgba(255,192,0,0.5)', 'rgba(192,160,255,0.5)', 'rgba(0,153,255,0.5)'];
                if ($info4data && $info4data['tjinfo']) {
                    foreach ($info4data['tjinfo'] as $kkk => $tvv) {
                        if (!empty($tvv['xname'])) {
                            $cockpitInfo['info4data']['chart_title'][] = $tvv['xname'];
                            $cockpitInfo['info4data']['color_list'][$kkk] = $colorArr[$kkk];
                            $valueArr = array(0, 0, 0, 0, 0);
                            if ($tvv['xv']) {
                                foreach ($tvv['xv'] as $cvv) {
                                    if (isset($cvv['xv1'])) {
                                        $valueArr[0] = $cvv['xv1'] > 0 ? intval($cvv['xv1']) : 0;
                                        if ($valueArr[0] > $maxV) {
                                            $maxV = $valueArr[0];
                                        }
                                    }
                                    if (isset($cvv['xv2'])) {
                                        $valueArr[1] = $cvv['xv2'] > 0 ? intval($cvv['xv2']) : 0;
                                        if ($valueArr[1] > $maxV) {
                                            $maxV = $valueArr[1];
                                        }
                                    }
                                    if (isset($cvv['xv3'])) {
                                        $valueArr[2] = $cvv['xv3'] > 0 ? intval($cvv['xv3']) : 0;
                                        if ($valueArr[2] > $maxV) {
                                            $maxV = $valueArr[2];
                                        }
                                    }
                                    if (isset($cvv['xv4'])) {
                                        $valueArr[3] = $cvv['xv4'] > 0 ? intval($cvv['xv4']) : 0;
                                        if ($valueArr[3] > $maxV) {
                                            $maxV = $valueArr[3];
                                        }
                                    }
                                    if (isset($cvv['xv5'])) {
                                        $valueArr[4] = $cvv['xv5'] > 0 ? intval($cvv['xv5']) : 0;
                                        if ($valueArr[4] > $maxV) {
                                            $maxV = $valueArr[4];
                                        }
                                    }
                                }
                            }

                            $cockpitInfo['info4data']['chart_y'][$kkk] = array('value' => $valueArr, 'name' => $tvv['xname'], 'areaStyle' => array('color' => $colorRGBArr[$kkk]));
                        }
                    }
                }

                if ($info4data && $info4data['tjattr']) {
                    foreach ($info4data['tjattr'] as $avv) {
                        if (isset($avv['attr1'])) {
                            $cockpitInfo['info4data']['chart_x'][0] = array('name' => $avv['attr1'], 'max' => $maxV);
                        }
                        if (isset($avv['attr2'])) {
                            $cockpitInfo['info4data']['chart_x'][1] = array('name' => $avv['attr2'], 'max' => $maxV);
                        }
                        if (isset($avv['attr3'])) {
                            $cockpitInfo['info4data']['chart_x'][2] = array('name' => $avv['attr3'], 'max' => $maxV);
                        }
                        if (isset($avv['attr4'])) {
                            $cockpitInfo['info4data']['chart_x'][3] = array('name' => $avv['attr4'], 'max' => $maxV);
                        }
                        if (isset($avv['attr5'])) {
                            $cockpitInfo['info4data']['chart_x'][4] = array('name' => $avv['attr5'], 'max' => $maxV);
                        }
                    }
                }

            }

            if ($info && $info['info5data']) {
                $itemArr = array('title' => '空气', 'status' => '优', 'value' => 1, 'icon' => 'https://hf.pigcms.com/static/wxapp/cockpitScreen/air_quality_icon.png', 'changeIcon' => 'https://hf.pigcms.com/static/wxapp/cockpitScreen/air_quality_down.png');
                if($weatherInfo){
                    $itemArr['status']=$weatherInfo['quality'];
                    $itemArr['value']=$weatherInfo['level'];
                }
                $cockpitInfo['info5data']['nav_list'][] = $itemArr;
                $info5data = json_decode($info['info5data'], 1);
                if ($info5data && $info5data['navtitle']) {
                    $cockpitInfo['info5data']['navtitle'] = $info5data['navtitle'];
                }
                if ($info5data && $info5data['navinfo']) {
                    foreach ($info5data['navinfo'] as $nvv) {
                        if (!empty($nvv['xname'])) {
                            $itemArr = array('title' => $nvv['xname'], 'status' => $nvv['xv1'], 'value' => $nvv['xv2'], 'icon' => 'https://hf.pigcms.com/static/wxapp/cockpitScreen/noise_situation_icon.png', 'changeIcon' => 'https://hf.pigcms.com/static/wxapp/cockpitScreen/percent_icon.png');
                            $cockpitInfo['info5data']['nav_list'][] = $itemArr;
                        }
                    }
                }

                if ($info5data && $info5data['tjtitle']) {
                    $cockpitInfo['info5data']['tj_title'] = $info5data['tjtitle'];
                }
                $colorArr = ['#1ABFF1', '#1AF17A', '#c0ff00', '#FF6633', '#ffc000', '#c0a0ff', '#0099FF'];
                $info5data['colorArr'] = $colorArr;
                if ($info5data && $info5data['tjinfo']) {
                    foreach ($info5data['tjinfo'] as $kkk => $tvv) {
                        if (!empty($tvv['xname'])) {
                            $ivvx = $kkk % 2;
                            if ($ivvx == 0) {
                                $itemArr = array('name' => $tvv['xname'], 'value' => $tvv['xv1'] . '%', 'nameColor' => '#fff', 'startLinear' => 'rgba(3, 81, 230, 0)', 'endLinear' => 'rgba(3,81,230, 0.8)');
                            } else {
                                $itemArr = array('name' => $tvv['xname'], 'value' => $tvv['xv1'] . '%', 'nameColor' => '#7F9EBD', 'startLinear' => 'rgba(9,229,235, 0)', 'endLinear' => 'rgba(9,229,235, 0.8)');
                            }
                            $cockpitInfo['info5data']['tj_list'][] = $itemArr;
                        }
                    }
                }
            }

            if ($info && $info['info6data']) {
                $info6data = json_decode($info['info6data'], 1);
                if ($info6data && $info6data['navtitle']) {
                    $cockpitInfo['info6data']['navtitle'] = $info6data['navtitle'];
                }
                if ($info6data && $info6data['navinfo']) {
                    $colorArr = ['#C01C38', '#11A56A', '#C01C38', '#E5CC4C', '#C01C38', '#11A56A', '#C01C38', '#E5CC4C'];
                    foreach ($info6data['navinfo'] as $k6k => $nvv) {
                        $itemArr = array('title' => '', 'value' => 0);
                        if (!empty($nvv['xname'])) {
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1']);
                        }
                        if ($k6k <= 3) {
                            $cockpitInfo['info6data']['nav_list'][] = $itemArr;
                        } else {
                            $colorkey = $k6k - 3;
                            $itemArr['color'] = $colorArr[$colorkey];
                            $cockpitInfo['info6data']['tj_list'][] = $itemArr;
                        }

                    }
                }
                if ($info6data && $info6data['navleft']) {
                    if($info6data['navleft']['0']['xname']){
                        $cockpitInfo['info6data']['nav_left']['title']=$info6data['navleft']['0']['xname'];
                    }
                    if($info6data['navleft']['0']['xv1']){
                        $cockpitInfo['info6data']['nav_left']['value']=$info6data['navleft']['0']['xv1'];
                    }

                }
            }

            if ($info && $info['info7data']) {
                $info7data = json_decode($info['info7data'], 1);
                if ($info7data && $info7data['navtitle']) {
                    $cockpitInfo['info7data']['navtitle'] = $info7data['navtitle'];
                }
                if ($info7data && $info7data['navinfo']) {
                    $colorArr = ['#E5CC4C', '#E5CC4C', '#E5CC4C'];
                    $imgUrlArr = ['https://hf.pigcms.com/static/wxapp/cockpitScreen/blue_back.png', 'https://hf.pigcms.com/static/wxapp/cockpitScreen/red_back.png', 'https://hf.pigcms.com/static/wxapp/cockpitScreen/yellow_back.png'];
                    foreach ($info7data['navinfo'] as $k7k => $nvv) {
                        $itemArr = array('title' => '', 'value' => 0);
                        if (!empty($nvv['xname'])) {
                            $itemArr = array('title' => $nvv['xname'], 'value' => $nvv['xv1']);
                        }
                        $itemArr['color'] = $colorArr[$k7k];
                        $itemArr['imgurl'] = $imgUrlArr[$k7k];

                        $cockpitInfo['info7data']['nav_list'][] = $itemArr;
                    }
                }
            }
            $houseCameraDevice= new HouseCameraDevice();
            if ($info && $info['info8data']) {
                $info8data = json_decode($info['info8data'], 1);
                if ($info8data && $info8data['navtitle']) {
                    $cockpitInfo['info8data']['navtitle'] = $info8data['navtitle'];
                }
                if ($info8data && $info8data['navinfo']) {
                    $houseCameraTip=new HouseCameraTip();
                    $whereArr=array();
                    $whereArr[]=array('tip_id','>',0);
                    $whereArr[]=array('status','=',0);
                    $xCount = $houseCameraTip->getCount($whereArr);
                    if($xCount>0){

                    }else{
                        $xCount=0;
                    }
                    $cockpitInfo['info8data']['nav_list'][] = array('title' => '监控出现人头数', 'value' => $xCount);
                    foreach ($info8data['navinfo'] as $k8k => $nvv) {
                        $itemArr = array('title' => '', 'value' => '');
                        if (!empty($nvv['xname'])) {
                            $itemArr['title'] = $nvv['xname'];
                        }
                        if ($nvv['is_img'] == 1) {
                            $itemArr['camera_sn'] = $nvv['camera_sn'];
                            $whereArr=array();
                            $whereArr[]=array('camera_status','<>',4);
                            $whereArr[]=array('camera_sn','=',$nvv['camera_sn']);
                            $whereArr[]=array('look_url','<>',"");
                            $whereArr[]=array('lookUrlType','in',array('flv'));
                            $camera_device_obj=$houseCameraDevice->getOne($whereArr,'*');
                            $camera_device='';
                            if($camera_device_obj && !$camera_device_obj->isEmpty()){
                                $camera_device=$camera_device_obj->toArray();
                            }
                            $value = trim($nvv['xv1']);
                            $value = htmlspecialchars_decode($value,ENT_QUOTES);
                            $camera_name=trim($nvv['xv2']);
                            $camera_name= htmlspecialchars_decode($camera_name,ENT_QUOTES);
                            if($camera_device){
                                $value=$camera_device['look_url'];
                                $camera_name=$camera_device['camera_name'];
                            }
                            $itemArr['value'] = $value;
                            $itemArr['camera_name'] = $camera_name;
                            $itemArr['look_url_type'] = $nvv['look_url_type'];
                            if($value && empty($nvv['look_url_type'])){
                                if(strpos($value,'ws:')!==false){
                                    $itemArr['look_url_type']='ws';
                                }elseif(strpos($value,'wss:')!==false){
                                    $itemArr['look_url_type']='wss';
                                }elseif(strpos($value,'rtsp:')!==false){
                                    $itemArr['look_url_type']='rtsp';
                                }elseif(strpos($value,'rtmp:')!==false){
                                    $itemArr['look_url_type']='rtmp';
                                }elseif((strpos($value,'http:')!==false||strpos($value,'https:')!==false) && strpos($value,'.m3u8')!==false){
                                    $itemArr['look_url_type']='hls';
                                }elseif((strpos($value,'http:')!==false||strpos($value,'https:')!==false) && strpos($value,'.flv')!==false){
                                    $itemArr['look_url_type']='flv';
                                }

                            }
                            
                            $cockpitInfo['info8data']['v_list'][] = $itemArr;
                        } else {
                            $itemArr['value'] = !empty($nvv['xv1']) ? trim($nvv['xv1']) : '';
                            $cockpitInfo['info8data']['nav_list'][] = $itemArr;
                        }
                    }
                }
                if ($info8data && $info8data['faceimg']) {
                    foreach ($info8data['faceimg'] as $fimg) {
                        $xfimg = $this->handleImg($fimg);
                        $cockpitInfo['info8data']['faceimg_list'][] = $xfimg;
                    }
                }
            }
        }
        $houseVillageNews=new HouseVillageNews();
        $whereArr=array();
        $whereArr[]=array('news_id','>',0);
        $dataObj=$houseVillageNews->getLists($whereArr,'news_id,title,add_time,content,cat_id,village_id',1,5);
        if($dataObj && !$dataObj->isEmpty()){
            $itemList=$dataObj->toArray();
            if(!empty($itemList)){
                foreach ($itemList as $kk=>$vv){
                    $itemList[$kk]['content']=htmlspecialchars_decode($vv['content'],ENT_QUOTES);
                    $itemList[$kk]['icon']="https://hf.pigcms.com/static/wxapp/cockpitScreen/right_arr_single.png";
                    $itemList[$kk]['add_time_str']=date('Y/m/d H:i:s',$vv['add_time']);
                }
                $cockpitInfo['info9data']['nav_list']=$itemList;
            }
        }
        return $cockpitInfo;
    }


    public function handleImg($imgSrc = '')
    {
        if ($imgSrc) {
            if (strpos($imgSrc, '/upload/') !== false) {
                $imgSrc = replace_file_domain($imgSrc);
            } elseif (substr($imgSrc, 1, 6) == 'static') {
                $imgSrc = cfg('site_url') . $imgSrc;
            } elseif (substr($imgSrc, '0', '4') == '000/') {
                $imgSrc = file_domain() . '/upload/adver/' . $imgSrc;
            } else {
                $imgSrc = file_domain() . '/upload/service/' . $imgSrc;
            }
            $imgSrc = str_replace('//upload', '/upload', $imgSrc);
        }
        return $imgSrc;
    }

    public function getWeatherInfo($city_id=0){

        $service_area = new AreaService();
        $area_info = $service_area->getAreaOne(['area_id'=>$city_id],'area_name');

        if($area_info && !$area_info->isEmpty()){
            $area_name=$area_info['area_name'];
        }
        if(empty($area_name)){

        }
        $weather_info_key='weather_info_city_'.$city_id;
        $service_config_data = new ConfigDataService();
        $weather_info=$service_config_data->get_one(array('name'=>$weather_info_key));
        $weather_data=array();
        $data=array();
        if($weather_info && !empty($weather_info['value'])){
            $weather_data=json_decode($weather_info['value'],1);
            $data=$weather_data['weather'];
        }
        $nowtime=time();
        $expiretime=$nowtime-7200;
        $service_aockpit=new AockpitService();
        if($area_name && (empty($weather_data) || ($weather_data['expiretime']<$expiretime))){
            $data_tmp = $service_aockpit->weather($area_name);
            if(!empty($data_tmp)){
                $data=$data_tmp;
                $weather_info_tmp=array('expiretime'=>$nowtime,'weather'=>$data_tmp);
                $config_data=array('value'=>json_encode($weather_info_tmp,JSON_UNESCAPED_UNICODE));
                if(!empty($weather_info)){
                    $service_config_data->updateConfig(array('name'=>$weather_info_key),$config_data);
                }else{
                    $config_data['name']=$weather_info_key;
                    $service_config_data->addConfig($config_data);
                }
            }
        }
        return $data;
    }
}