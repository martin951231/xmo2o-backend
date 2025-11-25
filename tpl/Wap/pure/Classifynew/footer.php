<div id="backtotop" class="backtotop"> 
	<span class="icon-vertical-align-top">
		<img class="back_top" src="{pigcms{$static_path}classifynew/images/back_top.png"/>
	</span>
</div>
<script>
function get_currenturl() {
	var loc = location.href.split('#')[0];
	return loc;
}
function Anmi(obj, x, fast, callback) {
	var ani = ' animated';
	if(fast == 'fast'){
		ani = ' animated-fast';
	}
	obj.removeClass().addClass(x + ani).one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
		jQuery(this).removeClass();
		if(callback && typeof callback == 'function') {
			callback();
		}
	});
}
jQuery(function(){
	var f_iosActionsheet = jQuery('#f_iosActionsheet');
	var f_iosMask = jQuery('#f_iosMask');
	function f_hideActionSheet() {
		f_iosActionsheet.removeClass('weui-actionsheet_toggle');
		f_iosMask.fadeOut(200);
	}
	f_iosMask.on('click', f_hideActionSheet);
	jQuery('#f_iosActionsheetCancel').on('click', f_hideActionSheet);
	jQuery(" .f_sharebtn").removeAttr('onclick').unbind( "click").unbind("touchstart").unbind("touchend").on("click", function(){
		f_iosActionsheet.addClass('weui-actionsheet_toggle');
		f_iosMask.fadeIn(200);
	});
});

</script>
<script>
	var wechatbtn = jQuery('.weixin,.weixin_timeline');
	var wechatmask = jQuery('#wechat-masker');
	wechatmask.find('div').hide();
	
	var wechatguider = jQuery('#other-guider');
	var showmethd = 'fadeInDown';
	
	wechatguider.show();

	wechatbtn.on('click',function(){
		wechatmask.show();
		Anmi(wechatguider, showmethd, 'fast');
		Anmi(wechatmask, 'fadeIn', 'fast');
	});
	wechatmask.on('click',function(){
		Anmi(wechatguider, 'fadeOutUp', 'normal');
		Anmi(wechatmask, 'fadeOut', 'normal', function(){
			wechatmask.hide();
		});
	});
</script>
<if condition="$config['is_demo_domain']">
	<script>
		function demoDomain_tip(text,title){
			$.alert(text+"<br/><br/><div style='font-size:12px;letter-spacing:1px;color:#ccc;'>此提醒仅小猪O2O演示站可见</div>", title);
		}
	</script>
