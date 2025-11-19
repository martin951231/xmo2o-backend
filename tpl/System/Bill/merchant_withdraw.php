<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Bill/merchant_withdraw')}"   <if condition="empty($_GET['withdraw_status'])">class="on"</if> >商家余额存入记录</a>
					<a href="{pigcms{:U('Bill/merchant_withdraw',array('withdraw_status'=>1))}" <if condition="$_GET['withdraw_status'] eq 1">class="on"</if> >待提现商家</a>
					<a href="{pigcms{:U('Bill/merchant_withdraw_list')}">已提现记录</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Bill/merchant_withdraw')}" method="get">
							<input type="hidden" name="c" value="Bill"/>
							<input type="hidden" name="a" value="merchant_withdraw"/>
							<if condition="isset($_GET['withdraw_status'])">
							<input type="hidden" name="withdraw_status" value="{pigcms{$_GET['withdraw_status']}"/>
							</if>
							筛选: 
							<if condition="$admin_area neq 3">
							<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
							</if>
							<div style="display:inline">
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>								
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
								<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							商家分类：
							<select name="cat_fid">
								<option value="">全部</option>
								<volist name='category_list' id='cvo'>
									<eq name='cvo.cat_fid' value='0'>
										<option value="{pigcms{$cvo.cat_id}">{pigcms{$cvo.cat_name}</option>
									</eq>
								</volist>
							</select>
							<select name="cat_id">
								<option value="">全部</option>
								<volist name='category_list' id='cvo'>
									<gt name='cvo.cat_fid' value='0'>
										<option value="{pigcms{$cvo.cat_id}" class="item_box{pigcms{$cvo.cat_fid}">{pigcms{$cvo.cat_name}</option>
									</gt>
								</volist>
							</select>
							</div>
							<input type="submit" value="查询" class="button"/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>平台商家当前剩余总余额{pigcms{$all_money}</b>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>平台商家总余额（含已提现）{pigcms{$all_mer_money|floatval}</b>
							<if condition="$_GET['withdraw_status'] eq 1">
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>平台商家待兑现总金额：{pigcms{$all_need_pay|floatval}</b>
							</if>
						</form>
					</td>
					<td>
                        <a href="{pigcms{:U('Bill/merchant_withdraw',$_GET)}&export=1" class="button" style="float:right;margin-right: 10px;"><if condition="$_GET['withdraw_status'] eq 1">待提现商家<else/>导出余额记录</if></a>
						<!--a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出余额记录</a-->
				
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
							<col width="100" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>编号</th>
								<th>商家名称</th>
								<if condition="!isset($config['not_o2o_demo'])">
								<th>联系电话</th>
								</if>
								<if condition="$_GET['withdraw_status'] eq 1">
									<th>提现时间</th>
                                    <th>账号信息</th>
								</if>
								<th>最近待兑现</th>
								<th>商家余额</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$mer_withdraw_list">
								<volist name="mer_withdraw_list" id="vo">
									<tr>
										<td>{pigcms{$vo.mer_id}</td>
										<td>{pigcms{$vo.name}</td>
										<if condition="!isset($config['not_o2o_demo'])">
										<td>{pigcms{$vo.phone}</td>
										</if>
										<if condition="$_GET['withdraw_status'] eq 1">
											<td>{pigcms{$vo.withdraw_time|date="Y-m-d H:i:s",###}</td>
                                            <td>{pigcms{$vo.bank}<if condition="$vo['bank_detail'] neq ''">|{pigcms{$vo.bank_detail}</if></td>
										</if>
										<td>{pigcms{$vo['withdraw_money']/100}{pigcms{$config['Currency_txt']}</td>
										<td>{pigcms{$vo.money}</td>
										<td class="textcenter"><a href="javascript:parent.addIframe('商家余额存入记录-{pigcms{$vo.name}','main_bill_merchant_money_list_{pigcms{$vo['mer_id']}','{pigcms{:U('Bill/merchant_money_list',array('mer_id'=>$vo['mer_id']))}');" >查看</a></td>
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
<script>
		var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Bill/merchent_withdraw_export')}"
</script>
<script type="text/javascript">
var cat_fid="{pigcms{$_GET.cat_fid}";
var cat_id="{pigcms{$_GET.cat_id}";
$('select[name=cat_fid]').change(function(){
	var cat_fid=$(this).val();
	$('select[name=cat_id]').val('');
	if(cat_fid>0){
		$('select[name=cat_id]').find('option:gt(0)').hide();
		$('select[name=cat_id]').find('option.item_box'+cat_fid).show();
	}else{
		$('select[name=cat_id]').find('option:gt(0)').hide();
	}
});
if(cat_fid>0){
	$('select[name=cat_fid]').val(cat_fid);	
}
$('select[name=cat_fid]').change();
if(cat_id>0){
	$('select[name=cat_id]').val(cat_id);	
}
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>