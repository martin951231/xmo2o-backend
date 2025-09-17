<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/score_pay_back_list')}" class="on">积分返现记录</a>
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('score_pay_back_list')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="score_pay_back_list"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
							</select>
					

							<font color="#000">日期筛选：</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<input type="submit" value="查询" class="button"/>　　
							昨日抽成：{pigcms{$yesterday_system_take}{pigcms{$config['Currency_txt']}  &nbsp;&nbsp;&nbsp;&nbsp;今日抽成：{pigcms{$today_system_take}{pigcms{$config['Currency_txt']}  &nbsp;&nbsp;&nbsp;&nbsp;返现比例：{pigcms{$config.score_pay_back_percent|floatval}% &nbsp;&nbsp;&nbsp;&nbsp;昨天返现：{pigcms{$yesterday_pay_back|floatval}{pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;今天待返现：{pigcms{$today_system_take*$config['score_pay_back_percent']/100|floatval}{pigcms{$config['Currency_txt']}
						</form>
					</td>
                    <td>
                        <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出报表</a>
                    </td>
				</tr>
			</table>

			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>编号</th>
								<th>昵称</th>
								<th>电话</th>
								<th>积分数量</th>
								<th>描述</th>
								<th>查看用户信息</th>
								<th>时间</th>
							
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($order_list)">
								<volist name="order_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.nickname}</td>
										<td>{pigcms{$vo.phone}</td>
										<td><if condition="$vo.type eq 1"><font color="green">+<elseif condition="$vo.type eq 2" /><font color="red">-<elseif condition="$vo.type eq 3" /><font color="red">-</if>{pigcms{$vo.score|floatval}</font></td>
										<td>{pigcms{$vo.desc}</td>
										<td>
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);">查看用户信息</a>
										</td>
										<td>
											{pigcms{$vo['time']|date='Y-m-d H:i:s',###}<br/>
										
										</td>
								
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
$(function(){
	$('#status').change(function(){
		location.href = "{pigcms{:U('User/recharge_list')}&status=" + $(this).val();
	});
});

var url = "{pigcms{$config.site_url}";
var export_url = "{pigcms{:U('User/score_pay_back_export')}";

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>