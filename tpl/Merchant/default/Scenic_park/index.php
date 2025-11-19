<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('index')}">功能库</a>
			</li>
			<li class="active">停车场</li>
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
					<button class="btn btn-success" onclick="CreateShop()">新增停车场</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>编号</th>
									<th>停车场名</th>
									<th>停车场地址</th>
									<th>车位数量</th>
									<th>状态</th>
									<th class="button-column" style="width:100px;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$now_ticket">
									<volist name="now_ticket" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.parking_id}</td>
											<td>{pigcms{$vo.parking_name}</td>
											<td>{pigcms{$vo.parking_address}</td>
											<td>{pigcms{$vo.parking_count}</td>
											<td>
												<switch name="vo['parking_status']">
													<case value="2">关闭</case>
													<case value="1">开启</case>
												</switch>
											</td>
											<td class="button-column" nowrap="nowrap">
												<a title="修改" class="green" style="padding-right:8px;" href="{pigcms{:U('edit',array('parking_id'=>$vo['parking_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a id="shopList" title="删除" class="red" style="padding-right:8px;" href="{pigcms{:U('del',array('parking_id'=>$vo['parking_id']))}">
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
