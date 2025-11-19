<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}">物业管理</a>
            </li>
            <li class="active">物业对帐</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('pay_order')}" method="get">
							<input type="hidden" name="c" value="Unit"/>
							<input type="hidden" name="a" value="pay_order"/>
							
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"  style="height:42px"/>&nbsp;&nbsp;
							<select name="searchtype"  style="height:42px">
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option> 
                                <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>业主姓名</option> 
								<option value="usernum" <if condition="$_GET['searchtype'] eq 'usernum'">selected="selected"</if>>物业编号</option>	
								<option value="order_name" <if condition="$_GET['searchtype'] eq 'order_name'">selected="selected"</if>>缴费项名称</option>
                                <option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
                            </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            缴费类型: <select name="pay_cost_type" style="height:42px">
                                <option value="0" <if condition="$_GET['pay_cost_type'] eq '0'">selected="selected"</if>>所有</option>
                                <option value="1" <if condition="$_GET['pay_cost_type'] eq '1'">selected="selected"</if>>业主缴费</option>
                                <option value="2" <if condition="$_GET['pay_cost_type'] eq '2'">selected="selected"</if>>积分抵扣</option>
                            </select>
							支付方式: <select name="searchstatus" style="height:42px">
								<option value="0" <if condition="$_GET['searchstatus'] eq '0'">selected="selected"</if>>所有</option>
								<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>在线支付</option>
                                <option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>线下支付</option>
                                <option value="3" <if condition="$_GET['searchstatus'] eq '3'">selected="selected"</if>>积分抵扣</option>
							</select>
							时间筛选：
							<input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>&nbsp;&nbsp;-&nbsp;&nbsp;
							<input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                            <br/><br/>
                            物业服务时间：
                            <input type="text" name="month_start_time" class="input-text" value="{pigcms{$_GET['month_start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>&nbsp;&nbsp;-&nbsp;&nbsp;
                            <input type="text" name="month_end_time" class="input-text" value="{pigcms{$_GET['month_end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;
                            <button class="btn btn-success" type="submit">查询</button>&nbsp;&nbsp;
							<button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('pay_order')}'">重置</button>&nbsp;&nbsp;

							<!-- <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Library/owner_arrival')}'" <if condition="!in_array(35,$house_session['menus'])">disabled="disabled"</if>>现场缴费</button>&nbsp;&nbsp; -->
							<button class="btn btn-success" type="button" onclick="exportEXCEL('{pigcms{:U('export',$_GET)}',this)" <if condition="!in_array(34,$house_session['menus'])">disabled="disabled"</if>>EXCEL导出</button>&nbsp;&nbsp;
                            <a href="{pigcms{:U('pay_order_all')}" class="btn btn-success"  <if condition="!in_array(33,$house_session['menus'])">disabled="disabled"</if>>全部数据</a>
						</form>
					</td>
				</tr>
			</table>
			
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">订单编号</th>
                                    <th width="5%">收银台订单编号</th>
                                    <th width="5%">缴费项</th>
                                    <th width="5%">缴费类型</th>
                                    <th width="5%">应缴金额</th>
                                    <th width="5%">已缴金额</th>
                                    <th width="5%">积分抵扣金额</th>
                                    <th width="5%">积分使用数量</th>
                                    <th width="5%">支付方式</th>
                                    <th width="8%">支付时间</th>
                                    <th width="5%">业主名</th>
                                    <th width="5%">联系方式</th>
                                    <th width="10%">住址</th>
									<th width="5%">物业服务周期</th>
									<th width="8%">赠送物业服务时间</th>
									<th width="5%">服务时间</th>
                                    <th width="5%">自定义缴费周期</th>
                                    <th width="5%">备注</th>
                                    <th width="5%">开票状态</th>
                                    <th width="5%">账单状态</th>
                                    <!-- <th width="5%">对账状态</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$order_list['order_list']">
                                    <volist name="order_list['order_list']" id="vo"> 
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td>{pigcms{$vo.order_id}</td>
                                            <td><if condition="$vo['cashier_id']">{pigcms{$vo.cashier_id}<else/>--</if></td>
                                            <td>{pigcms{$vo.order_name}</td>
                                            <td>
                                                <if condition="$vo.pay_cost_type eq 1">积分抵扣<else/>业主缴费</if>
                                            </td>
                                            <td>{pigcms{$vo.money}</td>
                                            <td>{pigcms{$vo.paymoney}</td>
                                            <td>{pigcms{$vo.score_deducte}</td>
                                            <td>{pigcms{$vo.score_used_count}</td>
                                            <td>
                                                <if condition="$vo['pay_type'] eq 0 && !$vo['cashier_id']">
                                                    <strong style="color: green">在线支付</strong><if condition="$vo.online_pay_type">({pigcms{$vo.online_pay_type})</if>
                                                <elseif condition="$vo['pay_type'] eq 0 && $vo['cashier_id'] gt 0" />
                                                    <strong style="color: green">在线支付</strong>(收银台扫码)
                                                <elseif condition="$vo['pay_type'] eq 1" />
                                                    线下支付<if condition="$vo['pay_type_name']">({pigcms{$vo.pay_type_name})</if>
                                                <elseif condition="$vo['pay_type'] eq 2" />
                                                    积分抵扣
                                                </if>
                                            </td>
                                            <td>{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}</td>
                                            <td><if condition='$vo["username"]'>{pigcms{$vo.username}<else/>--</if></td>
                                            <td><if condition='$vo["phone"]'>{pigcms{$vo.phone}<else/>--</if></td>
                                            <td>{pigcms{$vo.address}</td>
											<td><if condition="$vo['order_type'] eq 'property'">{pigcms{$vo.property_month_num}个月<else/>—</if></td>
											<if condition='!empty($vo["presented_property_month_num"]) AND ($vo["diy_type"] eq 0)'><td>{pigcms{$vo.presented_property_month_num}个月</td><elseif condition='$vo["diy_type"] eq 1' /><td>{pigcms{$vo.diy_content}</td><else /><td class="red">无</td></if>                                            
                                            <td style="text-align: center;"><if condition="$vo['custom_time_str']">{pigcms{$vo.custom_time_str}<elseif condition="$vo['property_time_str']&&$vo['property_month_num']"/>{pigcms{$vo.property_time_str}<else/>--</if></td>                                            
                                            <td style="text-align: center;"><if condition="$vo['order_type'] eq 'custom_payment'">{pigcms{$vo.payment_paid_cycle}/周期<else/>—</if></td>
                                            <td onclick="aaaa('{pigcms{$vo.remarks}')" style="cursor:pointer ">查看</td>
                                            <td><if condition="$vo['is_kai'] eq 1"><span style="color: green">已开票</span><else/><span style="color: red">未开票</span></if></td>
                                            <td><if condition="$vo['order_status'] eq 1"><span style="color: green">正常</span><else/><span style="color: red">已退款</span></if></td>
                                           <!--  <td>
                                                <if condition="$vo['is_pay_bill'] eq 0">
                                                    <if condition="$vo.pay_type eq 1">
                                                        <strong style="color: green">线下支付</strong>
                                                    <else />
                                                        <strong style="color: red">未对账</strong>
                                                    </if>
                                                <else />
                                                    <strong style="color: green"><strong type="color:green">已对账</strong>
                                                </if>
                                            </td> -->
                                        </tr>
                                    </volist>
									<tr class="even">
										<td colspan="20">
											本页应缴总金额：<strong style="color: green">{pigcms{$total}</strong>,本页已缴总金额：<strong style="color: green">{pigcms{$totalPayMoney}</strong>,本页总退款：<strong style="color: green">{pigcms{$refund_total}</strong>　本页已出账金额：<strong style="color: red">{pigcms{$finshtotal}</strong><br/>
                                            总金额：<strong style="color: green">{pigcms{$order_list.totalMoney.totalMoney}</strong>　总退款：<strong style="color: green">{pigcms{$order_list.refund_total.totalMoney}</strong>　<br/>
										</td>
									</tr>
									<tr class="odd">
										<td colspan="20" id="show_count"></td>
									</tr>
									<tr><td class="textcenter pagebar" colspan="20">{pigcms{$order_list.pagebar}</td></tr>
                                <else />
                                    <tr class="odd"><td class="button-column" colspan="20" >没有任何数据。</td></tr>
								</if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="font-size: 30px; padding: 15px;"></div>
<script>
    function aaaa(content){
        if(content==''){
            content = '<span style=" color:red; padding:38%;">暂无数据</span>';
        }
        layer.open({
            type: 1,
            area: ['420px', '240px'],
            content: '<div style=" font-size: 20px;  padding: 15px;">'+String(content)+'</div>'
        });
    }
    var isexporting=false;
    function exportEXCEL(url,_this){
        if(isexporting){
            return false;
        }
        
        isexporting=true;
        var obj=$(_this);
        setTimeout(function(){
            window.isexporting=false;
            obj.html('EXCEL导出');
            obj.prop('disabled',false);
        },60000);
        obj.html('正在导出，请稍等...');
        obj.prop('disabled',true);
        location.href=url;
    }
    
</script>
<include file="Public:footer"/>
