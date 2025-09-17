<?php
/*
 * 渠道二维码
 *
 */
class RecognitionAction extends BaseAction{
	public function see_qrcode($type,$id){
		//判断ID是否正确，如果正确且以前生成过二维码则得到ID
		if($type == 'group'){
			$pigcms_return = D('Group')->get_qrcode($id);
		}elseif($type == 'merchant'){
			$pigcms_return = D('Merchant')->get_qrcode($id);
		}elseif($type == 'merchantstore'){
			$pigcms_return = D('Recognition')->get_qrcode_byThirdid($type,$id);
		}elseif($type == 'meal'){
			$pigcms_return = D('Merchant_store')->get_qrcode($id);
		}elseif($type == 'shop' || $type == 'mall'){
			$pigcms_return = D('Merchant_store_shop')->get_qrcode($id,$type);
		}elseif($type == 'lottery'){
			$pigcms_return = D('Lottery')->get_qrcode($id);
		}elseif($type == 'appoint'){
			$pigcms_return = D('Appoint')->get_qrcode($id);
		}elseif($type == 'appoint_category'){
			$pigcms_return = D('Appoint_category')->get_qrcode($id);
		}elseif($type == 'wifi'){
			$pigcms_return = D('Recognition')->get_wifi_qrcode($id);
		}elseif($type == 'waimai'){
			$pigcms_return = D('Waimai_store')->get_qrcode($id);
		}elseif($type == 'chanel'){
			$pigcms_return = D('Chanel_msg_list')->get_qrcode($id);
		}elseif($type == 'house'){
			$pigcms_return = D('House_village')->get_qrcode($id);
		}elseif($type == 'house_new'){
			$pigcms_return = D('House_village')->get_qrcode($id);
		}elseif($type == 'coupon'){
			$pigcms_return = D('System_coupon')->get_qrcode($id);
		}elseif($type == 'card_coupon'){
			$pigcms_return = D('Card_new_coupon')->get_qrcode($id);
		}elseif($type == 'gift'){
			$pigcms_return = D('Gift')->get_qrcode($id);
		}elseif($type == 'scan_get_score'){
			$pigcms_return = D('Scan_get_score_list')->get_qrcode($id);
		}else if($type == 'qiyeStaff'){
            //企业用户绑定平台用户
            $pigcms_return = M('Qiye_staff')->where(['staff_id'=>$id])->find();
        }else if($type == 'fixed_point_delive'){
            //外卖定点配送
            $pigcms_return = M('Shop_fixed_point_room')->where(['room_id'=>$id])->find();
		}else{
			exit('您查看的内容非法！无法查看二维码！');
		}

		if(empty($pigcms_return) && $type == 'waimai'){
			exit('请您完善店铺设置信息！');
		}elseif(empty($pigcms_return)){
			exit('您查看的内容不存在！无法查看二维码！');
		}


		if(empty($pigcms_return['qrcode_id'])){
			$qrcode_return = D('Recognition')->get_new_qrcode($type,$id);
		}elseif($_GET['gid']>0){
			$qrcode_return = D('Recognition')->get_tmp_qrcode(600000000+$_GET['gid']);
			redirect($qrcode_return['ticket']);
		}else{
			$qrcode_return = D('Recognition')->get_qrcode($pigcms_return['qrcode_id']);
		}

		if($qrcode_return['error_code']){
			exit($qrcode_return['msg']);
		}else if($qrcode_return['qrcode'] == 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='){
			$qrcode_return = D('Recognition')->get_new_qrcode($type,$id);
		}

		if($_GET['img']){
			if($type == 'scan_get_score'){
				echo '<html><head><style>*{margin:0;padding:0;}</style></head><body style="position:relative;width:100%;height:100%;overflow:hidden;"><div style="position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);text-align:center;"><img src="'.$qrcode_return['qrcode'].'"/><p style="font-size:30px;">长按识别二维码 关注公众号</p></div></body></html>';
			}else{
				echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img src="'.$qrcode_return['qrcode'].'"/></body></html>';
			}
		}else{
			redirect($qrcode_return['qrcode']);
		}
	}
	public function see_login_qrcode(){
		$qrcode_return = D('Recognition')->get_login_qrcode();
		if($_GET['content_type'] == 'json'){
			if($qrcode_return['error_code']){
				$this->error($qrcode_return['msg']);
			}else{
				$this->success($qrcode_return);
			}
			die;
		}
		if($qrcode_return['error_code']){
			echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
		}else{
			$this->assign($qrcode_return);
			$this->display();
		}
	}
	public function see_admin_qrcode(){
		$qrcode_return = D('Recognition')->get_admin_qrcode();
		if($qrcode_return['error_code']){
			echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
		}else{
			$this->assign($qrcode_return);
			$this->display();
		}
	}
	public function see_tmp_qrcode(){
        $qrcode_id = $_GET['qrcode_id'];
        $village_id = $_GET['village_id'];

        if($village_id){
            $property_id = D('House_village')->where(['village_id'=>$village_id])->getField('property_id');
            $weixin_bind = D('Weixin_bind')->field(true)->where(array('mer_id' => $property_id, 'type' => 2))->find();
            if($weixin_bind){
                $qrcode_return = D('Weixin_bind')->get_tmp_qrcode($qrcode_id,$village_id);
                if($qrcode_return['error_code']){
                    echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
                }else{
                    $this->assign($qrcode_return);
                    $this->display();
                }
                exit();
            }
        }


        //走平台的
        $qrcode_return = D('Recognition')->get_tmp_qrcode($qrcode_id);
        if($qrcode_return['error_code']){
            echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
        }else{
            $this->assign($qrcode_return);
            $this->display();
        }

	}

    /**
     * 平台公众号二维码
     */
	public function get_tmp_qrcode(){
		$qrcode_return = D('Recognition')->get_tmp_qrcode($_GET['qrcode_id']);
		if($qrcode_return['error_code']){
			exit($qrcode_return['msg']);
		}else{
			redirect($qrcode_return['ticket']);
		}
	}

    /**
     * 物业公众号二维码
     */
	public function get_weixin_qrcode()
    {
        $qrcode_id = $_GET['qrcode_id'];
        $village_id = $_GET['village_id'];
        $qrcode_return = D('Weixin_bind')->get_tmp_qrcode($qrcode_id,$village_id);
        if($qrcode_return['error_code']){
            exit($qrcode_return['msg']);
        }else{
            redirect($qrcode_return['ticket']);
        }
    }
	
	public function get_own_qrcode_html(){
		$img_width = $_GET['img_width'] ? $_GET['img_width'] : 308;
		$img_height = $_GET['img_height'] ? $_GET['img_height'] : 308;
		$img_url = U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($_GET['qrCon'])),'size'=>$_GET['size'],'down'=>$_GET['down']));
		echo '<html><head></head><body style="padding:0;margin:0;"><img src="'.$img_url.'" style="width:'.$img_width.'px;height:'.$img_height.'px"></body></html>';
	}
	
	public function get_own_qrcode(){
        header('Content-Type: image/png');
        ob_clean();
		$qrCon = $_GET['qrCon'];
		import('@.ORG.phpqrcode');
		$size = $_GET['size'] ? $_GET['size']: 10;
		$is_down = $_GET['down'] ? true: false;
		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1,$is_down);
	}
}
