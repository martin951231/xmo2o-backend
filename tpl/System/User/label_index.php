<include file="Public:header"/>
<style type="text/css">
td span{
	cursor:pointer;
}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/index')}">用户列表</a>
					<a href="{pigcms{:U('User/label_index')}" class="on">用户标签管理</a>
					<!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/label_edit')}','添加用户标签',750,700,true,false,false,addbtn,'add',true);">添加用户标签</a> -->

					<a href="{pigcms{:U('User/label_edit')}">添加用户标签</a>
					<a href="{pigcms{:U('User/privileged_user')}">特权用户</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/label_index')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="label_index"/>
							筛选: <input type="text" name="name" class="input-text" value="{pigcms{$_GET['name']}" placeholder='请输入用户标签名称' />
							<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
							<a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出用户</a>
						</form>
						
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>标签名称</th>
								<th>用户数</th>
								<th>是否默认</th>
								<th>标签类型</th>
								<th>打标条件</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.user_num} &nbsp;<a href="{pigcms{:U('User/label_bind_user_list',array('id'=>$vo['id']))}">查看详情</a>
										</td>
										<td>
											<eq name='vo.is_default' value='1'>是<else/>否</eq>
										</td>
										<td>
											<eq name='vo.type' value='1'>自动标签<else/>手动标签</eq>
										</td>
										<td>
											<if condition="$vo['type'] eq 1">
												&nbsp;<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/label_condition_detail',array('id'=>$vo['id']))}','打标详情',550,450,true,false,false,'','detail',true);">查看详情</a>
											<else/>
											——
											</if>
										</td>
										<td>
											<span data-href="{pigcms{:U('User/label_del',array('id'=>$vo['id']))}" class="del">删除</span>|
											<a onclick="window.top.artiframe('{pigcms{:U('User/label_edit',array('id'=>$vo['id']))}','编辑标签信息',850,400,true,false,false,editbtn,'edit',true);">编辑</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script>
		 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('User/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script type="text/javascript">
$(function(){
	$(document).on('click','.del',function(){
		var href=$(this).data('href');
		console.log(href);
		layer.open({
			content:'确认删除该标签',
			btn:['确认','取消'],
			offset:'250px',
			yes:function(index){
				$.post(href,function(res){
					layer.close(index);
					layer.alert(res.info,{offset:'250px'},function(index2){
						layer.close(index2);
						if(res.status){
							window.location.reload();
						}
					});
				});
			},
			cancel:function(){

			}
		});
	});
});
</script>
<include file="Public:footer"/>