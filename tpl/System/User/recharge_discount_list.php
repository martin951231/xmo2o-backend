<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('User/recharge_list')}">订单列表</a>
					<a href="{pigcms{:U('User/admin_recharge_list')}" >管理员充值列表</a>
					<a href="{pigcms{:U('User/recharge_discount_list')}" class="on">充值优惠管理</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('recharge_discount_list')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="recharge_discount_list"/>
							筛选:
							<font color="#000">日期：</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							状态：
							<select name="status" id="status">
								<option value="-1" <if condition="$_GET['status'] eq -1 OR !isset($_GET['status'])">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>开启</option>
								<option value="0" <if condition="$_GET['status'] eq 0 AND isset($_GET['status'])">selected="selected"</if>>关闭</option>
							</select>
							<input type="submit" value="查询" class="button"/>　
							<a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('User/recharge_discount_add',array('id'=>$vo['id']))}','添加充值优惠规则',700,500,true,false,false,editbtn,'edit',true);" class="button" style="float:right;margin-right: 10px;">添加充值优惠规则</a>
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
						<thead>
							<tr>
								<th>编号</th>
								<th>满足金额</th>
								<th>赠送金额</th>
								<th>添加时间</th>
								<th>状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.discount_id}</td>
										<td>{pigcms{$vo.full_money|floatval}</td>
										<td>{pigcms{$vo.money|floatval}</td>
										<td>{pigcms{$vo['add_time']|date='Y-m-d H:i:s',###}</td>
										<td>
											<if condition="$vo['status'] eq 1">
											<font color="green">开启</font>
											<else/>
											<font color="red">关闭</font>
											</if></td>
										<td class="textcenter">
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/recharge_discount_add',array('discount_id'=>$vo['discount_id']))}','编辑',800,560,true,false,false,editbtn,'order_edit',true);">编辑</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="6">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="6">列表为空！</td></tr>
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

</script>
<include file="Public:footer"/>