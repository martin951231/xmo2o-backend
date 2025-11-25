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
					<li class="sc on">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
            <div class="fr navright">
                <div class="askfor">
                    <div class="ask_top">
                        <h2>我的收藏</h2>
                    </div>
                    <div class="house_dd">
                        <dl>
                            <dt class="clr">
                                <div class="w600 fl">景点信息</div>
                                <div class="w170 fl">价格</div>
                                <div class="w110 fr">操作</div>
                            </dt>
                            <volist name="collection" id="vo">
	                            <dd class="clr">
	                                <div class="w600 fl clr">
	                                    <div class="img fl">
	                                        <a href="{pigcms{$vo['url']}">
	                                            <img src="{pigcms{$vo['pic']}" width="170" height="96">
	                                        </a>
	                                    </div>
	                                    <div class="p185">
	                                        <h2>
	                                            <a href="{pigcms{$vo['url']}">{pigcms{$vo['scenic_title']}</a>
	                                        </h2><a href="{pigcms{$vo['url']}">
	                                        <div class="score">
	                                            <div class="atar_Show">
	                                                <p></p>
	                                            </div>
	                                            <span>{pigcms{$vo['score_mean']}</span>分
	                                            <em>{pigcms{$vo['count_reply']}人点评</em>
	                                        </div></a>
	                                    </div>
	                                </div>
	                                <div class="w170 fl">
	                                    <span class="price">￥<i>{pigcms{$vo['money']}</i>起</span>
	                                </div>
	                                <div id="submit" class="w110 fr" style="margin-top:25px;" onclick="submit({pigcms{$vo['scenic_id']})">
	                                    <div class="clk del">删除</div>
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
	/*删除*/
	//$(".house_dd .del").click(function(){
//		$(this).parents(".house_dd dd").fadeOut(function(){
//			$(this).remove();
//		});
//	})
	/*评分*/
	$(".atar_Show p").each(function(index, element) {
		var num=$(this).parents(".score").find("span").text();
		var www=num*13;
		$(this).css("width",www);
	});
	var is_collection	=	true;
	function submit(scenic_id){
		if(is_collection === true){
			is_collection = false;
			$.ajax({
				type : "post",
				url : "{pigcms{:U('Scenic_list/collection_json')}",
				dataType : "json",
				data:{
					scenic_id		:	scenic_id,
					type	:	2,
				},
				async:false,
				success : function(result){
					if(result.errorCode != 0){
						alert(result.errorMsg);
					}else if(result.errorCode == 0){
						$(".p590 .stars").removeClass("on");
						alert('取消收藏成功');
						window.location.reload();
					}
				},
				error:function(){
					alert('取消收藏失败');
				}
			})
			is_collection = true;
		}
	}
</script>
<include file="Public/footer"/>