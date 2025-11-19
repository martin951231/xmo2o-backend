<include file="NewPublic:header" />
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
    			<i class="ace-icon fa fa-wechat"></i>
    			{pigcms{:L_("微信模板消息模板")}
			</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
				    <if condition="isset($weixin['code']) AND $weixin['code'] eq 3">
					<div class="tabbable">
						<div class="tab-content">
							<div>
								<a href="{pigcms{:U('NewWeixin/tpladd',['iframe'=>$_GET['iframe']])}" class="btn btn-success">{pigcms{:L_("新建模板")}</a>
							</div>
							<div id="yw0" class="grid-view">
								<table class="table table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th>{pigcms{:L_("模板编号")}</th>
											<th>{pigcms{:L_("模板名")}</th>
											<th>{pigcms{:L_("回复内容")}</th>
											<th>{pigcms{:L_("头部颜色")}</th>
											<th>{pigcms{:L_("文字颜色")}</th>
											<th>{pigcms{:L_("状态")}</th>
											<th>{pigcms{:L_("模板")}ID</th>
											<th>{pigcms{:L_("操作")}</th>
										</tr>
									</thead>
									<tbody id="shopList">
										<if condition="$list"> <volist name="list" id="t">
										<tr>
											<td>{pigcms{$t.tempkey}</td>
											<td>{pigcms{$t.name}</td>
											<td><pre>{pigcms{$t.content}</pre></td>
											<td><input type="text" name="topcolor[]" value="{pigcms{$t.topcolor}" class="px color" style="background: {pigcms{$t.topcolor}" /></td>
											<td><input type="text" name="textcolor[]" value="{pigcms{$t.textcolor}" class="px color" style="background: {pigcms{$t.textcolor}" /></td>
											<td><if condition="$t['status'] eq 0">{pigcms{:L_("关闭")}<else />{pigcms{:L_("开启")}</if></td>
											<td>{pigcms{$t.tempid}</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('NewWeixin/tpledit', array('id' => $t['id'],'iframe'=>$_GET['iframe']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('NewWeixin/tpldel', array('id' => $t['id'],'iframe'=>$_GET['iframe']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
										</volist> <else />
										<tr class="odd">
											<td class="button-column" colspan="8">{pigcms{:L_("无内容")}</td>
										</tr>
										</if>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<else />
					<div>
						<div class="alert alert-danger">
							<button type="button" class="close" data-dismiss="alert">
								<i class="ace-icon fa fa-times"></i>
							</button>
							{pigcms{:L_("您当前的账号是")}{pigcms{$weixin['errmsg']},{pigcms{:L_("没有模板消息的权限！")}
						</div>
					</div>
					</if>
					
				</div>
			</div>
		</div>
	</div>
	<include file="NewPublic:footer" />