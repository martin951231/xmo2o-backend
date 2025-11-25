<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
						<a href="{pigcms{:U('Bill/area_commission_withdraw_list_audit', ['status' => 0])}" <if condition="$_GET['status'] eq '0' || !isset($_GET['status'])">class="on"</if>>待兑现记录</a>
						 <a href="{pigcms{:U('Bill/area_commission_withdraw_list_audit', ['status' => 1])}"  <if condition="$_GET['status'] eq '1'">class="on"</if>>已兑现记录</a>
					
					
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bill/area_commission_withdraw_list_audit')}" method="get">
							<input type="hidden" name="c" value="Bill"/>
							<input type="hidden" name="a" value="area_commission_withdraw_list_audit"/>
							<input type="hidden" name="status" value="{pigcms{$_GET['status']}"/>
							<if condition="$admin_area neq 3">
                                <div id="choose_pca" street_id="{pigcms{$_GET.street_id}" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>　
                            <if condition="$_GET['status'] eq '1'">
								日期筛选：
								<input type="text" class="input" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
								<input type="text" class="input" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								提现至：
								<select name="type">
									<option value="1" <if condition="$_GET['type'] eq '1'">selected="selected"</if>>支付宝</option>								
									<option value="3" <if condition="$_GET['type'] eq '3'">selected="selected"</if>>银行卡</option>
								</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </if>
							
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
							<col width="100" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>提现时间</th>
								<th>姓名</th>
								<th>提现总金额</th>
								<th>提现至</th>
								<th>所属区域</th>
								<th>账号明细</th>
								<th width="300">备注</th>
								<if condition="$_GET['status'] eq '1'">
								<th>兑换方式</th>
								<else />
								<th width="200" style="text-align: center;">操作</th>
								</if>
								
							</tr>
						</thead>
						<tbody>
					
							<if condition="$data">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.addtime}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.money}</td>
										<td>{pigcms{$vo.type_text}</td>
                                        <td><if condition="$vo['street_id'] gt 0">{pigcms{$vo.street_name}<else/>{pigcms{$vo.area_name}</if></td>
										<td>{pigcms{$vo.remark}</td>
										<td>{pigcms{$vo.remark1}</td>
										<if condition="$_GET['status'] eq '1'">
										<td>{pigcms{$vo.paytype}</td>
										<else />
										<td style="text-align: center;">
											<a style="padding: 5px 10px;background-color: #ccc" href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Bill&a=edit_area_commission&id={pigcms{$vo.id}','编辑',500,100,true,false,false,editbtn,'add',true);">设为已兑现</a>
											<a style="padding: 5px 10px;background-color: #ccc" href="javascript:void(0);" onclick="window.o2oTop.artiframe('/admin.php?g=System&c=Bill&a=edit_area_commission_fail&id={pigcms{$vo.id}','编辑',400,200,true,false,false,editbtn,'add',true);">驳回</a>
										</td>
										</if>
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