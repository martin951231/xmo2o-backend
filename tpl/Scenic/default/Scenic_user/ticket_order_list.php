<include file="Public/header"/>
<include file="Public/header_user"/>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp on">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('guide_order')}">我是向导订单</a>
					</li>
					<li class="yb">
						<a href="{pigcms{:U('mate')}">我的游伴</a>
					</li>
					<li class="cx">
						<a href="{pigcms{:U('travel_person')}">出行人信息</a>
					</li>
					<li class="xc">
						<a href="{pigcms{:U('manager_travel')}">我的行程</a>
					</li>
					<li class="sc">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
			<div class="fr navright">
				<div class="allorder">
					<div class="allorder_top">
						<ul class="clr">
							<li <if condition="empty($_GET['status'])">class="on"</if>>
								<a href="{pigcms{:U('ticket_order_list')}">全部订单({pigcms{$scenic_totle_count})</a>
							</li>
							<li <if condition="$_GET['status'] eq 1">class="on"</if>>
								<a href="{pigcms{:U('ticket_order_list',array('status'=>1))}">待付款({pigcms{$pay_count})</a>
							</li>
							<li <if condition="$_GET['status'] eq 2">class="on"</if>>
								<a href="{pigcms{:U('ticket_order_list',array('status'=>2))}">待出行({pigcms{$travel_count})</a>
							</li>
							<li <if condition="$_GET['status'] eq 3">class="on"</if>>
								<a href="{pigcms{:U('ticket_order_list',array('status'=>3))}">待评论({pigcms{$comment_count})</a>
							</li>
						</ul>
					</div>
					<div class="allorder_end">
						<dl>
							<dt class="clr">
								<div class="w420 tab">订单信息</div>
								<div class="w125 tab">游玩日期</div>
								<div class="w100 tab">数量</div>
								<div class="w100 tab">总价</div>
								<div class="w95 tab">订单状态</div>
								<div class="w95 tab">操作</div>
							</dt>
							<volist name="scenic_order" id="vo">
								<dd>
									<div class="dd_top">
										<ul class="clr">
											<if condition="$vo['order_imm']"><li><span>订单编号：</span>{pigcms{$vo['order_imm']}</li></if>
											<li><span>下单时间：</span>{pigcms{$vo['add_time']}</li>
											<if condition="$vo['guide_count']"><li class="guide">含导游</li></if>
											<if condition="$vo['park_count']"><li class="guide">含车位</li></if>
											<if condition="$vo['mate_count']"><li class="guide">需游伴</li></if>
										</ul>
									</div>
									<div class="dd_end clr">
										<div class="w420 fl">
											<div class="img fl">
												<img src="{pigcms{$vo['pic']}" width="110" height="62">
											</div>
											<p class="p123">{pigcms{$vo['scenic_list']['scenic_title']}</p>
										</div>
										<div class="w125 tab ari">{pigcms{$vo['ticket_time']}</div>
										<div class="w100 tab ari">{pigcms{$vo['ticket_count']}</div>
										<div class="w100 tab ari fe">￥<i>{pigcms{$vo['order_total']}</i></div>
										<div class="w95 tab qx">
											<if condition="$vo['order_status'] eq 0"><a href="{pigcms{$vo['pay_url']}" style="color:red;">去支付</a>
											<elseif condition="$vo['order_status'] eq 1"/><span style="color:red;">未消费</span>
											<elseif condition="$vo['order_status'] eq 2"/><a href="{pigcms{:U('comment',array('order_id'=>$vo['order_id']))}" style="color:red;">去评论</a>
											<elseif condition="$vo['order_status'] eq 3"/><span style="color:green;">已完成</span>
											<elseif condition="$vo['order_status'] eq 4"/><span style="color:gray;">已退款</span>
											<elseif condition="$vo['order_status'] eq 5"/><span style="color:gray;">已过期</span>
											<elseif condition="$vo['order_status'] eq 6"/><span style="color:gray;">已过期</span></if>
										</div>
										<div class="w95 tab fe9"><a href="{pigcms{$vo['details_url']}">查看详情</a></div>
									</div>
								</dd>
							</volist>
						</dl>
						<style type="text/css">
							.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
						</style>
						<div class="pages">
							<span class="num">{pigcms{$pagebar}</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
<include file="Public/footer"/>