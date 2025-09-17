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
					<li class="cx on">
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
                <div class="people_list">
                    <h2 class="tit"><span>出行人列表</span></h2>
                    <div class="nav_list">
                        <h2>
                            <a href="{pigcms{:U('Scenic_user/add_travel')}"><span>添加出行人</span></a>
                            <!-- <span>修改出行人</span> -->
                        </h2>
                        <dl>
	                        <dt class="clr">
	                            <div class="fl w105">姓名</div>
	                            <div class="fl w225">性别</div>
	                            <div class="fl w145">电话</div>
	                            <div class="fl w300">身份证</div>
	                            <div class="fr w150">操作</div>
	                        </dt>
                        	<volist name="family" id="vo">
	                            <dd class="clr">
	                                <div class="fl w105">{pigcms{$vo['family_name']}</div>
	                                <div class="fl w225">{pigcms{$vo['gender']}</div>
	                                <div class="fl w145">{pigcms{$vo['phone']}</div>
	                                <div class="fl w300">{pigcms{$vo['certificates']}</div>
	                                <div class="fr w150 nav_del">
	                                    <div class="xg" onclick="edit({pigcms{$vo['family_id']})">修改</div>
	                                    <div class="del" onclick="delete_f({pigcms{$vo['family_id']})">删除</div>
	                                </div>
	                            </dd>
                            </volist>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>
<script type="text/javascript">
	/*删除*/
	$(".nav_list .del").click(function(){
		$(this).parents(".nav_list dd").fadeOut(function(){
			$(this).remove();
		});
	})
	/*男女切换*/
	$(".menw span").click(function(){
		$(this).addClass("on").siblings("span").removeClass("on");
	})
	function edit(family_id){
		location.href	=	"{pigcms{:U('Scenic_user/add_travel')}&family_id="+family_id;
	}
	var generate_status	=	true;
	function delete_f(family_id){
		if(generate_status == true){
			generate_status	=	false;
			$.ajax({
				type : "post",
				url : "{pigcms{:U('delete_travel')}",
				dataType : "json",
				data:{
					family_id	:	family_id,
				},
				async:false,
				success : function(result){
					if(result.errorCode == 0){
						alert('删除成功');
						location.href	=	"{pigcms{:U('travel_person')}";
					}else{
						alert(result.errorMsg);
						generate_status	=	true;
						return false;
					}
				},
				error:function(){
					alert('网络错误');
					generate_status	=	true;
					return false;
				}
			})
		}
	}
</script>
<include file="Public/footer"/>