<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Wxapp/wxapp_list')}">打开其他小程序列表</a>|
				<a href="{pigcms{:U('Wxapp/web_list')}">打开其他网页列表</a>
				<a href="{pigcms{:U('Wxapp/nfcScheme')}" class="on">NFC标签打开小程序</a>
			</ul>
		</div>
        <div class="page_tips" style="position: relative;height: 68px;">
			<button  onclick="window.o2oTop.artiframe('{pigcms{:U('Wxapp/generateNfcScheme')}','生成scheme码',700,420,true,false,false,addbtn,'add',true);" class="button" style="margin-right: 14px;">生成scheme码</button>
			说明：<div style="display: inline-block; float: left; position: absolute; top: 19px; line-height: 20px;">
			1. NFC标签调用小程序需要先完成设备接入。接入指引：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/NFC.html<br/>
			2. NFC的小程序scheme生成之后，不可删除，不可修改，永久生效</div>
		</div>
		<form name="myform" id="myform" action="">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>ID</th>
							<th>店铺名称</th>
							<th>类型</th>
							<th>model_id</th>
							<th>NFC标签数据</th>
							<th>更新时间</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($records)">
							<volist name="records" id="vo">
								<tr>
									<td>{pigcms{$vo.id}</td>
									<td>{pigcms{$vo.store_name}</td>
									<td>优惠买单</td>
									<td>{pigcms{$vo.model_id}</td>
									<td>
										URL：{pigcms{$vo.openlink}<br/>
										打开应用：com.tencent.mm
									</td>
									<td>{pigcms{$vo.update_time}</td>
								</tr>
							</volist>
						<else/>
							<tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
						</if>
						<tr><td class="textcenter" colspan="6">{pigcms{$pagebar}</td></tr
					</tbody>
				</table>
			</div>
		</form>
	</div>
<include file="Public:footer"/>