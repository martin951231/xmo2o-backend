<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U(ACTION_NAME)}">{pigcms{:L_("商家分类管理")}</a>
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
					<button class="btn btn-success" onclick="CreateClassify()">{pigcms{:L_("新建分类")}</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<span class="red">{pigcms{:L_("温馨提示：发布动态、相册必需要有分类！")}</span>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="7%">{pigcms{:L_("编号")}</th>
									<th width="7%">{pigcms{:L_("排序")}</th>
									<th width="50%">{pigcms{:L_("分类名称")}</th>
									<th width="10%">{pigcms{:L_("属于导航")}</th>
									<th style="text-align:center">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody id="tbodyList">
								<if condition="!empty($classifyarr)">
									<volist name="classifyarr" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>{pigcms{$vo.cyname}</td>
											<td style="color:red;"><if condition="$vo['typ'] eq 0">{pigcms{:L_("商家动态")}<elseif condition="$vo['typ'] eq 1"/>{pigcms{:L_("商家相册")} </if></td>
											<td class="button-column" nowrap="nowrap">
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Frontmanag/classify',array('id'=>$vo['id'],'typ'=>$vo['typ']))}">
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
	window.location.href = "{pigcms{:U('Frontmanag/classify')}";
}

function DelItem(vv,obj){
	 if(confirm("{pigcms{:L_("您确认要删除吗?\n删除之后此分类下的文章或图片也将被删除掉")}")){
		$.ajax({
			url:"{pigcms{:U('Frontmanag/delclassify')}",
			type:"post",
			data:{idx:vv},
			dataType:"JSON",
			success:function(ret){
			   if(!ret.error){
				  if($('#tbodyList tr').size()==1){
				    window.location.href = "{pigcms{:U('Frontmanag/mclassify')}";
				  }else{
			        obj.parent('td').parent('tr').remove();
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
