<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("优惠券管理")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group"  style="float: left;">
						<form action="{pigcms{:U('Card_new/card_new_coupon')}" method="get">
							<input type="hidden" name="c" value="Card_new"/>
							<input type="hidden" name="a" value="card_new_coupon"/>
							
							{pigcms{:L_("搜索")}：
							<select id="searchtype" name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected</if>>{pigcms{:L_("名称")}</option>
								<option value="coupon_id" <if condition="$_GET['searchtype'] eq 'coupon_id'">selected</if>>{pigcms{:L_("ID")}</option>
							</select>
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder=""/>
							&nbsp;&nbsp;
							{pigcms{:L_("状态")}：
							<select id="status" name="status">
								<option value="-1" <if condition="$_GET['status'] eq -1">selected</if>>{pigcms{:L_("全部")}</option>
								<option value="0" <if condition="$_GET['status'] eq 0">selected</if>>{pigcms{:L_("未启用")}</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected</if>>{pigcms{:L_("启用")}</option>
								<option value="2" <if condition="$_GET['status'] eq 2">selected</if>>{pigcms{:L_("超过期限")}</option>
								<option value="3" <if condition="$_GET['status'] eq 3">selected</if>>{pigcms{:L_("领完了")}</option>
							</select>
								
							<input type="submit" value="{pigcms{:L_("查询")}" class="btn"/>　
						</form>
					</div>
					<div class="form-group" style="float: left;">
						<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("新增优惠券")}</button>
						<a class="btn btn-success" href="{pigcms{:U('Card_new/send_coupon')}">{pigcms{:L_("派发优惠券")}</a>
						<a target="_blank" class="btn btn-success" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/merchant.coupon/MerGetRecords">{pigcms{:L_("发放记录")}</a>
						<a target="_blank" class="btn btn-success" href="{pigcms{$config.site_url}/v20/public/platform/#/dialog/merchant.coupon/MerUseRecords">{pigcms{:L_("核销记录")}</a>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="100">ID</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("名称")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("图片")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("二维码")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("使用平台")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("使用类别")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("使用分类")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("总数")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("已领取")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("已使用")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("起始时间")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("满减条件")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("只允许新用户")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("状态")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("编辑")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$coupon_list">
									<volist name="coupon_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td>{pigcms{$vo.coupon_id}</td>
										<td>{pigcms{$vo.name}</td>
										<td><img src="{pigcms{:replace_file_domain($vo['img'])}" style="width:30px;height:30px"/></td>
										<td><if condition="!empty($vo['wx_cardid'])">
											<a href="{pigcms{:U('Card_new/see_qrcode',array('id'=>$vo['coupon_id'],'wx_qrcode'=>1))}" class="see_qrcode" >{pigcms{:L_("查看二维码")}</a>
											<elseif condition="$vo.is_wx_card" />
												{pigcms{:L_("同步微信卡包失败")}
											<else />
												{pigcms{:L_("没有同步到微信")}
											</if>
										</td>
										<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
										<td><if condition="$vo.cate_name eq 'all'">{pigcms{:L_("全部类别")}<else />{pigcms{$category[$vo['cate_name']]}</if></td>
										<td><if condition="$vo.cate_id eq '0'">{pigcms{:L_("全部分类")}<else />{pigcms{$vo['cate_id']}</if></td>
										<td>{pigcms{$vo.num}</td>
										<td>{pigcms{$vo.had_pull}</td>
										<td>{pigcms{$vo.use_count}</td>
										<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
										<td>满 {pigcms{$vo.order_money} 减 {pigcms{$vo.discount} {pigcms{$config['Currency_txt']}</td>
										<td class="textcenter"><if condition="$vo['allow_new'] eq '1'"><font color="green">{pigcms{:L_("是")}</font><else /><font color="red">{pigcms{:L_("否")}</font></if></td>
										<td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">{pigcms{:L_("启用")}</font><elseif condition="$vo['status'] eq 2"/><font color="blue">{pigcms{:L_("超过期限")}</font><elseif condition="$vo['status'] eq 3" /><font color="black">{pigcms{:L_("领完了")}</font><else /><font color="red">{pigcms{:L_("不启用")}</font></if></td>
										<td class="textcenter"><a href="{pigcms{:U('Card_new/edit_coupon',array('coupon_id'=>$vo['coupon_id']))}"> {pigcms{:L_("编辑")}</a>
										<if condition="$vo.status neq 1">|<a onclick="drop_coupon({pigcms{$vo.coupon_id},'{pigcms{$vo.name}')"> {pigcms{:L_("删除")}</a></if>
										
										</td>
									
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});

function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}

function drop_coupon(coupon_id,coupon_name){
	layer.confirm('确定要删除优惠券<font color="red">【'+coupon_name+'】</font>吗？不可恢复。', {
	  title:"温馨提示",
	  btn: ['确定', '取消'] ,offset: getScrollTop()
	}, function(index, layero){
		$.get("{pigcms{:U('Card_new/delete_coupon')}"+'&coupon_id='+coupon_id,{},function(data){
			if (data.status==1) {
				layer.msg(data.info, {offset: getScrollTop()});
				history.go(0)
			}else{
				layer.msg(L_('删除失败'), {offset: getScrollTop()});
			}
		})

	}, function(index){
	});
}

</script>

<script>
	$(function(){
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("编辑")}',
				padding: 0,
				width: 800,
				height: 600,
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
		
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看二维码")}',
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
		
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
	
	function CreateShop(){
		window.location.href = "{pigcms{:U('Card_new/add_coupon')}";
	}
	
</script>
<include file="Public:footer"/>
