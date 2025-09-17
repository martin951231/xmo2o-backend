<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<a href="{pigcms{:U('Index/pass')}" class="on">修改密码</a>
			</div>
			<form id="myform" method="post" action="{pigcms{:U('Index/amend_pass')}" refresh="true">
				<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
					<tr>
						<th width="100">旧密码：</th>
						<td><input type="password" class="input-text" name="old_pass"/></td>
					</tr>
					<tr>
						<th>新密码：</th>
						<td><input type="password" class="input-text"  name="new_pass" id="password" validate="checkPasswd:true"/></td>
					</tr>
					<tr>
						<th>确认密码：</th>
						<td><input type="password" class="input-text"  name="re_pass" validate="checkPasswd:true,equalTo:'#password'"/></td>
					</tr>
				</table>
				<div class="btn">
					<input type="submit"  name="dosubmit" value="提交" class="button" />
					<input type="reset"  value="取消" class="button" />
				</div>
			</form>
		</div>

		<script type="text/javascript">
				$.validator.addMethod("checkPasswd",function(value,element,params){ 
					var checkPasswd = /^[a-zA-Z0-9_]{8,16}$/; 
					return this.optional(element)||(checkPasswd.test(value)); 
				},'密码由大小写字母、数字和下划线组成，8-16位')
		</script>
<include file="Public:footer"/>