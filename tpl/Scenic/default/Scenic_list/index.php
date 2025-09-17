<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--景区列表-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Index/index')}">网站首页</a> > <span class="now">景点门票</span>
			</div>
			<div class="tklist_end clr">
				<div class="fl tkend_l">
					<ul class="clr">
						<li onclick="order(1)" <if condition="$_GET['order'] eq 1 OR !$_GET['order']">class="on"</if>>推荐</li>
						<li onclick="order(3)" <if condition="$_GET['order'] eq 3">class="on pop"<else/>class="pop"</if>>人气最高</li>
						<li onclick="order(4)" <if condition="$_GET['order'] eq 4">class="on pop"<else/>class="pop"</if>>销量最高</li>
					</ul>
				</div>
			</div>
			<div class="ticklists">
				<ul>
					<volist name="scenic_list" id="vo">
						<li class="list_li">
							<div class="tick_top clr">
								<div class="fl">
									<a href="{pigcms{$vo['url']}"><img src="{pigcms{$vo['pic']}" width=410 height=230></a>
								</div>
								<div class="p432 clr">
									<div class="fl">
										<h5>{pigcms{$vo['province_id']}-{pigcms{$vo['city_id']}</h5>
										<a href="{pigcms{$vo['url']}"><h2>{pigcms{$vo['scenic_title']}</h2></a>
										<div class="score">
											<div class="atar_Show">
												<p></p>
											</div>
											<span>{pigcms{$vo['score_mean']}</span>分
										</div>
										<div class="address">
											<span>地址: {pigcms{$vo['province_id']}{pigcms{$vo['city_id']}{pigcms{$vo['scenic_address']}</span>
<!--											<a href="#">查看地图</a>-->
										</div>
										<div class="p432list">
											<ul class="clr">
												<if condition="$vo['is_guide'] eq 1"><li class="gui">景区导游</li></if>
												<if condition="$vo['is_broadcast'] eq 1"><li class="trip">智慧行程</li></if>
												<if condition="$vo['is_parking'] eq 1"><li class="stop">智能停车</li></if>
											</ul>
										</div>
									</div>
									<div class="fr book">
										<div class="fr_top">
											<div>￥<span>{pigcms{$vo['money']}</span>起</div>
											<if condition="$vo['scenic_ticket']"><a href="{pigcms{$vo['url']}">立即预订</a></if>
										</div>
									</div>
								</div>
							</div>
							<if condition="$vo['scenic_ticket']">
								<div class="tick_end">
									<dl>
										<dt>
											<ul class="clr">
												<li class="w50">景点门票</li>
												<li class="w20">原价</li>
												<li class="w20">现价</li>
											</ul>
										</dt>
										<volist name="vo['scenic_ticket']" id="voo">
											<dd>
												<ul class="clr">
													<li class="w50">【{pigcms{$voo['children']}】{pigcms{$voo['ticket_title']}</li>
													<li class="w20"><del>￥{pigcms{$voo['old_price']}</del></li>
													<li class="w20 fe">￥<span>{pigcms{$voo['ticket_price']}</span></li>
													<li class="li_right"><span>在线支付</span><a href="{pigcms{$voo['url']}">立即预订</a></li>
												</ul>
											</dd>
										</volist>
									</dl>
									<a href="javascript:void(0)" class="more">
										<span>查看全部</span>
										<i>(6)</i>
									</a>
								</div>
							</if>
						</li>
					</volist>
				</ul>
			</div>
			<style type="text/css">
				.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
			</style>
			<div class="pages">
				<span class="num">{pigcms{$pagebar}</span>
			</div>
		</div>
	</section>
<script type="text/javascript">
	/*筛选*/
	$(".tkend_r li").click(function(){
		if($(this).hasClass("on")){
			$(this).removeClass("on")
		}else{
			$(this).addClass("on");
		}
	})
	/*评分*/
	$(".atar_Show p").each(function(index, element){
		var num=$(this).parents(".score").find("span").text();
		var www=num*13;//
		$(this).css("width",www);
	});
	/*展开 收起*/
	$(".tick_end dl").each(function(){
		if($(this).find("dd").length > 3){
			$(this).siblings("a.more").show();
			$(this).addClass("showMore");
			var index=$(this).find("dd").index();
			$(this).siblings("a.more").find("i").text("("+index+")");
		}else{
			$(this).siblings("a.more").hide();
			$(this).find("dd:last-child ul").css("border-bottom","0px");
		}
	})
	$(".tick_end a.more").toggle(function(){
		$(this).addClass("morelv").find("span").text("收起更多");
		$(this).siblings("dl").addClass("hasMore");
	},function(){
		$(this).removeClass("morelv").find("span").text("展开更多");
		$(this).siblings("dl").removeClass("hasMore");
	})
	function order(order){
		window.location.href	=	'{pigcms{$site_url}'+'scenic.php?c=Scenic_list&a=index&order='+order;
	}
</script>
<include file="Public/footer"/>