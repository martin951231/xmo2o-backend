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

        $qrcode_return['qrcode'] = 'https://cloud.mrsqfw.com/upload/qrcode/xiaoming.png';

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
        // 关键修改：默认高度加上 30px 文字区域的高度
        $original_height = $_GET['img_height'] ? $_GET['img_height'] : 308;
        $img_height = $original_height + 30; // <-- 在这里加上你预留的文字高度！

        $info = $this->parseUrlParams(htmlspecialchars_decode($_GET['qrCon'])); // 解析 URL 中的查询参数

        // 注意：这里的 img_url 不需要变
        $img_url = U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($_GET['qrCon'])),'size'=>$_GET['size'],'down'=>$_GET['down'],'name'=>$info['name']));

        // HTML 使用新的高度
        echo '<html><head></head><body style="padding:0;margin:0;"><img src="'.$img_url.'" style="width:'.$img_width.'px;height:'.($img_height-30).'px"></body></html>';
    }
	
//	public function get_own_qrcode(){
//        header('Content-Type: image/png');
//        ob_clean();
//		$qrCon = $_GET['qrCon'];
//		import('@.ORG.phpqrcode');
//		$size = $_GET['size'] ? $_GET['size']: 10;
//		$is_down = $_GET['down'] ? true: false;
//		QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1,$is_down);
//	}
    public function get_own_qrcode(){
        // --- 【请在这里修改配置！！！】 ---

        // 🚨 1. 将这里的值替换为测试时成功的【精确字体路径】！
        $font_path = $_SERVER['DOCUMENT_ROOT'] . '/tpl/Static/fonts/simhei.ttf';
        // 2. 确保你的文字是 UTF-8 编码
        $text_to_add = $_GET['name'];
        // ------------------------------------

        $qrCon = $_GET['qrCon'];
        // 确保你的 ThinkPHP import 路径是正确的
        import('@.ORG.phpqrcode');
        $size = $_GET['size'] ? $_GET['size']: 10;
        $is_down = $_GET['down'] ? true: false;

        // --- 字体路径检查（必须确保该路径是测试成功的那个！） ---
        if (!file_exists($font_path) || !is_readable($font_path)) {
            header('Content-Type: text/plain; charset=utf-8');
            exit('致命错误：字体文件无法访问。请检查 $font_path 变量和文件权限是否与测试脚本一致。当前路径: ' . $font_path);
        }

        // 忽略下载逻辑（if ($is_down) { ... } 略去）

        // --- 图像处理：利用临时文件进行中转 (修复 ob_start 失败问题) ---

        $temp_filename = tempnam(sys_get_temp_dir(), 'qr_'); // 1. 生成临时文件路径
        if ($temp_filename === false) {
            header('Content-Type: text/plain');
            exit('无法创建临时文件，请检查系统临时目录权限。');
        }

        // 2. 将二维码输出写入到临时文件 (使用静默缓冲丢弃强制输出)
        ob_start();
        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)), $temp_filename, 0, $size, 1, false);
        ob_end_clean(); // 丢弃所有强制输出的数据

        // 3. 从临时文件中读取图像资源
        $qr_image = imagecreatefrompng($temp_filename);

        // 4. 删除临时文件
        unlink($temp_filename);

        if (!$qr_image) {
            header('Content-Type: text/plain');
            exit('GD库无法从临时文件创建图像资源。');
        }

        // --- GD 逻辑：创建画布并绘制文字 ---

        $qr_width = imagesx($qr_image);
        $qr_height = imagesy($qr_image);
        $new_width = $qr_width;
        $new_height = $qr_height + 60; // 使用常量高度

        $final_image = imagecreatetruecolor($new_width, $new_height);

        $white = imagecolorallocate($final_image, 255, 255, 255);
        $black = imagecolorallocate($final_image, 0, 0, 0);

        imagefill($final_image, 0, 0, $white);
        imagecopy($final_image, $qr_image, 0, 0, 0, 0, $qr_width, $qr_height);

        // ------------------- [ 绘制文字 (最安全的参数) ] -------------------

        // 计算文字边界和居中X坐标
        $text_box = imagettfbbox(20, 0, $font_path, $text_to_add);
        $text_width = $text_box[2] - $text_box[0];

        $x_coordinate = ($new_width - $text_width) / 2; // 居中X坐标

        // Y坐标：二维码底部 + 边距 (15px) + 字体大小 (确保文字完全在预留的 60px 空间内)
        $y_coordinate = $qr_height + 15 + 20;

        imagettftext(
            $final_image,
            20,
            0,
            $x_coordinate,
            $y_coordinate,
            $black,
            $font_path, // 最终确定的字体路径
            $text_to_add
        );

        // ------------------- [ 最终输出并清理 ] -------------------
        header('Content-Type: image/png');
        imagepng($final_image);

        imagedestroy($qr_image);
        imagedestroy($final_image);
    }
    /**
     * 解析 URL 中的查询参数
     * @param string $url 完整的 URL 地址
     * @return array 解析后的参数关联数组（自动处理中文解码）
     */
    public function parseUrlParams($url) {
        // 1. 分离 URL 中的查询字符串部分（? 后面的内容）
        $queryString = parse_url($url, PHP_URL_QUERY);

        if (empty($queryString)) {
            return []; // 无查询参数时返回空数组
        }

        // 2. 解析查询字符串为数组（自动处理 & 分隔）
        parse_str($queryString, $params);

        // 3. 对参数值进行 URL 解码（处理中文或特殊字符）
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }

        return $params;
    }
}
