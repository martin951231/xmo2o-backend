<include file="Public:header"/>
	<table class="search_table" width="100%">
		<tr>
			<td>
				<form action="{pigcms{:U('Village_group/change_leader')}" method="get">
					<input type="hidden" name="c" value="Village_group"/>
					<input type="hidden" name="a" value="change_leader"/>
					<input type="hidden" name="order_id" value="{pigcms{$order.order_id}">
					<input type="hidden" name="start_uid" value="{pigcms{$order.start_uid}">
					筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
						<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>团长昵称</option>
						<option value="hid" <if condition="$_GET['searchtype'] eq 'hid'">selected="selected"</if>>团长ID</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>团长电话</option>
						<option value="address" <if condition="$_GET['searchtype'] eq 'address'">selected="selected"</if>>自提地址</option>
					</select>
					<input type="submit" value="查询" class="button"/>
				</form>
			</td>
		</tr>
	</table>
		<div class="table-list">
			<style>
			.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
			</style>
			<table width="100%" cellspacing="0">
				<colgroup>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col width="250" align="center"/>
				</colgroup>
				<thead>
					<tr>
						<th>团长ID</th>
						<th>团长名称</th>
						<th>联系电话</th>
						<th>绑定uid</th>
						<th>绑定社区名称</th>
						<th>自提地址</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="!empty($header)">
						<volist name="header" id="vo">
							<tr>
								<td>{pigcms{$vo.id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.phone}</td>
								<td>{pigcms{$vo.uid}</td>
								
								<if condition="empty($vo['pick_name'])">
								<!--td>{pigcms{$vo.home}</td>
								<td>{pigcms{$vo.address}</td-->	
								<td>-</td>
								<td>-</td>
								<else />
								<td>{pigcms{$vo.pick_name}</td>
								<td>{pigcms{$vo.pick_addr}</td>
								</if>
								<td class="textcenter">
									<if condition="$order['start_uid'] eq $vo['id']">
									<a style="cursor: pointer;" class="selectThis" data-id="{pigcms{$vo.id}">已选择</a>
									<else />
									<a style="cursor: pointer;" class="selectThis" data-id="{pigcms{$vo.id}">选择</a>
									</if>
								</td>
							</tr>
						</volist>
						<tr><td class="textcenter pagebar" colspan="11">{pigcms{$pagebar}</td></tr>
					<else/>
						<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
					</if>
				</tbody>
			</table>
		</div>

	<form id="myform" method="post" action="{pigcms{:U('change_leader')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="order_id" value="{pigcms{$order.order_id}">
			<input type="hidden" name="start_uid" value="{pigcms{$order.start_uid}">
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<script type="text/javascript">
		var start_uid = {pigcms{$order.start_uid};
	$(function(){
		$(".selectThis").bind("click", function(){
			var hid = $(this).data('id');
			if(hid == start_uid){
				return ;
			}
			$(".selectThis").each(function(){
				$(this).text("选择");
			});
			$(this).text("已选择");
			$('[name="start_uid"]').val(hid);
			start_uid = hid;
		});
	})
	</script>
	
	
<include file="Public:footer"/>