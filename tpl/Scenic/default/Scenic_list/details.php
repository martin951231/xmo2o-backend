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
	<!--小导航-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Index/index')}">网站首页</a> > <a href="{pigcms{:U('Scenic_list/index')}">景点门票</a> > <span class="now">景点详情</span>
			</div>
		</div>
	</section>
	<!--景区详情-->
	<section class="carousel">
		<div class="w1200 clr">
			<div class="carousel_n clr">
				<div class="fl">
					<div class="game">
						<ul class="bigImg">
							<volist name="scenic_list['pic']" id="vo">
								<li>
									<img src="{pigcms{$vo}">
								</li>
							</volist>
						</ul>
						<div class="smallScroll">
							<a class="sPrev" href="javascript:void(0)"></a>
								<div class="smallImg">
									<ul>
										<volist name="scenic_list['pic']" id="vo">
											<li><img src="{pigcms{$vo}"></li>
										</volist>
									</ul>
								</div>
							<a class="sNext" href="javascript:void(0)"></a>
						</div>
						<div class="pageState"></div>
					</div>
				</div>
				<div class="p590">
					<h2>{pigcms{$scenic_list['scenic_title']}</h2>
					<div class="address">
						<span class="on">景点地址：</span>
						<div class="p70">
							<span class="c6">{pigcms{$scenic_list['province_name']}{pigcms{$scenic_list['city_name']}{pigcms{$scenic_list['scenic_address']}</span>
<!--							<a href="#" class="dt">查看地图</a>-->
						</div>
					</div>
					<div class="address time">
						<span class="on">开放时间：</span>
						<div class="p70">
							<span class="c6">{pigcms{$scenic_list['start_time']}-{pigcms{$scenic_list['end_time']}</span>
						</div>
					</div>
					<div class="address">
						<span class="on">用户评分：</span>
						<div class="score">
							<div class="atar_Show">
								<p></p>
							</div>
							<span>{pigcms{$scenic_list['score_mean']}</span>分
							<a href="javascript:void(0)" data-cat_id="1" class="pl">查看{pigcms{$scenic_list['count_reply']}条点评</a>
						</div>
					</div>
					<div class="clr loca">
						<div class="fl">￥<span>{pigcms{$scenic_list['money']}</span>起/人</div>
					</div>
					<if condition="$scenic_list['collection'] eq 1"><div class="stars on"></div><else/><div class="stars"></if>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			/*评论定位*/
            $(".score a.pl").click(function(){
                var index=$(".tab_top ul li").length-1;
                $('html,body').animate({scrollTop:$('.varietylist-'+$(this).data('cat_id')).offset().top},500);
                $(".tab_top ul li:last-child").addClass("on").siblings().removeClass("on");
                $(".hand").eq(index).show().siblings(".hand").hide();
            })
		</script>
	</section>
	<!--景区门票列表-->
	<if condition="$scenic_ticket OR $scenic_ticket_children">
		<section class="tickets">
			<div class="w1200">
				<div class="tickets_top">
					<span>景点门票</span>
				</div>
				<div class="tickets_end">
					<dl>
						<dt>
							<ul class="clr">
								<li class="w695">产品名称</li>
								<li class="w100">原价</li>
								<li class="w100">现价</li>
								<li class="w160">支付方式</li>
							</ul>
						</dt>
						<if condition="$scenic_ticket">
							<dd>
								<div class="adult fl">
									<span>成人票</span>
								</div>
								<div class="product clr">
									<volist name="scenic_ticket" id="vv">
										<div class="adult_end">
											<ul class="clr">
												<li class="w590 clr">
													<span class="title">{pigcms{$vv['ticket_title']}</span>
													<span class="time"><i class="ago">{pigcms{$vv['end_time']}之前</i><i>可订今日票</i></span>
													<span class="tui">随时退</span>
												</li>
												<li class="w100 del">￥{pigcms{$vv['old_price']}</li>
												<li class="w100 fe">￥<span>{pigcms{$vv['ticket_price']}</span></li>
												<li class="w160 right"><span>在线支付</span><a href="{pigcms{$vv['url']}">立即预订</a></li>
											</ul>
											<div class="display">
												<a href="javascript:void(0)" class="collect">收起</a>
												<div class="display_list">
													<span class="on fl">预定时间</span>
													<div class="p65" style=" color: #666666; font-size: 12px;">游玩当天{pigcms{$vv['end_time']}前可下单，请尽早预订，以免耽误您的行程。</div>
												</div>
												<div class="display_list">
													<span class="on fl">预定须知</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$vv['ticket_explain']}</div>
												</div>
												<div class="display_list">
													<span class="on fl">入园方式</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$vv['ticket_cue']}</div>
												</div>
												<div class="display_list">
													<span class="on fl">入园提示</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$vv['park_intr']}</div>
												</div>
											</div>
										</div>
									</volist>
								</div>
							</dd>
						</if>
						<if condition="$scenic_ticket_children">
							<dd>
								<div class="adult fl">
									<span>儿童票</span>
								</div>
								<div class="product clr">
									<volist name="scenic_ticket_children" id="va">
										<div class="adult_end">
											<ul class="clr">
												<li class="w590 clr">
													<span class="title">{pigcms{$va['ticket_title']}</span>
													<span class="time"><i class="ago">{pigcms{$va['end_time']}之前</i><i>可订今日票</i></span>
													<span class="tui">随时退</span>
												</li>
												<li class="w100 del">￥{pigcms{$va['old_price']}</li>
												<li class="w100 fe">￥<span>{pigcms{$va['ticket_price']}</span></li>
												<li class="w160 right"><span>在线支付</span><a href="{pigcms{$vv['url']}">立即预订</a></li>
											</ul>
											<div class="display">
												<a href="javascript:void(0)" class="collect">收起</a>
												<div class="display_list">
													<span class="on fl">预定时间</span>
													<div class="p65" style=" color: #666666; font-size: 12px;">游玩当天{pigcms{$va['end_time']}前可下单，请尽早预订，以免耽误您的行程。</div>
												</div>
												<div class="display_list">
													<span class="on fl">预定须知</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$va['ticket_explain']}</div>
												</div>
												<div class="display_list">
													<span class="on fl">入园方式</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$va['ticket_cue']}</div>
												</div>
												<div class="display_list">
													<span class="on fl">入园提示</span>
													<div class="p65" style=" color: #666666; font-size: 12px; line-height: 20px;">{pigcms{$va['park_intr']}</div>
												</div>
											</div>
										</div>
									</volist>
								</div>
							</dd>
						</if>
					</dl>
				</div>
			</div>
		</section>
	</if>
	<!--景区介绍-->
	<section class="spottab varietylist-1">
		<div class="w1200">
			<div class="tab_top">
				<ul class="clr">
					<li class="icons on">景点介绍</li>
					<li class="icons">预订须知</li>
					<li class="icons">游客点评</li>
<!--					<li class="icons">交通指南</li>-->
					<!-- <li class="slider"></li> -->
				</ul>
			</div>
			<div class="tab_end">
				<div class="hand">
					<h5 class="title">景点介绍</h5>
					<div class="p40">{pigcms{$scenic_list['scenic_intr']}</div>
				</div>
				<div class="hand" style="display: none;">
					<h5 class="title xz">预订须知</h5>
					<div class="p40">{pigcms{$scenic_list['scenic_explain']}</div>
				</div>
				<div class="hand" style="display: none;">
                    <h5 class="title dp">游客点评</h5>
                    <if condition="$scenic_reply['all_count']">
	                    <div class="p35">
	                        <div class="ism_top clr">
	                            <div class="ism_l fl">
	                                <p>满意度</p>
	                                <div class="ism_jg" id="all_count">{pigcms{$scenic_reply['all']}%</div>
	                                <p>来自<i>{pigcms{$scenic_reply['all_count']}</i>名游客的点评</p>
	                            </div>
	                            <div class="ism_z fl">
	                                <ul>
	                                    <li class="clr">
	                                        <span class="fl">好评</span>
	                                        <div class="fl bar" data-num="{pigcms{$scenic_reply['all_count']}">
	                                            <div class="bar_n" data-num="{pigcms{$scenic_reply['good_count']}"></div>
	                                        </div>
	                                        <div class="fl ari"><i></i>%</div>
	                                    </li>
	                                    <li class="clr">
	                                        <span class="fl">中评</span>
	                                        <div class="fl bar" data-num="{pigcms{$scenic_reply['all_count']}">
	                                            <div class="bar_n" data-num="{pigcms{$scenic_reply['in_count']}"></div>
	                                        </div>
	                                        <div class="fl ari"><i></i>%</div>
	                                    </li>
	                                    <li class="clr">
	                                        <span class="fl">差评</span>
	                                        <div class="fl bar" data-num="{pigcms{$scenic_reply['all_count']}">
	                                            <div class="bar_n" data-num="{pigcms{$scenic_reply['poor_count']}"></div>
	                                        </div>
	                                        <div class="fl ari"><i></i>%</div>
	                                    </li>
	                                </ul>
	                            </div>
	                        </div>
	                        <div class="ism_end">
	                            <div class="ism_list clr">
	                                <a href="javascript:void(0)" class="on" id="1_news" onclick="news(1)"><i>全部点评</i>（{pigcms{$scenic_reply['all_count']}）</a>
	                                <a href="javascript:void(0)" id="2_news" onclick="news(2)"><i>好评</i>（{pigcms{$scenic_reply['good_count']}）</a>
	                                <a href="javascript:void(0)" id="3_news" onclick="news(3)"><i>中评</i>（{pigcms{$scenic_reply['in_count']}）</a>
	                                <a href="javascript:void(0)" id="4_news" onclick="news(4)"><i>差评</i>（{pigcms{$scenic_reply['poor_count']}）</a>
	                            </div>
	                            <div class="ism_ul" id="comment_one">
	                                <ul id="comment_one_ul">
                                		<volist name="scenic_reply_conter.all_reply" id="all_reply">
		                                    <li class="clr">
		                                        <div class="img fl">
		                                            <img src="{pigcms{$all_reply['avatar']}" width="70" height="70">
		                                            <p>{pigcms{$all_reply['nickname']}</p>
		                                        </div>
		                                        <div class="p110">
		                                            <div class="score">
		                                                <div class="atar_Show">
		                                                    <p></p>
		                                                </div>
		                                                <span>{pigcms{$all_reply['reply_score']}</span>分
		                                            </div>
		                                            <p>{pigcms{$all_reply['reply_content']}</p>
		                                            <div class="time">{pigcms{$all_reply['reply_time']}</div>
		                                        </div>
		                                    </li>
	                                    </volist>
	                                </ul>
	                                <if condition="$scenic_reply['all_count'] gt 10">
                                		<div class="load" id="comment_list1" onclick="comment_list(1)">点击加载更多评论...</div>
	                                </if>
	                            </div>
	                            <div class="ism_ul" id="comment_two" style="display:none;">
	                                <ul id="comment_two_ul">
                            			<volist name="scenic_reply_conter.good_reply" id="good_reply">
		                                    <li class="clr">
		                                        <div class="img fl">
		                                            <img src="{pigcms{$good_reply['avatar']}" width="70" height="70">
		                                            <p>{pigcms{$good_reply['nickname']}</p>
		                                        </div>
		                                        <div class="p110">
		                                            <div class="score">
		                                                <div class="atar_Show">
		                                                    <p></p>
		                                                </div>
		                                                <span>{pigcms{$good_reply['reply_score']}</span>分
		                                            </div>
		                                            <p>{pigcms{$good_reply['reply_content']}</p>
		                                            <div class="time">{pigcms{$good_reply['reply_time']}</div>
		                                        </div>
		                                    </li>
		                                </volist>
	                                </ul>
	                                <if condition="$scenic_reply['good_count'] gt 10">
                                		<div class="load" id="comment_list2" onclick="comment_list(2)">点击加载更多评论...</div>
	                                </if>
	                            </div>
	                            <div class="ism_ul" id="comment_three" style="display:none;">
	                                <ul id="comment_three_ul">
                                		<volist name="scenic_reply_conter.in_reply" id="in_reply">
		                                    <li class="clr">
		                                        <div class="img fl">
		                                            <img src="{pigcms{$in_reply['avatar']}" width="70" height="70">
		                                            <p>{pigcms{$in_reply['nickname']}</p>
		                                        </div>
		                                        <div class="p110">
		                                            <div class="score">
		                                                <div class="atar_Show">
		                                                    <p></p>
		                                                </div>
		                                                <span>{pigcms{$in_reply['reply_score']}</span>分
		                                            </div>
		                                            <p>{pigcms{$in_reply['reply_content']}</p>
		                                            <div class="time">{pigcms{$in_reply['reply_time']}</div>
		                                        </div>
		                                    </li>
		                                </volist>
	                                </ul>
	                                <if condition="$scenic_reply['in_count'] gt 10">
                               			<div class="load" id="comment_list3" onclick="comment_list(3)">点击加载更多评论...</div>
                               		</if>
	                            </div>
	                            <div class="ism_ul" id="comment_four" style="display:none;">
	                                <ul id="comment_four_ul">
	                                    <volist name="scenic_reply_conter.poor_reply" id="poor_reply">
		                                    <li class="clr">
		                                        <div class="img fl">
		                                            <img src="{pigcms{$poor_reply['avatar']}" width="70" height="70">
		                                            <p>{pigcms{$poor_reply['nickname']}</p>
		                                        </div>
		                                        <div class="p110">
		                                            <div class="score">
		                                                <div class="atar_Show">
		                                                    <p></p>
		                                                </div>
		                                                <span>{pigcms{$poor_reply['reply_score']}</span>分
		                                            </div>
		                                            <p>{pigcms{$poor_reply['reply_content']}</p>
		                                            <div class="time">{pigcms{$poor_reply['reply_time']}</div>
		                                        </div>
		                                    </li>
		                                </volist>
	                                </ul>
	                                <if condition="$scenic_reply['poor_count'] gt 10">
                                		<div class="load" id="comment_list4" onclick="comment_list(4)">点击加载更多评论...</div>
	                                </if>
	                            </div>
	                        </div>
	                    </div>
	                <else/>
						<div><img src="{pigcms{$static_path}images/hbqcp_03.jpg"/></div>
	                </if>
                </div>
				<!--<div class="hand" style="display: none;">
					<h5 class="title jt">交通指南</h5>
					<div class="p40">
						<img src="images/dt_03.jpg" width=1120 height=620>
					</div>
				</div>-->
			</div>
		</div>
	</section>
	<!--语音播报-->
	<if condition="$scenic_project">
		<section class="recom cast">
	        <div class="w1200">
	            <div class="recom_n">
	                <div class="recom_top clr">
	                    <h2 class="fl">语音播报</h2>
	                    <!-- <a href="#" class="fr">更多 ></a> -->
	                </div>
	                <div class="public_end clr">
	                    <dl class="clr">
                    		<volist name="scenic_project" id="pro">
		                        <dd>
		                            <a href="{pigcms{$pro['url']}" class="scenic_a">
		                                <div class="img">
		                                    <img src="{pigcms{$pro['pic']}" width=100% height=160>
		                                </div>
		                                <div class="text">
		                                    <h5 class="scenic_h5">{pigcms{$pro['project_title']}</h5>
		                                    <div class="scenic_div">{pigcms{$pro['project_conter']}</div>
		                                </div>
		                            </a>
		                            <div class="w34">
		                                <img class="img1" src="{pigcms{$static_path}images/dht_06.png">
		                                <img class="img2" src="{pigcms{$static_path}images/dht_03.png">
		                                <audio  class="music" src="{pigcms{$pro['mp3']}"></audio>
		                            </div>
		                        </dd>
	                        </volist>
	                    </dl>
	                    <div class="recom_end">
	                        <a class="prev" href="javascript:void(0)"></a>
	                        <a class="next" href="javascript:void(0)"></a>
	                    </div>
	                </div>

	            </div>
	        </div>
	    </section>
    </if>
	<!--景区活动-->
	<if condition="$scenic_activity">
		<section class="activity">
			<div class="w1200">
				<div class="activity_n">
					<div class="activity_top clr">
						<h2 class="fl">景内活动</h2>
						<a href="{pigcms{:U('Scenic_inside/inside_activity',array('scenic_id'=>$scenic_list['scenic_id']))}" class="fr">更多 ></a>
					</div>
					<div class="public_end activity_end clr">
						<dl class="clr">
							<volist name="scenic_activity" id="vq">
								<dd>
									<a href="{pigcms{$vq['url']}">
										<div class="img">
											<img src="{pigcms{$vq['activity_img']}" width=100% height=177>
										</div>
										<div class="text">
											<h5>{pigcms{$vq['activity_title']}</h5>
											<p>{pigcms{$vq['activity_stitle']}</p>
											<div class="text_bot">
												<div class="bot_list clr">
													<span class="fl">活动时间：</span>
													<div class="p85">{pigcms{$vq['start_time']}</div>
												</div>
												<div class="bot_list2 clr">
													<span class="fl">已报名数：</span>
													<div class="p85"><i>{pigcms{$vq['sign_number']}</i>人</div>
												</div>
											</div>
										</div>
									</a>
									<div class="posi jx">正在进行</div>
								</dd>
							</volist>
						</dl>
					</div>
				</div>
			</div>
		</section>
	</if>
	<!--景区商品推荐-->
	<volist name="arr_category" id="ve">
		<section class="recom quarter">
			<div class="w1200">
				<div class="recom_n">
					<div class="recom_top clr">
						<h2 class="fl" style="background: url({pigcms{$ve['cat_img']}) left center no-repeat; background-size: 31px 20px">{pigcms{$ve['cat_name']}</h2>
						<a href="{pigcms{$ve['url']}" class="fr">更多 ></a>
					</div>
					<div class="public_end clr">
						<dl class="clr">
							<volist name="ve['list']" id="vr">
								<dd>
									<a href="{pigcms{$vr['url']}">
										<div class="img">
											<img src="{pigcms{$vr['pic']}" width=100% height=138>
										</div>
										<div class="text">
											<h2>{pigcms{$vr['com_name']}</h2>
											<div class="clr textend">
												<div class="fl">￥<span>{pigcms{$vr['price']}</span>起</div>
											</div>
										</div>
									</a>
								</dd>
							</volist>
						</dl>
						<div class="recom_end">
							<a class="prev" href="javascript:void(0)"></a>
							<a class="next" href="javascript:void(0)"></a>
						</div>
					</div>
				</div>
			</div>
		</section>
	</volist>
	<!--其他景区推荐-->
	<section class="recom">
		<div class="w1200">
			<div class="recom_n">
				<div class="recom_top clr">
					<h2 class="fl">景区推荐</h2>
				</div>
				<div class="public_end clr">
					<dl class="clr">
						<volist name="nearby" id="vl">
							<dd>
								<a href="{pigcms{$vl['url']}">
									<div class="img">
										<img src="{pigcms{$vl['pic']}" width=100% height=138>
									</div>
									<div class="text">
										<h2>{pigcms{$vl['scenic_title']}</h2>
										<div class="clr textend">
											<div class="fl">￥<span>{pigcms{$vl['money']}</span>起</div>
											<div class="fr">
												<span>评分:</span>
												<span class="score">{pigcms{$vl['score_mean']}</span>
											</div>
										</div>
									</div>
								</a>
							</dd>
						</volist>
					</dl>
					<div class="recom_end">
						<a class="prev" href="javascript:void(0)"></a>
						<a class="next" href="javascript:void(0)"></a>
					</div>
				</div>
			</div>
		</div>
	</section>
<script type="text/javascript">
	var user	=	"{pigcms{$_SESSION['user']}";
	jQuery(".game").slide({ titCell:".smallImg li", mainCell:".bigImg", effect:"left"});
	jQuery(".game .smallScroll").slide({ mainCell:"ul",effect:"left",scroll:1,vis:4,pnLoop:false,autoPage:true,prevCell:".sPrev",nextCell:".sNext" });
	jQuery(".recom_n").slide({mainCell:".public_end dl",effect:"left",scroll:1,vis:4,pnLoop:false,autoPage:true});
	/*评分*/
	$(".atar_Show p").each(function(index, element) {
		var num=$(this).parents(".score").find("span").text();
		var www=num*13;//
		$(this).css("width",www);
	});
    /*收藏*/
	var is_collection = true;
    $(".p590 .stars").click(function(){
		if(!user){
			$(".board").show();
			$(".mark").show();
			return false;
		}
        if($(this).hasClass("on")){
        	if(is_collection === true){
				is_collection = false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('Scenic_list/collection_json')}",
					dataType : "json",
					data:{
						scenic_id		:	"{pigcms{$_GET['scenic_id']}",
						type	:	2,
					},
					async:false,
					success : function(result){
						if(result.errorCode != 0){
							alert(result.errorMsg);
						}else if(result.errorCode == 0){
							$(".p590 .stars").removeClass("on");
							alert('取消收藏成功');
						}
					},
					error:function(){
						alert('取消收藏失败');
					}
				})
				is_collection = true;
			}
        }else{
        	if(is_collection === true){
				is_collection = false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('collection_json')}",
					dataType : "json",
					data:{
						scenic_id		:	"{pigcms{$_GET['scenic_id']}",
						type	:	1,
					},
					async:false,
					success : function(result){
						if(result.errorCode != 0){
							alert(result.errorMsg);
						}else if(result.errorCode == 0){
							$(".p590 .stars").addClass("on");
							alert('收藏成功');
						}
					},
					error:function(){
						alert('收藏失败');
					}
				})
				is_collection = true;
			}
        }
    })
	/*展开收起*/
	$(".product").each(function(){
		$(this).find(".adult_end:last-child()").css("border-bottom","none")
	})
	$(".adult_end .title").click(function(){
		if($(this).parents("ul").siblings(".display").is(":hidden")){
			$(this).parents("ul").siblings(".display").slideDown().parents(".adult_end").addClass("on");
		}else{
			$(this).parents("ul").siblings(".display").slideUp().parents(".adult_end").removeClass("on");
		}
	})
	$(".collect").click(function(){
		$(this).parents(".display").slideUp().parents(".adult_end").removeClass("on");
	})
	/*tab切换*/
	$(".tab_top li.icons").click(function(){
		// if ($(this).hasClass('slider')){
		// 	return;
		// }
		// var w=$(this).width();
		// var whatTab = $(this).index();
		// var howFar = w * whatTab;
		// $(".slider").css({
		// 	left: howFar + "px"
		// });
		$(this).addClass("on").siblings().removeClass("on");
		var index=$(this).index();
		$(".hand").eq(index).show().siblings(".hand").hide();
	});
	/*景内活动*/
	$(".activity dd:last-child").css("margin-right","0px");
	/*语音播报*/
	$(".w34") .click(function(){
		var audio = $(this).find(".music");
		var v=audio[0];
		if(v.paused){ //判断暂停 或者播放
			var music=$(this).parents("dd").siblings("dd").find(".music");
			for(var i=0; i<music.length;i++){
				var t=music[i];
				t.pause();
				music.siblings(".img1").removeClass("animat-op");
				music.siblings(".img2").removeClass("animat-op1");
			};
			v.play();//播放
			$(this).find(".img1").addClass("animat-op");
			$(this).find(".img2").addClass("animat-op1");
		}else{
			v.pause();// 暂停
			$(this).find(".img1").removeClass("animat-op");
			$(this).find(".img2").removeClass("animat-op1");
		}
	})
	$(".ism_z li:last-child").css("padding-bottom",0);
	/*评分百分比*/
	$(".ism_z li").each(function(){
		var bar = $(this).find(".bar");
		var num1=bar.data("num");
		var w=bar.width();
		var bar_n = $(this).find(".bar_n");
		var num2=bar_n.data("num");
		bar_n.width(w*(num2/num1));
		$(this).find("i").text(((num2/num1)*100).toFixed(0)*100/100);
	})
	/*定位*/
	$(".score a.pl").click(function(){
		var index=$(".tab_top ul li").length-1;
		$('html,body').animate({scrollTop:$('.varietylist-'+$(this).data('cat_id')).offset().top},500);
		$(".tab_top ul li:last-child").addClass("on").siblings().removeClass("on");
		$(".hand").eq(index).show().siblings(".hand").hide();
	})
	//评论
	$(".ism_list a").click(function(){
		$(this).addClass("on").siblings("a").removeClass("on");
	})
	function news(id){
		if(id == 1){
			$("#comment_one").show();
			$("#comment_two").hide();
			$("#comment_three").hide();
			$("#comment_four").hide();
		}else if(id == 2){
			$("#comment_one").hide();
			$("#comment_two").show();
			$("#comment_three").hide();
			$("#comment_four").hide();
		}else if(id == 3){
			$("#comment_one").hide();
			$("#comment_two").hide();
			$("#comment_three").show();
			$("#comment_four").hide();
		}else if(id == 4){
			$("#comment_one").hide();
			$("#comment_two").hide();
			$("#comment_three").hide();
			$("#comment_four").show();
		}
//		$('.ism_list a').removeClass("on");
//		$('#'+id+'_news').addClass("on");
//		$('.ism_ul').html("<ul><li class='clr'><div class='img fl'><img src='images/user_avatar.jpg' width='70' height='70'><p>Miss 胡</p></div><div class='p110'><div class='score'><div class='atar_Show'><p></p></div><span>3</span>分</div><p>这次的行程总的来说是不错的</p><div class='time'>2016-11-29 15:59:48</div></div></li></ul><div class='load'>点击加载更多评论...</div>");
	}
	var comment_one_page	=	2;
	var comment_two		=	2;
	var comment_three	=	2;
	var comment_four	=	2;
	var page	=	2;
	function comment_list(id){
		if(id == 1){
			page	=	comment_one_page;
		}else if(id == 2){
			page	=	comment_two;
		}else if(id == 3){
			page	=	comment_three;
		}else if(id == 4){
			page	=	comment_four;
		}
		$.ajax({
			type : "post",
			url : "{pigcms{:U('get_comment')}",
			dataType : "json",
			data:{
				page	:	page,
				id	:	id,
				scenic_id	:	"{pigcms{$_GET['scenic_id']}",
			},
			async:true,
			success : function(result){
				var list	=	'';
				var list_result	=	result.result;
				if(list_result){
					if(result.errorCode == 0){
						var list_length	=	result.result.length;
						for(var i=0;i<list_length;i++){
							list	+=	'<li class="clr">';
                            list	+=	'    <div class="img fl">';
                            list	+=	'        <img src="'+list_result[i].avatar+'" width="70" height="70">';
                            list	+=	'        <p>'+list_result[i].nickname+'</p>';
                            list	+=	'    </div>';
                            list	+=	'    <div class="p110">';
                            list	+=	'        <div class="score">';
                            list	+=	'            <div class="atar_Show">';
                            list	+=	'                <p></p>';
                            list	+=	'            </div>';
                            list	+=	'            <span>'+list_result[i].reply_score+'</span>分';
                            list	+=	'        </div>';
                            list	+=	'        <p>'+list_result[i].reply_content+'</p>';
                            list	+=	'        <div class="time">'+list_result[i].reply_time+'</div>';
                            list	+=	'    </div>';
                            list	+=	'</li>';
						}
					}else{
						alert(result.errorMsg);
					}
					if(list_length < 10){
						$("#comment_list"+id).remove();
					}
				}else{
					$("#comment_list"+id).remove();
				}
				if(id == 1){
					$("#comment_one_ul").append(list);
					comment_one_page++;
				}else if(id == 2){
					$("#comment_two_ul").append(list);
					comment_two++;
				}else if(id == 3){
					$("#comment_three_ul").append(list);
					comment_three_page++;
				}else if(id == 4){
					$("#comment_four_ul").append(list);
					comment_four_page++;
				}
			},
			error:function(){
				alert('网络错误，请稍后重试！');
			}
		})
	}
</script>
<include file="Public/footer"/>