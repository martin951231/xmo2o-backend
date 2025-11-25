<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U(ACTION_NAME)}">{pigcms{:L_("商家动态管理")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.red{display:inline-block; font-size: 18px;margin-left: 70px;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("发布内容")}</button><span class="red">{pigcms{:L_("温馨提示：必需有分类才能发布成功！")}</span>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="7%">{pigcms{:L_("编号")}</th>
									<th width="7%">{pigcms{:L_("排序")}</th>
									<th width="40%">{pigcms{:L_("标题")}</th>
									<th width="8%">{pigcms{:L_("所属分类")}</th>
									<th width="10%">{pigcms{:L_("是否发布")}</th>
									<th width="11%">{pigcms{:L_("发布时间")}</th>
									<th style="text-align:center">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody id="tbodyList">
								<if condition="!empty($introduce)">
									<volist name="introduce" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>{pigcms{$vo.title}</td>
											<td>{pigcms{$vo.cyname}</td>
											<td style="color:red;"><if condition="$vo['isfabu'] eq 1">{pigcms{:L_("已发布")}<else/>{pigcms{:L_("未发布")} / <a class="green" href="javascript:;" onclick="FaBu({pigcms{$vo['id']},$(this))">{pigcms{:L_("点击发布")}</a></if></td>
											<td>{pigcms{$vo.addtime|date="Y-m-d H:i:s",###}</td>
											<td class="button-column" nowrap="nowrap">
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Frontmanag/fbrenews',array('id'=>$vo['id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="javascript:;" onclick="DelItem({pigcms{$vo['id']},$(this));">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function CreateClassify(){
	window.location.href = "{pigcms{:U('Frontmanag/classify',array('typ'=>0))}";
}
function CreateShop(){
  window.location.href = "{pigcms{:U('Frontmanag/fbrenews')}";
}
function FaBu(vv,obj){
	$.ajax({
		url:"{pigcms{:U('Frontmanag/fbStatus')}",
		type:"post",
		data:{idx:vv},
		dataType:"JSON",
		success:function(ret){
		   if(!ret.error){
			  obj.parent('td').text('{pigcms{:L_("已发布")}');
		   }else{
			 alert('{pigcms{:L_("发布状态修改失败！")}');
		   }
		}
	});
}
function DelItem(vv,obj){
	 if(confirm('{pigcms{:L_("您确认要删除吗")}?')){
		$.ajax({
			url:"{pigcms{:U('Frontmanag/delintroduce')}",
			type:"post",
			data:{idx:vv},
			dataType:"JSON",
			success:function(ret){
			   if(!ret.error){
				  if($('#tbodyList').size()==1){
				    window.location.href = "{pigcms{:U('Frontmanag/renews')}";
				  }else{
			        obj.parent().parent('td').remove();
				  }
			   }else{
			     alert('{pigcms{:L_("删除失败！")}');
			   }
			}
		});
  }
}
</script>

<include file="Public:footer"/>
