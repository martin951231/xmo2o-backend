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
					<div class="people_list_n">
						<h5><if condition="$_GET['family_id']">修改出行人<else/>新增出行人</if></h5>
						<div class="date_list">
							<div class="surface clr">
								<span class="on fl"><i></i><em>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名：</em></span>
								<div class="p105 clr">
									<input id="name" value="{pigcms{$find['family_name']}" type="text" placeholder="请输入姓名">
									<em>请输入姓名</em>
								</div>
							</div>
							<div class="surface clr">
								<span class="on fl"><i></i><em>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp别：</em></span>
								<div class="p105 clr menw gender">
									<span <if condition="$find['gender'] eq 1">class="on"</if> >男</span>
									<span <if condition="$find['gender'] eq 2">class="on"</if> >女</span>
								</div>
							</div>
							<div class="surface clr">
								<span class="on fl"><i></i><em>手机号码：</em></span>
								<div class="p105 clr">
									<input id="phone" value="{pigcms{$find['phone']}" type="text" placeholder="请输入手机号码">
									<em>请输入手机号码</em>
								</div>
							</div>
							<div class="surface clr">
								<span class="on fl"><i></i><em>身份证号：</em></span>
								<div class="p105 clr">
									<input id="id_number" value="{pigcms{$find['certificates']}" type="text" placeholder="请输入身份证号">
									<em>请输入身份证号</em>
								</div>
							</div>
							<div class="submit">
								<a href="javascript:void(0)" id="submit" class="preservation">保存</a>
								<a href="{pigcms{:U('Scenic_user/travel_person')}" class="cancel">取消</a>
							</div>
						</div>
					</div>
				</div>
			</div>
 		</div>
	</section>
<script type="text/javascript">
	var family_id	=	"{pigcms{$family_id}";
	/*男女切换*/
	$(".menw span").click(function(){
		$(this).addClass("on").siblings("span").removeClass("on");
	})
	var generate_status	=	true;
	$("#submit").click(function(){
		if(generate_status == true){
			generate_status	=	false;
			var name	=	$("#name").val();
			if(!name){
				alert('姓名不能为空');
				generate_status	=	true;
				return false;
			}
			var gender	=	$(".gender .on").text();
			if(gender == '男'){
				gender	=	1;
			}else{
				gender	=	2;
			}
			var phone	=	$("#phone").val();
			is_phone	=	checkPhone(phone);
			if(is_phone == false){
				alert('请输入正确的手机号');
				generate_status	=	true;
				return false;
			}
			var id_number	=	$("#id_number").val();
			var check = checkCardId(id_number);
			if(check == false){
				generate_status	=	true;
				return false;
			}
			$.ajax({
				type : "post",
				url : "{pigcms{:U('add_travel_json')}",
				dataType : "json",
				data:{
					family_id	:	family_id,
					family_name	:	name,
					phone	:	phone,
					certificates:	id_number,
					gender	:	gender,
				},
				async:false,
				success : function(result){
					if(result.errorCode == 0){
						if(result.result.status == 1){
							alert('修改成功');
						}else{
							alert('添加成功');
						}
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
	})
</script>
<include file="Public/footer"/>