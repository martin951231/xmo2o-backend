<include file="Public:header"/>
	<style>
		.sub_mch{
			display:none
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Qiye/add')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="160">企业账号</th>
				<td><input type="text" class="input fl" name="account" size="25" validate="required:true" tips="设定之后，以后不能再修改！"/></td>
			</tr>
			<tr>
				<th width="160">企业密码</th>
				<td><input type="password" id="check_pwd" check_width="180" class="input fl" name="pwd" size="25" validate="required:true,minlength:6" tips="企业的密码很重要，填写难度较强的密码有效保护企业的信息，也可以保护网站的数据安全。"/></td>
			</tr>
			<tr>
				<th width="160">企业名称</th>
				<td><input type="text" class="input fl" name="name" size="25" validate="required:true"/></td>
			</tr>

            <tr>
                <th width="160">联系电话</th>
                <td><input type="text" class="input fl" name="phone" size="25" placeholder="联系人的电话" validate="required:true"/></td>
            </tr>

            <tr>
                <th width="160">联系邮箱</th>
                <td><input type="text" class="input fl" name="email" size="25" placeholder="可不填写" validate="email:true"/></td>
            </tr>

            <tr>
                <th width="160">所在区域</th>
                <td id="choose_cityarea" province_id="" city_id="" area_id="" circle_id="-1"></td>
            </tr>

            <tr>
                <th width="160">企业状态</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
                </td>
            </tr>

            <tr>
                <th width="160">结算手续费比例</th>
                <td><input type="text" class="input fl" name="settlement_ratio" size="25"/> %</td>
            </tr>

            <tr>
                <th width="160">结算方式</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>先消费后结算</span><input type="radio" name="settlement_type" value="1" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>先结算后消费</span><input type="radio" name="settlement_type" value="2" /></label></span>
                </td>
            </tr>

            <tr>
                <th width="160">备注</th>
                <td><input type="text" class="input fl" name="remark" size="25" placeholder="可不填写" /></td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script>

	</script>
	
<include file="Public:footer"/>