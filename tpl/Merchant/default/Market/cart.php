<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/market')}">{pigcms{:L_("批发")}-{pigcms{:L_("供货")}</a>
			</li>
			<li class="active">{pigcms{:L_("购物车")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("商品详细内容")} -->
    <form action="{pigcms{:U('Market/goPay')}" method="post" id="pay-buy-form">
    <input type="hidden" name="fid" value="{pigcms{$fid}">
	<div class="shopContent">
		<div class="shopTop row" style="width: 100%;">
			<div class="col-xs-12" style="color:#666">
				<div class="col-xs-1"><label style="margin-left: -1px"><if condition="empty($fid)"><input type="checkbox" id="allCheckbox" style='float:left'></if>{pigcms{:L_("操作")}</label></div>
				<div class="col-xs-5">{pigcms{:L_("商品信息")}</div>
				<div class="col-xs-1">{pigcms{:L_("批发单价")}({pigcms{$config['Currency_txt']})</div>
				<div class="col-xs-1">{pigcms{:L_("购买总量")}</div>
                <div class="col-xs-1">{pigcms{:L_("运费")}</div>
                <div class="col-xs-2">{pigcms{:L_("总价")}</div>
				<div class="col-xs-1">{pigcms{:L_("操作")}</div>
			</div>
		</div>
        <php> foreach ($cartList as $rowset) { </php>
        <div class="merchant">
        <h4> {pigcms{:L_("商家")}: <span>{pigcms{$rowset['name']}</span></h4>
        <div class="foodCont row" style="width: 100%;">
            <volist name="rowset['cartlist']" id="row">
			<div class="col-xs-12">
				<div class="col-xs-1">
					<if condition="empty($fid)"><input type="checkbox" class="checkbox checkbox_{pigcms{$row['cartid']}" name="cartids[]" value="{pigcms{$row['cartid']}" data-money="{pigcms{$row['money']|floatval}" data-discount_money="{pigcms{$row['discount_totalMoney']|floatval}"></if>
				</div>
                <div class="col-xs-5 mesage">
                    <div class="clearfix flex">
                        <img src="{pigcms{$row['image']}" class="pull-left">
                        <span class="pull-left">{pigcms{$row['name']}</span>
                        <if condition="$row['pick_address']">
                            <div style="padding: 0 10px;">
                                <div  class="flex" style="font-weight: bolder;">自提点： </div>
                                <volist name="row['pick_address']" id="item">
                                    <div  class="flex">
                                        <input class="pull-left" type="radio" name='pick_id_{pigcms{$row['cartid']}' value="{pigcms{$item['pick_addr_id']}" <php>if ($row['pick_id'] == $item['pick_addr_id']) {echo "checked='checked'";}</php>/>&nbsp;&nbsp;
                                        <div class="pick_name" data-name="{pigcms{$item['name']}" data-phone="{pigcms{$item['phone']}" data-address="{pigcms{$item['area_info']['province']} {pigcms{$item['area_info']['city']} {pigcms{$item['area_info']['area']}">{pigcms{$item['name']}</div>
                                    </div>
                                </volist>
                            </div>
                        </if>
                    </div>
                    <if condition="$row['discount_info']">
                        <div class="question_box properties">
                            <p><i class=""></i> {pigcms{:L_("批发满")}{pigcms{$row['discount_info']['num']}{pigcms{:L_("件,享")}{pigcms{$row['discount_info']['discount']}{pigcms{:L_("折优惠")}</p>
                        </div>
                    </if>
                    <if condition="$row['spec_list']">
                        <h5>{pigcms{:L_("商品规格详情")}</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{pigcms{:L_("商品条形码")}</th>
                                    <volist name="row['spec_list']" id="gs">
                                    <th>{pigcms{$gs['name']}</th>
                                    </volist>
                                    <if condition="$config['merchant_type_switch'] eq 1">
                                        <th width="100" class="button-column">{pigcms{:L_("普通批发单价")}（{pigcms{$config['Currency_txt']}）</th>
                                        <th width="100" class="button-column" <php>if (1==$merchant_type['is_vip']) { echo "style='color:#FE5F7F;'"; }</php>>{pigcms{:L_("VIP批发单价")}（{pigcms{$config['Currency_txt']}）</th>
                                        <else/>
                                        <th width="100" class="button-column">{pigcms{:L_("批发单价")}（{pigcms{$config['Currency_txt']}）</th>
                                    </if>
                                    <th>{pigcms{:L_("最低批发数")}</th>
                                    <th>{pigcms{:L_("本次批发数（正整数）")}</th>
                                    <th>{pigcms{:L_("总价")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <volist name="row['list']" id="gl" key="id_index" >
                                    <tr id="{pigcms{$gl['index']}">
                                        <td>{pigcms{$gl['number']}</td>
                                        <volist name="gl['spec']" id="g">
                                        <td>{pigcms{$g['spec_val_name']}</td>
                                        </volist>
                                        <if condition="$config['merchant_type_switch'] eq 1">
                                            <td>{pigcms{$gl['price']}</td>
                                            <td <php>if ($gl['vip_wholesale_price']>0) { echo "style='color:#FE5F7F;'"; }</php>>{pigcms{$gl['vip_wholesale_price']}</td>
                                            <else/>
                                            <td>{pigcms{$gl['price']}</td>
                                        </if>
                                        <td>{pigcms{$gl['min_num']}</td>
                                        <td>{pigcms{$gl['stock_num']}</td>
                                        <if condition="$is_vip && $gl['vip_wholesale_price'] gt 0">
                                            <td style='color:#FE5F7F;'>{pigcms{$gl['stock_num'] * $gl['vip_wholesale_price']}</td>
                                            <else/>
                                            <td>{pigcms{$gl['stock_num'] * $gl['price']}</td>
                                        </if>
    <!--                                     <td>{pigcms{$gl['min_num']}</td> -->
    <!--                                     <td><input type="number" value="{pigcms{$gl['stock_num']}"></td> -->
                                        <!--td style="width: 30%;"><input type="text" class="txt" onkeyup="buy_num_keyup(this.value,this,'{pigcms{$gl.stock_num}')" name="buy_nums[{pigcms{$gl['index']}]" value="{pigcms{$gl['buy_num']}" style="width:80px;"><span class="form_tips" style="color: red; display: none; height: 20px;">{pigcms{:L_("最多不得超过")}{pigcms{$gl.stock_num}{pigcms{:L_("库存")}</span></td-->
                                    </tr>
                                </volist>
                            </tbody>
                        </table>
                    </if>
                </div>
                <if condition="!$row['spec_list']">
                    <div class="col-xs-1 red">{pigcms{$row['price']|floatval}</div>
                    <else/>
                    <div class="col-xs-1 red">&nbsp;&nbsp;&nbsp;</div>
                </if>
                <div class="col-xs-1">{pigcms{$row['num']}</div>
                <div class="col-xs-1 express_fee_{pigcms{$row['cartid']}">{pigcms{$row['total_express_fee']}</div>
                <div class="col-xs-2 red" style="display: -webkit-box;">
                    <if condition="$totalMoney gt 0">
                        {pigcms{$row['actual_money']|floatval}
                        <else/>
                        {pigcms{$row['money']|floatval}
                    </if>
                    &nbsp;&nbsp;

                    <if condition="$row['wholesale_discount'] gt 0 && $row['wholesale_discount'] lt 10">
                        <div style="background-color: #F7511D;color: white;border-radius: 10px;padding: 1px 15px;">
                            <span id="discount_totalMoney">{pigcms{$row['wholesale_discount']|floatval}</span>{pigcms{:L_("折优惠")}
                        </div>
                    </if>
                </div>
                <if condition="empty($fid)"><a href="javascript:void(0);" class="col-xs-1 delete" data-cartid="{pigcms{$row['cartid']}">{pigcms{:L_("删除")}</a></if>
            </div>
            </volist>
            </div>
        </div>
        <php> } </php>
		<div class="priceBot row" style="width: 95%;">
			<div class="col-xs-12 clearfix">
				<if condition="empty($fid)"><div class="col-xs-2 reset acc clear" >{pigcms{:L_("清空购物车")}</div></if>
				<div class="col-xs-2 fontColor acc">{pigcms{:L_("已选商品")}<span id="totalCount">{pigcms{$totalCount|intval}</span>件</div>
                <div class="col-xs-2 fontColor acc">{pigcms{:L_("运费总价")}:<span id="total_express_fee">{pigcms{$config['Currency_symbol']}{pigcms{$total_express_fee|floatval}</span></div>
                <div class="col-xs-2 fontColor acc">{pigcms{:L_("商品总价")}:<span id="totalMoney">{pigcms{$config['Currency_symbol']}{pigcms{$totalMoney|floatval}</span></div>
                <div class="col-xs-2 fontColor acc">{pigcms{:L_("已优惠")}:<span id="discountMoney">{pigcms{$config['Currency_symbol']}{pigcms{$discountMoney|floatval}</span></div>
				<div class="jie pull-right">
					<a href="javascript:void(0)" id="gopay">{pigcms{:L_("去结算")}</a>
				</div>
			</div>
		</div>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-11">
					<div class="form-group">
						<label class="col-sm-1">{pigcms{:L_("收货人")}</label>
						<input class="col-sm-1" name="username" type="text" value="{pigcms{$username}"/>
						<span class="form_tips">{pigcms{:L_("必填")}</span>
					</div>
                    <div class="form-group">
                        <label class="col-sm-1"><label>{pigcms{:L_("收货所在地")}</label></label>
                        <if condition="empty($fid)">
                            <fieldset id="choose_cityarea" province_id="{pigcms{$now_goods.province_id}" city_id="{pigcms{$now_goods.city_id}" area_id="{pigcms{$now_goods.area_id}"></fieldset>
                            <else/>

                        </if>
                    </div>
					<div class="form-group">
						<label class="col-sm-1">{pigcms{:L_("联系电话")}</label>
						<input class="col-sm-1" name="userphone" type="text" value="{pigcms{$userphone}"/>
						<span class="form_tips">{pigcms{:L_("必填")}</span>
					</div>
					<div class="form-group">
						<label class="col-sm-1">{pigcms{:L_("收货地址")}</label>
						<input class="col-sm-3" name="address" type="text" value="{pigcms{$address}"/>
						<span class="form_tips">{pigcms{:L_("必填(填写的完整地址")})</span>
					</div>
					<div class="form-group">
						<label class="col-sm-1">{pigcms{:L_("备注说明")}</label>
						<textarea class="col-sm-3" rows="5" cols="10" name="desc">{pigcms{$desc}</textarea>
					</div>
				</div>
			</div>
		</div>
	</div>


    <div class="page-content">

        <div class="page-content-area" id="pay_bank_list">
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
                                        <input type="radio" name="pay_type" value="{pigcms{$key}" <if condition="$total_money gt 0"><if condition="$i eq 1">checked="checked"</if></if>>
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

    </form>
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
.shopContent{overflow:hidden;}
.foodCont .col-xs-12{    
	padding-right: 12px;
    padding-left: 12px;
  /*  align-items: center;
   display: flex; */
}
.foodCont .col-xs-1.red{padding-left: 3px;}
.foodCont .col-xs-2.red{padding-left: 5px;}
    .shopContent .row{
    	width: 80%;
		padding: 10px;
		margin-left: 30px;
    }
	.shopTop.row{
		margin-top: 20px;
		width: 80%;
		padding: 10px;
		margin-left: 30px;
		border:1px solid #eee;
		height: 40px;
        background-color: #f5f5f5;
	}
	.col-xs-1,.col-xs-6,.col-xs-2{
		padding: 0;
	}
	.shopContent h4{
		width: 80%;
		padding: 10px;
		margin-left: 30px;
		background: #F5F5F5;
		color: #666;
		font-size: 16px;
	}
	.shopContent h4 span{color: #333;}
	.mesage span{
		margin-left: 15px;
		font-size: 16px;
		line-height: 60px;
	}
	.mesage img{
		width: 60px;
		height: 60px;
	}
	.mesage p{
		margin: 15px 0;
		font-size: 14px;
		color: #D81E06;
	}
	.mesage h5{
		color: #999;
	}

	 .foodCont div {
		padding: 0;
	}
	.priceBot{
		position: relative;
		padding:0px!important;
		background: #F7F7F7;
	}
	.acc{
		line-height: 65px;
	}
	.reset{
		color: #256DB1;
	}
	.fontColor span{
		font-size: 17px;
		font-weight:700;
		color: #06C1AF;
	}
	.jie a{
		display: inline-block;
		padding: 20px;
		text-align: center;
		background: #06C1AF;
		color: #fff;
		width: 130px;
		height: 65px;
		line-height: 2;
		position: absolute;
		right: 0;
	}
	.red{
		color: #FF0000;
	}
    .foodCont .col-xs-12{
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }
    .foodCont .col-xs-12:last-child{
        border-bottom: 0px solid #eee;
    }
</style>
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
var fid = '{pigcms{$fid}';
$(document).ready(function(){

    $('.pick_name').hover(function(){
        var top = $(this).offset().top;
        var left = $(this).offset().left+$(this).width()+10;
        $('body').append('<div id="pick_name_div" style="position:absolute;z-index:5555;background:white;top:'+top+'px;left:'+left+'px;border:1px solid #ccc;padding:10px;"><div style="margin-bottom:10px;"><b>{pigcms{:L_("自提点：")}</b>'+$(this).data('name')+'</div><div><b>{pigcms{:L_("电话：")}</b>'+$(this).data('phone')+'</div><div><b>{pigcms{:L_("所在城市：")}</b>'+$(this).data('address')+'</div></div>');
    },function(){
        $('#pick_name_div').remove();
    });


    $('#allCheckbox').click(function(){
        if ($(this).is(':checked')) {
            $('.checkbox').prop('checked', true);
        } else {
            $('.checkbox').prop('checked', false);
        }
        cntFun();
    });
    $('.checkbox').click(function(){cntFun()});
    $('.delete').click(function(){
        var cartid = $(this).data('cartid'), obj = $(this);
        $.post('{pigcms{:U("Market/delCart")}', {'cartid':cartid}, function(res){
            if (res.errCode) {
                layer.msg(res.msg);
            } else {
                if (obj.parents('.merchant').find('.col-xs-12').size() < 2) {
                    obj.parents('.merchant').remove();
                } else {
                    obj.parents('.col-xs-12').remove();
                }
                cntFun();
            }
        }, 'json');
    });
    
    $('.clear').click(function(){
        $.post('{pigcms{:U("Market/delCart")}', {'cartid':-1}, function(res){
            if (res.errCode) {
                layer.msg(res.msg);
            } else {
                $('.foodCont').html('');
                $('.merchant').remove();
                cntFun();
            }
        }, 'json');
    });
    
    $('#gopay').click(function(){
        if (fid < 1) {
            var totalCount = 0;
            $('.checkbox').each(function(){
                if ($(this).is(':checked')) {
                    totalCount ++;
                }
            });
            if (totalCount < 1) {
                layer.msg('{pigcms{:L_("请选择商品")}');
                return false;
            }
        }
        if ($('input[name=username]').val().length < 1) {
            layer.msg('{pigcms{:L_("收货人姓名必填")}');
            return false;
        }
        if ($('input[name=userphone]').val().length < 1) {
            layer.msg('{pigcms{:L_("收货人电话必填")}');
            return false;
        }
        if ($('input[name=address]').val().length < 1) {
            layer.msg('{pigcms{:L_("收货地址必填")}');
            return false;
        }
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
                        },
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
        } else {
            $('form').submit();
        }
        return false;
    });


    setTimeout(function () {
        $('#choose_province').change(function () {
            count_money();
        });
    },500)
    $('#J-order-weixin-button').live('click',function(){
        window.location.href= "{pigcms{:U('Market/buy_order')}";
        // window.location.href="{pigcms{:U('Pay/weixin_back',array('order_type'=>'market'))}&order_id={pigcms{$_GET['order_id']}&pay_type="+$('input[name="pay_type"]:checked').val();
    });
});


function count_money() {
    console.log('count_money');
    $.post('{pigcms{:U("Market/getCartPrice")}', $("form").serialize(), function(response){
        console.log('response', response);
        $.each(response, function(index, val) {
            var total_express_fee = val['total_express_fee'];
            total_express_fee = parseFloat(total_express_fee).toFixed(2);
            $('.express_fee_'+val['cartid']).html(total_express_fee);
        });
        setTimeout(function () {
            cntFun();
        },500)
    }, 'json');
}

function cntFun()
{
    var totalCount = 0, totalMoney = 0, total_express_fee = 0;
    $('.checkbox').each(function(){
        if ($(this).is(':checked')) {
            totalCount ++;
            totalMoney += parseFloat($(this).data('money'));
            var cartid = $(this).val();
            total_express_fee += parseFloat($('.express_fee_'+cartid).html());
        }
    });
    console.log('totalMoney', totalMoney);
    var wholesale_discount = "{pigcms{$row['wholesale_discount']|floatval}";
    var discountMoney = 0;
    if (parseFloat(wholesale_discount) > 0 && parseFloat(wholesale_discount) < 10) {
        discount_totalMoney = totalMoney * parseFloat(wholesale_discount) * 0.1;
        discountMoney = parseFloat(totalMoney).toFixed(2) - parseFloat(discount_totalMoney).toFixed(2)
    } else {
        discount_totalMoney = totalMoney;
        discountMoney = 0;
    }

    $('#totalCount').html(totalCount);
    $('#total_express_fee').html('{pigcms{$config['Currency_symbol']}' + parseFloat(parseFloat(total_express_fee).toFixed(2)));
    $('#totalMoney').html('{pigcms{$config['Currency_symbol']}' + parseFloat(parseFloat(discount_totalMoney).toFixed(2)));
    $('#discountMoney').html('{pigcms{$config['Currency_symbol']}' + parseFloat(parseFloat(discountMoney).toFixed(2)));
}
</script>
<include file="Public:footer"/>