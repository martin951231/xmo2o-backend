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
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd on">
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
                            <li <if condition="!$_GET['rela_status']">class="on"</if>>
                                <a href="{pigcms{:U('guide_order')}">全部订单({pigcms{$count[0]})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 1">class="on"</if>>
                                <a href="{pigcms{:U('guide_order',array('rela_status'=>1))}">未服务({pigcms{$count[1]})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 2">class="on"</if>>
                                <a href="{pigcms{:U('guide_order',array('rela_status'=>2))}">待评价({pigcms{$count[2]})</a>
                            </li>
                            <li <if condition="$_GET['rela_status'] eq 3">class="on"</if>>
                                <a href="{pigcms{:U('guide_order',array('rela_status'=>3))}">已完成({pigcms{$count[3]})</a>
                            </li>
                        </ul>
                    </div>
                    <div class="allorder_end conduct_end xdconduct_end">
                        <dl>
                            <dt class="clr">
                                <div class="w160 fl">订单信息</div>
                                <div class="w230 fl">使用日期</div>
                                <div class="w225 fl">出行人信息</div>
                                <div class="w110 fl">总价</div>
                                <div class="w110 fl">订单状态</div>
                            </dt>
                            <volist name="scenic_aguide_order" id="vv">
	                            <dd>
	                                <div class="dd_top">
	                                    <ul class="clr">
	                                        <li><span>下单时间：</span>{pigcms{$vv['create_time']}</li>
	                                    </ul>
	                                </div>
	                                <div class="dd_end">
	                                    <div class="dd_end_top clr">
	                                        <div class="w160 h120 ">
	                                            <div class="h28">{pigcms{$vv['name']}</div>
	                                            <div class="ff8 h28">{pigcms{$vv['phone']}</div>
	                                        </div>
	                                        <div class="w230 h120  ari">
	                                            <p>{pigcms{$vv['start_time']} 至 {pigcms{$vv['end_time']}</p>
	                                            <p class="c3f ff8">共{pigcms{$vv['number']}天</p>
	                                        </div>

	                                        <div class="w225 h120 ">
	                                            <ul>
	                                            	<volist name="vv.family" id="vo">
	                                                	<li>{pigcms{$vo['family_name']} {pigcms{$vo['certificates']}</li>
	                                                </volist>
	                                            </ul>
	                                            <a href="javascript:void(0)" class="more ff8">展开全部</a>
	                                        </div>
	                                        <div class="w110 h120  fe">￥<i class="ari">{pigcms{$vv['total_price']}</i></div>
	                                        <div class="w110 h120  ks">已服务</div>
	                                    </div>
	                                    <div class="dd_end_js">

	                                        <div class="dd_end_sm clr">
	                                            <span class="dd_end_bc fl">补充说明：</span>
	                                            <div class="p70">
	                                                <div class="dd_end_span">{pigcms{$vv['explain']}</div>
	                                                <span class="dd_end_zk">展开</span>
	                                            </div>
	                                        </div>
	                                    </div>
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
				// $(this).find("dd:last-child ul").css("border-bottom","0px");
			}
		})

		$(".dd_end .w225 a.more").toggle(function(){
			$(this).text("收起更多");
			$(this).siblings("ul").addClass("hasMore")
		},function(){
			$(this).text("展开更多");
			$(this).siblings("ul").removeClass("hasMore")
		})
		//补充说明
		$(".dd_end_sm").each(function(){
			if($(this).height()>24){
				$(".dd_end_span").addClass("showMore")
			}
		})
		$(".dd_end_zk").click(function(){
			if(!$(this).siblings(".dd_end_span").hasClass("hasMore")){
				$(this).siblings(".dd_end_span").addClass("hasMore")
				$(this).text("收起");
				$(this).addClass("on");
			}else{
				$(this).siblings(".dd_end_span").removeClass("hasMore")
				$(this).text("展开")
				$(this).removeClass("on");
			}
		})
	</script>
<include file="Public/footer"/>