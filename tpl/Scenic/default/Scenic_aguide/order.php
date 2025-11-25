<include file="Public/header"/>
<link href="{pigcms{$static_path}css/mobiscroll.2.13.2.css" rel="stylesheet"/>
<script src="{pigcms{$static_path}js/mobiscroll.2.13.2.js"></script>
    <section class="search">
        <div class="w1200 clr">
            <div class="fl">
                <div class="logo fl">
                    <a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
                </div>
            </div>
            <div class="fr">
                <ul class="arrow clr">
                    <li class="clr on">
                        <i>1</i>
                        <span>选择向导</span>
                    </li>
                    <li class="clr ou">
                        <i>2</i>
                        <span>填写订单</span>
                    </li>
                    <li class="clr">
                        <i>3</i>
                        <span>在线支付</span>
                    </li>
                    <li class="clr">
                        <i>4</i>
                        <span>支付成功</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <section class="form">
        <div class="w1200">
            <div class="form_list dy_list">
                <dl>
                    <dd>
                        <div class="dd_top clr">
                            <div class="fl icon">
                                <h2 class="fl guide">导游信息</h2>
                            </div>
                        </div>
                        <div class="date_list">
                            <div class="gay_img clr">
                                <div class="img fl">
                                    <img src="{pigcms{$list['avatar']}" width="112" height="112">
                                </div>
                                <div class="p136">
                                    <div class="name_top">
                                        <span>{pigcms{$list['guide_name']}</span><if condition="$list['guide_sex'] eq 2"><i></i><else/><i class="on"></i></if>
                                    </div>
                                    <div class="text_hz">
                                        <span>实名</span>
                                        <span>导游证</span>
                                    </div>
                                    <div class="score">
                                        <div class="atar_Show">
                                            <p></p>
                                        </div>
                                        <span>{pigcms{$list['score_mean']}</span>分
                                    </div>
                                    <div class="price">
                                        <if condition="$list.guide_price eq 0.00"><span>免费</span><else/>
											<span>￥<i>{pigcms{$list.guide_price}</i></span>/天
                                        </if>
                                    </div>
                                </div>
                            </div>
                            <div class="dates">
                                <span>预定日期：</span>
                                <input type="text" value="{pigcms{$time}" readonly="readonly" id="demo">
                                <span class="m5">至</span>
                                <input type="text" placeholder="请选择游玩日期" value="{pigcms{$time}" readonly="readonly" id="demo1">
                                <span class="ml5">共计<i id="number_day">1</i>天</span>
                            </div>
                        </div>
                    </dd>
                    <dd>
                        <div class="dd_top clr">
                            <div class="fl icon">
                                <h2 class="fl travel">出行人列表</h2>
                                <span class="fl remark">请准确填写出行人信息</span>
                            </div>
                        </div>
                        <div class="common">
                            <h2>常用旅客</h2>
                            <div class="enger clr">
                            	<volist name="scenic_family" id="vv">
                                	<a href="javascript:void(0)">{pigcms{$vv['family_name']}<span style="display:none;">{pigcms{$vv['family_id']}</span></a>
                                </volist>
                            </div>
                        </div>
                        <div class="date_list">
                            <div class="date">
                                <h2>出行人</h2>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" placeholder="请输入姓名" name="name" id="text1">
                                        <em>请输入姓名</em>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp别：</em></span>
                                    <div class="p105 clr menw">
                                        <span class="on">男</span>
                                        <span >女</span>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>手机号码：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" placeholder="请输入手机号码" id="text2">
                                        <em>请输入手机号码</em>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>身份证号：</em></span>
                                    <div class="p105 clr">
                                        <input type="text" placeholder="请输入身份证号" id="text3">
                                        <em>请输入身份证号</em>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="javascript:void(0)" class="add addson">添加出行人</a>
                    </dd>
                    <dd>
                        <div class="dd_top clr">
                            <div class="fl icon">
                                <h2 class="fl infor">联系人信息</h2>
                                <span class="fl remark">用于向导联系您</span>
                            </div>
                        </div>
                        <div class="date_list">
                            <div class="date">
<!--                                <h2>联系人</h2>-->
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名：</em></span>
                                    <div class="p105 clr">
                                        <input id="name" type="text" placeholder="请输入姓名">
                                        <em>请输入姓名</em>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>手机号码：</em></span>
                                    <div class="p105 clr">
                                        <input id="phone" type="text" placeholder="请输入手机号码">
                                        <em>请输入手机号码</em>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </dd>
                    <dd>
                        <div class="dd_top clr">
                            <div class="fl icon">
                                <h2 class="fl texts">备注信息</h2>
                            </div>
                        </div>
                        <div class="gclose">
                            <div class="date">
                                <h2>补充说明</h2>
                                <div class="stop_list">
                                    <div class="surface clr">
                                        <textarea id="explain" placeholder="如果对订单有特殊要求，可以通过这里留言。"></textarea>
                                    </div>
                                 </div>
                            </div>
                        </div>
                    </dd>
                </dl>
                <div class="return clr">
                    <div class="return_n clr">
                        <div class="fl">
                            <a href="#" class="rehui">返回上一步</a>
                        </div>
                        <div class="fr clr">
                            <div class="fl total">
                                <span>应付总价</span>
                                <i>￥<em id="money">{pigcms{$list.guide_price}</em></i>
                            </div>
                            <div class="fr" id="submit">
                                <a href="javascript:void(0);" class="submit">同意以下协议，提交订单</a>
                            </div>
                        </div>
                    </div>
                    <div class="read fr on" id="clause">我已阅读并核对订单信息，接受以下合同条款中所有内容</div>
                </div>
            </div>
        </div>
    </section>

	<script type="text/javascript">
		/*评分*/
		$(".atar_Show p").each(function(index, element) {
			var num=$(this).parents(".score").find("span").text();
			var www=num*13;//
			$(this).css("width",www);
		});
		//插件日历
		$(function () {
			var currYear = new Date().getFullYear();
			var opt = {
				'dateYMD': {
					preset: 'date',
					dateFormat: 'yyyy-mm-dd',
					theme: 'android-ics light', //皮肤样式
					display: 'modal',           //显示方式
					mode: 'scroller',           //日期选择模式
					showNow: true,
					nowText: "今天",
					startYear: currYear,    //开始年份
					endYear: currYear + 10, //结束年份
					minDate: new Date(),    //只能选择
				},'select': {
					preset: 'select'
				}
			}
			$('#demo').scroller($.extend(opt['dateYMD'],opt['default']));
			$('#demo1').scroller($.extend(opt['dateYMD'],opt['default']));
			$('#demo,#demo1').bind('change',function(){
				var appDate = $('#demo').val().split('-');
				var	str=appDate[0]+appDate[1]+appDate[2];
				var appDate1 = $('#demo1').val().split('-');
				var	str1=appDate1[0]+appDate1[1]+appDate1[2];
				if(str1 < str){
					alert('结束日期不能小于开始日期');
					return false;
				}
				var appDateD = new Date(appDate[0],appDate[1]-1,appDate[2]);
				var appDate1D = new Date(appDate1[0],appDate1[1]-1,appDate1[2]);
				var time	=	(appDate1D-appDateD)/86400000+1;
				$("#number_day").html(time);
				var money	=	'{pigcms{$list.guide_price}';
				var total	=	money*time;
				$("#money").html(total);
			});
		});
		var number = 0;
		/*常用旅客*/
		$(".enger a,.return .read").click(function(){
			if($(this).hasClass("on")){
				$(this).removeClass("on");
			}else{
				$(this).addClass("on");
			}
		})
		/*男女切换*/
		$(".menw span").click(function(){
			$(this).addClass("on").siblings("span").removeClass("on");
		})
		function add(number){
			if($("."+number+"s").hasClass("on")){
				$("."+number+"s").removeClass("on");
			}else{
				$("."+number+"s").addClass("on");
			}
		}
		var generate_status	=	true;
		/*添加出行人*/
		$(".addson").click(function(){
			var user	=	"{pigcms{$_SESSION['user']['uid']}";
			if(!user){
				$(".board").show();
				$(".mark").show();
				return false;
			}
			number = number+1;
			if(generate_status == true){
				generate_status	=	false;
				var family_name	=	$('#text1').val();
				var certificates=	$('#text3').val();
				var phone		=	$('#text2').val();
				var gender		=	$('.surface .p105 span.on').text();
				if(family_name == ''){
					generate_status	=	true;
					alert('姓名不能为空');
					return false;
				}
				var check	=	checkPhone(phone);
				if(check == false){
					generate_status	=	true;
					alert('请填写正确的手机号码');
					return false;
				}
				var check = checkCardId(certificates);
				if(check == false){
					generate_status	=	true;
					return false;
				}
				if(gender == '男'){
					gender	=	1;
				}else{
					gender	=	2;
				}
				$.ajax({
					type : "post",
					url : "{pigcms{:U('Scenic_user/add_travel_json')}",
					dataType : "json",
					data:{
						family_name	:	family_name,
						certificates	:	certificates,
						phone	:	phone,
						gender	:	gender,
					},
					success : function(result){
						if(result.errorCode == 0){
							var rideList	=	result.result.family_id;
							$(".enger").append("<a href='javascript:void(0)' class='"+number+"s' onclick='add("+number+")'>"+family_name+"<span style='display:none;'>"+rideList+"</a>");
							$(".date_list .date").find("input").val("");
							generate_status	=	true;
						}else{
							alert(result.errorMsg);
							generate_status	=	true;
						}
					},
					error:function(){
						alert('提交订单失败');
						generate_status	=	true;
					}
				})
			}
		})
		$("#submit").click(function(){
			var start_time = $('#demo').val();			//开始日期
			var end_time = $('#demo1').val();			//结束日期
			var number_day	=	$("#number_day").text();	//预定天数
			if(number_day <= 0){
				alert('开始时间不能大于结束时间');
				return false;
			}
			var family_id	=	'';
			var travel_number	=	0;
			$(".common .enger a.on").each(function(){
				var on	=	$(this).text();
				if(on){
					family_id	=	family_id+','+$(this).children('span').text();
					travel_number	=	travel_number+1;
				}
            });
			if(!travel_number){
				generate_status	=	true;
				alert('请选择出行人');
				return false;
        	}
			var name	=	$('#name').val();		//取票人
			if(!name){
				alert('请填写联系人');
				return false;
			}
			var phone	=	$("#phone").val();		//取票人电话
			if(!phone){
				alert('请填写取票人电话');
				return false;
			}
			var explain	=	$("#explain").val();
			var clause	=	$("#clause").attr("class");
			if(clause == 'read fr'){
				alert('请勾选合同条款');
				return false;
			}
			if(generate_status	==	true){
				generate_status	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('go_pay')}",
					dataType : "json",
					data:{
						start_time	:	start_time,
						end_time	:	end_time,
						gender		:	1,
						number_day	:	number_day,
						phone		:	phone,
						name		:	name,
						family_id	:	family_id,
						explain		:	explain,
						guide_id	:	'{pigcms{$list.guide_id}',
					},
					async:false,
					success : function(result){
						if(result.errorCode == 0){
							location.href =	result.result;
						}else{
							generate_status	=	true;
							alert(result.errorMsg);
						}
					},
					error:function(){
						generate_status	=	true;
						alert('下单失败，请重试。');
					}
				})
			}
		})
	</script>
<include file="Public/footer"/>