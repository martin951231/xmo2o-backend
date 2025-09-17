<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/1/22 10:01
 * 小区企业微信相关
 */
class qyWeixinAction extends BaseAction{

    public function services_housekeeper() {
        $village_id = $this->house_session['village_id'];
        $property_id = $this->house_session['property_id'];
        // 查询小区物业服务管家信息
        $house_services_housekeeper = M('House_services_housekeeper');
        $house_worker = M('House_worker');
        if (IS_POST) {
            $housekeeper_id = I('housekeeper_id',0,'intval');
            if ($housekeeper_id) {
                $where_services_housekeeper = [
                    'housekeeper_id' => $housekeeper_id,
                ];
                $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
                if (!$services_housekeeper){
                    $this->error('对应操作对象不存在！');
                }
            }
            $data[] = '';
            $data['village_id'] = $village_id;
            $data['property_id'] = $property_id;
            if ($_POST['work_arr']) {
                $data['work_arr'] = implode(',',$_POST['work_arr']);
            } else {
                $this->error('请选择对应要绑定的员工！');
            }
            if ($_POST['welcome_tip']) {
                $data['welcome_tip'] = $_POST['welcome_tip'];
            }
            if ($_POST['qy_qrcode']) {
                $data['qy_qrcode'] = $_POST['qy_qrcode'];
            }
            if (isset($_POST['template_type'])) {
                $data['template_type'] = intval($_POST['template_type']);
            }
            if ($_POST['template_url']) {
                $data['template_url'] = $_POST['template_url'];
            }
            if ($_POST['effect_img']) {
                $data['effect_img'] = $_POST['effect_img'];
            }
            if ($housekeeper_id) {
                $data['last_time'] = time();
                $set = $house_services_housekeeper->where($where_services_housekeeper)->data($data)->save();
            } else {
                $data['add_time'] = time();
                $set = $house_services_housekeeper->data($data)->add();
            }
            if ($set) {
                $this->success('操作成功');exit;
            } else {
                $this->success('操作失败');exit;
            }
        }

        $where_services_housekeeper = [
            'village_id' => $village_id,
            'single_id' => 0,
            'floor_id' => 0,
        ];
        $services_housekeeper = $house_services_housekeeper->where($where_services_housekeeper)->find();
        if ($services_housekeeper && $services_housekeeper['work_arr']) {
            $work_arr = explode(',',$services_housekeeper['work_arr']);
            if ($work_arr) {
                $where_workers = [
                    'wid' => ['in', $work_arr],
                    'status' => 1,
                    'is_del'=>0
                ];
                $work_arr_info = $house_worker->where($where_workers)->field('wid,name')->select();
                $this->assign('work_arr_info', $work_arr_info);
            }
        } else {
            $work_arr = [];
        }
        $this->assign('work_arr', $work_arr);
        $this->assign('detail', $services_housekeeper);
        $qyhelp_url = $this->config['site_url'].'/static/file/village/information/qyhelp.pdf';
        $this->assign('qyhelp_url', $qyhelp_url);

        // 查询该小区小所有工作人员
        $where_worker = [
            'village_id' => $village_id,
            'qy_status' => 1,
            'qy_id' => ['neq', ''],
            'status' => 1,
            'is_del'=>0
        ];
        $worker_list = $house_worker->where($where_worker)->order('wid ASC')->select();
        if (!$worker_list) {
            $worker_list = [];
        }
        $this->assign('worker_list', $worker_list);
        $this->display();
    }

    public $font_url = './static/fonts/apple_lihei_bold.otf';

