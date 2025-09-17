<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<title>绑定人脸识别门禁</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name='apple-touch-fullscreen' content='yes' />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="format-detection" content="address=no" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/village.css" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/bbs.css" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/LCalendar.css" />
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<style type="text/css">
			#container {
				bottom: -80px;
				top:0
			}
            .fabu-btn{
                -webkit-appearance: none;
                border-radius: 0;
                padding: 0;
                border-spacing: 0px !important;
                outline: medium;
                word-break: break-word;
                border: none;
                list-style: none;
            }
            .new_face {
                float: right;
                background: #06c1ae;
                list-style: none;
                color: white;
                border: none;
                padding: 5px;
                font-size: 12px;
            }
            .new_load {
                float: right;
                background: #06c1ae;
                list-style: none;
                color: white;
                border: none;
                padding: 5px;
                font-size: 12px;
            }
			
			
			.page__hd {
				padding: 15px;
			}
			.page__title {
				text-align: left;
				font-size: 20px;
				font-weight: 400;
				margin: 20px 0 10px;
			}
			.page__desc {
				margin-top: 4px;
				color: rgba(0,0,0,.5);
				text-align: left;
				font-size: 14px;
			}
			.village_my nav{
				padding-left: 10px;
			}
		</style>
	</head>

	<body>	
		<div class="page__hd">
			<h1 class="page__title">请上传3-5张脸部照片<span style="font-size:14px;color:#666;font-style:normal;"><if condition="($now_village_bind['type'] eq 0 || $now_village_bind['type'] eq 3) && $village_info">（{pigcms{$village_info['name']}）</if></span></h1>
			<p class="page__desc">为提高人脸识别的速度，请尽量从正、侧脸等多角度拍照。<br/>为保证人脸识别的真实性，添加图片时只能相机拍照上传。<em style="font-size:12px;color:#999;font-style:normal;">（不同手机型号上传方式可能会不同）</em></p>
		</div>
		<form action="__SELF__" method="post">
			<div class="village_my">
				<nav>
					<section>
					<dd class="item">
						<div class="weui-uploader__hd" style="display: -webkit-box;display: -webkit-flex;display: flex;padding-bottom: 5px;-webkit-box-align: center;-webkit-align-items: center;align-items:center;">
                            <p class="weui-uploader__title" style="font-size:17px;padding-left:5px;-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;">图片上传</p>
                            <div class="weui-uploader__info countNum" style="color:rgba(0,0,0,0.3);"><em class="loadedNum" style="font-style:normal;">{pigcms{:count($now_village_bind['face_img'])}</em>/5</div>
                        </div>
						<div class="upload_box">
							<ul class="upload_list clearfix" id="upload_list">
								<php>if (!empty($now_village_bind['face_img'])) {</php>
									<volist name="now_village_bind['face_img']" id="img">
										<li class="upload_item" id="imgShow{pigcms{$key+1}" style="background:url({pigcms{:replace_file_domain($img['img_url'])}) center center / cover;">
											<a href="javascript:void(0);" class="upload_delete" title="删除"></a>
											<img src="{pigcms{:replace_file_domain($img['img_url'])}" class="upload_image loading_img" url="{pigcms{:replace_file_domain($img['img_url'])}">
											<input type="hidden" name="inputimg[]" value="{pigcms{$img['img_url']}">
											<br>
										</li>
									</volist>
								<php>}</php>
								<li class="upload_action">
									<img src="{pigcms{$static_path}images/upimg.png">
									<input type="file" accept="image/*" capture="camera" id="fileImage">
								</li>
							</ul>
						</div>
					</dd>
					</section>
				</nav>
				<if condition="!empty($now_village_bind['face_img'])">
					<section style="padding:15px;">
						<php>if (0==$now_village_bind['face_img_status']) {</php>
							<p>上次保存的照片正在同步至设备中，请稍等</p>
						<php>} else if (1==$now_village_bind['face_img_status']) {</php>
							<p>上次保存的照片已成功同步至设备</p>
						<php>} else if (2==$now_village_bind['face_img_status']) {</php>
							<p>上次保存的照片同步失败。<php>if (2==$now_village_bind['face_img_reason']) {</php>原因：{pigcms{$now_village_bind['face_img_reason']}<php>}</php></p>
						<php>}</php>
						<php>if ($now_village_bind['is_new_face']) {</php>
							<button class="new_face" data-pigcms_id="{pigcms{$now_village_bind['pigcms_id']}">重新同步</button>
						<php>} else if(0==$now_village_bind['face_img_status']) {</php>
							<button class="new_load" data-pigcms_id="{pigcms{$now_village_bind['pigcms_id']}">刷新页面</button>
						<php>}</php>
					</section>
				</if>
			</div>
			<footer class="footerMenu wap house">
				<input type="hidden" value="{pigcms{$_GET['pigcms_id']}" name="pigcms_id" />
				<input type="submit" value="保存" class="fabu-btn" />
			</footer>
		</form>
		<script type="text/javascript" src="{pigcms{$static_path}js/LCalendar.js"></script>
		<script src="{pigcms{$static_path}js/exif.js"></script>
		<script src="{pigcms{$static_path}js/imgUpload.js"></script>
		<script type="text/javascript" language="JavaScript">
			
			var ajaxImgUpload_url = "{pigcms{:U('ajaxImgUpload')}&ml=face_door&village_id={pigcms{$_GET['village_id']}";
			
			
			
			if ($(".upload_list").length) {
				var imgUpload = new ImgUpload({
					fileInput: "#fileImage",
					container: "#upload_list",
					countNum: "#uploadNum",
                    maxNum: 5,
					url: ajaxImgUpload_url,
					countNum: '.countNum',
					leftNum: '.countNum .leftNum',
				})
			}

			$('.new_face').click(function () {
                var pigcms_id = $(this).data('pigcms_id');
                console.log('pigcms_id', pigcms_id);

                $.ajax({
                    type : "post",
                    url : "{pigcms{:U('new_bind_face')}",
                    dataType : "json",
                    data:{
                        pigcms_id	:	pigcms_id
                    },
                    success : function(result){
                        if (d.errorCode>0) {
                            alert(d.errorMsg);
                        } else {
                            alert('绑定操作成功');
                            window.location.reload();
                        }
                        console.log('result', result);
                    },
                    error:function(){
                        request = true;
                        alert('接口出错');
                    }
                })
                return false;
            });
			$('.new_load').click(function () {
                window.location.reload();
                return false;
            });
			
			if($('#upload_list .upload_item').size() > 0){
				$('#upload_list .upload_item, #upload_list .upload_action').height($('#upload_list .upload_item').outerWidth());
			}else{
				$('#upload_list .upload_item, #upload_list .upload_action').height($('#upload_list .upload_action').outerWidth());
			}
		</script>
		{pigcms{$hideScript}

	</body>

</html>