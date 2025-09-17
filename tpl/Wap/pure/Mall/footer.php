<div>
	<if condition="(MODULE_NAME neq 'Mall' or (ACTION_NAME neq 'category' and ACTION_NAME neq 'cart' and ACTION_NAME neq 'myCenter')) AND $config['mall_footer_position'] || ($is_right==1 && $is_app_browser)"> 
		<style>
			.right-mask{position: fixed;top: 0;left: 0;right: 0;bottom: 0;z-index: 10000;background: rgba(0, 0, 0, .6);display: none;}
			.right-mask.active{display: block;}
			.flex{display: -webkit-box;display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;display:flex;}
			.flex-align-center{-webkit-box-align: center;-moz-box-align: center;-ms-flex-align: center;-webkit-align-items: center;align-items: center;}
			.fixed-right{position: fixed;right: -60px;top: 50%;z-index: 11000;transition: all .3s;}
			.fixed-right.active{right: 0;}
			.fixed-right .fixed-icon{width: 20px;height: auto;}
			.fixed-right .fixed-icon img{width: 100%;}
			.fixed-right ul{width: 60px;background: #fff;border-radius: 10px 0 0 10px;}
			.fixed-right ul li {padding: 5px 0;}
			.fixed-right ul li a.active {color: #3cc1ae;}
			.fixed-right ul li a p {text-align: center;font-size: 12px;}
			.fixed-right ul li a em {margin: 0 auto;display: block;height: 20px;width: 22px;margin-top: 3px;margin-bottom: 2px;background-repeat: no-repeat;background-size: 100%;border-bottom: 0px;}
			.fixed-right ul li a.active em.home, .fixed-right ul li a.hover em.home {background-image: url(../tpl/Wap/pure/static/mall/images/footer_home_active.png);}
			.fixed-right ul li a em.home {background-image: url(../tpl/Wap/pure/static/mall/images/footer_home.png);}
			.fixed-right ul li a.active em.store, .fixed-right ul li a.hover em.store {background-image: url(../tpl/Wap/pure/static/mall/images/footer_store_active.png);}
			.fixed-right ul li a em.store {background-image: url(../tpl/Wap/pure/static/mall/images/footer_store.png);}
			.fixed-right ul li a em.group {background-image: url(../tpl/Wap/pure/static/mall/images/footer_group.png);}
			.fixed-right ul li a.active em.group, .fixed-right ul li a.hover em.group {background-image: url(../tpl/Wap/pure/static/mall/images/footer_group_active.png);}
			.fixed-right ul li a em.my {background-image: url(../tpl/Wap/pure/static/mall/images/footer_my.png);}
			.fixed-right ul li a.active em.my {background-image: url(../tpl/Wap/pure/static/mall/images/footer_my_active.png);}
			.fixed-right ul li a em.my_act {background-image: url(../tpl/Wap/pure/static/mall/images/my_act.png);}
			.fixed-right ul li a.active em.my_act {background-image: url(../tpl/Wap/pure/static/mall/images/my_act_active.png);} 
		</style>
		<div class="right-mask"></div>
		<div class="flex flex-align-center fixed-right">
			<div class="fixed-icon"><img src="../tpl/Wap/pure/static/mall/images/icon_shop_arrow.png" alt=""></div>
			<ul class="home_li">
				<!--<li >
					<a <if condition="MODULE_NAME eq 'Mall' and ACTION_NAME eq 'home'"> class="active" </if>href="{pigcms{:U('Mall/home')}" target="self"><em class="home"></em><p>首页</p></a>
				</li>-->
				<li>
					<a <if condition="ACTION_NAME eq 'category'"> class="active" </if>href="{pigcms{:U('Mall/category')}" target="self"><em class="store"></em><p>分类</p></a>
				</li>
				<li>
					<a <if condition="ACTION_NAME eq 'cart'"> class="active" </if>href="{pigcms{:U('Mall/cart')}" target="self"><em class="group"></em><label class="cart_num"></label><p>购物车</p></a>
				</li>
				<li>
					<a <if condition="MODULE_NAME eq 'Mall' and ACTION_NAME eq 'myCenter'"> class="active" </if> href="{pigcms{:U('Mall/myCenter',array('from'=>'mall'))}" target="self"><em class="my"></em><p>我的</p></a>
				</li>
			</ul>
		</div>
		<script>
			$(document).on('click', '.right-mask, .fixed-right .fixed-icon', function(){
				if ($('.right-mask').is(':hidden')) {
					$('.right-mask, .fixed-right').addClass('active')
				} else {
					$('.right-mask, .fixed-right').removeClass('active')
				}
			})
		</script>
	<elseif condition="MODULE_NAME neq 'Mall' or !$config['mall_footer_position'] or (ACTION_NAME neq 'category' and ACTION_NAME neq 'cart' and ACTION_NAME neq 'myCenter')"/>
		<footer class="footerMenu wap">
			<ul class="home_li">
				<li >
					<a <if condition="MODULE_NAME eq 'Mall' and ACTION_NAME eq 'home'"> class="active" </if>href="{pigcms{:U('Mall/home')}" target="self"><em class="home"></em><p>首页</p></a>
				</li>
				<li>
					<a <if condition="ACTION_NAME eq 'category'"> class="active" </if>href="{pigcms{:U('Mall/category')}" target="self"><em class="store"></em><p>分类</p></a>
				</li>
				<li>
					<a <if condition="ACTION_NAME eq 'cart'"> class="active" </if>href="{pigcms{:U('Mall/cart')}" target="self"><em class="group "></em><label class="cart_num"> </label><p>购物车</p></a>
				</li>
				<li>
					<a <if condition="MODULE_NAME eq 'Mall' and ACTION_NAME eq 'myCenter'"> class="active" </if> href="{pigcms{:U('Mall/myCenter',array('from'=>'mall'))}" target="self"><em class="my"></em><p>我的</p></a>
				</li>
			</ul>
		</footer>
	</if>
</div>
<script>
	var cart_init_number = {pigcms{$cart_init_number};
	if(cart_init_number > 99){
		cart_init_number = String(cart_init_number).substring(2,0)
		$('.cart_num').html(cart_init_number + '+')
	}else if(cart_init_number > 0){
		$('.cart_num').html(cart_init_number)
		if(cart_init_number < 10){
			$('.cart_num').css('right', '27px');
		}
	}else{
		$('.cart_num').remove();
	}
	
	 
</script>