    /**
     * Notes: 实时获取预览图
     * @author: wanzy
     * @date_time: 2021/1/27 13:49
     */
    public function services_look_img() {
        $village_id = $this->house_session['village_id'];
        // 模板类型 0 无需模板 1系统模板 2上传模板
        $template_type = I('template_type',0,'intval');
        // 模板链接
        $template_url = I('template_url','','strval');
        // 企业微信群二维码链接
        $qy_qrcode = I('qy_qrcode','','strval');
        if (!$qy_qrcode) {
            exit(json_encode(array('error'=>1,'msg'=>'缺少企业微信群二维码链接')));
        }
        $qy_qrcode = replace_file_domain($qy_qrcode);
        if (2==$template_type && !$template_url) {
            exit(json_encode(array('error'=>1,'msg'=>'缺少对应模板链接')));
        }
        if ($template_url) {
            $template_url = replace_file_domain($template_url);
        }
        if (0==$template_type) {
            exit(json_encode(array('error'=>1,'msg'=>'无需模板')));
        } elseif (1==$template_type) {
            $house_session = $this->house_session;
            $village_name = $house_session['village_name'];
            if (mb_strlen($village_name)<=10) {
                $background = $this->config['site_url'] . '/tpl/House/default/static/images/qyWeixin/qyweixin1.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            } elseif (mb_strlen($village_name)<=16) {
                $background = $this->config['site_url'] . '/tpl/House/default/static/images/qyWeixin/qyweixin2.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            } else {
                $background = $this->config['site_url'] . '/tpl/House/default/static/images/qyWeixin/qyweixin3.png';//海报最底层得背景
                $village_name = '- '.$village_name.' -';
            }
            $bbox = imagettfbbox(18, 0, $this->font_url, $village_name);
            $qy_qrcode_width = 240;
        } elseif (2==$template_type)  {
            $background = $template_url;//海报最底层得背景
            $qy_qrcode_width = 100;
        }

        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
        $qy_qrcode = thumb($qy_qrcode,$qy_qrcode_width,$qy_qrcode_width);

        //填充背景
        $info = getimagesize($qy_qrcode);
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $res = $function($qy_qrcode);
        // 位置
        if (1==$template_type) {
            $qr_position = array(
                'left'=>(($backgroundWidth-$qy_qrcode_width)/2),
                'top'=>442,
                'right'=>0,
                'bottom'=>0,
                'width'=>$qy_qrcode_width,
                'height'=>$qy_qrcode_width,
                'opacity'=>100
            );
            $text = array (
                'village_name' => array (
                    'text' => $village_name,
                    'left'=> ($backgroundWidth/2)-($bbox[2]/2),
                    'top'=> 340,
                    'right'=>0,
                    'bottom'=>0,
                    'fontSize'=>18,
                    'fontColor'=>'49,37,214',
                )
            );
        } else {
            $qr_position = array(
                'left'=>20,
                'top'=> $backgroundHeight-120,
                'right'=>0,
                'bottom'=>0,
                'width'=>100,
                'height'=>100,
                'opacity'=>100
            );
            $text = [];
        }
        $resWidth = $qy_qrcode_width;
        $resHeight = $qy_qrcode_width;

        $canvas = imagecreatetruecolor($qr_position['width'], $qr_position['height']);
//        $c = imagecolorallocate($canvas, 0, 0, 0);
//        $color = imagecolortransparent($canvas,$c);

//        imagefill($canvas, 0, 0, $color);

        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0,$qr_position['width'], $qr_position['height'],$resWidth,$resHeight);
        //放置图像

        imagecopymerge($imageRes,$canvas, $qr_position['left'],$qr_position['top'],$qr_position['right'],$qr_position['bottom'],$qr_position['width'],$qr_position['height'],$qr_position['opacity']);//左，上，右，下，宽度，高度，透明度

        if ($text) {
            foreach ($text as $val) {
                $fontWidth = intval($val['fontSize']);//获取文字宽度
                $val['fontPath'] =$this->font_url;
                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];

                imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);
            }
        }

        if(!file_exists('./upload/qyWeixinImg')){
            mkdir('./upload/qyWeixinImg',0777,true);
        }
        $file = '/upload/qyWeixinImg/services_look_img_'.$village_id.'_'.$template_type.'_'.time().'.jpg';
        $filename = '.'.$file;

        imagejpeg ($imageRes,$filename,90); //保存到本地
        imagedestroy($imageRes);

        //判断是否需要上传至云存储
        $file_handle = new file_handle();
        $file_handle->upload($filename);
        $file_path = replace_file_domain($file);
        exit(json_encode(array('error'=>0,'data'=>['file_path'=>$file_path,'file' => $file])));
    }
}