<include file="Public:header"/>
<style>

</style>
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Deliver/user_edit')}&uid={pigcms{$_GET['uid']}&frame={pigcms{$_GET['frame']}">配送员详细信息</a>|
			<!-- <a href="{pigcms{:U('Deliver/user_registry')}&uid={pigcms{$_GET['uid']}&frame={pigcms{$_GET['frame']}" class="on">注册信息</a> -->
		</ul>
	</div>
	<form id="myform" method="post" action="{pigcms{:U('Deliver/user_edit')}" frame="true" refresh="true">
		<input type="hidden" name="uid" value="{pigcms{$now_user.uid}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">姓名</th>
				<td width="90%">
					<!-- <input type="text" class="input fl" name="name" size="20" validate="maxlength:100,required:true" value="{pigcms{$now_user.name}"/> -->
					{pigcms{$now_user.name}
				</td>
			</tr>
			<tr>
				<th width="15%">手机号</th>
				<td width="90%">
					<!-- <php>if(!$config['is_network_demo_domain']){</php>
						<pigcms:national_phone id="phone_country_type" style="float:left;margin-right:5px;">
							<option value="{pigcms{$vo.code}"  <if condition="$now_user.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
						</pigcms:national_phone>
						<input type="text" class="input fl" name="phone" size="30" validate="<if condition="!$config['international_phone']">mobile:true,</if>required:true" value="{pigcms{$now_user.phone}"/>
					<php>}</php> -->
					{pigcms{$now_user.phone}
				</td>
			</tr>
			<tr>
				<th width="15%">身份证号</th>
				<td width="90%">
					<!-- <input type="text" class="input fl" name="card_number" size="20" validate="maxlength:100,required:true" value="{pigcms{$now_user.card_number}"/> -->
					{pigcms{$now_user.card_number}
				</td>
			</tr>
			<php>if($now_user['user_field']){</php>
				{foreach $now_user['user_field'] as $v}
				<tr>
					<th width="15%">{pigcms{$v['value']}</th>
					<td width="90%">
						<!-- <input type="text" class="input fl" name="card_number" size="20" validate="maxlength:100,required:true" value="{pigcms{$now_user.card_number}"/> -->
						{pigcms{$v['value']}
					</td>
				</tr>
				{/foreach}
			<php>}</php>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>