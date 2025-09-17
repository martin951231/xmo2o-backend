<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Index/system_log')}" class="on">日志列表</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Index/system_log')}" method="get">
						<input type="hidden" name="c" value="Index"/>
						<input type="hidden" name="a" value="system_log"/>
						
						搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="请输入关键字" />
						<select name="searchtype" id="searchtype">
							<option value="content" <if condition="$_GET['searchtype'] eq 'content'">selected="selected"</if>>操作内容</option>
							<option value="admin" <if condition="$_GET['searchtype'] eq 'admin'">selected="selected"</if>>操作人员</option>
							<option value="dataid" <if condition="$_GET['searchtype'] eq 'dataid'">selected="selected"</if>>数据ID</option>
						</select>
						<font color="#000">日期筛选：</font>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						<input type="submit" value="查询" class="button"/>　
					</form>
				</td>
			</tr>
		</table>
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<colgroup>
						<col/>
						<col/>
						<col/>
						<col/>
						<col/>
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th>编号</th>
							<th>操作时间</th>
							<th>操作人</th>
							<th>归属业务</th>
							<th>数据ID</th>
							<th>操作内容</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<notempty name="data">
							<volist name="data" id="vo">
							<tr>
								<td>{pigcms{$vo.id}</td>
								<td style="width: 200px;">{pigcms{$vo.addtime|date='Y-m-d H:i:s',###}</td>
								<td>{pigcms{$vo.realname}</td>
								<td>{pigcms{$vo.business}</td>
								<td>{pigcms{$vo.data_id}</td>
								<td style="width: 700px;">{pigcms{$vo.content}</td>
								<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Index/system_log_detail',array('log_id'=>$vo['id'],'frame_show'=>true))}','操作详情',900,'90%',true,false,false,false,'detail',true);">详情</a></td>
							</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
						<else />
						<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
						</notempty>
					</tbody>
				</table>
			</div>
	</div>
<script>
$(function(){
	$('#status').change(function(){
		location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&status=" + $(this).val() + "&pay_type=" + $('#pay_type').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $('#order_from').val();
	});
	
	$('#pay_type').change(function(){
		location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&pay_type=" + $(this).val() + "&status=" + $('#status').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $('#order_from').val();
	});
    $('#order_from').change(function(){
        location.href = "{pigcms{:U('Shop/order', array('from_type'=>$from_type,'type' => $type, 'sort' => $sort))}&pay_type=" + $('#pay_type').val() + "&status=" + $('#status').val() + "&keyword=" + $('#keyword').val() + "&searchtype=" + $('#searchtype').val() + "&begin_time=" + $('#begin_time').val() + "&end_time=" + $('#end_time').val() + "&order_from=" + $(this).val();
    });
});
</script>
<include file="Public:footer"/>