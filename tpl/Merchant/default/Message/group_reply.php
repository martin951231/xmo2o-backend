<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-comments-o comments-o-icon"></i>
			<li class="active">{pigcms{:L_("顾客交流")}</li>
			<li><a href="{pigcms{:U('Message/group_reply')}">{pigcms{$config.group_alias_name}{pigcms{:L_("评论")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<form id="frmselect" method="get" action="" style="margin-bottom:0;">
					<input type="hidden" name="c" value="Message"/>
					<input type="hidden" name="a" value="group_reply"/>
					<select id="group_id" name="group_id">
						<option value="all">{pigcms{:L_("全部")}{pigcms{$config.group_alias_name}</option>
						<volist name="group_list" id="vo">
							<option value="{pigcms{$vo.group_id}" <if condition="$_GET['group_id'] eq $vo['group_id']">selected="selected"</if>>{pigcms{$vo.s_name}</option>
						</volist>
					</select>
				</form>
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="70">{pigcms{:L_("顾客")}ID</th>
									<th width="190">{pigcms{:L_("订单名称")}</th>
									<th>{pigcms{:L_("评论内容")}</th>
									<th width="150">{pigcms{:L_("评论时间")}</th>
									<th width="80">{pigcms{:L_("评论打分")}</th>
									<th width="80">{pigcms{:L_("是否回复")}</th>
									<th class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$reply_list">
									<volist name="reply_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.uid}</td>
											<td>{pigcms{$vo.order_name}</td>
											<td>{pigcms{$vo.comment|msubstr=###,0,50}</td>
											<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo.score}</td>
											<td><if condition="$vo['merchant_reply_time']">{pigcms{:L_("已回复")}<else/>{pigcms{:L_("未回复")}</if></td>
											<td><a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("评论列表")}" href="{pigcms{:U('Message/group_reply_detail',array('id'=>$vo['pigcms_id']))}">{pigcms{:L_("评论详情")}</a></td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="7" >{pigcms{:L_("暂无评论！")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function(){
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
</script>
<include file="Public:footer"/>
