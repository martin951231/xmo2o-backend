<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Sms/ali_sms')}" class="on">模板列表</a>
				</ul>
			</div>
			
			<table class="search_table" width="100%">
				<tr>
					<td>
						<input type="button" value="添加模板" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('Sms/add_ali_sms_template')}','添加模板',800,480,true,false,false,addbtn,'add',true);"/>
					</td>
					<td>
						<form action="{pigcms{:U('Sms/ali_sms')}" method="get">
							<input type="hidden" name="c" value="Sms"/>
							<input type="hidden" name="a" value="ali_sms"/>
							<input type="hidden" name="searchtype" value="name"/>
						
							按模板名/模板CODE 搜索: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					.content{width:80px;overflow: hidden;text-overflow:ellipsis;white-space: nowrap;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="250" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>名称</th>
								<if condition="isset($config['open_aliyun_sms']) || isset($config['open_ali_national_sms'])">
									<th>阿里云模板编号</th>
								</if>
								<if condition="isset($config['open_huaweiyun_sms'])">
									<th>华为云模板编号</th>
								</if>
								<th>模板内容【此展示简易模板，其他的点击编辑查看】</th>
								<th>模板状态</th>	
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($sms_list)">
								<volist name="sms_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
										<if condition="isset($config['open_aliyun_sms']) || isset($config['open_ali_national_sms'])">
											<td>{pigcms{$vo.templateCode}</td>
										</if>
										<if condition="isset($config['open_huaweiyun_sms'])">
											<td>{pigcms{$vo.huawei_templateCode}</td>
										</if>
										<td>{pigcms{$vo.simple_content}</td>			
										<td><if condition="$vo.status eq 1"><font color="green">正常</font><else /><font color="red">禁止</font></if></td>							
										<td class="textcenter">
											<a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('Sms/add_ali_sms_template',array('id'=>$vo['id']))}','编辑',800,480,true,false,false,editbtn,'edit',true);">编辑</a> ｜ <a class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Sms/del_ali_sms_template')}">删除</a>
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
<include file="Public:footer"/>