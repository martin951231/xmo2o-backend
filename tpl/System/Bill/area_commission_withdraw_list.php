<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="javascript:history.go(-1)" >上一级</a>
					<a href="{pigcms{:U('Bill/area_commission_withdraw_list', array('area_id' => $_GET['area_id'],'street_id' => $_GET['street_id']))}" class="on">区域代理商提现记录</a>
						 
					
					
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bill/area_commission_withdraw_list')}" method="get">
							<input type="hidden" name="c" value="Bill"/>
							<input type="hidden" name="a" value="area_commission_withdraw_list"/>
							<input type="hidden" name="area_id" value="{pigcms{$_GET['area_id']}"/>
                            <input type="hidden" name="street_id" value="{pigcms{$_GET['street_id']}"/>
							提现至：
							<select name="type">
								<option value="0">全部</option>
								<option value="1" <if condition="$_GET['type'] eq '1'">selected="selected"</if>>支付宝</option>								
								<option value="3" <if condition="$_GET['type'] eq '3'">selected="selected"</if>>银行卡</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type="submit" value="查询" class="button"/>
						&nbsp;&nbsp;&nbsp;&nbsp;<button class="button" onclick="exports(); return false;">导出</button>
							
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
								<th>提现金额</th>
								<th>状态</th>
								<th width="300">备注</th>
							</tr>
						</thead>
						<tbody>
					
							<if condition="$data">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.addtime}</td>
										<td>{pigcms{$vo.money}</td>
										<td>
											<if condition="$vo.status eq '0'">
												<span style="color:red;">待审核</span>
											<elseif condition="$vo.status eq '1'"/>
												<span style="color:green;">已通过</span>
											<else />
												<span style="color:#aaa;">审核失败</span>
											</if>
										</td>
										<td>{pigcms{$vo.remark}</td>
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
<style type="text/css">
	.my_money span{
		padding: 9px 42px;
		border: 1px solid #fff;
		margin-left: 10px;
		color:#fff;
		border-radius:1px;
	}
	.my_money a:hover {text-decoration:none;}
</style>
<script>
	function exports(){
		var type = $('select[name="type"]').val();
		var area_id = $('input[name="area_id"]').val();
		var street_id =  $('input[name="street_id"]').val();
		var export_url ="{pigcms{:U('Bill/area_commission_withdraw_list_export')}&type="+type+'&area_id='+area_id+'&street_id='+street_id;
		window.location.href = export_url;	
	}
</script>
<include file="Public:footer"/>