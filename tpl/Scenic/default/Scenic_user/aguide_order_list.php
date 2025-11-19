<include file="Public/header"/>
<include file="Public/header_user"/>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy on">
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
                            <li <if condition="empty($_GET['rela_status'])">class="on"</if> >
                                <a href="{pigcms{:U('Scenic_user/aguide_order_list')}">全部订单({pigcms{$arr['number_all']})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 10">class="on"</if> >
                                <a href="{pigcms{:U('Scenic_user/aguide_order_list',array('rela_status'=>10))}">未支付({pigcms{$arr['number_0']})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 1">class="on"</if> >
                                <a href="{pigcms{:U('Scenic_user/aguide_order_list',array('rela_status'=>1))}">未开始({pigcms{$arr['number_1']})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 2">class="on"</if> >
                                <a href="{pigcms{:U('Scenic_user/aguide_order_list',array('rela_status'=>2))}">未评价({pigcms{$arr['number_2']})</a>
                            </li>
                        </ul>
                    </div>
                    <div class="allorder_end conduct_end">
                        <dl>
                            <dt class="clr">
                                <div class="w160 fl">订单信息</div>
                                <div class="w230 fl">预定日期</div>
                                <div class="w80 fl">出行人数</div>
                                <div class="w225 fl">出行人信息</div>
                                <div class="w110 fl">总价</div>
                                <div class="w110 fl">订单状态</div>
                            </dt>
                            <volist name="user_order" id="vo">
	                            <dd>
	                                <div class="dd_top">
	                                    <ul class="clr">
	                                        <li><span>下单时间：</span>{pigcms{$vo['add_time']}</li>
	                                    </ul>
	                                </div>
	                                <div class="dd_end clr">
	                                    <div class="w160 fl">
	                                        <div class="img fl">
	                                            <img src="{pigcms{$vo['user']['avatar']}" width="56" height="56">
	                                        </div>
	                                        <p class="p68">{pigcms{$vo['guide_user']['guide_name']}</p>
	                                    </div>
	                                    <div class="w230 fl ari">
	                                        <p>{pigcms{$vo['start_time']} 至 {pigcms{$vo['end_time']}</p>
	                                        <p class="c3f">共{pigcms{$vo['number']}天</p>
	                                    </div>
	                                    <div class="w80 fl ari">1</div>
	                                    <div class="w225 fl">
	                                        <ul>
	                                            <volist name="vo.family" id="vvo">
	                                                <li>{pigcms{$vvo['family_name']} ({pigcms{$vvo['certificates']})</li>
	                                            </volist>
	                                        </ul>
	                                        <a href="javascript:void(0)" class="more">展开全部</a>
	                                    </div>
	                                    <div class="w110 fl fe">￥<i class="ari">{pigcms{$vo['total_price']}</i></div>
	                                    <if condition="$vo['rela_status'] eq 0"><a href="javascript:void(0)"><div class="w110 fl fe">去支付</div></a>
		                                    <elseif condition="$vo['rela_status'] eq 1"/><div class="w110 fl fe">未服务</div>
		                                    <elseif condition="$vo['rela_status'] eq 2"/><a href="{pigcms{:U('Scenic_user/aguide_comment',array('order_id'=>$vo['order_id']))}"><div class="w110 fl fe">去评价</div></a>
		                                    <elseif condition="$vo['rela_status'] eq 3"/><div class="w110 fl ks">已完成</div><else/><div class="w110 fl wc">已过期</div>
	                                    </if>
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
<script type="text/javascript">
	/*展开 收起*/
	$(".dd_end .w225 ul").each(function(){
		if($(this).find("li").length > 2){
			$(this).addClass("showMore").siblings("a.more").show();
		}else{
			$(this).siblings("a.more").hide();
		}
	})
	$(".dd_end .w225 a.more").toggle(function(){
		$(this).text("收起更多");
		$(this).siblings("ul").addClass("hasMore")
	},function(){
		$(this).text("展开更多");
		$(this).siblings("ul").removeClass("hasMore")
	})
</script>
<include file="Public/footer"/>