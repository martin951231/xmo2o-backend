<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>{pigcms{$config.group_alias_name}评价</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/wap_pay_check.css" rel="stylesheet"/>
	<link href="{pigcms{$static_path}css/wap_uploadimg.css" rel="stylesheet"/>
<style>
    h6 {
        font-size: .3rem;
        font-weight: normal;
        margin-bottom: .2rem;
    }
    .btn-wrapper {
        margin: .28rem .2rem;
    }
    .score {
        position: relative;
    }
    .score:before,
    .score span:after {
        content: '★★★★★';
        position: absolute;
        font-family: 'base_icon';
        font-size: .5rem;
        color: #e9e9e9;
        letter-spacing: .4rem;
        line-height: 1em;
        left: 0;
    }
    .score input {
        opacity: .0;
        width: 100%;
        height: 100%;
        -webkit-appearance: initial;
        outline: none;
        z-index: 3;
    }
    .score label {
        display: inline-block;
        width: .84rem;
        height: .5rem;
    }
    .score span {
        position: absolute;
        visibility: hidden;
        color: #f49231;
        top: 0;
        left: 0;
        width: 100%;
        line-height: 1.9em;
        font-size: 14px;
        pointer-events: none;
        text-align: right;
    }

    .score span:after {
        color: #f49231;
    }
    .score input:checked + span {
        visibility: visible;
    }
    .score_1:checked + span:after {
        content: '★';
    }
    .score_2:checked + span:after {
        content: '★★';
    }
    .score_3:checked + span:after {
        content: '★★★';
    }
    .score_4:checked + span:after {
        content: '★★★★';
    }
    textarea {
        width: 100%;
    }
    .react .kv-line {
        margin: 0;
    }
</style>
</head>
<body id="index" data-com="pagecommon">
        <div id="tips" class="tips"></div>
        <form id="form" method="post" action="{pigcms{:U('My/group_feedback',array('order_id'=>$now_order['order_id'],'group_id'=>$group_id))}">
			<dl class="list">
				<dd class="dd-padding">
					<h6>评分</h6>
					<div class="score">
						<label>
							<input type="radio" class="score_1" value="1" name="score"/>
							<span><b>1</b>分</span>
						</label>
						<label>
							<input type="radio" class="score_2" value="2" name="score"/>
							<span><b>2</b>分</span>
						</label>
						<label>
							<input type="radio" class="score_3" value="3" name="score"/>
							<span><b>3</b>分</span>
						</label>
						<label>
							<input type="radio" class="score_4" value="4" name="score"/>
							<span><b>4</b>分</span>
						</label>
						<label>
							<input type="radio" class="score_5" value="5" name="score" checked="checked"/>
							<span><b>5</b>分</span>
						</label>
					</div>
				</dd>
			</dl>
			<dl class="list">
				<dd>
					<dl>
						<dd class="dd-padding">
							<textarea name="comment" class="input-weak" placeholder="最少评论10字，您的评价将是其他用户的重要参考" style="height:4.2em;text-indent:0rem;"></textarea>
						</dd>

						 <dd class="item uploadNum" id="uploadNum">还可上传<span class="leftNum orange">8</span>张图片，已上传<span class="loadedNum orange">0</span>张(非必填)</dd> 
						<dd class="item"> 
						 <div class="upload_box"> 
						  <ul class="upload_list clearfix" id="upload_list"> 
						   <li class="upload_action"> <img src="{pigcms{$static_path}classify/upimg.png" /> <input type="file" accept="image/*" id="fileImage" name="" /> </li>
						  </ul> 
						 </div>
						</dd>
                        <if condition="$now_order['is_group_combine'] eq 0">
						<dd>
							<label class="react">
								<div class="kv-line-r">
									<h6>匿名评价</h6>
									<input type="checkbox" name="anonymous" value="1" class="choose mt kv-v"/>
								</div>
							</label>
						</dd>
                        </if>
					</dl>
				</dd>
			</dl>
			<div class="btn-wrapper"><button type="submit" class="btn btn-larger btn-block btn-strong">发布</button></div>
		</form>
    	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>
		<script src="{pigcms{$static_path}classify/exif.js"></script> 
		<script src="{pigcms{$static_path}classify/imgUpload.js"></script> 
		<script>
			$(function(){
				$('#form').submit(function(){
					$('#tips').hide();
					if($('textarea[name="comment"]').val().length < 10){
						$('#tips').html('最少评论10字，您的评价将是其他用户的重要参考').show();
						return false;
					}
				});
				if ($(".upload_list").length) {
					if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
						$('#upload_list').on("click", ".upload_delete", function() {
							$(this).closest(".upload_item").remove();
							$('.loadedNum').html($('#upload_list .upload_item').size());
						})
						$('.upload_action').click(function(){
							wx.chooseImage({
								count: 1, // 默认9
								success: function (res) {
									var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
									wx.uploadImage({
										localId: localIds[0], // 需要上传的图片的本地ID，由chooseImage接口获得
										isShowProgressTips: 1, // 默认为1，显示进度提示
										success: function (res) {
											var serverId = res.serverId; // 返回图片的服务器端ID
											$.post("{pigcms{:U('My/ajaxGetWxImg')}", {'serverId':serverId, 'picType':'group'}, function(data){
												var str = '<li class="upload_item" style="height:81px; background:url(' + data.info.url + ') center center / cover;"><a href="javascript:void(0);" class="upload_delete" title="删除"></a><img src="' + data.info.url + '" class="upload_image loading_img" url="' + data.info.url + '"><input type="hidden" name="inputimg[]" value="' + data.info.title + '"><br></li>';
												$('#upload_list').prepend(str);
												
												$('.loadedNum').html($('#upload_list .upload_item').size());
											});
										},
										fail: function(res){
											alert('微信上传组件返回：' + res.errMsg);
										}
									});
								},
								fail: function(res){
									alert('微信选择图片组件返回：' + res.errMsg);
								}
							});
							return false;
						});
					}else{
						var imgUpload = new ImgUpload({
							fileInput: "#fileImage",
							container: "#upload_list",
							countNum: "#uploadNum",
							url:"{pigcms{$config.site_url}/wap.php?g=Wap&c=My&a=ajaxImgUpload&ml=group"
						})
					}
				}
			});
		</script>
		<include file="Public:footer"/>
{pigcms{$hideScript}
</body>
</html>