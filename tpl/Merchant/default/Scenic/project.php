<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-home"></i>
				<a href="{pigcms{:U('Scenic/project')}">景区管理</a>
			</li>
			<li class="active">项目列表</li>
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
					<button class="btn btn-success" onclick="CreateShop()">新建项目</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>编号</th>
									<th>排序</th>
									<th>项目名称</th>
									<th>项目单价</th>
									<th>地图状态</th>
									<th>项目状态</th>
									<th>项目详情</th>
									<th class="button-column" style="width:100px;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$now_scenic">
									<volist name="now_scenic" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.project_id}</td>
											<td>{pigcms{$vo.project_sort}</td>
											<td>{pigcms{$vo.project_title}</td>
											<td>{pigcms{$vo.project_price}</td>
											<td>
												<switch name="vo['panorama_map']">
													<case value="2">关闭</case>
													<case value="1">开启</case>
												</switch>
											</td>
											<td>
												<switch name="vo['project_status']">
													<case value="2">关闭</case>
													<case value="1">正常</case>
												</switch>
											</td>
											<td><a href="{pigcms{:U('project_details',array('project_id'=>$vo['project_id']))}">查看项目详情</a></td>
											<td class="button-column" nowrap="nowrap">
												<a title="修改" class="green" style="padding-right:8px;" href="{pigcms{:U('Scenic/project_edit',array('project_id'=>$vo['project_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a title="删除" class="red" style="padding-right:8px;" href="{pigcms{:U('Scenic/project_del',array('project_id'=>$vo['project_id']))}">
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
	window.location.href = "{pigcms{:U('Scenic/project_add')}";
}
</script>
<include file="Public:footer"/>