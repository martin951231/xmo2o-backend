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
					<li class="xd on">
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
                <div class="askfor">
                    <div class="ask_top">
                        <h2>申请向导</h2>
                    </div>
                    <div class="ask_end">
                        <div class="ask_list">
                            <ul class="clr">
                                <li class="clr fl on">
                                    <div class="fl line"><i>1</i></div>
                                    <div class="fl text">实名认证</div>
                                </li>
                                <a href="{pigcms{:U('guide')}"><li class="clr fl on">
                                    <div class="fl line"><i>2</i></div>
                                    <div class="fl text">查看实名验证</div>
                                </li></a>
                                <li class="clr fl on">
                                    <div class="fl line"><i>3</i></div>
                                    <div class="fl text">发布向导</div>
                                </li>
                                <li class="clr fl on">
                                    <div class="fl line"><i>4</i></div>
                                    <div class="fl text">向导查看</div>
                                </li>
                            </ul>
                        </div>
                        <div class="forms_list let800">
                            <div class="date">
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>头&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp像：</em></span>
                                    <div class="p105 clr">
                                        <img src="{pigcms{$now_user['avatar']}" class="w54">
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>真实姓名：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$scenic_aguide['guide_name']}</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>审核状态：</em></span>
                                    <div class="p105 fz18 clr">
                                        <if condition="$scenic_aguide['guide_status'] eq 1"><span class="aud tg">正常</span>
                                        <elseif condition="$scenic_aguide['guide_status'] eq 2"/><span class="aud tg">审核中</span>
                                        <elseif condition="$scenic_aguide['guide_status'] eq 3"/><span style="color:#f5083a;border:#f5083a 1px solid;" class="aud tg">关闭</span>
                                        <elseif condition="$scenic_aguide['guide_status'] eq 4"/><span class="aud tg">审核不通过</span>
                                        </if>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>喜欢昵称：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$scenic_aguide['guide_nickname']}</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>从业年限：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$scenic_aguide['guide_life']}年</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>年&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp龄：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$age}岁</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp别：</em></span>
                                    <div class="p105 fz18 clr"><if condition="$scenic_aguide['guide_sex'] eq 1">男<else/>女</if></div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>服务地区：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$scenic_aguide['province_ids']}，{pigcms{$scenic_aguide['city_ids']}</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>预定价格：</em></span>
                                    <div class="p105 fz18 clr">
                                        <span class="fzbc">{pigcms{$scenic_aguide['guide_price']}/天</span>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>游记图片：</em></span>
                                    <div class="p105 clr">
                                        <div class="item">
                                            <ul class="upload_list3 clr" id="upload_list3">
                                            	<volist name="scenic_aguide['pic']" id="vo">
	                                                <li class="upload_action">
	                                                    <img src="{pigcms{$vo}">
	                                                </li>
                                                </volist>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <if condition="$scenic_aguide['guide_status'] eq 1"><div id="close" class="refer">关闭向导</div>
                                <elseif condition="$scenic_aguide['guide_status'] eq 3"/><div id="open" style="background:#1bccaf;" class="refer">开启向导</div>
                                <elseif condition="$scenic_aguide['guide_status'] eq 4"/>
	                                <div class="surface clr">
	                                    <span class="on fl"><i></i><em>失败原因：</em></span>
	                                    <div class="p105 fz18 clr">
	                                        <span style="color:red;">{pigcms{$scenic_aguide['guide_remarks']}</span>
	                                    </div>
	                                </div>
	                                <a href="{pigcms{:U('again_guide_release')}"><div style="background:#999;" class="refer">重新申请</div></a>
                                </if>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script type="text/javascript">
		var type	=	true;
		$("#close").click(function(){
			if(type == true){
				type	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('close_guide')}",
					dataType : "json",
					data:{
						guide_status	:	3,
						guide_id	:	'{pigcms{$scenic_aguide.guide_id}',
					},
					async:false,
					success : function(result){
						var rideList	=	result;
						if(result.errorCode == 0){
							location.reload();
						}else{
							type	=	true;
							alert(result.errorCode)
						}
					},
					error:function(){
						type	=	true;
					}
				})
			}
		})
		$("#open").click(function(){
			if(type == true){
				type	=	false;
				$.ajax({
					type : "post",
					url : "{pigcms{:U('close_guide')}",
					dataType : "json",
					data:{
						guide_status	:	1,
						guide_id	:	'{pigcms{$scenic_aguide.guide_id}',
					},
					async:false,
					success : function(result){
						var rideList	=	result;
						if(result.errorCode == 0){
							location.reload();
						}else{
							type	=	true;
							alert(result.errorMsg)
						}
					},
					error:function(){
						type	=	true;
					}
				})
			}
		})
    </script>
<include file="Public/footer"/>