<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Coupon/index')}" class="on">SYSTEM_COUPON_CENTER_1</a>
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Coupon/add')}','SYSTEM_COUPON_CENTER_2','80%','80%',true,false,false,addbtn,'edit',true);">SYSTEM_COUPON_CENTER_2</a>
					<a href="{pigcms{:U('Coupon/had_pull')}" >领取列表</a>
					<a href="{pigcms{:U('Coupon/send_coupon')}" >派发优惠券</a>
                    <a href="{pigcms{:U('Coupon/center_decoration')}" >领券中心装修</a>
                    <a target="_blank" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/SysGetRecords" >发放记录</a>
                    <a target="_blank" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/platform.coupon/SysUseRecords" >核销记录</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form id="myform1" action="{pigcms{:U('Coupon/index')}" method="get">
							<input type="hidden" name="c" value="Coupon"/>
							<input type="hidden" name="a" value="index"/>
							<if condition="$admin_area neq 3">
								<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" style="display:inline"></div>
							</if>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>标题</option>
								<option value="coupon_id" <if condition="$_GET['searchtype'] eq 'coupon_id'">selected="selected"</if>>优惠券ID</option>
							</select>
							状态:
							<select name="status">
								<option value="-1" <if condition="$_GET['status'] eq -1 OR !isset($_GET['status'])">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>启用</option>
								<option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>超过期限</option>
								<option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已领完</option>
								<option value="0" <if condition="$_GET['status'] eq 0 AND isset($_GET['status'])">selected="selected"</if>>禁止</option>
							</select>
                            <input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
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
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>名称</th>
								<th>城市</th>
								<th>使用平台</th>
								<th>使用类别</th>
								<th>使用分类</th>
								<th>总数</th>
								<th>已领取</th>
								<th>已使用</th>
								<th>起始时间</th>
								<th>满减条件</th>
                                <th>SYSTEM_COUPON_CENTER_5/商家补贴金额</th>
                                <th>指定店铺</th>
								<th class="textcenter">只允许新用户</th>
								<th>查看二维码</th>
								<th class="textcenter">状态</th>
								<th class="textcenter">编辑</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($coupon_list)">
								<volist name="coupon_list" id="vo">
									<tr>
										<td>{pigcms{$vo.coupon_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.city_name|default='通用'}</td>
										<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
										<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
										<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
										<td>{pigcms{$vo.num}</td>
										<td>{pigcms{$vo.had_pull}</td>
										<td>{pigcms{$vo.use_count}</td>
										<td>{pigcms{$vo.start_time|date='Y-m-d H:i',###} 到 {pigcms{$vo.end_time|date='Y-m-d H:i',###}</td>
										<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>

                                        <td>SYSTEM_COUPON_CENTER_5：{pigcms{$vo.plat_money|getFormatNumber} / 商家：{pigcms{$vo.merchant_money|getFormatNumber}</td>
                                        <td><if condition="$vo['use_type'] eq 1"><a href="{pigcms{:U('Coupon/store_list',array('coupon_id' => $vo['coupon_id']))}">指定店铺</a><else/><font color="green">全部店铺</font></if></td>

										<td class="textcenter"><if condition="$vo['allow_new'] eq 1"><font color="green">是</font><else /><font color="red">否</font></if></td>
										<td><a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=coupon&id={pigcms{$vo.coupon_id}" class="see_qrcode">渠道消息二维码</a>&nbsp;&nbsp; <if condition="$vo.wx_qrcode neq ''"><a href="{pigcms{:U('Coupon/see_qrcode',array('id'=>$vo['coupon_id']))}" class="see_qrcode">微信卡券二维码</a></if></td>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">启用</font><elseif condition="$vo['status'] eq 2"/><font color="blue">超过期限</font><elseif condition="$vo['status'] eq 3" /><font color="black">领完了</font><else /><font color="red">禁止</font></if></td>
										<td class="textcenter">
										<if condition="$vo.edit eq 1">
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Coupon/edit',array('coupon_id'=>$vo['coupon_id']))}','编辑优惠券信息','80%','80%',true,false,false,editbtn,'edit',true);">编辑</a>
										</if>
										<if condition="$vo.status neq 1">|
										<a href="javascript:void(0);" class="delete_row" parameter="coupon_id={pigcms{$vo.coupon_id}" url="{pigcms{:U('Coupon/delete_coupon')}">删除</a></if>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	$(function(){
		$('#indexsort_edit_btn').click(function(){
			$(this).prop('disabled',true).html('提交中...');
			$.post("/merchant.php?g=Merchant&c=Config&a=merchant_indexsort",{group_indexsort:$('#group_indexsort').val(),indexsort_groupid:$('#indexsort_groupid').val()},function(result){
				alert('处理完成！正在刷新页面。');
				window.location.href = window.location.href;
			});
		});
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.o2oTop.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'查看渠道二维码',
				padding: 0,
				width: 430,
				height: 433,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});
		
		$('select[name="status"]').change(function(){
			window.location.href="{pigcms{:U('Coupon/index')}"+$('#myform1').serialize();
		})
	});
	
</script>
<include file="Public:footer"/>