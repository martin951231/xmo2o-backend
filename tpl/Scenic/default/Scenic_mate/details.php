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
				<li class="pull on">
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
				<span>您当前位置：</span><a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a> &gt; <span class="now">游伴详情</span>
			</div>
		</div>
	</section>
    <section class="circums">
        <div class="w1200 clr">
            <div class="circums_left fl">
                <div class="while">
                    <div class="while_top clr">
                        <div class="fl">
                            <h2>{pigcms{$scenic_mate['scenic_title']}</h2>
                            <p>游玩时间：{pigcms{$scenic_mate['start_time']}</p>
                        </div>
                        <!--<div class="fr">
                            <a href="#" class="remain">留言</a>
                            <a href="#" class="share">分享</a>
                        </div>-->
                    </div>
                    <div class="while_end clr">
                        <div class="img fl">
                            <img src="{pigcms{$scenic_mate['scenic_user']['avatar']}" width="60" height="60">
                        </div>
                        <div class="p70">
                            <h2>{pigcms{$scenic_mate['scenic_user']['nickname']}</h2>
                            <p>{pigcms{$scenic_mate['mate_intr']}</p>
                            <div class="issue">发布时间：{pigcms{$scenic_mate['create_time']}</div>
                        </div>
                    </div>
                </div>
                <div class="enlist">
                    <h2>我要报名</h2>
                    <div class="relation clr">
                        <span class="fl ele">联系电话</span>
                        <div class="p85">
                            <div class="clr">
                                <input type="text" id="sign_phone" class="fl" placeholder="请输入您的联系方式">
                                <span class="fl fill">
                                    <i><em>*</em> 必填：</i>请务必填写您的真实号码，方便游伴联系您。
                                </span>
                            </div>
                            <div class="reply submit222">报名</div>
                        </div>
                    </div>
                </div>
                <div class="grow">
                    <h2>留言 ( <i>{pigcms{$scenic_mate['people_number']}</i> )</h2>
                    <dl>
                        <dt class="clr">
                            <div class="img fl">
                                <img src="{pigcms{$avatar}"  width="60" height="60">
                            </div>
                            <div class="p72 clr">
                                <textarea id="release_text"></textarea>
                                <div class="textile fr clr" onclick="user_reply(0)">
                                    <div class="spread fl">发布</div>
                                </div>
                            </div>
                        </dt>
                        <volist name="scenic_mate.words" id="vv">
	                        <dd>
		                        <div class="dd_top clr">
		                            <div class="img fl">
		                                <img src="{pigcms{$vv['user']['avatar']}" width="60" height="60">
		                                <if condition="$vv['user']['sex'] eq 1"><i class="man"></i><else/><i class="woman"></i></if>
		                            </div>
		                            <div class="p72 clr">
		                                <h2>{pigcms{$vv['user']['nickname']}</h2>
		                                <h5>{pigcms{$vv['create_time']}</h5>
		                                <p>{pigcms{$vv['words_content']}</p>
		                            </div>
		                            <div class="fr answer">回复<textarea style="display:none;">{pigcms{$vv['words_id']}</textarea></div>
		                        </div>
	                            <volist name="vv.son" id="vvo">
		                            <div class="dd_top dd_top_hf clr">
		                                <div class="img fl">
		                                    <img src="{pigcms{$vvo['user']['avatar']}"  width="60" height="60">
		                                    <if condition="$vvo['user']['sex'] eq 1"><i class="man"></i><else/><i class="woman"></i></if>
		                                </div>
		                                <div class="p72 clr">
		                                    <h2>{pigcms{$vvo['user']['nickname']}</h2>
		                                    <h5>{pigcms{$vvo['create_time']}</h5>
		                                    <p>{pigcms{$vvo['words_content']}</p>
		                                </div>
		                            </div>
	                            </volist>
	                        </dd>
                        </volist>
                    </dl>
                </div>
            </div>
            <div class="circums_right fr">
                <h2 class="circums_h2">
                    <a href="javascript:void(0)" class="submit222"><span>我要报名</span></a>
                </h2>
                <div class="text">
                    <dl>
                        <dt><i>0</i>人报名</dt>
                    </dl>
                    <dl class="text_dl">
                        <volist name="scenic_mate.scenic_mates" id="vo">
	                        <dd class="clr">
	                            <div class="img fl">
	                                <img src="{pigcms{$vo['user']['avatar']}" width="72" height="72">
	                                <if condition="$vo['user']['sex'] eq 1"><i class="man"></i><else/><i class="woman"></i></if>
	                            </div>
	                            <div class="p85">
	                                <h2>{pigcms{$vo['user']['nickname']}</h2>
	                                <p>{pigcms{$vo['user']['phone']}</p>
	                            </div>
	                        </dd>
                        </volist>
                    </dl>
                </div>
            </div>
        </div>
    </section>
	<script type="text/javascript">
		var number=0;
		var user_info	=	"{pigcms{$user}";
		var avatar	=	"{pigcms{$avatar}";
		var mate_status	=	true;
		$(".grow dt textarea").click(function(){
			$(".cance").show();
		})
		$(".grow dt .spread").click(function(){
			$(".kym").hide();
		})
		$(".grow dt .cance").click(function(){
			$(this).hide();
			$(".grow dt textarea").val(" ");
			$(".kym").hide();
		});
		$(".kym").click(function(){
			$(this).hide();
			$(".grow dt textarea").val(" ");
			$(".grow dt .cance").hide();
		})
		$(".text dt i").text($(".text_dl dd").length);
		$(".grow dd .answer").click(function(){
			var submit_reply	=	$("#submit_reply").text();
			if(!submit_reply){
				var inpty1	=	$(this).children("textarea").text();
				var aaa	=	$(this).parents(".dd_top").after("<div class='dd_end clr'><div class='img fl'><img src='"+avatar+"'  width='60' height='60'></div><div class='p72 clr'><textarea id='son_release' placeholder='写下你的回复...'></textarea><div class='textile fr clr'><div class='cance fl'>取消</div><div class='spread fl' onclick=user_reply('"+inpty1+"') id='submit_reply'>提交</div></div></div></div>");
			}
		})
		$(document).on("click", ".grow dd .cance", function () {
			$(this).parents(".dd_end").remove();
		});
		$(".submit222").click(function(){
			if(!user_info){
				$(".board").show();
				$(".mark").show();
				return false;
			}
			var sign_phone	=	$("#sign_phone").val();
			if(sign_phone == ''){
				status = 1;
				alert('手机号不能为空');
				return false;
			}
			if(mate_status == true){
				mate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('sign_up_json')}",
					dataType : "json",
					data:{
						mate_id	:	'{pigcms{$mate_id}',
						phone	:	sign_phone,
					},
					async:false,
					success : function(result){
						var rideList	=	result;
						if(rideList.errorCode == 0){
							location.reload();
						}else{
							mate_status	=	true;
							alert(rideList.errorMsg)
							return false;
						}
					},
					error:function(){
						mate_status	=	true;
						alert('网络错误')
						return false;
					}
				})
			}
		})
		function user_reply(id){
			if(!user_info){
				$(".board").show();
				$(".mark").show();
				return false;
			}
			if(id != 0){
				var text	=	$("#son_release").val();
			}else{
				var text	=	$("#release_text").val();
			}
			if(!text){
				alert('留言内容不能为空');
			}
			if(mate_status == true){
				mate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('reply_json')}",
					dataType : "json",
					data:{
						mate_id	:	'{pigcms{$mate_id}',
						words_content	:	text,
						words_id	:	id,
					},
					async:false,
					success : function(result){
						var rideList	=	result;
						if(rideList.errorCode == 0){
							location.reload();
						}else{
							mate_status	=	true;
							alert(rideList.errorMsg)
							return false;
						}
					},
					error:function(){
						mate_status	=	true;
						alert('网络错误')
						return false;
					}
				})
			}
		}
	</script>
<include file="Public/footer"/>



