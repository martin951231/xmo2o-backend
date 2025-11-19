<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/recommend_goods_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("推荐商品列表")}</a></li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						<button id='choose_good_btn' class="btn btn-success" style="float:left;">{pigcms{:L_("添加推荐商品")}</button>
						<button id='cancel_good_btn' class="btn btn-cancel" style="float:left;margin-left:20px;">{pigcms{:L_("取消推荐商品")}</button>
					</div>
					<div class="alert alert-info" style="margin:10px;">
						<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
						1. {pigcms{:L_("商城商品详情页中店铺推荐商品功能即用户在前台查看商城商品详情时可以在详情页里为用户推荐店铺的热卖商品。")}<br/>
						2. {pigcms{:L_("用户前台商品详情页中的店铺推荐商品来自于下方设为推荐的商品。")}<br/>
						3.{pigcms{:L_("用户前台商品详情页中的店铺推荐商品显示规则为随机推荐最多6个商品。")}<br/>
						4. {pigcms{:L_("每次用户进入详情里的推荐商品都是展示不一样的随机推荐商品；如果设为推荐的商品小于3，则前台不展示推荐商品；如果设为推荐的商品数量大于等于3且小于6，则前台只展示3个推荐商品；如果设为推荐的商品数量大于等于6，则前台只展示6个推荐商品。")}<br/>

					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50"><input type='checkbox' id='checkall'/></th>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th class="button-column">{pigcms{:L_("商品名称")}</th>
									<th width="100">{pigcms{:L_("价格")}</th>
									<th width="100">{pigcms{:L_("二维码")}</th>
									<th class="button-column" style="width:100px;">{pigcms{:L_("单位")}</th>
									<th width="50">{pigcms{:L_("原始库存")}</th>
									<th width="50">{pigcms{:L_("实际库存")}</th>
									<th width="50">{pigcms{:L_("今日销量")}</th>
									<th width="50">{pigcms{:L_("总销量")}</th>
									<th class="button-column" style="width:180px;">{pigcms{:L_("最后操作时间")}</th>

								</tr>
							</thead>
							<tbody>
								<if condition="$goods_list">
									<volist name="goods_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><input type="checkbox" name='goods_arr[]' value='{pigcms{$vo.goods_id}'/></td>
											<td>{pigcms{$vo.goods_id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.price|floatval}</td>
											<td><a href="{pigcms{:U('see_goods_qrcode',array('code'=>$vo['goods_id']))}" class="see_qrcode">{pigcms{:L_("二维码")}</a></td>
											<td class="button-column">{pigcms{$vo.unit}</td>
											<if condition="$vo['original_stock'] eq -1">
											<td>{pigcms{:L_("无限")}</td>今日销量
											<else />
											<td>{pigcms{$vo.original_stock}</td>
											</if>
                                            <if condition="$vo['stock_num'] eq -1">
                                                <td>{pigcms{:L_("无限")}</td>
                                                <else />
                                                <td>{pigcms{$vo.stock_num}</td>
                                            </if>
											<td>{pigcms{$vo.today_sell_count}</td>
											<td>{pigcms{$vo.sell_count}</td>
											<td class="button-column">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>

										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="13" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	//"店铺")}id
	var store_id={pigcms{$now_store.store_id|default=2};
	var selecteditems="{pigcms{$selecteditems}";
	var src="?c=Diypage&a=good&store_id="+store_id+"&type=more&number=1&radio=0&shop_type=2&selecteditems="+selecteditems;
	$('#choose_good_btn').click(function(){
		win=layer.open({
			title:false,
			closeBtn:0,
			type:2,
			content:src,
			area:['650px','576px'],
			shade:0.2,
			cancel:function(){

			},
			move:false
		});
	});
	$('#checkall').click(function(){
		var flag=this.checked;
		$('input[name="goods_arr[]"]').prop('checked',flag);
	});
	$(document).on('click','#cancel_good_btn',function(){
		var str='';
		$('input[name="goods_arr[]"]:checked').each(function(){
			str+=$(this).val()+',';
		});
		if(str.length==0){
			layer.alert('{pigcms{:L_("最少选择一个取消推荐的商品！")}');
			return false;
		}
		request(str,0);
	});
	$('.see_qrcode').click(function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
				
			},
			id: 'handle',
			title:'{pigcms{:L_("查看商品二维码")}',
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
});
</script>
<script type="text/javascript">
var win='';
/*{pigcms{:L_("关闭弹窗")}*/
function login_box_close(){
	layer.close(win);
}
/*{pigcms{:L_("获取弹窗返回数据")}*/
var goods_str='';
var href="{pigcms{:U('Mall/save_goods_recommed')}";
function widget_box_after(number,data){
	if(data.length>0){
		$.each(data,function(n,v){
			goods_str+=v.id+',';
		});
		request(goods_str);
	}else{
		login_box_close();	
	}
}
function request(goods_str,is_recommend){
	is_recommend=isNaN(is_recommend)?1:is_recommend;
	$.post(href,{'goods_str':goods_str,is_recommend:is_recommend},function(res){
		layer.alert(res.info,{closeBtn:0},function(index){
			if(res.status){
				layer.close(index);
				if(is_recommend){
					login_box_close();
				}
				window.location.reload();
			}	
		});
	});
}
</script>
<include file="Public:footer"/>
