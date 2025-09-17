<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
						<a href="{pigcms{:U('Bd/bd_list')}">业务经理列表</a>
						<a href="{pigcms{:U('Bd/bd_son_list')}" class="on"><if condition="!empty($pbd_info['bd_name'])">{pigcms{$pbd_info.bd_name} 的</if>业务员列表</a>
						<a  href="javascript:void(0);" onclick="window.top.artiframe('/admin.php?g=System&c=Bd&a=add&type=1&pbd_point_id={pigcms{$pbd_info.bd_id}','新增业务员',500,350,true,false,false,addbtn,'add',true);">新增业务员</a>
					
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bd/bd_son_list')}" method="get">
							<input type="hidden" name="c" value="Bd"/>
							<input type="hidden" name="a" value="bd_son_list"/>
							<if condition="$admin_area neq 3">
                                <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>　
								筛选：
								<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="业务员名称" />
								
							
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
						</colgroup>
						<thead>
							<tr>
								<th>姓名</th>
								<th>城市/区</th>
								<th>我的佣金比例</th>
								<th>邀请码</th>
								<th><a href="{pigcms{:U('Bd/bd_son_list',array('sort'=>$sort))}" style="color:blue;">获得总佣金</a></th>
								<th>我推荐的总佣金</th>
								<th>我的业务经理</th>
								<th>状态</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
					
							<if condition="$data">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.bd_name}</td>
										<td>{pigcms{$vo.area_name}</td>
										<td>{pigcms{$vo.commission_rate}%</td>
										<td>{pigcms{$vo.invitation_code}</td>
										<td>{pigcms{$config.Currency_symbol}{pigcms{$vo.all_money}</td>
										<td>{pigcms{$config.Currency_symbol}{pigcms{$vo.my_all_money} / 共{pigcms{$vo.recom_merchant_nums}个商家 <a style="color:green;" href="{pigcms{:U('Bd/commission_record',array('bd_id'=>$vo['bd_id']))}">记录></a></td>
										<td>{pigcms{$vo.pbd_name}</td>
										<td><if condition="$vo.status eq '1'"><span style="color:green;">{pigcms{$vo.status_txt}</span><else /><span style="color:red;">{pigcms{$vo.status_txt}</span></if></td>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('/admin.php?g=System&c=Bd&a=edit&bd_id={pigcms{$vo.bd_id}','编辑业务员',500,250,true,false,false,editbtn,'edit',true);">编辑</a>
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
		<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>