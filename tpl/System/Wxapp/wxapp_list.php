<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Wxapp/wxapp_list')}" class="on">打开其他小程序列表</a>|
				<a href="{pigcms{:U('Wxapp/web_list')}">打开其他网页列表</a>
				<a href="{pigcms{:U('Wxapp/nfcScheme')}">NFC标签打开小程序</a>
			</ul>
		</div>
		<div class="page_tips" style="position: relative;height: 68px;">
			<button  onclick="window.o2oTop.artiframe('{pigcms{:U('Wxapp/wxapp_edit')}','添加小程序',600,420,true,false,false,addbtn,'add',true);" class="button" style="margin-right: 14px;">添加小程序</button>
			说明：<div style="display: inline-block; float: left; position: absolute; top: 19px; line-height: 20px;">
			1. 该功能为平台小程序支持打开另一个小程序。<br/>
			<!-- 2. 请先设置好要打开的小程序 appId，最多支持10个小程序。<br/> -->
			2. 添加好其他小程序的appId后，可用于系统后台--系统设置广告管理或导航管理里选择要打开的其他小程序；系统后台其他小程序列表里添加好appId后，需联系售后重新打包小程序才能生效</div>
		</div>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>编号</th>
							<th>小程序名称</th>
							<th>小程序appId</th>
							<th>小程序原始ID</th>
							<th>添加时间</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($wxapp_list)">
							<volist name="wxapp_list" id="vo">
								<tr>
									<td>{pigcms{$vo.pigcms_id}</td>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.appid}</td>
									<td>{pigcms{$vo.username}</td>
									<td>{pigcms{$vo.time|date='Y-m-d H:i:s',###}</td>
									<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Wxapp/wxapp_edit',array('id'=>$vo['pigcms_id']))}','编辑小程序信息',600,420,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.pigcms_id}" url="{pigcms{:U('Wxapp/wxapp_del')}">删除</a></td>
								</tr>
							</volist>
						<else/>
							<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<include file="Public:footer"/>