<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/amend')}" frame="true" refresh="true" autocomplete="off">
		<input type="hidden" name="uid" value="{pigcms{$now_user.uid}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">ID</th>
				<td width="35%">
					<div>{pigcms{$now_user.uid}<if condition="$now_user.spread gt 0">({pigcms{$now_user.spread_txt})</if>
					<if condition="$now_user['avatar']">
						<br/><img style="width:50px;height:50px;margin-top:5px" src="{pigcms{$now_user.avatar|replace_file_domain}"/>
					</if>
					</div>
				</td>
				<th width="15%">微信唯一标识</th>
				<td width="35%">
					<if condition="$now_user['openid']">
						<div>公众号：{pigcms{$now_user.openid}</div>
					</if>
					<if condition="$now_user['app_openid']">
						<div>App：{pigcms{$now_user.app_openid}</div>
					</if>
					<if condition="$now_user['wxapp_openid']">
						<div>小程序：{pigcms{$now_user.wxapp_openid}</div>
					</if>
					<if condition="$now_user['union_id']">
                        <div>微信统一：{pigcms{$now_user.union_id}</div>
                    </if>
					<div style="margin-top:5px;">平台公众号：<if condition="$now_user['is_follow']">已关注<else/>未关注</if></div>
					<if condition="$now_user['device_id']">
                        <div>APP设备号：{pigcms{$now_user.device_id}</div>
                    </if>
				</td>
			</tr>

			<tr>
				<th width="15%">昵称</th>
				<td width="35%"><input type="text" class="input fl" name="nickname" size="20" validate="maxlength:50,required:true" value="{pigcms{$now_user.nickname}" readonly="readonly" id="nickname"/></td>
				
				<th width="15%">手机号</th>
				<td width="35%" <php>if(isset($config['not_o2o_demo'])){</php>style="display:none"<php>}</php>>
				
				<pigcms:national_phone id="phone_country_type" style="float:left;margin-right:5px;">
					<option value="{pigcms{$vo.code}"  <if condition="$now_user.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
				</pigcms:national_phone>

				<input type="text" class="input fl" name="phone" size="20" <if condition="!$config['international_phone']">validate="mobile:true"</if> value="{pigcms{$now_user.phone}" autocomplete="off" id="phone" readonly="readonly"/></td>

			</tr>
			<if condition="$now_user['school_type'] eq '1'">
			<tr>
				<th width="15%">身份证号</th>
				<td width="35%"><input type="text" class="input fl" name="school_sfz" size="20" validate="maxlength:50,required:true" value="{pigcms{$now_user.school_sfz}" id="school_sfz"/></td>
				
				<th width="15%">学工号</th>
				<td width="35%">

				<input type="text" class="input fl" name="school_no" size="20" value="{pigcms{$now_user.school_no}" autocomplete="off" id="school_no" /></td>

			</tr>
			</if>
			<tr>
				<th width="15%">密码</th>
				<td width="35%"><input type="password" class="input fl" name="pwd" size="20" value="" tips="不修改则不填写" autocomplete="new-password" id="pwd" readonly="readonly"/></td>
				<th width="15%">性别</th>
				<td width="35%" class="radio_box">
					<span class="cb-enable"><label class="cb-enable <if condition="$now_user['sex'] eq 1">selected</if>"><span>男</span><input type="radio" name="sex" value="1"  <if condition="$now_user['sex'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_user['sex'] eq 2">selected</if>"><span>女</span><input type="radio" name="sex" value="2"  <if condition="$now_user['sex'] eq 2">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<tr>
				<th width="15%">姓名</th>
				<td width="35%"><input type="text" class="input fl" name="truename" size="20" validate="maxlength:20" value="{pigcms{$now_user.truename}"/></td>
				<th width="15%">邮箱</th>
				<td width="35%" >
					<input type="text" class="input " name="email" size="30" value="{pigcms{$now_user.email}"/>
				</td>
			</tr>
			<tr>
				<th width="15%">QQ号</th>
				<td width="35%"><input type="text" class="input fl" name="qq" size="20" value="{pigcms{$now_user.qq}"/></td>
				<th width="15%">状态</th>
				<td width="35%" class="radio_box">
					<span class="cb-enable"><label class="cb-enable <if condition="$now_user['status'] eq 1">selected</if>"><span>正常</span><input type="radio" name="status" value="1"  <if condition="$now_user['status'] eq 1">checked="checked"</if>/></label></span>
					   <if condition="$now_user['status'] eq 2">
						<span class="cb-disable"><label class="cb-disable selected"><span>未审核</span><input type="radio" name="status" value="2"  checked="checked"/></label></span>
						<elseif condition="$now_user['status'] eq 0" />				     
					     <span class="cb-disable"><label class="cb-disable selected"><span>禁用</span><input type="radio" name="status" value="0" checked="checked"/></label></span>
						 <else/>				     
					     <span class="cb-disable"><label class="cb-disable"><span>禁用</span><input type="radio" name="status" value="0"/></label></span>
						 </if>
				</td>
				</tr>
			
			<tr>
			<th width="15%">出生日期</th>
			<td width="35%">
				<input type="text" class="input fl" name="birthday" size="20" id="d4311"  value="{pigcms{$now_user.birthday}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>	
			</td>
			<th width="15%"></th>
			<td width="35%">
				
			</td>
			</tr>
			<if condition="$config['live_open'] eq '1'">
			<tr>
				<th width="15%">直播权限</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_user['open_live_show'] eq 1">selected</if>"><span>开启</span><input type="radio" name="open_live_show" value="1"  <if condition="$now_user['open_live_show'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_user['open_live_show'] neq 1">selected</if>"><span>关闭</span><input type="radio" name="open_live_show" value="0"  <if condition="$now_user['open_live_show'] neq 1">checked="checked"</if>/></label></span>		
				</td>
				<th width="15%">短视频权限</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_user['open_live_video'] eq 1">selected</if>"><span>开启</span><input type="radio" name="open_live_video" value="1"  <if condition="$now_user['open_live_video'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_user['open_live_video'] neq 1">selected</if>"><span>关闭</span><input type="radio" name="open_live_video" value="0"  <if condition="$now_user['open_live_video'] neq 1">checked="checked"</if>/></label></span>		
				</td>
			</tr>
			</if>
			 <if condition="isset($config['specificfield'])">
			 	<tr>
				<th width="15%">姓名</th>
				<td width="35%"><input type="text" class="input fl" name="truename" value="{pigcms{$now_user.truename}"/></td>
				<th width="15%">邮箱</th>
				<td width="35%"><input type="text" class="input fl" name="email" value="{pigcms{$now_user.email}"/></td>
			</tr>
				<tr>
				<th width="15%">地址</th>
				<td width="85%" colspan="3"><input type="text" class="input fl" name="youaddress" value="{pigcms{$now_user.youaddress}" style="width: 95%" /></td>
			</tr>
			 </if>
			<!--tr>
				<th width="15%">手机号验证</th>
				<td width="35%"><div style="height:24px;line-height:24px;"><if condition="$vo['is_check_phone'] eq 1"><font color="green">已验证</font><else/><font color="red">未验证</font></if></div></td>
				<th width="15%">关注微信号</th>
				<td width="35%"><div style="height:24px;line-height:24px;"><if condition="$vo['is_follow'] eq 1"><font color="green">已关注</font><else/><font color="red">未关注</font></if></div></td>
			</tr-->
			<tr>
				<th width="15%">注册时间</th>
				<td width="35%"><div style="height:24px;line-height:24px;">{pigcms{$now_user.add_time|date='Y-m-d H:i:s',###}</div></td>
				<th width="15%">注册IP</th>
				<td width="35%"><div style="height:24px;line-height:24px;">{pigcms{$now_user.add_ip}</div></td>
			</tr>
			<tr>
				<th width="15%">最后访问时间</th>
				<td width="35%"><div style="height:24px;line-height:24px;">{pigcms{$now_user.last_time|date='Y-m-d H:i:s',###}</div></td>
				<th width="15%">最后访问IP</th>
				<td width="35%"><div style="height:24px;line-height:24px;">{pigcms{$now_user.last_ip}</div></td>
			</tr>
			<if condition="$config['single_system_type'] neq 'village_group'">
			<tr> 
				<th width="15%">{pigcms{$config.score_name}</th>
				<td width="85%" colspan="3">
				<div style="height:30px;line-height:24px;">
				现在{pigcms{$config.score_name}：{pigcms{$now_user.score_count} &nbsp;&nbsp;&nbsp;&nbsp;
				 <php>if($can_recharge==1){</php>
				<select name="set_score_type"><option value="1">增加</option><option value="2">减少</option></select>&nbsp;&nbsp;
				<input type="text" class="input" name="set_score" size="10" validate="number:true" tips="此处填写增加或减少的{pigcms{$config.score_name}，不是将{pigcms{$config.score_name}变为此处填写的值"/>
				备注：<input type="text" class="input" name="score_remark" size="20"  tips="更改积分时请填写备注"/><php>}</php>
				</div>
				
				</td>
			</tr>
			</if>
			<tr <php>if($can_recharge==0 || $config['open_frozen_money']==0){echo 'style="display:none"';}</php>>
				<th width="160">是否积分冻结</th>
				<td>
					<span class="cb-enable">
						<label class="cb-enable <php>if($now_user['forzen_score']==1){</php>selected<php>}</php>"><span>是</span>
						<input type="radio" name="forzen_score" value="1" <php>if($now_user['forzen_score']==1){</php>checked="checked"<php>}</php>/>
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable <php>if($now_user['forzen_score']==0){</php>selected<php>}</php>"><span>否</span>
						<input type="radio" name="forzen_score" value="0" <php>if($now_user['forzen_score']==0){</php>checked="checked"<php>}</php>/>
						</label>
					</span>
					<em tips="冻结后用户不能领红包兑换余额，消费" class="notice_tips"></em>
				</td>
			</tr> 
			<if condition="$config['single_system_type'] neq 'village_group'">
				<tr>
					<th width="15%">余额</th>
					<td width="85%" colspan="3"><div style="height:30px;line-height:24px;">现在余额：{pigcms{$config['Currency_symbol']}{pigcms{$now_user.now_money|floatval=###} &nbsp;&nbsp;&nbsp;&nbsp;
					<php>if($can_recharge==1){</php><select name="set_money_type"><option value="1">增加</option><option value="2">减少</option></select>&nbsp;&nbsp;<input type="text" class="input" name="set_money" size="10" validate="number:true" tips="此处填写增加或减少的额度，不是将余额变为此处填写的值"/>
					备注：<input type="text" class="input" name="money_remark" size="20"  tips="更改余额时请填写备注"/><php>}</php>
					</div></td>
				</tr>
			</if>
			<tr <php>if($can_recharge==0 || $config['open_frozen_money']==0){echo 'style="display:none"';}</php>>
				<th width="15%">余额冻结</th>
				<td width="85%" colspan="3">
				<div style="height:30px;line-height:24px;"><input type="text" class="input fl" name="frozen_money" size="10" validate="number:true,min:0" value="{pigcms{$now_user.frozen_money}"  tips="冻结金额"/>
				</div></td>
			</tr>
			<tr <php>if($can_recharge==0 || $config['open_frozen_money']==0){echo 'style="display:none"';}</php>>
				<th width="15%">冻结时间</th>
				<td width="85%" colspan="3"><div style="height:30px;line-height:24px;">
				冻结时间：
				<input type="text" class="input-text" name="frozen_time" style="width:120px;" id="d4311"  value="<if condition="$now_user.frozen_time gt 0">{pigcms{$now_user.frozen_time}</if>" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>	
				解冻时间：
				<input type="text" class="input-text" name="free_time" style="width:120px;" id="d4311" value="<if condition="$now_user.free_time gt 0">{pigcms{$now_user.free_time}</if>" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" tips="冻结时间"/>
				</div></td>
			</tr>
			<tr <php>if($can_recharge==0 || $config['open_frozen_money']==0){echo 'style="display:none"';}</php>>
				<th width="15%">冻结理由</th>
				<td width="85%" colspan="3"><div style="height:30px;line-height:24px;">
				<input type="text" class="input" name="frozen_reason" size="40" value="{pigcms{$now_user.frozen_reason}" tips="冻结理由"/>
				</div></td>
			</tr>

			<tr <php>if($can_recharge==0 || $config['open_frozen_money']==0 || ($now_user['user_card_count'] < 1)){echo 'style="display:none"';}</php>>
            <th width="15%">一卡通</th>
            <td width="85%" colspan="3"><div style="height:30px;line-height:24px;">
                    <div style="height:30px;line-height:24px;">
                        <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/user_card_list',array('uid'=>$now_user['uid']))}','余额记录列表',680,560,true,false,false,null,'money_list',true);">会员卡数量:{pigcms{$now_user.user_card_count}</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>会员卡可用余额: ￥{pigcms{$now_user.user_card_balance_count|floatval=###}</span>
                    </div>
                </div></td>
            </tr>

			<if condition="$config['single_system_type'] neq 'village_group'">
				<tr>
					<th width="15%">实体卡</th>
					<td width="85%" colspan="3"><div style="height:30px;line-height:24px;">实体卡卡号<input type="text" class="input" name="cardid" size="16"  value="{pigcms{$now_user.cardid}" validate="number:true" tips="绑定后不能更改" readOnly/>实体卡初始余额<input type="text" class="input" name="balance_money" size="10" value="{pigcms{$balance_money}" validate="number:true" tips="实体卡绑定时的初始金额，一旦绑定不能更改" readOnly/></div></td>
				</tr>
				<tr>
					<th width="15%">等级</th>
					<td width="85%" colspan="3">
					<div style="height:30px;line-height:24px;">现在等级：<php>if(isset($levelarr[$now_user['level']])){ echo $levelarr[$now_user['level']]['lname'];}else{echo '暂无等级';}</php> &nbsp;&nbsp;&nbsp;&nbsp;
					<if condition="!empty($levelarr)">
					请设定等级：&nbsp;&nbsp; 
					<select name="level" style="width:100px;">
					<option value="0">无</option>
					<volist name="levelarr" id="vo">
					<option value="{pigcms{$vo['level']}" <if condition="$now_user['level'] eq $vo['level']"> selected="selected"</if>>{pigcms{$vo['lname']}</option>
					</volist>
					</select>
					</if>
					&nbsp;&nbsp;</div>
					</td>
				</tr>
				<tr>
					<th width="15%">标签</th>
					<td width="85%" colspan="3">
						<div class="show-select-value" style="">
							<div class="select-list">
								<volist name="label_select" id="vo">
									<div class="item" data-id="{pigcms{$vo.id}">{pigcms{$vo.name}<span class="icon">×</span></div>
								</volist>
							</div>
							<span class="select-icon"></span>
							<ul class="list">
								<volist name="labelarr" id="vo">
									<li class="item" data-id="{pigcms{$vo.id}">{pigcms{$vo.name}</li>
								</volist>
							</ul>
							<input type="hidden" name="label_id" value="{pigcms{$now_user['label_id']}">
						</div>
					</td>
				</tr>
				<if condition="$config['open_spread_appoint_user']">
					<tr>
						<th width="160">是否具有分佣功能</th>
						<td>
							<span class="cb-enable">
								<label class="cb-enable <php>if($now_user['can_spread']==1){</php>selected<php>}</php>"><span>是</span>
								<input type="radio" name="can_spread" value="1" <php>if($now_user['can_spread']==1){</php>checked="checked"<php>}</php>/>
								</label>
							</span>
							<span class="cb-disable">
								<label class="cb-disable <php>if($now_user['can_spread']==0){</php>selected<php>}</php>"><span>否</span>
								<input type="radio" name="can_spread" value="0" <php>if($now_user['can_spread']==0){</php>checked="checked"<php>}</php>/>
								</label>
							</span>
							<em tips="开启后具有分佣功能" class="notice_tips"></em>
						</td>
					</tr> 
				</if>
				<!-- <tr>
					<th width="15%">标签</th>
					<td width="85%" colspan="3">
					<div style="height:30px;line-height:24px;">现在标签：<php>if(isset($labelarr[$now_user['label_id']])){ echo $labelarr[$now_user['label_id']]['name'];}else{echo '暂无标签';}</php> &nbsp;&nbsp;&nbsp;&nbsp;
					<if condition="!empty($labelarr)">
						请设定标签：&nbsp;&nbsp; 
						<select name="label_id" style="width:100px;">
							<option value="0">无</option>
							<volist name="labelarr" id="vo2">
								<option value="{pigcms{$vo2['id']}" <if condition="$now_user['label_id'] eq $vo2['id']"> selected="selected"</if>>{pigcms{$vo2['name']}</option>
							</volist>
						</select>
					</if>
					&nbsp;&nbsp;</div>
					</td>
				</tr> -->

                <if condition="$user_level_activation_code">
                    <tr>
                        <th width="15%" style="color: red;">激活码</th>
                        <td width="85%" colspan="3">
                            <div style="height:30px;line-height:24px;">
                                   {pigcms{$user_level_activation_code['activation_code']}({pigcms{$status_info[$user_level_activation_code['status']]})
                            </div>
                        </td>
                    </tr>
                </if>
				<tr>
					<th width="15%">记录表</th>
					<td width="85%" colspan="3">
						<div style="height:30px;line-height:24px;">
							<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/money_list',array('uid'=>$now_user['uid']))}','余额记录列表',680,560,true,false,false,null,'money_list',true);">余额记录</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/score_list',array('uid'=>$now_user['uid']))}','{pigcms{$config.score_name}记录列表',680,560,true,false,false,null,'score_list',true);">{pigcms{$config.score_name}记录</a>
							<if condition="$now_user['user_card_count'] gt 0">
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/transaction_records',array('uid'=>$now_user['uid']))}','余额记录',680,560,true,false,false,null,'money_list',true);">一卡通余额</a>
							</if>
						</div>
					</td>
				</tr>
			</if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script>
		$(function(){
			$('#nickname,#phone,#pwd').focus(function(){
				$('#nickname').prop('readonly','');
				$('#phone').prop('readonly','');
				$('#pwd').prop('readonly','');
			});
		});
	</script>
	<style type="text/css">
		.show-select-value{position: relative;float: left;border: 1px solid #d5d5d5;width: 400px;min-height: 30px;border-radius: 2px;background: #fff;}
		.show-select-value .select-list{margin: 10px}
		.show-select-value .select-list .item{float: left;border:1px solid #d5d5d5;background: #f2f2f2;padding:0 5px;margin: 0 10px 10px 0;border-radius: 2px;}
		.show-select-value .select-list .item .icon{padding: 0 5px;font-weight: bold;cursor: pointer;}
		.show-select-value .select-icon{position: absolute;right: 10px;top: 17px;vertical-align: middle;width: 0;height: 0;border: 5px solid transparent;border-top-color:#666;}
		.show-select-value .list{position: absolute;bottom: -100px;width: 400px;border: 1px solid #1686ef;background: #fff;border-radius: 2px;display: none;}
		.show-select-value .list.active{display: block;}
		.show-select-value .list .item{padding: 2px 10px}
		.show-select-value .list .item:hover{background: #1686ef;color: #fff;}
		
	</style>
	<script type="text/javascript">
		$(document).on('click', '.show-select-value', function(){
			if ($(this).find('.list').attr('class').indexOf('active') != -1) {
				$(this).find('.list').removeClass('active');
				return 
			}
			$(this).find('.list').addClass('active').css('bottom', -$(this).find('.list').height()-2)
		})

		$(document).on('click', '.show-select-value .list .item', function(){
			var label_id = $(this).data('id');

			var label_ids = $('input[name=label_id]').val();
			if (label_ids != '') {
				var label_ids_arr = label_ids.split(',');
				var selected = 0;
				$.each(label_ids_arr,function (index,j) {
					if(label_id == j){
						selected = 1;
					}
				})
				if (selected==0) {
					label_ids_arr.push(label_id);
					label_ids = label_ids_arr.join(',');
					$('input[name=label_id]').val(label_ids);
				}else{
					$('.show-select-value .list').removeClass('active');
					return false;
				}
			}else{
				$('input[name=label_id]').val(label_id);
			}
			$('.show-select-value .select-list').append('<div class="item" data-id="'+label_id+'" >'+ $(this).html() +'<span class="icon">×</span></div>');
			$('.show-select-value .list').removeClass('active');
			return false
		})

		$(document).on('click', '.show-select-value .select-list .item .icon', function(){
		    console.log($(this).parent().attr("data-id"));
			var label_id = $(this).parent().attr("data-id");
			var label_ids = $('input[name=label_id]').val();
			var label_ids_arr = label_ids.split(',');

			label_ids_arr.splice($.inArray(label_id,label_ids_arr),1);
			label_ids = label_ids_arr.join(',');
			console.log(label_ids)
			$('input[name=label_id]').val(label_ids);
			$(this).parents('.item').remove()
			return false
		})
	</script>
<include file="Public:footer"/>