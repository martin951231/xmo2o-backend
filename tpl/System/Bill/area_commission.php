<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="javascript:history.go(-1)" >上一页</a>
					<if condition="$_GET['province_id'] gt 0 OR $_GET['type'] eq 1" >
						 <a href="{pigcms{:U('Bill/merchant_percentage', array('province_id' => $_GET['city_id']?:$_GET['area_id']))}" >抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['province_id']?:$_GET['area_id']))}" class="on">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('province_id' => $_GET['province_id']?:$_GET['area_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['province_id'],'province_id' => $_GET['province_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['province_id'],'province_id' => $_GET['province_id']))}" >商家统一折扣统计</a>
					<elseif condition="$_GET['city_id'] gt 0 OR $_GET['type'] eq 2 "  />
						<a href="{pigcms{:U('Bill/merchant_percentage', array('city_id' => $_GET['city_id']?:$_GET['area_id']))}">抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['city_id']?:$_GET['area_id']))}" class="on">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('city_id' => $_GET['city_id']?:$_GET['area_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['city_id'],'city_id' => $_GET['city_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['city_id'],'city_id' => $_GET['city_id']))}" >商家统一折扣统计</a>
					<elseif condition="$_GET['area_id'] gt 0 OR $_GET['type'] eq 3" />
						 <a href="{pigcms{:U('Bill/merchant_percentage', array('area_id' => $_GET['area_id']))}">抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['area_id']))}" class="on">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('area_id' => $_GET['area_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['area_id'],'area_id' => $_GET['area_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['area_id'],'area_id' => $_GET['area_id']))}" >商家统一折扣统计</a>
					</if>
					
					
				</ul>
			</div>
			<div style="margin-top:10px;width:100%;height:240px;background-color:#81d2cf">
					<p style="text-align:center;font-family: 'Arial Normal', 'Arial';font-weight: 400;font-style: normal;font-size: 36px;color: #FFFFFF;padding-top: 36px;">{pigcms{$config['Currency_symbol']}{pigcms{$all_money} <span style="font-size: 14px;">当前佣金比例：{pigcms{$rate}%</span></p>
					<p style="text-align:center; " class="my_money">
						<a href="javascript:void(0);" onclick="return check_tixian();">
							<span >{pigcms{:L_("申请提现")}</span>
						</a>
					
						<a href="{pigcms{:U('Bill/area_commission_withdraw_list',['area_id'=>$_GET['area_id'],'street_id'=>$_GET['street_id']])}">
							<span >{pigcms{:L_("提现记录")}</span>
						</a>
					</p>
				
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bill/area_commission')}" method="get">
							<input type="hidden" name="c" value="Bill"/>
							<input type="hidden" name="a" value="area_commission"/>
							<input type="hidden" name="area_id" value="{pigcms{$_GET['area_id']}"/>
                            <input type="hidden" name="street_id" value="{pigcms{$_GET['street_id']}"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>								
								<option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>店铺名称</option>
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							
							<div style="display:inline"><font color="#000">佣金结算时间筛选 ：</font></div>
							<input type="text" class="input" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="text" class="input" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							业务类型：
							<select name="order_type">
								<volist name="get_alias_c_name" id="v">
                                <option value="{pigcms{$key}" <if condition="$_GET['order_type'] eq $key">selected="selected"</if>>{pigcms{$v}</option>
                            	</volist>
                            </select>&nbsp;&nbsp;
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
								<th>业务类型</th>
								<th style="width: 300px;">订单号</th>
								<th>商家名称</th>
								<th>店铺名称</th>
								<th>订单金额</th>
								<th>佣金比例</th>
								<th>获得佣金</th>
								<th style="width: 120px;">佣金结算时间</th>
							</tr>
						</thead>
						<tbody>
					
							<if condition="$data">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.order_type_txt}</td>
										<td>{pigcms{$vo.order_id}</td>
										<td>{pigcms{$vo.mer_name}</td>
										<td>{pigcms{$vo.store_name}</td>
										<td>{pigcms{$vo.order_money|getFormatNumber=###}</td>
										<td>{pigcms{$vo.rate|default=''}</td>
										<td><span style="color:green;">+{pigcms{$vo.commission}</span></td>
										<td>{pigcms{$vo.addtime}</td>
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
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript">
	function check_tixian(){

		$.post("{pigcms{:U('Bill/check_tixian')}", {area_id:"{pigcms{$_GET['area_id']}",street_id:"{pigcms{$_GET['street_id']}"}, function(data){
			if(data.status == 1){
				window.o2oTop.artiframe("{pigcms{:U('Bill/area_commission_withdraw',['area_id'=>$_GET['area_id'],'street_id'=>$_GET['street_id']])}",'申请提现',800,500,true,false,false,addbtn,'add',true);
			}
			else{
				layer.alert(data.msg, {offset: '100px'});
			}
				
		},'json')
		return false;
	}
</script>
<include file="Public:footer"/>