<div class="component-order-nav mt-component--booted">
	<div class="side-nav J-order-nav">
		<div class="J-side-nav__user side-nav__user cf">
			<a href="javascript:void(0);" title="帐户设置" class="J-user item user">
				<img src="<if condition="$now_user['avatar']">{pigcms{$now_user.avatar}<else/>{pigcms{$static_path}images/user-default-avatar.png</if>" width="30" height="30" alt="{pigcms{$now_user.nickname}头像"/>
			</a>
			<div class="item info_nickname">
				<div class="info__name" style="height:36px;line-height:36px;">{pigcms{$now_user.nickname}</div>
			</div>
			<div>等级：<a href="{pigcms{:U('Level/index')}">
				<php>if(isset($levelarr[$now_user['level']])){ 
				$imgstr='';
				if(!empty($levelarr[$now_user['level']]['icon'])) $imgstr='<img src="'.file_domain().$levelarr[$now_user['level']]['icon'].'" width="15" height="15">';
				echo $imgstr.' '.$levelarr[$now_user['level']]['lname'];
				}else{echo '暂无等级';}</php></a>
			</div>
			<div <if condition="$config.sign_get_score eq 0">style="display:none"</if>>
				<a class="btn-white js-btn-sign sign_in" data-bk="sy_qd" href="javascript:void(0);" id="sign_today"> 签到</a>
			</div>
		</div>
		<div class="side-nav__account cf">
			<a class="item item--first" href="{pigcms{:U('Credit/index')}" title="{pigcms{$now_user.now_money}">{pigcms{$now_user.now_money}<span>余额</span></a>
			<a class="item" href="{pigcms{:U('Point/index')}" title="{pigcms{$now_user.score_count}">{pigcms{$now_user.score_count}<span>{pigcms{$config['score_name']}</span></a>
		</div>
		<dl class="side-nav__list">
			<dt class="first-item"><strong>我的订单</strong></dt>
			<dd>
				<ul class="item-list">
					<if condition="$config['group_alias_name']">
						<li <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('index'))">class="current"</if>><a href="{pigcms{:U('Index/index')}" <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('index'))">style='background:{pigcms{$config.mobile_head_color}'</if>>{pigcms{$config.group_alias_name}订单</a></li>
					</if>
					<if condition="$config['appoint_alias_name']">
						<li <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('appoint_order'))">class="current"</if>><a href="{pigcms{:U('Index/appoint_order')}" <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('appoint_order'))">style='background:{pigcms{$config.mobile_head_color}'</if>>{pigcms{$config.appoint_alias_name}订单</a></li>
					</if>
					<li <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('shop_list'))">class="current"</if>><a href="{pigcms{:U('Index/shop_list')}" <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('shop_list'))">style='background:{pigcms{$config.mobile_head_color}'</if>>{pigcms{$config.shop_alias_name}订单</a></li>
					<if condition="isset($config['gift_alias_name'])">
						<li <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('gift_list'))">class="current"</if>><a href="{pigcms{:U('Index/gift_list')}" <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('gift_list'))">style='background:{pigcms{$config.mobile_head_color}'</if>>{pigcms{$config.gift_alias_name}订单</a></li>
					</if>
					<if condition="!$config['single_system']">
						<li <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('lifeservice'))">class="current"</if>><a href="{pigcms{:U('Index/lifeservice')}" <if condition="in_array(MODULE_NAME,array('Index')) && in_array(ACTION_NAME,array('lifeservice'))">style='background:{pigcms{$config.mobile_head_color}'</if>>缴费订单</a></li>
					</if>
					<if condition="$config['group_alias_name']">
						<li <if condition="in_array(MODULE_NAME,array('Collect'))">class="current"</if>><a href="{pigcms{:U('Collect/index')}" <if condition="in_array(MODULE_NAME,array('Collect'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的收藏</a></li>
					</if>
				</ul>
			</dd>
			<dt><strong>我的评价</strong></dt>
			<dd>
				<ul class="item-list">
					<li <if condition="in_array(MODULE_NAME,array('Rates')) && in_array(ACTION_NAME,array('index','meal','shop'))">class="current"</if>><a href="{pigcms{:U('Rates/index')}" <if condition="in_array(MODULE_NAME,array('Rates')) && in_array(ACTION_NAME,array('index','meal','shop'))">style='background:{pigcms{$config.mobile_head_color}'</if>>待评价</a></li>
					<li <if condition="in_array(MODULE_NAME,array('Rates')) && in_array(ACTION_NAME,array('rated','meal_rated','shop_rated'))">class="current"</if>><a href="{pigcms{:U('Rates/rated')}" <if condition="in_array(MODULE_NAME,array('Rates')) && in_array(ACTION_NAME,array('rated','meal_rated','shop_rated'))">style='background:{pigcms{$config.mobile_head_color}'</if>>已评价</a></li>
				</ul>
			</dd>
			<if condition="$config.open_score_fenrun eq 1">
			<dt><strong>我的佣金</strong></dt>
			<dd class="last">
				<ul class="item-list">
					<li <if condition="in_array(ACTION_NAME,array('frozen_award_index'))">class="current"</if>><a href="{pigcms{:U('Fenrun/frozen_award_index')}" <if condition="in_array(ACTION_NAME,array('frozen_award_index'))">style='background:{pigcms{$config.mobile_head_color}'</if>>冻结佣金</a></li>
					<li <if condition="in_array(ACTION_NAME,array('user_free_award_list'))">class="current"</if>><a href="{pigcms{:U('Fenrun/user_free_award_list')}" <if condition="in_array(ACTION_NAME,array('user_free_award_list'))">style='background:{pigcms{$config.mobile_head_color}'</if>>可用佣金</a></li>
				
				
				</ul>
			</dd>
			</if>
			<dt><strong>我的账户</strong></dt>
			<dd class="last">
				<ul class="item-list">
					<li <if condition="in_array(MODULE_NAME,array('Spread'))">class="current"</if>><a href="{pigcms{:U('Spread/index')}" <if condition="in_array(MODULE_NAME,array('Spread'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的推广</a></li>
					<if condition="isset($config['specificfield'])">
					<li <if condition="MODULE_NAME eq 'Index' AND ACTION_NAME eq 'myinfo'">class="current"</if>><a href="{pigcms{:U('Index/myinfo')}" <if condition="MODULE_NAME eq 'Index' AND ACTION_NAME eq 'myinfo'">style='background:{pigcms{$config.mobile_head_color}'</if>>完善信息</a></li>
					</if>
					<li <if condition="in_array(MODULE_NAME,array('Point'))">class="current"</if>><a href="{pigcms{:U('Point/index')}" <if condition="in_array(MODULE_NAME,array('Point'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的{pigcms{$config['score_name']}</a></li>
					<li <if condition="in_array(MODULE_NAME,array('Credit'))">class="current"</if>><a href="{pigcms{:U('Credit/index')}" <if condition="in_array(MODULE_NAME,array('Credit'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的余额</a></li>
					<if condition="$config.open_score_fenrun eq 1"><li <if condition="in_array(ACTION_NAME,array('fenrun_money_list'))">class="current"</if>><a href="{pigcms{:U('Fenrun/fenrun_money_list')}" <if condition="in_array(ACTION_NAME,array('fenrun_money_list'))">style='background:{pigcms{$config.mobile_head_color}'</if>>分润钱包</a></li></if>
					<li <if condition="in_array(MODULE_NAME,array('Level'))">class="current"</if>><a href="{pigcms{:U('Level/index')}" <if condition="in_array(MODULE_NAME,array('Level'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的等级</a></li>
					<li <if condition="in_array(MODULE_NAME,array('Adress'))">class="current"</if>><a href="{pigcms{:U('Adress/index')}" <if condition="in_array(MODULE_NAME,array('Adress'))">style='background:{pigcms{$config.mobile_head_color}'</if>>收货地址</a></li>
				</ul>
			</dd>
			<if condition="$config['portal_switch'] eq 1">
				<dt><strong>我的门户</strong></dt>
				<dd class="last">
					<ul class="item-list">
						<li <if condition="in_array(MODULE_NAME,array('Portal')) && in_array(ACTION_NAME,array('tieba'))">class="current"</if>><a href="{pigcms{:U('Portal/tieba')}" <if condition="in_array(MODULE_NAME,array('Portal')) && in_array(ACTION_NAME,array('tieba'))">style='background:{pigcms{$config.mobile_head_color}'</if>>我的帖子</a></li>
					<li <if condition="in_array(MODULE_NAME,array('Yellow'))">class="current"</if>><a href="{pigcms{:U('Yellow/index')}" <if condition="in_array(MODULE_NAME,array('Yellow'))">style='background:{pigcms{$config.mobile_head_color}'</if>>黄页申请</a></li>
				</ul>
			</dd>
			</if>
			
		</dl>
	</div>
</div>
<script>

</script>
<script>

$('#sign_today').click(function(){
	<if condition="$config.pc_sign_url neq ''">
		var pc_url = '{pigcms{$config.pc_sign_url}';
		pc_url = pc_url.substr(0,4).toLowerCase() == "http" ? pc_url : "http://" + pc_url;;
	
		window.location.href= pc_url;
	<else />
	$.post('{pigcms{:U('Index/sign')}','', function(data, textStatus, xhr) {
		artDialog({	
            content:data.msg,
            lock:true,
            style:'succeed noClose'
        });

	});
	</if>

});


</script>