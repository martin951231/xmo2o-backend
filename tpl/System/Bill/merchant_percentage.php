<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="javascript:history.go(-1)" >上一页</a>
					<if condition="$_GET['province_id'] gt 0 OR $_GET['type'] eq 1" >
						 <a href="{pigcms{:U('Bill/merchant_percentage', array('province_id' => $_GET['province_id']))}" class="on">抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['province_id'],'type'=>1))}">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('province_id' => $_GET['province_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['province_id'],'province_id' => $_GET['province_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['province_id'],'province_id' => $_GET['province_id']))}" >商家统一折扣统计</a>
					<elseif condition="$_GET['city_id'] gt 0 OR $_GET['type'] eq 2 "  />
						<a href="{pigcms{:U('Bill/merchant_percentage', array('city_id' => $_GET['city_id']))}" class="on">抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['city_id'],'type'=>2))}">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('city_id' => $_GET['city_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['city_id'],'city_id' => $_GET['city_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['city_id'],'city_id' => $_GET['city_id']))}" >商家统一折扣统计</a>
					<elseif condition="$_GET['area_id'] gt 0 OR $_GET['type'] eq 3" />
                        <a href="{pigcms{:U('Bill/merchant_percentage', array('area_id' => $_GET['area_id']))}" class="on">抽成记录列表</a>
						<a href="{pigcms{:U('Bill/area_commission', array('area_id' => $_GET['area_id'],'type'=>3))}">区域代理商佣金列表</a>
						<a href="{pigcms{:U('Bill/express_statistics', array('area_id' => $_GET['area_id']))}" >配送费统计</a>
						<a href="{pigcms{:U('Shop/merchant_reduce', array('area_id' => $_GET['area_id'],'area_id' => $_GET['area_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
						<a href="{pigcms{:U('Merchant/discount', array('area_id' => $_GET['area_id'],'area_id' => $_GET['area_id']))}" >商家统一折扣统计</a>
                     <elseif condition="$_GET['street_id']" />
                        <a href="{pigcms{:U('Bill/merchant_percentage', array('street_id' => $_GET['street_id']))}" class="on">抽成记录列表</a>
                        <a href="{pigcms{:U('Bill/area_commission', array('street_id' => $_GET['street_id']))}">区域代理商佣金列表</a>
                        <a href="{pigcms{:U('Bill/express_statistics', array('street_id' => $_GET['street_id']))}" >配送费统计</a>
                        <a href="{pigcms{:U('Shop/merchant_reduce', array('street_id' => $_GET['street_id']))}" >{pigcms{$config['shop_alias_name']}优惠补贴统计</a>
					</if>
					
					
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bill/merchant_percentage')}" method="get">
							<input type="hidden" name="c" value="Bill"/>
							<input type="hidden" name="a" value="merchant_percentage"/>
							<input type="hidden" name="province_id" value="{pigcms{$_GET['province_id']}"/>
							<input type="hidden" name="city_id" value="{pigcms{$_GET['city_id']}"/>
							<input type="hidden" name="area_id" value="{pigcms{$_GET['area_id']}"/>
                            <input type="hidden" name="street_id" value="{pigcms{$_GET['street_id']}"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>								
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
								<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							
							<div style="display:inline"><font color="#000">时间筛选 ：</font></div>
							<input type="text" class="input" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="text" class="input" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
					
							<input type="submit" value="查询" class="button"/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>商家总抽成金额：{pigcms{$all_money}</b>&nbsp;&nbsp;&nbsp;&nbsp;<b>
							<if condition="$config.open_extra_price eq 1">
							商家送出{pigcms{$config.score_name}数：{pigcms{$all_score|floatval}</b>&nbsp;&nbsp;&nbsp;&nbsp;<b>区域管理员{pigcms{$config.score_name}结算比例：{pigcms{$_SESSION['system']['score_percent']|floatval}</b>&nbsp;&nbsp;&nbsp;&nbsp;<b>{pigcms{$config.score_name}结算金额：{pigcms{$all_score*$_SESSION['system']['score_percent']/100|floatval}</b>
							</if>
							
							<a href="javascript:void(0)" onclick="exports();"  class="button">导出EXCEL</a>
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
								<th>编号</th>
								<th>商家名称</th>
								<th>联系电话</th>
								<th>当前商家余额</th>
								<th>送出{pigcms{$config.score_name}</th></if>
								<th>抽成金额</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$result['mer_percentage_list']">
								<volist name="result.mer_percentage_list" id="vo">
									<tr>
										<td>{pigcms{$vo.mer_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.phone}</td>
										<td>{pigcms{$vo.now_money}</td>
										<td><if condition="$config.open_extra_price eq 1">{pigcms{$vo['all_score']+$vo['send_count']|round}
										<php>if($vo['send_count']>0){ echo " 线下送出".floatval($vo['send_count']).$config['extra_price_alias_name'];}</php>
										<else />{pigcms{$vo.all_score|round}</if></td>
										<td><if condition="$vo.money gt 0">{pigcms{$vo.money}<else />0</if></td>
										
										<td class="textcenter"><a href="{pigcms{:U('Bill/merchant_money_list',array('mer_id'=>$vo['mer_id']))}" >查看</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$result.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script>
		var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Bill/export_merchant_percentage')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>