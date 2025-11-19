<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Lang/service_lang')}" class="on">系统支持语言</a>|
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Lang/service_lang_add')}','添加语言',450,320,true,false,false,addbtn,'lang_add',true);">添加语言</a>|
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Lang/create_view')}','刷新视图',450,60,true,false,false,confirmbtn,'lang_add',true);">刷新视图</a>|
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Lang/create_table_field')}','更新文字字段',450,200,true,false,false,confirmbtn,'lang_add',true);">更新文字字段</a>
			</ul>
		</div>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>编号</th>
							<th style="color:red;">语言标识符</th>
							<th>客户端识别符</th>
							<th>语言包别名</th>
							<th>语种</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<volist name="lang_list" id="vo">
							<tr>
								<td>{pigcms{$vo.id}</td>
								<td>{pigcms{$vo.val}</td>
								<td>{pigcms{$vo.as}</td>
								<td>{pigcms{$vo.alias}</td>
								<td>{pigcms{$vo.display}</td>
								<td class="textcenter"><if condition="$vo['val'] neq 'chinese'"><a style="color:red;" href="{pigcms{:U('Lang/service_lang_del',array('id'=>$vo['id']))}" onclick="return confirm('你确定要删除此记录吗？');">删除</a></if></td>
							</tr>
						</volist>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<include file="Public:footer"/>