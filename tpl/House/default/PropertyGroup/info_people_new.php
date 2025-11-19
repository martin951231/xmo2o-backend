<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>
	<form id="myform" method="post" action="" enctype="multipart/form-data">

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">编号</th>
				<td>{pigcms{$res.job_number}</td>
			</tr>
			<tr>
				<th width="80">姓名</th>
				<td>{pigcms{$res.name}</td>
			</tr>
			<tr>
				<th width="80">性别</th>
				<if condition="$res['gender'] eq 1">
					<td>男</td>
					<else/>
					<td>女</td>
				</if>

			</tr>
			<tr>
				<th width="80">手机号码</th>
				<td>{pigcms{$res.phone}</td>
			</tr>
			<if condition="$property['login_identify'] == 1">
				<tr>
					<th width="80">身份证号</th>
					<td>{pigcms{$res.id_card}</td>
				</tr>
				<tr>
					<th width="80">IC卡</th>
					<td>{pigcms{$res.ic_card}</td>
				</tr>
			</if>
			<tr>
				<th width="80">微信号</th>
				<td>{pigcms{$res.weixin_num}</td>
			</tr>
			<if condition="$property['login_identify'] == 1">
			<tr>
				<th width="80">入职时间</th>
				<if condition="$res['job_create_time'] gt 0">
					<td>{pigcms{$res.job_create_time|date='Y-m-d',###}</td>
					<else/>
					<td>-</td>
				</if>
			</tr>
			</if>
			<tr>
				<th width="80">是否可以开门</th>
				<if condition="$res['open_door'] eq 1">
					<td>是</td>
					<else/>
					<td>否</td>
				</if>

			</tr>
			<tr>
				<th width="80">备注</th>
				<td>{pigcms{$res.note}</td>
			</tr>
            <if condition="$qy_txt neq ''">
                <tr>
                    <th width="80">企业微信同步状态</th>
                    <td>{pigcms{$qy_txt}</td>
                </tr>
                <tr>
                    <th width="80">企业微信同步时间</th>
                    <td>{pigcms{$qy_time|default='暂无'}</td>
                </tr>
            </if>



		</table>

	</form>



<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<script>



    $("#yes").click(function () {
        $("#adver_region").hide();
    })

    $("#no").click(function () {
        $("#adver_region").show();
    })




//	var admin_area_id = {pigcms{$system_session.area_id};
//	if(admin_area_id > 0){
//		$("#adver_currency").hide();
//		$("#no").trigger('click');
//	}
</script>
<include file="Publics:footer"/>