<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no,minimal-ui">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="keywords" content="{pigcms{$config.seo_keywords}">
<meta name="description" content="{pigcms{$config.seo_description}">
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/index.js"></script>
<script src="{pigcms{$static_path}js/shop_reply.js"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<title>评论订单</title>
</head>
<body class=" hPC" style="padding-bottom: initial; background:#fff;">
<div id="page-loader" style="width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; z-index: 10000; text-align: center; display: none; background-color: rgba(200, 200, 200, 0.2);">
    <div style="margin-top:200px;color: white;background-color: rgba(30,30,30,0.8);padding: 10px;width: 140px;margin-left: auto;margin-right: auto;border-radius: 5px;font-size: 14px;  font-family: &#39;Helvetica&#39;;-webkit-box-shadow: 1px 1px 2px rgba(0,0,0,.4);">正在加载中……</div>
</div>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_5e96991.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_dd39d16.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_3a812b5.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shop_57c5f10.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shopcomment_4bd95ec.css">
<link href="{pigcms{$static_path}css/wap_uploadimg.css" rel="stylesheet"/>
<style type="text/css">
.border_b{position: relative;}
.border_b::after{
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: #ddd;
    transform: scaleY(0.33333);
    -webkit-transform: scaleY(0.33333);
}
p.nav{
    padding:15px;
    overflow: auto;
    display:flex;
    align-items:center;
}
.nav img{
    display: block;
    object-fit: cover;
    width: 60px;
    height: 60px;
}
.nav span{
    padding:0 5px 0 10px;
    color: #000;
}

</style>
<div id="pager"> <img src="{pigcms{$static_path}images/hm.gif" width="0" height="0" style="display:block">
    <div id="wrapper" style="background:#fff">
        <div id="fis_elm__2">
            <div id="common-widget-nav" class="common-widget-nav ">
                <div class="left-slogan"> <a class="left-arrow icon-arrow-left2" data-node="navBack" id="goBackUrl" href="javascript:history.go(-1);"></a> </div>
                <div class="center-title"> <a href="javascript:void(0)">添加评论</a> </div>
                <div class="right-slogan "> </div>
            </div>
        </div>        
        <div id="fis_elm__3">
            <div id="shopcomment-add-wrapper">
                <volist name='list' id='vo'>
                    <input type='hidden' name="order_detail_id" value="{pigcms{$vo.id}"/>
                    <p class="nav border_b">
                       <img src="{pigcms{$vo.goods_image}"/>
                       <span>{pigcms{$vo.name}</span> 
                    </p>
                </volist>
                <div id="widget-shopcomment-add">
                    <notempty name='list'>
                    <!-- <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"><b class="revtit">给店铺评价</b></li>
                        </ul>
                    </div> -->
                    <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"> <span class="revtit">整体评价</span>
                                <div class="revinp">
                                	<span class="level store_whole">
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                	</span> 
                                	<span class="revgrade">优</span> 
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="add-list"s>
                        <textarea class="text-area comment-desc" placeholder="你的意见很重要，来点评一下吧！"></textarea>
                    </div>
                    <!-- <div class="add-list">
                        <div class="add-list-title">请选择您要点赞的商品</div>
                        <div class="add-list-span recommend-list">
                        <volist name="now_order['info']" id="vo">
                            <span data-goods-id="{pigcms{$vo.goods_id}">{pigcms{$vo.name}</span>
                        </volist>
                        </div>
                    </div> -->                    
                    <dl class="list">
                        <dd>
                            <dl>
                                <dd class="item uploadNum" style="margin: 5px 0;" id="uploadNum">还可上传<span class="leftNum orange">8</span>张图片，已上传<span class="loadedNum orange">0</span>张(非必填)</dd> 
                                <dd class="item"> 
                                    <div class="upload_box"> 
                                        <ul class="upload_list clearfix" id="upload_list" style="display:block;"> 
                                            <li class="upload_action"> <img src="{pigcms{$static_path}classify/upimg.png" /> <input type="file" accept="image/jpg,image/jpeg,image/png,image/gif" id="fileImage" name="" /> 
                                            </li> 
                                        </ul> 
                                    </div>
                                </dd>
                            </dl>
                        </dd>
                    </dl>
                    </notempty>
                    <if condition="$is_service eq 1">
                    <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"><b class="revtit">给配送员评价</b></li>
                        </ul>
                    </div>
                    <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"> <span class="revtit">整体评价</span>
                                <div class="revinp">
                                    <span class="level deliver_whole">
                                        <i class="level_solid" cjmark=""></i> 
                                        <i class="level_solid" cjmark=""></i> 
                                        <i class="level_solid" cjmark=""></i> 
                                        <i class="level_solid" cjmark=""></i> 
                                        <i class="level_solid" cjmark=""></i> 
                                    </span> 
                                    <span class="revgrade">优</span> 
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="add-list">
                        <textarea class="text-area comment-deliver" placeholder="你的意见很重要，来点评一下吧！"></textarea>
                    </div>
                    </if>
                </div>
                </div>
                <div class="comment-btn" shop_id="{pigcms{$now_order.store_id}" order_id="{pigcms{$now_order.order_id}" data-node="comment-btn">提交评价</div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}classify/exif.js"></script> 
<script src="{pigcms{$static_path}classify/imgUpload.js"></script> 
<script type="text/javascript">
$(document).ready(function(){
    var is_service="{pigcms{$is_service|default=0}";
	$('.comment-btn').click(function(){
// 		var mass = $(".mass").children(".level_solid").length;
// 		var send = $(".send").children(".level_solid").length;
		var whole = $('.store_whole').children('.level_solid').length;
		var textAre = $(".comment-desc").val();
		var oid = $(this).attr("order_id");
        
        var score = $('.deliver_whole').children('.level_solid').length;
        var comment = $(".comment-deliver").val();
// 		var sid = $(this).prop("shop_id");
		var goods_ids = new Array();
		$('.select').each(function(i){
			goods_ids[i] = $(this).attr('data-goods-id');
		});
// 		var minutes = $(".minutes").attr("time");
		var postData = {'whole':whole, 'textAre':textAre, 'order_id':oid, 'goods_ids':goods_ids, 'score':score, 'comment':comment};
        //订单详情id
        var order_detail_id=$('input[name=order_detail_id]').val();

        postData['order_detail_id']=order_detail_id;
        //图片
        var inputimg = new Array();
        $("input[name='inputimg[]']").each(function(i){
            inputimg[i] = $(this).val();
        });
        postData['inputimg']=inputimg;
        postData['is_service']=is_service;

		$.post("{pigcms{:U('My/add_goods_comment')}", postData, function(data){
			if (data.status == 1) {
                layer.open({title:['评论提示：','background-color:#FF658E;color:#fff;'],content:''+data.msg+'',btn: ['确定'],end:function(){window.location.href=data.url;}});
            } else {
            	layer.open({title:['评论提示：','background-color:#FF658E;color:#fff;'],content:''+data.msg+'',btn: ['确定']});
            }
		}, 'json');
	});

    $(".add-list span").click(function(){
        $(this).toggleClass("select");
    });
});
</script>
{pigcms{$hideScript}
<script>
// alert(navigator.userAgent.toLowerCase());
if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
	var reg = /versioncode=(\d+),/;
	var arr = reg.exec(navigator.userAgent.toLowerCase());
	if(arr == null){
		
	}else{
		var version = parseInt(arr[1]);
		if(version >= 50){
			if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
				$('#goBackUrl').click(function(){
                    if(version >= 1000) {
                        window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'webViewGoBack'});
                    } else {
                        $('body').append('<iframe src="pigcmso2o://webViewGoBack" style="display:none;"></iframe>');
                    }
					
					return false;
				});
		}else{
				$('#goBackUrl').click(function(){
					window.lifepasslogin.webViewGoBack();
					return false;
				});
			}
		}
	}
}
</script>
<!-- 图片上传 -->
<script type="text/javascript">
$(function(){
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
				url:"{pigcms{$config.site_url}/wap.php?g=Wap&c=My&a=ajaxImgUpload&ml=shop"
			})
		}
	}
});
</script>
</html>