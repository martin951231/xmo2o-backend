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
<script>
    var description = new Array("{pigcms{:L_('非常差，很难吃')}","{pigcms{:L_('真的是差，都不忍心说你了')}","{pigcms{:L_('一般，还过得去吧')}","{pigcms{:L_('很好，是我想要的东西')}","{pigcms{:L_('太完美了，此物只得天上有，人间哪得几回闻!')}");
    var degree = ['','{pigcms{:L_('很差')}','{pigcms{:L_('差')}','{pigcms{:L_('中')}','{pigcms{:L_('良')}','{pigcms{:L_('优')}','{pigcms{:L_('未评分')}'];
    var description_des = '{pigcms{:L_('当前您的评价为')}';
</script>
<script src="{pigcms{$static_path}js/shop_reply.js"></script>
<script src="{pigcms{$static_path}layer/layer.m.js"></script>
<title>{pigcms{:L_('评论订单')}</title>
<style type="text/css">
    .recommend-list{
        margin-bottom: 20px;
    }
</style>
</head>
<body class=" hPC" style="padding-bottom: initial; background:#fff;">
<div id="page-loader" style="width: 100%; height: 100%; position: fixed; top: 0px; left: 0px; z-index: 10000; text-align: center; display: none; background-color: rgba(200, 200, 200, 0.2);">
    <div style="margin-top:200px;color: white;background-color: rgba(30,30,30,0.8);padding: 10px;width: 140px;margin-left: auto;margin-right: auto;border-radius: 5px;font-size: 14px;  font-family: &#39;Helvetica&#39;;-webkit-box-shadow: 1px 1px 2px rgba(0,0,0,.4);">{pigcms{:L_('正在加载中……')}</div>
</div>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_5e96991.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_dd39d16.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/lib_3a812b5.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shop_57c5f10.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/shopcomment_4bd95ec.css">

<div id="pager"> <img src="{pigcms{$static_path}images/hm.gif" width="0" height="0" style="display:block">
    <div id="wrapper" style="background:#fff">
        <div id="fis_elm__3">
            <div id="shopcomment-add-wrapper">
                <div id="widget-shopcomment-add">
                    <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"><b class="revtit">{pigcms{:L_('给店铺评价')}</b></li>
                        </ul>
                    </div>
                    <div class="gradecon" id="Addnewskill_119">
                        <ul class="rev_pro clearfix">
                            <li class="clearfix"> <span class="revtit">{pigcms{:L_('整体评价')}</span>
                                <div class="revinp">
                                	<span class="level store_whole">
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                		<i class="level_solid" cjmark=""></i> 
                                	</span> 
                                	<span class="revgrade">{pigcms{:L_('优')}</span> 
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="add-list">
                        <textarea class="text-area comment-desc" placeholder="{pigcms{:L_('你的意见很重要，来点评一下吧！')}"></textarea>
                    </div>
                    <if condition="$now_order['info']">
                        <div class="add-list">
                            <div class="add-list-title">{pigcms{:L_('请选择您要点赞的商品')}</div>
                            <div class="add-list-span recommend-list">
                            <volist name="now_order['info']" id="vo">
                                <span data-goods-id="{pigcms{$vo.goods_id}">{pigcms{$vo.name}</span>
                            </volist>
                            </div>
                        </div>
                    </if>
                    <if condition="$now_order['is_pick_in_store'] lt 2">
                        <div class="gradecon" id="Addnewskill_119" style="margin-top:20px;">
                            <ul class="rev_pro clearfix">
                                <li class="clearfix"><b class="revtit">{pigcms{:L_('给配送员评价')}</b></li>
                            </ul>
                        </div>
                        <div class="gradecon" id="Addnewskill_119">
                            <ul class="rev_pro clearfix">
                                <li class="clearfix"> <span class="revtit">{pigcms{:L_('整体评价')}</span>
                                    <div class="revinp">
                                        <span class="level deliver_whole">
                                            <i class="level_solid" cjmark=""></i> 
                                            <i class="level_solid" cjmark=""></i> 
                                            <i class="level_solid" cjmark=""></i> 
                                            <i class="level_solid" cjmark=""></i> 
                                            <i class="level_solid" cjmark=""></i> 
                                        </span> 
                                        <span class="revgrade">{pigcms{:L_('优')}</span> 
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="add-list">
                            <textarea class="text-area comment-deliver" placeholder="{pigcms{:L_('你的意见很重要，来点评一下吧！')}"></textarea>
                        </div>
                    </if>
                </div>
                <div class="comment-btn" shop_id="{pigcms{$now_order.store_id}" order_id="{pigcms{$now_order.order_id}" data-node="comment-btn">{pigcms{:L_('提交评价')}</div>
            </div>
        </div>
    </div>
</div>
</body>
<script type="text/javascript">
$(document).ready(function(){
    $(document).on('blur', 'textarea', function(){
        window.scroll(0,0)
    })

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
		$.post("{pigcms{:U('My/add_comment')}", postData, function(data){
			if (data.status == 1) {
                layer.open({title:["{pigcms{:L_('评论提示')}：",'background-color:#FF658E;color:#fff;'],content:''+data.msg+'',btn: ["{pigcms{:L_('确定')}"],end:function(){
					var stateObj = { foo: data.url };
					history.replaceState(stateObj, "", data.url);
					location.reload();
				}});
            } else {
            	layer.open({title:["{pigcms{:L_('评论提示')}：",'background-color:#FF658E;color:#fff;'],content:''+data.msg+'',btn: ["{pigcms{:L_('确定')}"]});
            }
		}, 'json');
	});

    $(".add-list span").click(function(){
        $(this).toggleClass("select");
    });
});
</script>
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
                    if (version >= 1000) {
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
</html>