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
				<li>
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--小导航-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a> &gt; <span class="now">向导详情</span>
			</div>
		</div>
	</section>
    <section class="round">
        <div class="w1200 clr">
            <div class="fl w768">
                <div class="baner_img">
                    <ul class="baner_ul">
                    	<volist name="list.pic" id="vo">
	                        <li>
	                            <img src="{pigcms{$vo}" width="768" height="390">
	                        </li>
                        </volist>
                    </ul>
                    <div class="hd">
                        <ul>
                        	<volist name="list.pic" id="vv">
                            	<li></li>
                            </volist>
                        </ul>
                    </div>
                    <a class="prev" href="javascript:void(0)"></a>
                    <a class="next" href="javascript:void(0)"></a>
                </div>
                <div class="spottab">
                    <div class="tab_top">
                        <ul class="clr">
                            <li class="icons on">简介</li>
                            <li class="icons">用户点评</li>
<!--                            <li class="icons">服务介绍</li>-->
                            <li class="slider"></li>
                        </ul>
                    </div>
                    <div class="tab_end">
                        <div class="hand">
                        	<div class="p20">
								<h6 class="tit-t">
									<span>简介</span>
								</h6>
	                            <div>{pigcms{$list['guide_intr']}</div>
                            </div>
                        </div>
                        <!--<div class="hand" style="display: none;">
                            <div class="p20">
                                html内容2
                            </div>
                        </div>-->
                        <div class="hand" style="display: none;">
                    		<if condition="$scenic_reply['all_count']">
                            <div class="p20">
                                <h6 class="tit-t">
                                    <span>用户点评</span>
                                </h6>
                                <div class="p35">
                                    <div class="ism_end">
                                        <div class="ism_list ism_list2 clr">
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
                            </div>
			                <else/>
								<div><img src="{pigcms{$static_path}images/hbqcp_03.jpg"/></div>
			                </if>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fr w408">
                <div class="w408_top">
                    <div class="img">
                        <img src="{pigcms{$list['avatar']}" width="154" height="154">
                        <if condition="$list['guide_sex'] eq 1"><i class="man"></i><else/><i class="woman"></if>
                    </div>
                    <div class="text_top">
                        <span class="name">{pigcms{$list['guide_name']}</span>
                        <span class="old">{pigcms{$list['date']}</span>
                    </div>
                    <div class="call">
                        <if condition="$list['real_name']"><span>实名</span></if>
                        <if condition="$list['guide_status']"><span>导游证</span></if>
                    </div>
                    <div class="experience">
                        <div class="expe_n">{pigcms{$list['guide_life']}年经验 | {pigcms{$list['city_id']}区域</div>
                        <div class="score">
                            <div class="atar_Show">
                                <p></p>
                            </div>
                            <span>{pigcms{$list['score_mean']}</span>分
                        </div>
                    </div>
                </div>
                <div class="w408_end">
                    <div class="end_je">
                        <span class="price">￥<i>{pigcms{$list['guide_price']}</i></span>/天
                    </div>
                   	<a id="submit" href="javascript:void(0);">去预订</a>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript">
    	var	guide_id	=	"{pigcms{$_GET['guide_id']}";
		jQuery(".baner_img").slide({mainCell:".baner_ul",effect:"leftLoop",autoPlay:true});
		/*tab切换*/
		$(".tab_top li.icons").click(function(){
			if ($(this).hasClass('slider')){
				return;
			}
			var w=$(this).width();
			var whatTab = $(this).index();
			var howFar = w * whatTab;
			$(".slider").css({
				left: howFar + "px"
			});
			$(this).addClass("on").siblings().removeClass("on");
			var index=$(this).index();
			$(".hand").eq(index).show().siblings(".hand").hide();
		});
		/*评分*/
		$(".atar_Show p").each(function(index, element){
			var num=$(this).parents(".score").find("span").text();
			var www=num*13;//
			$(this).css("width",www);
		});
		$("#submit").click(function(){
			var user	=	"{pigcms{$_SESSION['user']['uid']}";
			if(user){
				location.href	=	"{pigcms{:U('Scenic_aguide/order')}&guide_id="+guide_id;
			}else{
				$(".board").show();
				$(".mark").show();
			}
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