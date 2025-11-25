<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Index/saveAdmin')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">账号</th>
				<td><input type="text" class="input fl" name="account" id="account" size="20" placeholder="请输入账号" validate="maxlength:100,required:true" value="{pigcms{$admin['account']}"/></td>
			</tr>
			<tr>
				<th width="80">密码</th>
				<td><input type="password" class="input fl" name="pwd" id="pwd" size="20" placeholder=""  validate="checkPasswd:true"  tips="添加时候必填，在修改时候不填写证明不修改密码" autocomplete="new-password"/></td>
			</tr>
			<tr>
				<th width="80">真实姓名</th>
				<td><input type="text" class="input fl" name="realname" id="realname" size="20" placeholder="" tips="填写该账号使用者的真实姓名" value="{pigcms{$admin['realname']}"/></td>
			</tr>
			<if condition="$config.international_phone eq 1">
			<tr>
				<th width="80">区号</th>
				<td>
					

					<pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
				 	<option value="{pigcms{$vo.code}"  <if condition="$admin.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
				</pigcms:national_phone>
				
			</tr>
			</if>
			<tr>
				<th width="80">电话</th>
				<td>
				
				<input type="text" class="input fl" name="phone" size="20" placeholder=""  value="{pigcms{$admin['phone']}"/></td>
			</tr>
			<tr>
				<th width="80">EMAIL</th>
				<td><input type="text" class="input fl" name="email" size="20" value="{pigcms{$admin['email']}"/></td>
			</tr>
			<tr>
				<th width="80">QQ</th>
				<td><input type="text" class="input fl" name="qq" size="20" value="{pigcms{$admin['qq']}"/></td>
			</tr>
			<tr>
				<th width="80">描述</th>
				<td>
				<textarea name="des">{pigcms{$admin['des']}</textarea>
				</td>
			</tr>
			<if condition="$config.open_admin_code eq 1">
			<tr>
				<th width="80">邀请码</th>
				<td><input type="text" class="input fl" name="invit_code" size="20" value="{pigcms{$admin['invit_code']}"/></td>
			</tr>
			</if>
			<if condition="$config.open_extra_price  eq 1">
			<tr>
				<th width="80">区域管理员{pigcms{$config.score_name}结算比例</th>
				<td><input type="text" class="input fl" name="score_percent" size="20"  validate="required:true,min:0,max:100" value="{pigcms{$admin['score_percent']|floatval}"/></td>
			</tr>
			</if>
			
			
			<php>if($system['level']==2 || $system['level']==0){</php>
			<tr>
				<th width="160">是否是区域管理员</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$admin['open_admin_area'] eq 1">selected</if>"><span>是</span>
					<input type="radio" name="open_admin_area" value="1" <if condition="$admin['open_admin_area'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable" ><label class="cb-disable  <if condition="$admin['open_admin_area'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="open_admin_area" value="0" <if condition="$admin['open_admin_area'] eq 0">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="开启后请设置管理员所在区域"></em>
				</td>
			</tr>
			<php>}else if($system['level']==1){</php>
			<tr>
				<th width="160">是否是区域管理员</th>
				<td>
					是
					<input type="hidden" name="open_admin_area" value="1"/>
				</td>
			</tr>
			<php>}</php>
			
			<if condition="$system['area_type'] lt 3">
				<tr class="open_admin_area" style="<if condition="$admin['open_admin_area'] eq 0 AND $system['level']!=1">display: none;</if>">
					<th width="160">管理员所在区域</th>
					<td id="choose_pca" street_id="{pigcms{$admin.street_id}" province_idss="{pigcms{$admin.area.1}" city_idss="{pigcms{$admin.area.2}" area_id="{pigcms{$admin.area.3}" style="display:inline"></td>
				</tr>
			</if>
			<tr>
				<th width="80">是否接收商家申请提现/商家欠费时提醒</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$admin['withdraw_notice'] eq 1">selected</if>"><span>接收</span><input type="radio" name="withdraw_notice" value="1" <if condition="$admin['withdraw_notice'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$admin['withdraw_notice'] eq 0">selected</if>"><span>不接收</span><input type="radio" name="withdraw_notice" value="0" <if condition="$admin['withdraw_notice'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="开启后，商家提现与商家欠费会通过微信模板消息提醒到该管理员，区域管理员则接受自己区域下的商家提醒"></em>
				</td>
			</tr>

            <tr>
                <th width="80">是否接收商家申请注册时提醒</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$admin['mer_reg_notice'] eq 1">selected</if>"><span>接收</span><input type="radio" name="mer_reg_notice" value="1" <if condition="$admin['mer_reg_notice'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable  <if condition="$admin['mer_reg_notice'] eq 0">selected</if>"><span>不接收</span><input type="radio" name="mer_reg_notice" value="0" <if condition="$admin['mer_reg_notice'] eq 0">checked="checked"</if> /></label></span>
                    <em class="notice_tips" tips="开启后，商家注册会通过微信模板消息提醒到该管理员，区域管理员只能接收到自己区域下的商家申请提现"></em>
                </td>
            </tr>
			
			<if condition="$config['user_is_real_name']">
				<tr>
					<th width="80">是否接收【顺风车】车主认证审核提醒</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$admin['car_authentication_notice'] eq 1">selected</if>"><span>接收</span><input type="radio" name="car_authentication_notice" value="1" <if condition="$admin['car_authentication_notice'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable  <if condition="$admin['car_authentication_notice'] eq 0">selected</if>"><span>不接收</span><input type="radio" name="car_authentication_notice" value="0" <if condition="$admin['car_authentication_notice'] eq 0">checked="checked"</if> /></label></span>
						<em class="notice_tips" tips="开启后，用户在提交顺风车的车主认证时，管理员可以收到待审核的微信模板消息提醒"></em>
					</td>
				</tr>
			</if>
			
			<if condition="$config['wap_home_show_classify']">
				<tr>
					<th width="80">是否接收同城分类信息发布后审核提醒</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$admin['classify_notice'] eq 1">selected</if>"><span>接收</span><input type="radio" name="classify_notice" value="1" <if condition="$admin['classify_notice'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable  <if condition="$admin['classify_notice'] eq 0">selected</if>"><span>不接收</span><input type="radio" name="classify_notice" value="0" <if condition="$admin['classify_notice'] eq 0">checked="checked"</if> /></label></span>
						<em class="notice_tips" tips="开启后，用户在提交同城分类信息发布后，管理员可以收到待审核的微信模板消息提醒"></em>
					</td>
				</tr>
			</if>

            <tr>
                <th width="80">是否可审核普通管理员提交的余额变动申请</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$admin['verify_user_money_change'] eq 1">selected</if>"><span>接收</span><input type="radio" name="verify_user_money_change" value="1" <if condition="$admin['verify_user_money_change'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable  <if condition="$admin['verify_user_money_change'] eq 0">selected</if>"><span>不接收</span><input type="radio" name="verify_user_money_change" value="0" <if condition="$admin['verify_user_money_change'] eq 0">checked="checked"</if> /></label></span>
                    <em class="notice_tips" tips="开启后，普通管理员在提交用户余额变动数据后，当前管理员可以收到待审核的微信模板消息提醒"></em>
                </td>
            </tr>
			
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$admin['status'] eq 1">selected</if>"><span>显示</span><input type="radio" name="status" value="1" <if condition="$admin['status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$admin['status'] eq 0">selected</if>"><span>隐藏</span><input type="radio" name="status" value="0" <if condition="$admin['status'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			
			<tr>
				<th width="80">权限</th>
				<td>
					
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Index/menu',array('admin_id'=>$admin['id']))}','设置管理员使用权限',700,500,true,false,false,editbtn,'menu',true);" style="color:blue">设置管理员使用权限</a>
					
				</td>
				
			</tr>
			<if condition="$system['level'] eq 2">
			<tr>
				<th width="80">权限分组</th>
				<td>
					
					<select name="authority_group_id" tips="权限分组">
						<option value="0" <if condition="empty($admin['authority_group_id'])">selected="selected"</if>>不选</option>	
						<volist name="authority_group" id="vo">
							<option value="{pigcms{$vo.id}" <if condition="$admin['authority_group_id'] eq $vo['id']">selected="selected"</if>>{pigcms{$vo.name}</option>								
						</volist>
					</select>
				</td>
				
			</tr>
			</if>
			<if condition="$system['admin'] neq 2 AND $customization['life_tools']">
			<tr>
				<th width="80">上级管理员</th>
				<td>
					<select name="fid" tips="上级管理员" <if condition="$disable">disabled</if>> 
						<option value="0" <if condition="empty($admin['fid'])">selected="selected"</if>>不选</option>	
						<volist name="super_admins" id="vo">
							<option value="{pigcms{$vo.id}" <if condition="$admin['fid'] eq $vo['id']">selected="selected"</if>>{pigcms{$vo.show_name}</option>								
						</volist>
					</select>
				</td>
			</tr>
			</if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
		get_first_word('area_name','area_url','first_pinyin');
		
		$('input[name="open_admin_area"]').click(function(){
			var sub = $(this);
			if(sub.val()==1){
				$('.open_admin_area').show();
			}else{
				$('.open_admin_area').hide();
			}
		});

		$.validator.addMethod("checkPasswd",function(value,element,params){ 
			var checkPasswd = /^[a-zA-Z0-9_]{8,16}$/; 
			return this.optional(element)||(checkPasswd.test(value)); 
		},'密码由大小写字母、数字和下划线组成，8-16位')
	</script>
	<if condition="$system['area_type'] lt 3">
	<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js?221"></script>
	</if>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>