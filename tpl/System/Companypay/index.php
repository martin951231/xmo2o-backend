<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<if condition="$config['company_pay_open'] eq 1 or isset($config['company_pay_open'])">
					<a href="{pigcms{:U('Companypay/index')}" class="on">提款列表</a>|
					<a href="{pigcms{:U('Config/index',array('galias'=>'companyPay','header'=>'Companypay/header'))}">企业付款配置</a>
					</if>
					<a href="{pigcms{:U('Companypay/withdraw_by_hand')}">手动提款</a>
					<if condition="$config['company_pay_open'] eq 1 or isset($config['company_pay_open'])">
					<a href="{pigcms{:U('Companypay/withdraw_approval') }">审核在线提现</a>
					</if>
				</ul>
			</div>
			<if condition="$system_session['level'] eq 2">
				<div class="page_tips">
					<ol>
						<li>企业付款软件 对接文档&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=24" target="_blank">【点击查看】</a></li>
					</ol>
				</div>
			</if>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Companypay/index')}" method="get">
							<input type="hidden" name="c" value="Companypay"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="export" value="0"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="pay_id" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商ID</option>								
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
								<option value="user" <if condition="$_GET['searchtype'] eq 'user'">selected="selected"</if>>用户ID</option>
								<option value="house" <if condition="$_GET['searchtype'] eq 'house'">selected="selected"</if>>小区ID</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							付款状态: <select name="searchstatus">
								<option value="-1" <if condition="$_GET['searchstatus'] eq '-1'">selected="selected"</if>>全部</option>								
								<option value="0" <if condition="$_GET['searchstatus'] eq '0'">selected="selected"</if>>未付款</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>已付款</option>
								<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>已拒绝</option>
							</select>
                            
                            身份: <select name="pay_type">
                                <option value="0" <if condition="$_GET['pay_type'] eq 0">selected="selected"</if>>全部</option>
                                <volist name="pay_types" id="vo">
                                    <option value="{pigcms{$key}" <if condition="$_GET['pay_type'] eq $key">selected="selected"</if>>{pigcms{$vo}</option>s
                                </volist>
                            </select>
							<font color="#000">日期筛选：</font>
							<select name="timesearchtype">
								<option value="add_time" <if condition="$_GET['timesearchtype'] eq 'add_time'">selected="selected"</if>>申请时间</option>								
								<option value="pay_time" <if condition="$_GET['timesearchtype'] eq 'pay_time'">selected="selected"</if>>付款时间</option>
							</select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							<button class="button" id="forms" onclick="return search();">查询</button>
							<button class="button" id="export" onclick="return exports();">导出excel</button>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>付费类型</th>
								<th>商家/用户ID</th>
								<th>联系电话</th>
								<th>金额</th>
								<th>描述</th>
								<th>身份</th>
								<th>申请时间</th>
								<th>付款人</th>
								<th>付款时间</th>
								<th>失败原因</th>
								<th>状态</th>
							</tr>
						</thead>
						<tbody>
							
								<volist name="pay_list" id="vo">
									<tr>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td>{pigcms{$vo.pay_type}</td>
										<td>{pigcms{$vo.pay_id}</td>
										<td>{pigcms{$vo.phone}</td>
										<td>{pigcms{$vo['money']/100}</td>
										<td>{pigcms{$vo.desc}</td>
										<td>{pigcms{$vo.user_type}</td>
										<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
										<td>{pigcms{$vo.realname|default='--'}</td>
										<td><if condition="$vo['pay_time']">{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<else/>无</if></td>
										<td>{pigcms{$vo.fail_reason}</td>
										<td>
                                            <if condition="$vo['status'] eq 1"><font color="green">已付款</font>
                                                <elseif condition="$vo['status'] eq 2"/><font color="red">已拒绝</font>
<!--                                                |<a href="{pigcms{:U('Companypay/restore',array('pigcms_id'=>$vo['pigcms_id'],'status'=>0))}"><font color="green">恢复</font></a>-->
                                                <else/><font color="red">未付款</font>
<!--                                                |<a href="{pigcms{:U('Companypay/restore',array('pigcms_id'=>$vo['pigcms_id'],'status'=>2))}"><font color="black">取消</font></a>-->
                                            </if>
                                        </td>
										
										<!--<td class="textcenter"><a href="{pigcms{:U('Merchant/order',array('mer_id'=>$vo['mer_id']))}">查看账单</a></td>-->
										<!--td class="textcenter"><a href="{pigcms{:U('Merchant/weidian_order',array('mer_id'=>$vo['mer_id']))}">微店账单</a></td-->
									</tr>
								</volist>
							<tr><td class="textcenter pagebar" colspan="16">{pigcms{$pagebar}</td></tr>	
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
	function exports(){
		$('input[name="export"]').val(1);
		return true;
	}
	function search(){
		$('input[name="export"]').val(0);
		return true;
	}
	
</script>
<include file="Public:footer"/>