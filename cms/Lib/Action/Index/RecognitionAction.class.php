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
        $img_url = U('get_own_qrcode',array('qrCon'=>urlencode(htmlspecialchars_decode($_GET['qrCon'])),'size'=>$_GET['size'],'down'=>$_GET['down'],'name'=>$info['name'],'category_name'=>$info['category_name']));

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
        // --- 【配置项：可直接修改】 ---
        $font_path = $_SERVER['DOCUMENT_ROOT'] . '/tpl/Static/fonts/simhei.ttf';
        $font_size = 20;  // 下方文字（name）字体大小
        $font_size2 = 30; // 顶部文字（category_name）大号字体
        $qr_margin = 15;  // 二维码与上下文字的间距（适配大号字体，适当加大）
        $top_text_padding = 15; // 顶部文字与画布顶端的内边距（保证30px字体完整显示）
        // ------------------------------------

        // 1. 接收并过滤GET参数，避免恶意值和空值
        $text_to_add = isset($_GET['name']) ? trim(urldecode($_GET['name'])) : '默认名称';
        $category_name = isset($_GET['category_name']) ? trim(urldecode($_GET['category_name'])) : '默认分类';
        $qrCon = isset($_GET['qrCon']) ? urldecode($_GET['qrCon']) : '';
        $size = isset($_GET['size']) ? intval($_GET['size']) : 10;
        $is_down = isset($_GET['down']) ? boolval($_GET['down']) : false;

        // 2. 验证二维码内容非空
        if (empty($qrCon)) {
            header('Content-Type: text/plain; charset=utf-8');
            exit('错误：二维码内容不能为空');
        }

        // 3. 字体路径检查（必须确保文件存在且可读取）
        if (!file_exists($font_path) || !is_readable($font_path)) {
            header('Content-Type: text/plain; charset=utf-8');
            exit('致命错误：字体文件无法访问。当前路径: ' . $font_path);
        }

        // 4. 生成临时文件存储二维码（修复ob_start强制输出问题）
        $temp_filename = tempnam(sys_get_temp_dir(), 'qr_');
        if ($temp_filename === false) {
            header('Content-Type: text/plain; charset=utf-8');
            exit('无法创建临时文件，请检查系统临时目录权限');
        }

        // 5. 生成二维码并写入临时文件（静默丢弃多余输出）
        ob_start();
        import('@.ORG.phpqrcode');
        QRcode::png(htmlspecialchars_decode($qrCon), $temp_filename, 0, $size, 1, false);
        ob_end_clean();

        // 6. 从临时文件创建二维码图像资源
        $qr_image = imagecreatefrompng($temp_filename);
        if (!$qr_image) {
            unlink($temp_filename); // 清理无效临时文件
            header('Content-Type: text/plain; charset=utf-8');
            exit('GD库无法从临时文件创建二维码图像资源');
        }
        unlink($temp_filename); // 二维码资源创建成功后，删除临时文件

        // 7. 获取二维码尺寸，重新计算最终画布尺寸（适配顶部大号字体）
        $qr_width = imagesx($qr_image);
        $qr_height = imagesy($qr_image);
        // 画布高度：顶部预留（大号字体+内边距）+ 二维码高度 + 下方文字区域 + 底部间距
        $top_reserve = $font_size2 + $top_text_padding; // 顶部预留空间（适配30px字体）
        $bottom_reserve = 60; // 下方文字预留空间
        $new_width = $qr_width;
        $new_height = $top_reserve + $qr_height + $bottom_reserve;

        // 8. 创建最终画布并填充白色背景（【关键】先填充背景，再绘制其他内容）
        $final_image = imagecreatetruecolor($new_width, $new_height);
        if (!$final_image) {
            imagedestroy($qr_image);
            exit('无法创建最终画布图像资源');
        }
        // 分配颜色（关联当前画布，避免颜色无效）
        $white = imagecolorallocate($final_image, 255, 255, 255);
        $black = imagecolorallocate($final_image, 0, 0, 0);
        // 填充白色背景（先填充，后续绘制的内容不会被覆盖）
        imagefill($final_image, 0, 0, $white);

        // 9. 复制二维码到最终画布（下移至顶部文字下方，避免重叠）
        $qr_dst_y = $top_reserve + 10; // 二维码绘制在顶部预留空间下方，与顶部文字保持间距
        imagecopy($final_image, $qr_image, 0, $qr_dst_y, 0, 0, $qr_width, $qr_height);

        // 10. 计算两段文字的坐标（重点适配顶部大号字体居中）
        // 10.1 下方文字（$text_to_add：二维码下方，20px字体）
        $text1_box = imagettfbbox($font_size, 0, $font_path, $text_to_add);
        $text1_width = $text1_box[2] - $text1_box[0];
        $text1_x = ($new_width - $text1_width) / 2; // 水平居中
        // 垂直坐标：二维码下方+间距+字体大小，确保完整显示
        $text1_y = $qr_dst_y + $qr_height + $qr_margin + $font_size;

        // 10.2 顶部文字（$category_name：画布最上方，30px大号字体）
        // 单独基于font_size2计算文字边界，保证居中准确
        $text2_box = imagettfbbox($font_size2, 0, $font_path, $category_name);
        $text2_width = $text2_box[2] - $text2_box[0];
        $text2_x = ($new_width - $text2_width) / 2; // 水平居中（适配30px字体宽度）
        // 垂直坐标：基线位置=顶部内边距+字体大小，确保文字完整不截断（关键适配30px字体）
        $text2_y = $top_text_padding + $font_size2;

        // 11. 绘制两段文字（先绘下方，再绘顶部，不影响覆盖，视觉更清晰）
        // 绘制下方文字（$text_to_add：二维码下方，20px字体）
        imagettftext(
            $final_image,
            $font_size,
            0,
            $text1_x,
            $text1_y,
            $black,
            $font_path,
            $text_to_add
        );

        // 绘制顶部文字（$category_name：画布最上方，30px大号字体）
        imagettftext(
            $final_image,
            $font_size2,
            0,
            $text2_x,
            $text2_y,
            $black,
            $font_path,
            $category_name
        );

        // 12. 最终输出图像（下载或直接展示，修复filesize错误）
        if ($is_down) {
            // 下载逻辑：先保存到临时文件，再获取文件大小
            $download_filename = 'qrcode_with_text_' . date('YmdHis') . '.png';
            $temp_output = tempnam(sys_get_temp_dir(), 'qr_output_');
            imagepng($final_image, $temp_output);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $download_filename . '"');
            header('Content-Length: ' . filesize($temp_output));

            // 输出文件内容并清理临时文件
            readfile($temp_output);
            unlink($temp_output);
        } else {
            // 直接展示逻辑
            header('Content-Type: image/png');
            imagepng($final_image);
        }

        // 输出图像并释放所有内存资源
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
