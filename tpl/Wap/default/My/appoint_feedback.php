<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>预约评价</title>
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
			font-size: .26rem;
			font-weight: bold;
            color: #333;
            margin-bottom: .09rem;
		}
		.btn-wrapper {
			margin: .75rem .40rem 1.08rem;
		}
        .btn-wrapper .btn {
            border-radius: .12rem;
            height: .70rem;
            background: linear-gradient(90deg, #F7DE74, #FDCA2F);
            line-height:.70rem;
            font-size: .28rem;
            font-weight: bold;
            color: #2A2929;
        }
		.score {
			position: relative;
		}
		.score:before,
		.score span:after {
			content: '★★★★★';
			position: absolute;
			font-family: 'base_icon';
			font-size: .36rem;
			color: #D7D7D7;
			letter-spacing: .22rem;
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
            letter-spacing: .22rem;
		}
		.score label {
            margin-right: .22rem;
			display: inline-block;
			width: .36rem;
			height: .36rem;
		}
		.score span {
			position: absolute;
			visibility: hidden;
			color: #999999;
			top: 0;
			left: 0;
			width: 80%;
			line-height: 1.9em;
			font-size: 12px;
			pointer-events: none;
			text-align: right;
		}

		.score span:after {
			color: #FDCA2F;
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

		dl.list,dl.list dt, dl.list dd{ border:none}

        dl.list:first-child ,.list-common-wrap {
            margin: .10rem .24rem;
            background: #fff;
            border-radius: .1rem;
        }
        dl.list:first-child {
            margin-top: .20rem;
        }
        dl.list dd.title {
            padding: .26rem .22rem;
            font-size: .32rem;
            font-weight: bold;
            color: #000000;
            line-height: .42rem;
            border-bottom: .01rem solid #ECECEC;
            box-sizing: border-box;
        }
        .flex {
            display: flex;
        }
        .justify-between {
            justify-content: space-between;
        }
        .align-center {
            align-items: center;
        }
        .flex-1 {
            flex: 1;
        }
        .image-pic {
            margin-right: .40rem;
            width: .8rem;
            height: .8rem;
            border-radius: .1rem;
            overflow: hidden;
        }
        .image-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .worker-name {
            font-size: .26rem;
            font-weight: bold;
            color: #333333;
            margin-bottom: .15rem;
        }
        .worker-time {
            font-size: .2rem;
            font-weight: 500;
            color: #666666;
        }
        h6.label {
            margin: 0 .7rem 0 0;
            font-size: .26rem;
            font-weight: bold;
            color: #999;
            line-height: .48rem;
        }
        .profession h6.label {
            color: #000000;
        }
        .worker .score span {
			width: 140%;
            font-size: .26rem;
		}
        .score span.max-score {
            color: #000;
            font-weight: bold;
        }
        dl.list.worker {
            overflow-y: auto;
        }
        dl.list.worker .dd-padding {
            padding: 0.23rem 0.24rem;
        }
        dl.list dd.comment-wrap {
            padding: .55rem .26rem .36rem;
            margin: .30rem .24rem .60rem;
            background: #F8F7F7;
            border: 1px solid #EEEDED;
            border-radius: .10rem;
        }
        textarea.input-weak {
            outline: none;
            height: 1.18rem;
            text-indent:0rem;
            background-color:transparent;
            font-size:.24rem;
            resize: none;
            margin-bottom:.2rem;
        }
        .comment-wrap .upload_list {
            display: flex;
            flex-wrap: wrap;
        }
        .comment-wrap #upload_list .upload_action {
            width: 1.02rem;
            height: 1.02rem;
            border: 1px dashed #D0CFCF;
            border-radius: .05rem;
            box-sizing: border-box;
            background-color: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-around;
            position: relative;
            font-size: .18rem;
            font-weight: 500;
            color: #A7A7A7;
            margin: 0 0.1rem 0.1rem 0;
        }
        .comment-wrap #upload_list .upload_action img {
            width: .46rem;
            height: .39rem;
        }
        .comment-wrap #upload_list .upload_action #fileImage {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            opacity: 0;
        }
        .comment-wrap #upload_list .upload_item {
            width: 1.02rem!important;
            height: 1.02rem!important;
            box-sizing: border-box;
            margin: 0 0.1rem 0.1rem 0;
            border-radius: 0.05rem;
            border: none;
        }
        .kv-line-r {
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kv-line-r h6 {
            font-size: .24rem;
            font-weight: 500;
            color: #454545;
            margin: 0;
            line-height: 1;
        }
        .kv-line-r input.choose.mt.kv-v {
            margin-left: .14rem;
            width: .24rem;
            height: .24rem;
        }
        .kv-line-r input.mt[type=checkbox]:checked:after {
            font-size: .24rem;
            line-height: .20rem;
        }
        .kv-line-r input.mt[type=radio], input.mt[type=checkbox] {
            line-height: .20rem;
        }
	</style>
</head>
<body id="index" data-com="pagecommon" style="background-color:#F7F7F7;">
	<div id="tips" class="tips"></div>
	<form id="form" method="post" action="{pigcms{:U('My/appoint_feedback',array('order_id'=>$now_order['order_id']))}">
		<dl class="list list-common-wrap">
            <dd class="title"><span>您对本次服务满意吗?</span></dd>
			<dd class="dd-padding flex justify-between">
                <div class="image-pic">
                    <img src="{pigcms{$now_appoint['all_pic'][0]['image']}"  alt="">
                </div>
                <div class="flex-1">
                    <h6>{pigcms{$now_order['order_name']}</h6>
                    <div class="score">
                        <label>
                            <input type="radio" class="score_1" value="1" name="score"/>
                            <span>非常差</span>
                        </label>
                        <label>
                            <input type="radio" class="score_2" value="2" name="score"/>
                            <span>差</span>
                        </label>
                        <label>
                            <input type="radio" class="score_3" value="3" name="score"/>
                            <span>一般</span>
                        </label>
                        <label>
                            <input type="radio" class="score_4" value="4" name="score"/>
                            <span>满意</span>
                        </label>
                        <label>
                            <input type="radio" class="score_5" value="5" name="score" checked="checked"/>
                            <span>非常好</span>
                        </label>
                    </div>
                </div>
			</dd>
        </dl>
        <dl class="list list-common-wrap worker">
            <if condition="$merchant_workers_info">
                
                    <dd class="title"><span>您对<php>echo C('config.appoint_worker_name')</php>满意吗?</span></dd>
                    <dd class="dd-padding flex justify-between">
                        <div class="image-pic">
                            <img src="{pigcms{$now_appoint['all_pic'][0]['image']}"  alt="">
                        </div>
                        <div class="flex-1">
                            <p class="worker-name">{pigcms{$merchant_workers_info['name']}</p>
                            <p class="worker-time">服务时间：{pigcms{$merchant_workers_info['service_time']}</p>
                        </div>
                    </dd>
                    <dd class="dd-padding flex align-center profession">
                        <h6 class="label">专业</h6>
                        <div class="score">
                            <label>
                                <input type="radio" class="score_1" value="1" name="profession_score"/>
                                <span>非常差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_2" value="2" name="profession_score"/>
                                <span>差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_3" value="3" name="profession_score"/>
                                <span>一般</span>
                            </label>
                            <label>
                                <input type="radio" class="score_4" value="4" name="profession_score"/>
                                <span>满意</span>
                            </label>
                            <label>
                                <input type="radio" class="score_5" value="5" name="profession_score" checked="checked"/>
                                <span class="max-score">非常满意</span>
                            </label>
                        </div>
                    </dd>
                    <dd class="dd-padding flex align-center">
                        <h6 class="label">沟通</h6>
                        <div class="score">
                            <label>
                                <input type="radio" class="score_1" value="1" name="communicate_score"/>
                                <span>非常差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_2" value="2" name="communicate_score"/>
                                <span>差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_3" value="3" name="communicate_score"/>
                                <span>一般</span>
                            </label>
                            <label>
                                <input type="radio" class="score_4" value="4" name="communicate_score"/>
                                <span>满意</span>
                            </label>
                            <label>
                                <input type="radio" class="score_5" value="5" name="communicate_score" checked="checked"/>
                                <span class="max-score">非常满意</span>
                            </label>
                        </div>
                    </dd>
                    <dd class="dd-padding flex align-center">
                        <h6 class="label">守时</h6>
                        <div class="score">
                            <label>
                                <input type="radio" class="score_1" value="1" name="speed_score"/>
                                <span>非常差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_2" value="2" name="speed_score"/>
                                <span>差</span>
                            </label>
                            <label>
                                <input type="radio" class="score_3" value="3" name="speed_score"/>
                                <span>一般</span>
                            </label>
                            <label>
                                <input type="radio" class="score_4" value="4" name="speed_score"/>
                                <span>满意</span>
                            </label>
                            <label>
                                <input type="radio" class="score_5" value="5" name="speed_score" checked="checked"/>
                                <span class="max-score">非常满意</span>
                            </label>
                        </div>
                    </dd>
            </if>
            <dd class="comment-wrap">
                <div>
                    <textarea name="comment" class="input-weak" placeholder="最少评论10字，您的评价将是其他用户的重要参考"></textarea>
                </div>
                <div class="flex justify-between upload_box">
                    <ul class="upload_list flex-1" id="upload_list">
                        <li class="upload_action"> <img src="{pigcms{$static_path}images/paizhao.png" /> <input type="file" accept="image/jpg,image/jpeg,image/png,image/gif" id="fileImage" name="" /> <span>可传8张</span> </li>
                    </ul>
                    <label for="anonymous" class="kv-line-r" style="align-self: flex-end;">
                        <h6>匿名评价</h6>
                        <input id="anonymous" type="checkbox" name="anonymous" value="1" class="choose mt kv-v"/>
                    </label>
                </div>
            </dd>
		</dl>
		<div class="btn-wrapper"><button type="submit" class="btn btn-larger btn-block btn-strong">提交</button></div>
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
				alert('最少评论10字，您的评价将是其他用户的重要参考！');
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
									$.post("{pigcms{:U('My/ajaxGetWxImg')}", {'serverId':serverId, 'picType':'shop'}, function(data){
										var str = '<li class="upload_item" style="height:1.02rem; background:url(' + data.info.url + ') center center / cover;"><a href="javascript:void(0);" class="upload_delete" title="删除"></a><img src="' + data.info.url + '" class="upload_image loading_img" url="' + data.info.url + '"><input type="hidden" name="inputimg[]" value="' + data.info.title + '"><br></li>';
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
					url:"{pigcms{$config.site_url}/wap.php?g=Wap&c=My&a=ajaxImgUpload&ml=shop"
				})
			}
		}
	});
	</script>
	<include file="Public:footer"/>
	{pigcms{$hideScript}
</body>
</html>