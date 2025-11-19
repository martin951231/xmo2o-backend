<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Config/sign_set_list')}" class="on">签到天数列表</a>|
					<a href="javascript:void(0);" onclick="sign_setting(0,1);">添加签到天数</a>
				</ul>
			</div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col><col>  <col width="180" align="center"> </colgroup>
						<thead>
							<tr>
								<th>排序</th>
								<th>签到天数</th>
								<th>赠送礼品</th>
								<th>添加时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo" key='k'>
									<tr>
										<td>{pigcms{$k}</td>
										<td>{pigcms{$vo.day}</td>
										<td>{pigcms{$vo.desc}</td>
										<td>{pigcms{$vo.add_time|date="Y/m/d H:i",###}</td>
										<td>
											<a href="javascript:sign_setting({pigcms{$vo.id},0);">查看</a>|
											<a href="javascript:sign_setting({pigcms{$vo.id},1);">编辑</a>|
											<a href="javascript:void(0);" class="delete_row" url="{pigcms{:U('Config/sign_set_del',array('id'=>$vo['id']))}">删除</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="30">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="30">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
	var edit_url="{pigcms{:U('Config/sign_setting')}";
	function  sign_setting(id,is_edit) {
		window.location.reload();
		var url=edit_url;
		var title='添加签到天数';
		if(id>0){
			url+='&id='+id;
			title='编辑签到天数';
		}
		url+='&is_edit='+is_edit;

		if(is_edit){
			window.o2oTop.artiframe(url,title,500,320,true,false,false,addbtn,'add',true);			
		}else{
			window.o2oTop.artiframe(url,title,500,320,true,false,false,false,'add',true);
		}
	}
</script>
<include file="Public:footer"/>