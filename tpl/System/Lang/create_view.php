<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Lang/create_view')}" frame="true" refresh="true">
		<input type="hidden" name="change_location" value="0"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th>选择一张表</th>
				<td>
					<select name="table" id="table">
						<volist name="tables" id="vo">
						<option value="{pigcms{$vo}">pigcms_{pigcms{$vo}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="color:red;">
					<input type="button" value="批量刷新所有视图" class="button" id="fresh_all"/>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="color:red;">适用于那些只是主表增加/删除了字段，但该字段不需要增加到语言表（不进行多语言转换）的场景。</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script>
		$('#fresh_all').click(function(){
			$.each($('#table option'),function(i,item){
				$.post("{pigcms{:U('Lang/create_view')}",{table:$(item).attr('value')});
			});
		});
	</script>
<include file="Public:footer"/>