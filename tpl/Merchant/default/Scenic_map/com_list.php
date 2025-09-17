<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('index')}">景区管理</a>
			</li>
			<li>
				<a href="{pigcms{:U('index')}">景区地图分类</a>
			</li>
			<li>
				<a href="{pigcms{:U('one_index',array('map_id'=>$_GET['map_fid']))}">地图二级分类</a>
			</li>
			<li class="active">地图列表</li>
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
					<button class="btn btn-success" onclick="CreateShop()">新增地图</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>编号</th>
									<th>地标名</th>
									<th>经度</th>
									<th>纬度</th>
									<th>状态</th>
									<th class="button-column" style="width:100px;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$now_guide">
									<volist name="now_guide" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.com_id}</td>
											<td>{pigcms{$vo.com_name}</td>
											<td>{pigcms{$vo.long}</td>
											<td>{pigcms{$vo.lat}</td>
											<td>
												<switch name="vo['status']">
													<case value="0">关闭</case>
													<case value="1">开启</case>
												</switch>
											</td>
											<td class="button-column" nowrap="nowrap">
												<a title="修改" class="green" style="padding-right:8px;" href="{pigcms{:U('com_edit',array('com_id'=>$vo['com_id'],'map_id'=>$_GET['map_id'],'map_fid'=>$_GET['map_fid']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a id="shopList" title="删除" class="red" style="padding-right:8px;" href="{pigcms{:U('com_del',array('com_id'=>$vo['com_id']))}">
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
<script type="text/javascript">
function CreateShop(){
	window.location.href = "{pigcms{$url}";
}
</script>
<include file="Public:footer"/>