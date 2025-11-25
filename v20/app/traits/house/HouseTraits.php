<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      社区系统公用的一些方法或者参数，请勿携带具体业务
	 *            注意，为了避免重复名称污染，该文件的方法名统一使用 trait 开头
	 */

	namespace app\traits\house;

	use app\community\model\service\HouseVillageService;

    trait HouseTraits
	{

		/**
		 * 自动对 纯数字的 楼栋，附加 “栋”，比如传参数是“48”，然后会返回 “48(栋)”
		 * @param $name
		 * @param false $hastag 是否用标签包裹
		 *
		 * @return mixed|string
		 */
		public function traitAutoFixLouDongTips($name,$hastag = false,$village_id=0)
		{
			if ($hastag){
				return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . '('.((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'栋').')';
			}
			return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . ((new HouseVillageService())->getHouseAlias('single_name',$village_id)?:'栋');
		}

		/**
		 * 自动对 纯数字的 单元，附加 “单元”，比如传参数是“2”，然后会返回 “2(单元)”
		 * @param $name
		 * @param false $hastag 是否用标签包裹
		 *
		 * @return mixed|string
		 */
		public function traitAutoFixDanyuanTips($name,$hastag = false,$village_id=0)
		{
			if ($hastag){
				return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . '('.((new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元').')';
			}
			return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . ((new HouseVillageService())->getHouseAlias('floor_name',$village_id)?:'单元');
		}

		/**
		 * 自动对 纯数字的 楼层，附加 “层”，比如传参数是“2”，然后会返回 “2(层)”
		 * @param $name
		 * @param false $hastag 是否用标签包裹
		 *
		 * @return mixed|string
		 */
		public function traitAutoFixLoucengTips($name,$hastag = false)
		{
			if ($hastag){
				return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . '(层)';
			}
			return	preg_match('/[\x7f-\xff]/', $name) ? $name : $name . '层';
		}

		/**
		 * 根据小区id实时获取固定格式的企业微信群物料码预览图
		 * @param $village_id 小区id
		 * @param $params  eg.
		 *                   template_type  int  模板类型 0 无需模板 1系统模板 2上传模板
		 *                   template_url  string 模板链接
		 *                   qy_qrcode     string 企业微信群二维码链接
		 *                   village_name  string 小区名称
		 *
		 * @return array|void
		 *
		 *                   //TODO 暂沿用之前的方式，后面可以考虑替换成 imagick
		 */
		public function traitMakePreviewImgForQyWx($village_id,$params)
		{
			$qy_qrcode = trim($params['qy_qrcode']);// 企业微信群二维码链接
			$template_type = (int)$params['template_type'];// 模板类型 0 无需模板 1系统模板 2上传模板
			$template_url = isset($params['template_url']) ? trim($params['template_url']) : '';// 模板链接
			$village_name = $params['village_name']; //小区名称

			$font_url =  root_path().'public/static/fonts/apple_lihei_bold.otf';

			if (!$qy_qrcode) {
				return false; //缺少企业微信群二维码链接
			}
			$qy_qrcode = replace_file_domain($qy_qrcode);
			file_put_contents('xxxxxx.log',$qy_qrcode);
			if (2==$template_type && !$template_url) {
				return false;//缺少对应模板链接
			}
			if ($template_url) {
				$template_url = replace_file_domain($template_url);
			}
			if (0==$template_type) {
				return false;//无需模板
			} elseif (1==$template_type) {
				$site_url = cfg('site_url');
				if (mb_strlen($village_name)<=10) {
					$background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin1.png';//海报最底层得背景
					$village_name = '- '.$village_name.' -';
				} elseif (mb_strlen($village_name)<=16) {
					$background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin2.png';//海报最底层得背景
					$village_name = '- '.$village_name.' -';
				} else {
					$background = $site_url . '/tpl/House/default/static/images/qyWeixin/qyweixin3.png';//海报最底层得背景
					$village_name = '- '.$village_name.' -';
				}
				$bbox = imagettfbbox(18, 0, $font_url, $village_name);
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

			//关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
			imagecopyresampled($canvas, $res, 0, 0, 0, 0,$qr_position['width'], $qr_position['height'],$resWidth,$resHeight);
			//放置图像
			imagecopymerge($imageRes,$canvas, $qr_position['left'],$qr_position['top'],$qr_position['right'],$qr_position['bottom'],$qr_position['width'],$qr_position['height'],$qr_position['opacity']);//左，上，右，下，宽度，高度，透明度

			if ($text) {
				foreach ($text as $val) {
					$fontWidth = intval($val['fontSize']);//获取文字宽度
					$val['fontPath'] = $font_url;
					[$R,$G,$B] = explode(',', $val['fontColor']);
					$fontColor = imagecolorallocate($imageRes, $R, $G, $B);
					$val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
					$val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
					imagettftext($imageRes,$val['fontSize'],(isset($val['angle']) ? $val['angle'] : 0),$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);
				}
			}

			$path = root_path()."../upload/qyWeixinImg";
			if(!file_exists($path)){
				mkdir($path,0777,true);
			}
			$file = $path .'/services_look_img_'.$village_id.'_'.$template_type.'_'.time().'.jpg';
			$filename =  $file;

			imagejpeg ($imageRes,$filename,90);  //保存到本地
			imagedestroy($imageRes);
			file_put_contents('sssssss.log','-111--->$filename-->'.$filename.'--->$file--'.$file);
			//判断是否需要上传至云存储
			$upload_dir = 'qyWeixinImg';
			// 验证
			validate(['imgFile' => [
				'fileSize' => 1024 * 1024 * 10,   //10M
				'fileExt' => 'jpg,png,jpeg,gif,ico',
				'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
			]])->check(['imgFile' => $file]);
			// 上传图片到本地服务器uniqid
			$saveName = \think\facade\Filesystem::disk('public_upload')->putFile($upload_dir, $file, 'data');
			$saveName = str_replace("\\", '/', $saveName);

			$file_path = replace_file_domain($file);
			file_put_contents('sssssss.log',$saveName.'---->$file_path-->'.$file_path.'--->$file--'.$file);
			return ['file_path'=>$file_path,'file' => $file];
		}

	}