</if>
<div id="wechat-mask"><div id="wechat-guider"></div></div>
<div class="mask none" style="touch-action: none;"></div>
<script src="{pigcms{$static_path}classifynew/js/fastclick.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}classifynew/js/slider.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}classifynew/js/swiper.min.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}classifynew/js/md5.min.js" type="text/javascript"></script>
<script src="{pigcms{$static_path}classifynew/js/app.js?t={pigcms{:time()}" type="text/javascript"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>
<script>
function hb_dig(id){
	$.actions({
		title: '请选择置顶类型',
		actions: [            
			{text: "置顶一天(收费{pigcms{$config['classify_settop_day_money']}{pigcms{$config['Currency_txt']})", onClick: function () {
				confirm_top({pigcms{$config['classify_settop_day_money']|floatval},1,id);
			}},
			{text: "置顶一周(收费{pigcms{$config['classify_settop_day_money']*7}{pigcms{$config['Currency_txt']})", onClick: function () {;
				confirm_top({pigcms{$config['classify_settop_day_money']*7|floatval},7,id);				
			}},
			{text: "置顶15天(收费{pigcms{$config['classify_settop_day_money']*15}{pigcms{$config['Currency_txt']})", onClick: function () {confirm_top({pigcms{$config['classify_settop_day_money']*15|floatval},15,id);
			}},
			{text: "置顶一月(收费{pigcms{$config['classify_settop_day_money']*30}{pigcms{$config['Currency_txt']})", onClick: function () {
				confirm_top({pigcms{$config['classify_settop_day_money']*30|floatval},30,id);
			}},
		]
	});
}
//确认置顶
function confirm_top(price,type,id){
	price=price || 0;
	if(isNaN(price) || price<=0){
		return false;
	}
	console.log(price,type,id);
	$.confirm({
		title: '开启置顶',
		text: '开启置顶需要平台余额支付<strong class="amount">'+price+'</strong>{pigcms{$config['Currency_txt']}<br>您确认要置顶选定的信息吗?',
		onOK: function () {
			hb_jump('{pigcms{:U('settop')}&digtype='+type+'&id=' + id+_URLEXT);
		}
	});
}
function hb_hbchoice(id){
	$.actions({
		title: '请选择红包金额',
		actions: [            
			{text: "1{pigcms{$config['Currency_txt']}分5包",onClick: function() { 
				confirm_red_bag(1,id);
			}},
			{text: "5{pigcms{$config['Currency_txt']}分8包",onClick: function() {
				confirm_red_bag(5,id);
			}},
			{text: "10{pigcms{$config['Currency_txt']}分20包",onClick: function() { 
				confirm_red_bag(10,id);
			}},
			{text: "50{pigcms{$config['Currency_txt']}分100包",onClick: function() {
				confirm_red_bag(50,id);
			}},
		]
	});
}
//确认红包
function confirm_red_bag(redpack_money,id){
	redpack_money=redpack_money || 1;
	console.log(redpack_money,id);
	$.confirm({
		title: '发布红包',
		text: '发布红包需要平台余额支付<strong class="amount">'+redpack_money+'</strong>{pigcms{$config['Currency_txt']}<br>您确认要发布红包吗?',
		onOK: function () {
			hb_jump('{pigcms{:U('redpack')}&redpack_money='+redpack_money+'&id='+id+_URLEXT);
		}
	});
}
function hb_shuaxin(id){
	var classify_refresh_money = {pigcms{$config.classify_refresh_money|floatval=###};
	if(classify_refresh_money > 0){
		$.confirm({
			title: '刷新信息',
			text: '刷新信息需要支付<strong class="amount">'+classify_refresh_money+'</strong>{pigcms{$config['Currency_txt']}<br>您确认要刷新选定的信息吗?',
			onOK: function () {
				hb_jump('{pigcms{:U('refresh')}&id=' + id+_URLEXT);
			}
		});
	}else{
		hb_jump('{pigcms{:U('refresh')}&id=' + id+_URLEXT);
	}
}
function hb_paytel(id, pri, pricat){
	$.confirm({
		title: '查看联系方式',
		text: '查看联系方式需支付<strong class="amount">'+pri+'</strong>{pigcms{$config['Currency_txt']}<br>确认支付？',
		onOK: function () {
			hb_jump('{pigcms{:U('paytel')}&cat='+pricat+'&id=' + id+_URLEXT);
		}
	});
}
function showansi(obj) {
	var act = [], that = $(obj);
	var catid = that.data('catid'), id= that.data('id');

	if(catid){
		// act.push({
			// text: '立即支付', onClick: function () {
				// hb_jump('');
			// }
		// });
	}else {
		if(that.data('canhb')){

            var classify_hongbao = "{pigcms{$config['classify_hongbao']}";
            if (classify_hongbao && 1==classify_hongbao) {
                act.push({
                    text: '红包扩散', onClick: function () {
                        $.actions({
                            title: '请选择红包金额',
                            actions: [
                                {text: "1{pigcms{$config['Currency_txt']}分5包",onClick: function() {
                                        hb_jump('{pigcms{:U('redpack')}&redpack_money=1&id='+id+_URLEXT);
                                    }},
                                {text: "5{pigcms{$config['Currency_txt']}分8包",onClick: function() {
                                        hb_jump('{pigcms{:U('redpack')}&redpack_money=5&id='+id+_URLEXT);
                                    }},
                                {text: "10{pigcms{$config['Currency_txt']}分20包",onClick: function() {
                                        hb_jump('{pigcms{:U('redpack')}&redpack_money=10&id='+id+_URLEXT);
                                    }},
                                {text: "50{pigcms{$config['Currency_txt']}分100包",onClick: function() {
                                        hb_jump('{pigcms{:U('redpack')}&redpack_money=50&id='+id+_URLEXT);
                                    }},
                            ]
                        });
                    }
                });
            }
		}
		act.push({
			text: '分享扩散', onClick: function () {
				localStorage.setItem('wetip_'+id, 1);
				hb_jump('{pigcms{:U('view')}&id='+id+_URLEXT);
			}
		});
		if(that.data('canzd')){
			act.push({
				text: '置顶扩散', onClick: function () {
					$.actions({
						title: '请选择置顶类型',
						actions: [
							{
								text: "置顶一天(收费{pigcms{$config['classify_settop_day_money']}{pigcms{$config['Currency_txt']})", onClick: function () {
									confirm_top({pigcms{$config['classify_settop_day_money']},1,id);
								}
							},
							{
								text: "置顶一周(收费{pigcms{$config['classify_settop_day_money']*7}{pigcms{$config['Currency_txt']})", onClick: function () {
									confirm_top({pigcms{$config['classify_settop_day_money']*7},7,id);
								}
							},
							{
								text: "置顶15天(收费{pigcms{$config['classify_settop_day_money']*15}{pigcms{$config['Currency_txt']})", onClick: function () {
									confirm_top({pigcms{$config['classify_settop_day_money']*15},15,id);
								}
							},
							{
								text: "置顶一月(收费{pigcms{$config['classify_settop_day_money']*30}{pigcms{$config['Currency_txt']})", onClick: function () {
									confirm_top({pigcms{$config['classify_settop_day_money']*30},30,id);
								}
							},
						]
					});
				}
			});
		}
	}
	if(that.data('canzd')) {
		act.push({
			text: '刷新信息', onClick: function () {
				var classify_refresh_money = {pigcms{$config.classify_refresh_money|floatval=###};
				if(classify_refresh_money > 0){
					$.confirm({
						title: '刷新信息',
						text: '刷新信息需要支付<strong class="amount">'+classify_refresh_money+'</strong>{pigcms{$config['Currency_txt']}<br>您确认要刷新选定的信息吗?',
						onOK: function () {
							hb_jump('{pigcms{:U('refresh')}&id=' + id+_URLEXT);
						}
					});
				}else{
					hb_jump('{pigcms{:U('refresh')}&id=' + id+_URLEXT);
				}
			}
		});
	}

	
	act.push({
		text: '编辑', onClick: function () {
			hb_jump('{pigcms{:U('edit')}&id='+id+_URLEXT);
		}
	});
	act.push({
		text: '删除', onClick: function () {
			confirm_del('删除无法恢复，确认删除?', '{pigcms{:U('del')}&id='+id+_URLEXT, id);
		}
	});
	$.actions({
		title: '信息ID: '+id,
		actions: act,
        onClose: function() {
            $('html').removeClass('ovfHiden');
        }
	});
	hb_setcookie('disable_'+id, 1, 86400);
    $('html').addClass('ovfHiden');
	return false;
}
$(document).on('click', '.weui-mask', function () { 
    $('html').removeClass('ovfHiden');
})
</script>
<div class="swiper-container global-lightbox animated" id="globalLightbox">
	<div class="swiper-wrapper" id="globalWrapper">

	</div>
	<div class="swiper-pagination lightbox-pagination"></div>
	<a class="iconfont icon-guanbijiantou closeLightbox"> </a>
</div>
<if condition="$is_weixin_browser">
	<script>
		if(typeof wx !=='undefined'){
			wx.ready(function () {
				if (window.__wxjs_environment === 'miniprogram') {
					$(document).on('click', 'a', function () {
						var that = $(this);
						var jmpurl = that.attr('href');
						
						if(that.hasClass('weui-tabbar__item') && $(this).closest('.weui-tabbar').size() > 0){
							var stateObj = { foo: jmpurl };
							history.replaceState(stateObj, "", jmpurl);
							location.reload();
							return false;
						}
						
						if (jmpurl && jmpurl.indexOf('tel') === -1 && jmpurl.indexOf('sms') === -1 && jmpurl.indexOf('java') === -1 && jmpurl.indexOf("{pigcms{:U('Classifynew/index')}") === -1 && jmpurl.indexOf('&city=') === -1) {
							if (jmpurl.indexOf('https://') === -1 && jmpurl.indexOf('http://') === -1) {
								jmpurl = GSITE + jmpurl;
							}
							jmpurl = jmpurl.replace('http://', 'https://');
							if(typeof wx.miniProgram.navigateTo!=='undefined'){
								wx.miniProgram.navigateTo({ url: '/pages/webview/webview?webview_url=' + encodeURIComponent(jmpurl)+'&webview_title='+encodeURIComponent(_APPTITLE)});
								return false;
							}
						}
					});
					hb_setcookie('miniprogram', 1, 120);
					$('#fav_guide_mask').hide();
					$('.g_guide').remove();
				}else{
					hb_setcookie('miniprogram', '', 0);
				}
			});
		}
	</script>
</if>