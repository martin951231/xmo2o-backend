<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/market')}">{pigcms{:L_("进销存")}</a>
			</li>
			<li class="active">{pigcms{:L_("支付")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" id="pay-buy-form" method="post">
						<input type="hidden" value="{pigcms{$now_goods.goods_id}" id="goods_id" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品名称")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.name}</label>
								</div>
								<if condition="empty($now_goods['list'])">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("商品条形码")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.number}</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="wholesale_price">{pigcms{:L_("批发价")}</label></label>
									<label class="col-sm-1">{pigcms{$now_goods.price}</label>
								</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_goods['pic_arr']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/></li>
											</volist>
										</ul>
									</div>
								</div>
								<if condition="$now_goods['discount_info']">
                                    <div class="form-group topic_box">
                                        <div class="question_box properties">
                                            <p class="question_info">
                                                <span>{pigcms{:L_("批发满：")}</span>
                                                <input type="text" class="txt properties_name" value="{pigcms{$now_goods['discount_info']['num']}" style="width:50px" disabled/>
                                                <span>{pigcms{:L_("件，享受：")}</span>
                                                <input type="text" class="txt properties_num" value="{pigcms{$now_goods['discount_info']['discount']}" style="width:50px" disabled/>{pigcms{:L_("折优惠")}
                                            </p>
                                        </div>
                                    </div>
								</if>
                                <div class="form-group" style="font-weight: bolder;">
                                    <label class="col-sm-1">{pigcms{:L_("配送方式")}</label>
                                    <if condition="$now_goods['is_pick_in_store'] eq 0">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="0" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("到店自提")}
                                        </label>
                                    </if>
                                    <if condition="$now_goods['is_pick_in_store'] eq 1">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="1" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                        </label>
                                    </if>
                                    <if condition="$now_goods['is_pick_in_store'] eq 2">
                                        <label>
                                            <input type="radio" name='is_pick_in_store' value="2" checked='checked'/>&nbsp;&nbsp;{pigcms{:L_("物流配送")}
                                        </label>
                                    </if>
                                </div>

                                <if condition="$now_goods['is_pick_in_store'] eq 0">
                                    <div class="form-group pick_info" style="font-weight: bolder;">
                                        <label class="col-sm-1">{pigcms{:L_("自提点信息")}</label>
                                        <label class="flex" style="float: left;">
                                            <div style="padding: 5px;">
                                                <p>地址： {pigcms{$now_goods['address']}</p>
                                                <p>电话： {pigcms{$now_goods['userphone']}</p>
                                                <p>省市区： {pigcms{$now_goods.province}&nbsp;{pigcms{$now_goods.city}&nbsp;{pigcms{$now_goods.area}</p>
                                            </div>
                                        </label>
                                    </div>
                                    <div style="clear: both;"></div>
                                </if>

								<div class="topic_box">
									<table class="table table-striped table-bordered table-hover" id="table_list">
									<if condition="$now_goods['spec_list']">
									<tbody>
										<tr>
											<th>{pigcms{:L_("商品条形码")}</th>
											<volist name="now_goods['spec_list']" id="gs">
											<th>{pigcms{$gs['name']}</th>
											</volist>
											<th>{pigcms{:L_("批发价")}</th>
											<th>{pigcms{:L_("本次批发数")}</th>
											<th>{pigcms{:L_("总价")}</th>
										</tr>
										
										<volist name="now_goods['list']" id="gl" key="id_index" >
											<tr id="{pigcms{$gl['index']}">
												<td>{pigcms{$gl['number']}</td>
												<volist name="gl['spec']" id="g">
												<td>{pigcms{$g['spec_val_name']}</td>
												</volist>
												<td>{pigcms{$gl['price']}</td>
												<td>{pigcms{$gl['stock_num']}</td>
												<td>{pigcms{$gl['stock_num'] * $gl['price']}</td>
											</tr>
										</volist>
									</tbody>
									</if>
									</table>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("批发总数")}</label>
									<label class="col-sm-1">{pigcms{$now_goods.num}({pigcms{$now_goods.unit})</label>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("配送费")}</label>
                                    <label class="col-sm-1 red">{pigcms{$now_goods['total_express_fee']|floatval} ({pigcms{$config['Currency_txt']})</label>
                                </div>
								<div class="form-group">
									<label class="col-sm-1">
										<gt name='now_goods.group_id' value='0'>{pigcms{:L_("拼团")}</gt>{pigcms{:L_("商品总金额")}
									</label>
									<label class="col-sm-1 red">{pigcms{$now_goods.total_price|floatval}(元)</label>
                                    <if condition="$now_goods['system_discount_info'] gt 0 && $now_goods['system_discount_info'] lt 100 && empty($now_goods['discount_info'])">
                                        <label class="col-sm-6" style="display: -webkit-box;">
                                            <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                                                {pigcms{:L_("支付时，还可享受")}<span id="discount_totalMoney">{pigcms{$now_goods['system_discount_info']/10|floatval}</span>{pigcms{:L_("折优惠，以")}<span id="wholesale_discount">{pigcms{$now_goods['money']|floatval}</span>{pigcms{$config['Currency_txt']}{pigcms{:L_("购买")}
                                            </div>
                                        </label>
                                    </if>
								</div>
								<if condition="$now_goods['discount_info']">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("优惠后的总价")}</label>
                                        <label class="col-sm-1">{pigcms{$now_goods.actual_money|floatval}({pigcms{$config['Currency_txt']})</label>
                                        <if condition="$now_goods['system_discount_info'] gt 0 && $now_goods['system_discount_info'] lt 100">
                                            <label class="col-sm-6" style="display: -webkit-box;">
                                                <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                                                    {pigcms{:L_("支付时，还可享受")}<span id="discount_totalMoney">{pigcms{$now_goods['system_discount_info']/10|floatval}</span>{pigcms{:L_("折优惠，以")}<span id="wholesale_discount">{pigcms{$now_goods['money']|floatval}</span>{pigcms{$config['Currency_txt']}{pigcms{:L_("购买")}
                                                </div>
                                            </label>
                                        </if>
                                    </div>
								</if>
								<if condition="!$config['nmgzhcs_appid']">
									<div class="form-group">
										<label class="col-sm-1">{pigcms{:L_("您当前的余额")}</label>
										<label class="col-sm-1">{pigcms{$merchant.money|floatval}({pigcms{$config['Currency_txt']})</label>
									</div>
								</if>
                                <if condition="$now_goods['is_pick_in_store'] neq 0">
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("收货人")}</label>
                                        <label class="col-sm-1">{pigcms{$now_goods.username}</label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("联系电话")}</label>
                                        <label class="col-sm-1">{pigcms{$now_goods.userphone}</label>
                                    </div>
                                    <if condition="$now_goods['province'] neq '' OR $now_goods.city neq '' OR $now_goods.area neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label>{pigcms{:L_("收货所在地")}</label></label>
                                            <label class="col-sm-9">
                                                {pigcms{$now_goods.province}&nbsp;{pigcms{$now_goods.city}&nbsp;{pigcms{$now_goods.area}
                                            </label>
                                        </div>
                                    </if>
                                    <div class="form-group">
                                        <label class="col-sm-1">{pigcms{:L_("收货详细地址")}</label>
                                        <label class="col-sm-3">{pigcms{$now_goods.address}</label>
                                    </div>
                                </if>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("备注说明")}</label>
									<label class="col-sm-3">{pigcms{$now_goods.desc}</label>
								</div>
								
							</div>

                            <div class="tab-content">
                                <div id="pay_bank_list">
                                    <div class="payment-bank">
                                        <div class="payment-banktit">
                                            <b class="open">{pigcms{:L_("选择支付方式")}</b>
                                        </div>
                                        <div class="payment-bankcen flex">
                                            <div class="bank morebank" style="width:auto">
                                                <ul class="imgradio " style="list-style:none;margin-top: 15px;">
                                                    <li style="float: left;margin-right: 10px;">
                                                        <label>
                                                            <input type="radio" name="pay_type" value="" checked="checked">
                                                            <span style="color: #159b76;"><span style="color: #428bca;font-size: 18px;">商家余额</span>({pigcms{$merchant['money']} {pigcms{$config['Currency_txt']})</span>
                                                        </label>
                                                    </li>
                                                    <volist name="pay_method" id="vo">
                                                        <li style="float: left;margin-right: 10px;">
                                                            <label>
                                                                <input type="radio" name="pay_type" value="{pigcms{$key}">
                                                                <img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vo.name}" title="{pigcms{$vo.name}"/>
                                                            </label>
                                                        </li>
                                                    </volist>
                                                </ul>
                                                <div class="clr" style="clear: both;"></div>
                                            </div>
                                        </div>
                                        <div class="clr"></div>
                                    </div>
                                </div>

                            </div>


							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("支付")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
    .flex {
        display: flex !important;
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        align-items: center;
    }
input.ke-input-text {
	background-color: #FFFFFF;
	background-color: #FFFFFF!important;
	font-family: "sans serif",tahoma,verdana,helvetica;
	font-size: 12px;
	line-height: 24px;
	height: 24px;
	padding: 2px 4px;
	border-color: #848484 #E0E0E0 #E0E0E0 #848484;
	border-style: solid;
	border-width: 1px;
	display: -moz-inline-stack;
	display: inline-block;
	vertical-align: middle;
	zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.webuploader-element-invisible {
    position: absolute !important;
    clip: rect(1px 1px 1px 1px);
    clip: rect(1px,1px,1px,1px);
}
.webuploader-pick-hover .btn{
	background-color: #629b58!important;
    border-color: #87b87f;
}
</style>
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/activity.css">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    $(function(){
        var orderid = '';
        $('#pay-buy-form').submit(function(){
            if($('input[name="pay_type"]:checked').val() == 'weixin'){
                art.dialog({
                    title: '{pigcms{:L_("提示信息")}',
                    id: 'weixin_pay_tip',
                    opacity:'0.4',
                    lock:true,
                    fixed: true,
                    resize: false,
                    content: '{pigcms{:L_("正在获取微信支付相关信息，请稍等")}...'
                });
                $.post($('#pay-buy-form').attr('action'),$('#pay-buy-form').serialize(),function(result){
                    art.dialog.list['weixin_pay_tip'].close();
                    if(result.status == 1){
                        orderid = result.orderid;
                        art.dialog({
                            title: '{pigcms{:L_("请使用微信扫码支付")}',
                            id: 'weixin_pay_qrcode',
                            width:'350px',
                            opacity:'0.4',
                            lock:true,
                            fixed: true,
                            resize: false,
                            content: '<p style="margin-top:20px;margin-bottom:20px;text-align:center;font-size:16px;color:black;">{pigcms{:L_("请使用微信扫描二维码进行支付")}</p><p style="text-align:center;"><img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon='+result.info+'" style="width:240px;height:240px;"></p><p style="text-align:center;margin-top:20px;margin-bottom:20px;"><input id="J-order-weixin-button" type="button" class="btn_btn btn-large btn-pay" value="{pigcms{:L_("已支付完成")}"/></p>',
                            cancel: function(){
                                $("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
                                $("#J-order-pay-button").removeAttr("disabled");
                            }
                        });
                    }else{
                        $("#J-order-pay-button").val("{pigcms{:L_("去付款")}");
                        $("#J-order-pay-button").removeAttr("disabled");
                        art.dialog({
                            title: '{pigcms{:L_("错误提示：")}',
                            id: 'weixin_pay_error',
                            opacity:'0.4',
                            lock:true,
                            fixed: true,
                            resize: false,
                            content: result.info
                        });

                    }
                });
                return false;
            }
        });

        $('#J-order-weixin-button').live('click',function(){
            if (orderid) {
                window.location.href="{pigcms{:U('Pay/weixin_back',array('order_type'=>'market'))}&order_id="+orderid+"&pay_type="+$('input[name="pay_type"]:checked').val();
            } else {
                window.location.href= "{pigcms{:U('Market/buy_order')}";
            }
        });
    });
</script>



<include file="Public:footer"/>
