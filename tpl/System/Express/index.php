<include file="Public:header"/>
		<div class="mainbox">
			<include file="header"/>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<!-- <colgroup><col> <col> <col> <col><col><col><col> <col width="140" align="center"> </colgroup> -->
						<thead>
							<tr>
								<th style="width: 5%;">排序</th>
								<th style="width: 5%;">编号</th>
								<th style="width: 5%;">名称</th>
								<th style="width: 5%;">编码</th>
								<th style="width: 5%;">快递鸟编码</th>
								<th style="width: 10%;">链接地址</th>
                                <th style="width: 10%;" title="前往快递100查看面单模板id填写至此处">物流面单模板id</th>
                                <th style="width: 10%;" title="请上传物流面单模板id对应的示例图片">物流面单示例图</th>
								<th style="width: 10%;">编辑时间</th>
								<th>状态</th>
								<th>虚拟快递</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($express_list)">
								<volist name="express_list" id="vo">
									<tr>
										<td>{pigcms{$vo.sort}</td>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.code}</td>
										<td>{pigcms{$vo.kuaidiniao_code}</td>
										<td><a href="{pigcms{$vo.url}" target="_blank">{pigcms{$vo.url}</a></td>
                                        <td title="前往快递100查看面单模板id填写至此处">{pigcms{$vo.tempid}</td>
                                        <td title="请上传物流面单模板id对应的示例图片">
                                            <if condition="$vo['pic_txt']">
                                                <img src="{pigcms{$vo['pic_txt']}" style="max-width: 200px;" class="view_msg_express"/>
                                                <else/>
                                                暂无
                                            </if>
                                        </td>
										<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
										<td><if condition="$vo['status']"><font color="green">启用</font><else/><font color="red">关闭</font></if></td>
										<td><if condition="$vo['is_fake']"><font color="green">是</font><else/><font color="red">否</font></if></td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Express/edit',array('id'=>$vo['id'],'frame_show'=>true))}','查看详细信息',520,470,true,false,false,false,'add',true);">查看</a> | <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Express/edit',array('id'=>$vo['id']))}','编辑快递公司信息',520,550,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Express/del')}">删除</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>