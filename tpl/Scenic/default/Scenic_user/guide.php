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
                                <li class="clr fl on">
                                    <div class="fl line"><i>2</i></div>
                                    <div class="fl text">查看实名验证</div>
                                </li>
                                <li class="clr fl">
                                    <div class="fl line"><i>3</i></div>
                                    <div class="fl text">发布向导</div>
                                </li>
                                <li class="clr fl">
                                    <div class="fl line"><i>4</i></div>
                                    <div class="fl text">向导查看</div>
                                </li>
                            </ul>
                        </div>
                        <div class="forms_list let358">
                            <div class="date">
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>真实姓名：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$authentication['user_truename']}</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>身份证号：</em></span>
                                    <div class="p105 fz18 clr">{pigcms{$authentication['user_id_number']}</div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>审核状态：</em></span>
                                    <div class="p105 fz18 clr">
                                        <if condition="$authentication['authentication_status'] eq 0"><span class="aud">审核中</span>
										<elseif condition="$authentication['authentication_status'] eq 1"/><span class="aud">审核通过</span>
										<elseif condition="$authentication['authentication_status'] eq 2"/><span class="aud">审核不通过</span>
                                        </if>
                                    </div>
                                </div>
                                <div class="surface clr">
                                    <span class="on fl"><i></i><em>上传证件：</em></span>
                                    <div class="p105 pt15 clr">
                                        <ul class="fl ulp15">
                                            <li>
                                                <img src="{pigcms{$authentication['authentication_img']}" width="150" height="96">
                                            </li>
                                            <p class="c6">身份证正面</p>
                                        </ul>
                                        <ul class="fl ulp15">
                                            <li>
                                                <img src="{pigcms{$authentication['authentication_back_img']}" width="150" height="96">
                                            </li>
                                            <p class="c6">身份证背面</p>
                                        </ul>
                                    </div>
                                </div>
                                <if condition="$authentication['authentication_status'] eq 2">
	                                <div class="surface clr">
	                                    <span class="on fl"><i></i><em>失败原因：</em></span>
	                                    <div class="p105 fz18 clr"><if condition="$authentication['examine_remarks'] eq 1">照片模糊<elseif condition="$authentication['examine_remarks'] eq 2"/>照片和名字不符<else condition="$authentication['examine_remarks'] eq 3"/>身份证过期</if></div>
	                                </div>
                                	<a href="{pigcms{:U('add_guide')}"><div class="refer">重新审核</div></a>
                                <else/>
                                	<if condition="$scenic_aguide">
                                		<a href="{pigcms{:U('guide_service')}"><div class="refer">向导查看</div></a>
                                	<else/>
                                		<a href="{pigcms{:U('guide_release')}"><div class="refer">发布向导服务</div></a>
                                	</if>
                                </if>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<include file="Public/footer"/>