<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-home"></i>
				<a href="{pigcms{:U('index')}">景区管理</a>
			</li>
			<li class="active">门票列表</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<button class="btn btn-success" onclick="CreateShop()">新增门票</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>编号</th>
									<th>门票标题</th>
									<th>门票类型</th>
									<th>门票价格</th>
									<th>门票数量</th>
									<th>销售数量</th>
									<th>状态</th>
									<th>节假日</th>
									<th class="button-column" style="width:100px;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$now_ticket">
									<volist name="now_ticket" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.ticket_id}</td>
											<td>{pigcms{$vo.ticket_title}</td>
											<td><if condition="$vo['is_children'] eq 2">成人票<elseif condition="$vo['is_children'] eq 1"/><span style="color:red;">儿童票</span></if></td>
											<td>{pigcms{$vo.ticket_price}元</td>
											<td><if condition="$vo['count_num'] eq '-1'">无限制<else/>{pigcms{$vo.count_num}张</if></td>
											<td>{pigcms{$vo.sale_count}张</td>
											<td>
												<switch name="vo['ticket_status']">
													<case value="2">关闭</case>
													<case value="1">开启</case>
												</switch>
											</td>
											<td>
												<switch name="vo['is_general']">
													<case value="0">周末、法定节假日通用</case>
													<case value="1">周末不能使用</case>
													<case value="2">法定节假日不能使用</case>
													<case value="3">周末、法定节假日不能通用</case>
												</switch>
											</td>
											<td class="button-column" nowrap="nowrap">
												<a title="修改" class="green" style="padding-right:8px;" href="{pigcms{:U('edit',array('ticket_id'=>$vo['ticket_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a title="删除" class="red" style="padding-right:8px;" href="{pigcms{:U('del',array('ticket_id'=>$vo['ticket_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >无内容</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript">
function CreateShop(){
	window.location.href = "{pigcms{:U('add')}";
}
</script>
<include file="Public:footer"/>
