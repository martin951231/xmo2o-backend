<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li class="pull on">
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
	<!--小导航-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a> <if condition="$_GET['sor'] eq 2"><a href="{pigcms{:U('Scenic_travel_plan/select_scenic')}">&gt; 按景点规划行程</a><elseif condition="$_GET['sor'] eq 1"/><a href="{pigcms{:U('Scenic_travel_plan/arrange_travel_rand')}">&gt; 按旅行要求规划行程</a></if> &gt; <span class="now">开始规划行程</span>
			</div>
		</div>
	</section>
    <section class="varietylist">
        <section class="plot varietylist-1">
            <div class="w1200 clr">
                <div class="plot_l fl">
                    <div class="plot_top clr">
                        <div class="fl">
                            <h2>{pigcms{$scenic_city}{pigcms{$plan['day']}日行程计划</h2>
                            <div class="day">
                                <span class="fate">行程天数：<i>{pigcms{$plan['day']}天</i></span>
                                <span class="eyes">{pigcms{$plan['see_number']}</span>
                            </div>
                        </div>
                        <!--<div class="fr">
                            <div class="print">打印行程</div>
                            <div class="handset">手机查看</div>
                        </div>-->
                    </div>
                    <div class="plot_end clr">
                        <div class="scenic">
                            <a href="javascript:void(0)" class="on" data-cat_id="1">景区列表</a>
                            <a href="javascript:void(0)" data-cat_id="2">行程详情</a>
                        </div>
                        <div class="scenic_n">
                            <div class="scenic_list clr">
                                <div class="scenic_ul fl">
                                    <ul>
                                        <volist name="plan_detial" id="q" key="qq">
	                                        <li class="clr">
	                                            <div class="w65 fl">第{pigcms{$qq}天</div>
	                                            <div class="w70 fl">{pigcms{$scenic_city}</div>
	                                            <div class="w580 fl"><volist name="q" id="qqq" key="qqqq"><if condition="$qqqq neq 1">-</if>{pigcms{$qqq['scenic_title']}</volist></div>
	                                        </li>
                                        </volist>
                                    </ul>
                                    <div class="sc_open">
                                        <span>展开</span>
                                    </div>
                                </div>
                                <div class="about fr">
                                    <div class="about_p">￥<i>{pigcms{$plan['money']}</i>/人</div>
                                    <p>本次行程大约花费</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="plot_r fr">
                    <div class="plot_pin">
                        <h2>具体花销</h2>
                        <ul>
							<php>$tmp_arr=array();</php>
                            <volist name="plan_detial" id="w" key="ww">
	                            <li>
	                                <h5>第{pigcms{$ww}天</h5>
	                                <volist name="w" id="www" key="wwww">
		                                <div class="clr">
		                                    <div class="fl">{pigcms{$www['scenic_title']}</div>
									<php>if(!in_array($www['scenic_id'],$tmp_arr)){$tmp_arr[] = $www['scenic_id'];}else{ echo "</div>";continue;} </php>
		                                    <div class="fr">￥<i>{pigcms{$www['money']}</i>/人</div>
		                                </div>
                               		</volist>
	                            </li>
                            </volist>
                        </ul>
                        <div class="more">
                            <span>查看全部</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
		<section class="tdetails">
			<div class="w1200">
				<h2 class="feel varietylist-2">行程详情</h2>
				<div class="feel_list clr">
					<div class="fl feel_ul">
						<ul>
							<volist name="plan_detial" id="vov" key="kk">
								<li <if condition="$kk eq 1">class="on"</if> data-cats_id="{pigcms{$kk}">第{pigcms{$kk}天</li>
							</volist>
						</ul>
					</div>
					<div class="fr feel_in catenate">
					<php>$tmp_arr=array();</php>
						<volist name="plan_detial" id="vo" key="k">
							<div class="list_li catenate-{pigcms{$k}" data-cats_id="{pigcms{$k}">
								<div class="feel_top clr">
									<div class="fl day">D{pigcms{$k}</div>
									<div class="fl land">
										<p>第{pigcms{$k}天</p>
										<h2 p60>{pigcms{$scenic_city}</h2>
									</div>
								</div>
								
								<php>if(!in_array($vo[0]['scenic_id'],$tmp_arr)){$tmp_arr[] = $vo[0]['scenic_id'];}else{echo "<div class='hx'></div></div>";continue;} </php>
								<ul>
									<volist name="plan_detial[$k]" id="vv">
										<li>
											<div class="crown">
												<div class="li_top clr">
													<div class="fl">
														<h2 class="p60">{pigcms{$vv['scenic_title']}</h2>
														<div class="score">
															<div class="atar_Show">
																<p></p>
															</div>
															<span>{pigcms{$vv['score_mean']}</span>分
															<a class="pl">{pigcms{$vv['reply_count']}条点评</a>
														</div>
													</div>
													<div class="reserve">
														<a href="{pigcms{:U('Scenic_list/details',array('scenic_id'=>$vv['scenic_id']))}">
															<div class="grid">￥<i>{pigcms{$vv['money']}</i>起</div>
															<p>预订</p>
														</a>
													</div>
												</div>
												<div class="scheme">
													<div class="scheme_n">
														<dl class="scheme_dl clr">
															<volist name="vv['scenic_pic']" id="vvv">
																<dd>
																	<img src="{pigcms{:file_domain()}/upload/scenic/config/{pigcms{$vvv}"  width="220" height="114"/>
																</dd>
															</volist>
														</dl>
														<a href="javascript:void(0)" class="prev cut"></a>
														<a href="javascript:void(0)" class="next cut"></a>
													</div>
													<div style="line-height: 1.6; color: #666666; font-size: 12px;  text-align: justify;">{pigcms{$vv.scenic_intr|htmlspecialchars_decode}</div>
												</div>
											</div>
											<div class="proposal p60">建议游玩时间<php>if($vv['sugest_time']>=24){</php>{pigcms{:ceil($vv['sugest_time']/24)}天<php>}else{</php>{pigcms{$vv.sugest_time}小时<php>}</php></div>
<!--												<div class="route p60">自驾约11分钟  5.9公里<a href="#">[查看路线]</a></div>-->
											<div class="quarter">
												<h2 class="p60 tit">今日推荐住宿区域</h2>
												<dl class="alcohol clr">
													<volist name="vv.hotel" id="vvo">
														<dd>
															<a href="{pigcms{$vvo['url']}" class="alco_a">
																<div class="alcohol_top clr">
																	<h2>{pigcms{$vvo['name']}</h2>
																	<div class="cash">￥<i>{pigcms{$vvo['price']}</i>/间</div>
																</div>
																<div class="text">{pigcms{$vvo['intro']}</div>
															</a>
<!--																<a href="" class="post">查看位置</a>-->
														</dd>
													</volist>
												</dl>
											</div>
										</li>
									</volist>
							
								</ul>
								<div class="hx"></div>
							</div>
						</volist>
                    </div>
                </div>
            </div>
        </section>
    </section>
	<section class="cxode">
		<h2>扫描二维码，即可在手机上查看</h2>
		<img src="{pigcms{$static_path}images/ewm_03.jpg" width="100" height="100">
		<a href="javascript:void(0)"></a>
	</section>
<script type="text/javascript">
	/*二维码*/
	$(".handset").click(function(){
		$(".cxode").addClass("on").show();
		$(".cxode a").click(function(){
			$(".cxode").removeClass("on");
		})
	})
	jQuery(".scheme_n").slide({mainCell:".scheme_dl",effect:"left",scroll:1,vis:4,pnLoop:false,autoPage:true,});
	/*横线*/
	$(".list_li .hx").each(function(){
		$(this).height($(this).parents(".list_li").height());
	})
	$(".feel_in .list_li:last-child").each(function(){
		var hi=$(this).find(".quarter").height();
		$(this).find(".hx").height($(this).height()-hi)
	})
	/*评分*/
	$(".atar_Show p").each(function(index, element) {
		var num=$(this).parents(".score").find("span").text();
		var www=num*13;//
		$(this).css("width",www);
	});
	//景区列表
	$(".scenic_ul").each(function(){
		// if($(this).find("li").length > 3){
		 if($(this).height()>105){
			$(".sc_open").show();
			$(".scenic_ul").addClass("showMore");
		}
	})
	$(".sc_open").click(function(){
		if(!$(".scenic_ul").hasClass("hasMore")){
			$(".scenic_ul").addClass("hasMore");
			$(this).find("span").text("收起");
		}else{
			$(".scenic_ul").removeClass("hasMore");
			$(this).find("span").text("展开");
		}
	})
	//具体花销
	$(".plot_pin").each(function(){
		// if($(this).find("li").length > 3){
		if($(this).find("ul").height()>252){
			$(".more").show();
			$(".plot_pin").addClass("showMore");
		}
	})
	$(".more").click(function(){
		if(!$(".plot_pin").hasClass("hasMore")){
			$(".plot_pin").addClass("hasMore");
			$(this).find("span").text("收起全部");
		}else{
			$(".plot_pin").removeClass("hasMore");
			$(this).find("span").text("展开全部");
		}
	})
	//定位
	$(".scenic a").click(function(){
		$(this).addClass("on").siblings().removeClass("on");
		$('html,body').animate({scrollTop:$('.varietylist-'+$(this).data('cat_id')).offset().top-$('.varietylist').offset().top + $('.varietylist').scrollTop()+$(".plot").offset().top},500);
	})
	$(window).scroll(function(){
		if($(this).scrollTop()>$(".feel").offset().top){
			$(".feel_ul").addClass("fix");
			var top=$(this).scrollTop();
			var menu = $(".feel_ul");
			var item = $(".list_li");
			var onid = "";
			item.each(function(){
				var n = $(this);
				var itemtop=$('.catenate-'+$(this).data('cats_id')).offset().top-$('.catenate').offset().top + $('.catenate').scrollTop()+$(".feel").offset().top;
				if(top>itemtop-100){
					onid=n.data('cats_id');
				}
			});
			var link = menu.find(".on");
			link.removeClass("on");
			menu.find("[data-cats_id="+onid+"]").addClass("on");
		}
		else{
			$(".feel_ul").removeClass("fix");
		}
	})
	$(".feel_ul li").click(function(){
		$('html,body').animate({scrollTop:$('.catenate-'+$(this).data('cats_id')).offset().top-$('.catenate').offset().top + $('.catenate').scrollTop()+$(".feel").offset().top},500);
	})
</script>
<include file="Public/footer"/>