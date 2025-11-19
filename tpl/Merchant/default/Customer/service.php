<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-group"></i>
			<li class="active">{pigcms{:L_("粉丝管理")}</li>
			<li><a href="{pigcms{:U('Customer/service')}">{pigcms{:L_("客服列表")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12" style="padding-left:0px;padding-right:0px;">
					<a class="btn btn-success" href="{pigcms{:U('Customer/add')}">{pigcms{:L_("创建客服")}</a>
					&nbsp;&nbsp;&nbsp;<a class="btn btn-success" href="{pigcms{$config.site_url}/index.php?c=Impc" target="_blank">{pigcms{:L_("登录PC版客服中心")}</a>
					<div class="grid-view" style="padding-top:5px;">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>ID</th>
									<th>{pigcms{:L_("客服名称")}</th>
									<th>{pigcms{:L_("客服描述")}</th>
									<th>{pigcms{:L_("头像")}</th>
									<th>{pigcms{:L_("创建时间")}</th>
									<th class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$services">
									<volist name="services" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.pigcms_id}</td>
											<td>{pigcms{$vo.nickname}</td>
											<td>{pigcms{$vo.des}</td>
											<td><img alt="{pigcms{$vo.nickname}" src="{pigcms{:replace_file_domain($vo['head_img'])}" height="80"></td>
											<td>{pigcms{$vo.dateline|date='Y-m-d H:i:s',###}</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a href="{pigcms{:U('Customer/add',array('pigcms_id'=>$vo['pigcms_id']))}">{pigcms{:L_("编辑")}</a>
													<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del" href="{pigcms{:U('Customer/del',array('pigcms_id' => $vo['pigcms_id']))}">{pigcms{:L_("删除")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="6" >{pigcms{:L_("暂无客服！")}</td></tr>
								</if>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>