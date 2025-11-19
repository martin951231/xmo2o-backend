<include file="Public/header"/>
	<section class="search_ny">
		<div class="w1200 clr">
			<div class="fl">
				<div class="logo fl">
					<a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
				</div>
			</div>
			<div class="fl">
				<ul class="clr">
					<li>
						<a href="{pigcms{:U('Index/index')}">网站首页</a>
					</li>
					<li>
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
		</div>
	</section>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl detail">
				<div class="tklist_top clr">
					<div class="fl">
						<span>您当前位置：</span><a href="{pigcms{:U('Index/index')}">首页</a> &#62 <a href="{pigcms{:U('Scenic_user/ticket_order_list')}">个人中心</a> &#62 <span class="now">门票订单</span>
					</div>
				</div>
				<div class="detail_n">
					<div class="title clr" style="padding-bottom:10px;">
						<div class="img fl">
							<img src="{pigcms{$scenic_list['pic']}" width="206" height="115">
						</div>
						<div class="p226">
							<h2>{pigcms{$scenic_list['scenic_title']}</h2>
							<div class="tit_list">
								<span class="fl">门票单价：</span>
								<p class="p75">￥{pigcms{$ticket[0]['price']}</p>
							</div>
							<div class="tit_list">
								<span class="fl">购买数量：</span>
								<p class="p75">{pigcms{$arr.ticket_count}</p>
							</div>
							<div class="tit_list">
								<span class="fl">下单日期：</span>
								<p class="p75">{pigcms{$user_order['add_time']}</p>
							</div>
						</div>
					</div>
					<div class="travel">
						<h2 class="tit">出行人信息</h2>
						<ul class="tra_list">
							<volist name="family" id="vv">
								<li>
									<h2>出行人{pigcms{$key+1}</h2>
									<div class="clr">
										<div class="fl w228">姓 名：{pigcms{$vv['family_name']}</div>
										<div class="fl w345">身份证号：{pigcms{$vv['certificates']}</div>
										<div class="fl w150">验证码：{pigcms{$ticket[$key]['code']}</div>
										<if condition="$ticket[$key]['status'] eq 2"><div class="fl w130 on"><span>已入园</span></div><elseif condition="$ticket[$key]['status'] eq 1"/><div class="fl w130 off"><span>未入园</span></div></if>
									</div>
								</li>
							</volist>
						</ul>
					</div>
					<div class="door">
						<h2 class="tit">门票信息</h2>
						<div class="door_n">
							<h2>{pigcms{$scenic_ticket['ticket_title']}</h2>
							<dl>
								<dd>
                                    <span class="fl">有效日期</span>
                                    <div class="p70">{pigcms{$user_order['ticket_time']} 当日</div>
                                </dd>
                                <dd>
                                    <span class="fl">入园提示</span>
                                    <div class="p70">{pigcms{$scenic_ticket['park_intr']}</div>
                                </dd>
                                <dd style="border-bottom: #d5d5d5 0px dashed;">
                                    <span class="fl">入园方式</span>
                                    <div class="p70">{pigcms{$scenic_ticket['ticket_cue']}</div>
                                </dd>
							</dl>
						</div>
					</div>
					<if condition="$park">
						<div class="stopcar">
							<h2 class="tit">停车位信息</h2>
							<div class="stopcar_n">
								<h2>车牌号</h2>
								<div class="stop_list clr" style="border-bottom: #d5d5d5 0px dashed;">
									<div class="fl span_list">
										<volist name="park" id="vo"><span>{pigcms{$vo['code']}</span><if condition="($key+1) neq $arr['prak_count']">&nbsp;/&nbsp;</if></volist>
									</div>
									<div class="fr price">
										<span>￥<i>{pigcms{$scenic_list['parking_price']}</i> x{pigcms{$arr['prak_count']}</span>
									</div>
								</div>
								<!--<div class="map">
									<img src="images/dt_03.jpg" width="425px" height="280px">
								</div>-->
							</div>
						</div>
					</if>
					<if condition="$scenic_guide">
						<div class="guide">
							<h2 class="tit">景内导游信息</h2>
							<div class="guide_n clr">
								<div class="fl">
									<div class="fl img">
										<img src="{pigcms{$scenic_guide['pic']}" width="98" height="98">
									</div>
									<div class="p130 name">
										<div class="name_top">
											<span>{pigcms{$scenic_guide['guide_name']}</span><i <if condition="$scenic_guide['guide_gender'] eq 1">class="on"</if>></i>
										</div>
										<div class="name_list clr">
											<div class="name_end fl">
												<span class="fl">服务次数：</span>
												<p class="p2">共讲解<i>{pigcms{$scenic_guide['guide_service_number']}</i>次</p>
											</div>
										</div>
									</div>
								</div>
								<div class="fr feprice">￥<span>{pigcms{$scenic_list['guide_price']}</span></div>
							</div>
						</div>
					</if>
					<div class="paid clr">
						<div class="fr total">
							<span>实付总额</span>
							<i>￥<em>{pigcms{$arr.total_price}</em></i>
						</div>
					</div>
				</div>
			</div>
			<div class="fr number">
				<div class="number_top">订单编号：{pigcms{$user_order['order_imm']}</div>
				<div class="number_end">
					<if condition="$user_order['order_status'] eq 0 && $user_order['paid'] eq 1"><h2>未支付</h2><a href="{pigcms{:U('Scenic_pay/index',array('order_id'=>$user_order['order_id']))}">去支付</a>
						<elseif condition="$user_order['order_status'] eq 1 && $user_order['paid'] eq 2" /><h2>已支付 未入园</h2>
						<elseif condition="$user_order['order_status'] eq 2 && $user_order['paid'] eq 2" /><h2>已消费 未评论</h2><a href="{pigcms{:U('Scenic_user/comment',array('order_id'=>$user_order['order_id']))}">去评价</a>
						<elseif condition="$user_order['order_status'] eq 3 && $user_order['paid'] eq 2" /><h2>已评论 已完成</h2>
						<elseif condition="$user_order['order_status'] eq 4 && $user_order['paid'] eq 2" /><h2>已退款</h2>
						<elseif condition="$user_order['order_status'] eq 5 && $user_order['paid'] eq 1" /><h2>已过期 未付款</h2>
						<elseif condition="$user_order['order_status'] eq 6 && $user_order['paid'] eq 2" /><h2>已过期 未入园</h2>
					</if>
					<p><i></i>热线电话 <span>{pigcms{$scenic_list['scenic_phone']}</span></p>
				</div>
			</div>
		</div>
	</section>
<script type="text/javascript">
	/*评分*/
	$(".atar_Show p").each(function(index, element){
		var num=$(this).parents(".score").find("span").text();
		var www=num*13;
		$(this).css("width",www);
	});
</script>
<include file="Public/footer"/>