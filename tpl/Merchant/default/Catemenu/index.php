<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Catemenu/index')}">{pigcms{:L_("微网站")}</a>
			</li>
			<li class="active">{pigcms{:L_("底部菜单分类设置")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">	
							<li class="active">
								<a href="javascript:void(0);">{pigcms{:L_("底部菜单分类设置")}</a>
							</li>
							<li>
								<a href="{pigcms{:U('Catemenu/styleSet')}">{pigcms{:L_("底部菜单风格选择")}</a>
							</li>
							<li>
								<a href="{pigcms{:U('Catemenu/plugmenu')}">{pigcms{:L_("菜单颜色与版权")}</a>
							</li>
							<li>
								<a href="{pigcms{:U('Catemenu/music')}">{pigcms{:L_("背景音乐设置")}</a>
							</li>
						</ul>
					
						<div class="tab-content">
							<div class="tab-pane active">
								<div class="alert alert-block alert-success">
									<button type="button" class="close" data-dismiss="alert">
										<i class="ace-icon fa fa-times"></i>
									</button>	
									<p>
										{pigcms{:L_("一级主菜单最多4个，菜单风格")}1-8{pigcms{:L_("无子菜单，菜单风格")}9-16{pigcms{:L_("子菜单最多10个。")}
									</p>
								</div>
								<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("新增菜单")}</button>
								<div id="shopList" class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th id="shopList_c1" width="100">{pigcms{:L_("菜单名称")}</th>
												<th id="shopList_c1" width="100">{pigcms{:L_("显示顺序")}</th>
												<th id="shopList_c0" width="100">{pigcms{:L_("图标地址(透明png图标")})</th>
												<th id="shopList_c3" width="100">{pigcms{:L_("外链地址")}</th>
												<th id="shopList_c1" width="100">{pigcms{:L_("首页显示")}</th>
												<th id="shopList_c11" width="100">{pigcms{:L_("操作")}</th>
											</tr>
										</thead>
										<tbody>
											<if condition="$info">
												<volist name="info" id="vo">
													<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
														<td>{pigcms{$vo.name}</td>
														<td>{pigcms{$vo.orderss}</td>
														<td><div class="cateimg" style="margin-left:13px"><img src="{pigcms{$vo.picurl}" class="cateimg_small" width="50" height="50"/></div></td>
														<td>{pigcms{$vo.url}</td>
														<td><if condition="$vo['status'] eq 0">{pigcms{:L_("不显示")}<else/>{pigcms{:L_("显示")}</if></td>
														<td class="button-column" nowrap="nowrap">
														   <a href="{pigcms{:U('Catemenu/index',array('fid'=>$vo['id']))}">{pigcms{:L_("子菜单")}</a> 
															<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Catemenu/edit',array('id'=>$vo['id'], 'fid'=>$vo['fid']))}">
																<i class="ace-icon fa fa-pencil bigger-130"></i>
															</a>
															<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Catemenu/del',array('id'=>$vo['id']))}">
																<i class="ace-icon fa fa-trash-o bigger-130"></i>
															</a>
														</td>
													</tr>
												</volist>
											<else/>
												<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>
									{pigcms{$page}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
function CreateShop(){
	window.location.href = "{pigcms{:U('Catemenu/add', array('fid' => $fid))}";
}
function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}
</script>
<include file="Public:footer"/>