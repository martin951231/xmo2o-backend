<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
						<a href="{pigcms{:U('pick_list')}" class="on">自提点列表</a>|
						<!-- <a href="{pigcms{:U('pick_add')}" >添加自提点</a> -->
						<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('pick_add')}','添加自提点',450,320,true,false,false,addbtn,'store_add',true);">添加自提点</a>
						<a href="{pigcms{$config['site_url']}/giftpick.php?g=Giftpick" target="_blank">自提点登陆</a>
				</ul>
			</div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>省份</th>
								<th>城市</th>
								<th>区域</th>
								<th>自取点名称</th>
								<th>自取电话</th>
								<th>登录秘钥</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$list">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo['pick_id']}</td>
										<td>{pigcms{$vo['area_info']['province']}</td>
										<td>{pigcms{$vo['area_info']['city']}</td>
										<td>{pigcms{$vo['area_info']['area']}</td>
										<td>{pigcms{$vo.name}</td>
										<td><if condition ="$vo.phone eq 0">无<else />{pigcms{$vo.phone}</if></td>
										<td style="text-align:center;">
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('pick_pwd',array('pick_id'=>$vo['pick_id']))}','查看',500,200,true,false,false,false,'pick_pwd',true);">查看</a>
										</td>
										<td><if condition ="$vo['is_open'] eq 1">开启<else />关闭</if></td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('pick_edit',array('pick_id'=>$vo['pick_id']))}','编辑',450,320,true,false,false,editbtn,'store_add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="pick_id={pigcms{$vo.pick_id}" url="{pigcms{:U('pick_del')}">删除</a>
                                         </td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>