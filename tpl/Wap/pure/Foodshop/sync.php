<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta http-equiv="Expires" content="-1">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Pragma" content="no-cache">
<meta charset="utf-8">
<title>{pigcms{$store['name']}</title>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/css_whir.css"/>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>

<script type="text/javascript">var STATIC_PATH = '{pigcms{$static_path}', open_extra_price = '{pigcms{$config.open_extra_price}', extra_price_alias_name = '{pigcms{$config.extra_price_alias_name}';</script>
<script type="text/javascript">
var real_orderid = '{pigcms{$order["real_orderid"]}', status = '{pigcms{{pigcms{$order["status"]}}', order_id = '{pigcms{{pigcms{$order["order_id"]}}';
var call_store_url = "{pigcms{:U('Foodshop/call_store', array('order_id' => $order['real_orderid']))}";
var call_server_url = "{pigcms{:U('Foodshop/callServer', array('order_id' => $order['real_orderid']))}";
var check_status_url = "{pigcms{:U('Foodshop/check_status', array('order_id' => $order['order_id']))}";
var get_data_info_url = "{pigcms{:U('Foodshop/getDataInfo', array('real_orderid' => $order['real_orderid']))}";
var use_card_or_table = "{pigcms{:U('Foodshop/useCardOrTable', array('real_orderid' => $order['real_orderid']))}";
var go_pay_url = "{pigcms{:U('Foodshop/pay', array('order_id' => $order['real_orderid']))}";
var unlock_url = "{pigcms{:U('Foodshop/unlock', array('order_id' => $order['order_id']))}";
var can_lock = '{pigcms{$can_lock}';
</script>
<script charset="utf-8">var currency_symbol = "{pigcms{$config.Currency_symbol}";</script>
<script type="text/javascript" src="{pigcms{$static_path}js/foodshop_sync.js?t=1" charset="utf-8"></script> 
<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script> 
<!--[if lte IE 9]>
<script src="scripts/html5shiv.min.js"></script>
<![endif]-->
    <style>
        input.mt[type=checkbox]:checked {
            background-color: #2bb2a3;
            border: 0;
            color: #fff;
        }
        input.mt[type=checkbox] {
            -webkit-appearance: none;
            width: .45rem;
            height: .45rem;
            margin: -.07rem 0;
            border-radius: 50%;
            border: .02rem solid #ddd8ce;
            text-align: center;
            vertical-align: middle;
            line-height: 1.45rem;
            outline: 0;
            width:20px;
            height:20px;
        }
        input.mt[type=radio]:checked:after, input.mt[type=checkbox]:checked:after {
            content: "✓";
            font-size: 1.0rem;
        }
        .change_table_html ,.edit_note_content{
            display: none;
            width: 80%;
            background-repeat: repeat-x;
            background-size: 9px 44px;
            background-color: #fff;
            z-index: 999;
            position: fixed;
            top: 0px;
            left: 0px;
            height: 240px;
            margin: 10%;
            border-radius: 5px;
            top: 50%;
            margin-top: -175px;
        }
        .change_table_html .Sufrom_c .do_change,.edit_note_content .Sufrom_c .do_change {
            background: rgb(240, 60, 60);
            border: #c8c8c8 1px solid;
            color: #fff;
            transition: 1s all;
        }
        .change_table_html .Sufrom_c .cancel ,.edit_note_content .Sufrom_c .cancel{
            background: #c8c8c8;
            border: #c8c8c8 1px solid;
            color: #fff;
            transition: 1s all;
            margin-right: 2%;
        }
        .change_table_html .Sufrom_c a ,.edit_note_content .Sufrom_c a{
            display: inline-block;
            line-height: 32px;
            text-align: center;
            border: #000 1px solid;
            border-radius: 5px;
            width: 48%;
            font-size: 16px;
            float: left;
        }
        .change_table_html .Sufrom_c ,.edit_note_content .Sufrom_c {
            position: absolute;
            bottom: 0px;
            left: 0px;
            width: 80%;
            padding: 12px 10%;
            border-top: #eeeeee 1px solid;
        }
        .change_table_html .table_list ,.edit_note_content .table_list {
          
            padding: 12px 10%;
        }
        .change_table_html .table_title ,.edit_note_content .table_title {
            top: 0px;   
            border-bottom: #eeeeee 1px solid;
            height: 40px;
            font-size: 16px;
            line-height: 40px;
            color: #fff;
            background: rgb(240, 60, 60);
        }

        .change_table{
            margin-right: 3%;
            background: #fff;
            border: #e4e4e4 1px solid;
        }
        .change_table_html select{
            margin-right: 10px;
            border: solid 1px #ddd;
            font-size: 15px;
            padding: 5px 40px;
            margin-top: 30px;
        }
        .Total a.cancel{
            background: #c8c8c8;
            color: #fff;
            width: 130px;
            display: block;
            text-align: center;
            float: right;
        }
        .vegetables a{
            margin-bottom: 10px;
        }
        .flex{display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box;display:flex;}
        .flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;box-pack: center;justify-content: space-between;}
        .flex-justify-start{-webkit-box-pack: start;-webkit-justify-content: flex-start;-ms-flex-pack: start;box-pack: start;justify-content: flex-start;}
        .flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; box-align: center;align-items: center;}
        .flex_one{-webkit-box-flex: 1;-webkit-flex: 1;flex: 1;}
        .flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}
        .flex-grow{-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex-grow: 1;-ms-flex: 1;flex-grow: 1;}
        .Total a.cancel{
            border: 1px solid #c8c8c8;
        }
    </style>
</head>
<body>

<section class="Cart CartReserve">
    <if condition="($old_goods_list OR $package_list OR $must_list)">
    <div class="CartReserve_top"><span>已预订菜品</span></div>
    <php> if ($order['status'] < 3) { </php>
    <div style='text-align:center;color:#fff;line-height:35px;height:35px; margin: 8px 0px;' id="share">
        <span style='  padding: 7px 14px;background: #2BC7A2;'>邀请好友一起点餐</span>
    </div>
    <php>} </php>
    <if condition="$old_goods_list OR $package_list OR $must_list">
    <div class="Cart_list">
        <ul id="data">
        <volist name="old_goods_list" id="rowset">
            <php>if (isset($userList[$key])) { </php> 
            <li class="clr" style='paddong:0px'>
                <div class="Clist_left">
                    <img src="<php>if (empty($userList[$key]['avatar'])) {echo $static_path . 'images/user_header.png';} else { echo $userList[$key]['avatar'];}</php>" style='border-radius: 50%; width: 30px;  height: 30px;'>
                </div>
                <div style='padding-left: 10px;padding-top: 5px;  float: left;'>
                    <span style='font-weight:bold;color:#000000'>{pigcms{$userList[$key]['nickname']}</span>
                    <php> if ($key == $order['uid']) { </php>
                    <span style="padding: 5px 10px;background: #bb0b0b;margin-left: 10px;color: #fff;">下单人</span>
                    <php> } </php>
                </div>
                <!--div class="Clist_right" style='color: #d0d0d0; margin-top: -14px; border: 1px solid #bbb; padding: 1px 10px;  box-shadow: 0px 1px 1px #bbbbbb;'>
                        <span>删除ta</span>
                </div-->
            </li>
            <php>}</php>
            <volist name="rowset['goodsList']" id="goods">
            <li class="clr packages">
                <div class="Clist_left">
                    <h2>{pigcms{$goods['name']}</h2>
                    <if condition="$goods['spec']">
                    <span>({pigcms{$goods['spec']})</span>
                    </if>
                </div>
                <php>if ($goods['isNew'] == 1) { </php>
                <div class="phpo">
                    <img src="{pigcms{$static_path}images/dengdai.png">
                </div>
                <php>}</php>
                <div class="Clist_right">
                    <div class="MenuPrice">
                        <i>{pigcms{$config['Currency_symbol']}</i>{pigcms{$goods['price']|floatval}<if condition="$config.open_extra_price AND $goods.extra_price gt 0">+{pigcms{$goods['extra_price']|floatval}{pigcms{$config.extra_price_alias_name}</if>
                    </div>
                    <div class="Addsub">
                        <span>{pigcms{$goods['num']|floatval}{pigcms{$goods['unit']}</span>
                        <!--a href="javascript:void(0)" class="jian"></a-->
                        <!--a href="javascript:void(0)" class="jia"></a-->
                    </div>
                </div>
            </li>
            </volist>
            </volist>
            <volist name="package_list" key="uid" id="plist">
            <php>if (isset($userList[$key])) { </php> 
            <li class="clr" style='paddong:0px'>
                <div class="Clist_left">
                    <img src="<php>if (empty($userList[$key]['avatar'])) {echo $static_path . 'images/user_header.png';} else { echo $userList[$key]['avatar'];}</php>" style='border-radius: 50%; width: 30px;  height: 30px;'>
                </div>
                <div style='padding-left: 10px;padding-top: 5px;  float: left;'>
                    <span style='font-weight:bold;color:#000000'>{pigcms{$userList[$key]['nickname']}</span>
                </div>
                <!--div class="Clist_right" style='color: #d0d0d0; margin-top: -14px; border: 1px solid #bbb; padding: 1px 10px;  box-shadow: 0px 1px 1px #bbbbbb;'>
                        <span>删除ta</span>
                </div-->
            </li>
            <php>}</php>
            <php> foreach($plist['goodsList'] as $packList) {</php>
            <li class="clr on">
                <div class="clr Package">
                    <div class="Clist_left">
                        <h2 style="color: #29c7a2;">{pigcms{$packList['name']}</h2>
                    </div>
                    <if condition="isset($packList['isNew']) AND $packList['isNew'] eq 1">
                    <div class="phpo">
                        <img src="{pigcms{$static_path}images/dengdai.png">
                    </div>
                    </if>
                    <div class="Clist_right">
                        <div class="MenuPrice">
                            <i>{pigcms{$config['Currency_symbol']}</i>{pigcms{$packList['price']|floatval}    
                        </div>
                        <div class="Addsub">
                            <span>{pigcms{$packList['num']|floatval}份</span>
                        </div>
                    </div>
                </div>
                <div class="Package_end">
                    <dl>
                        <php> foreach ($packList['list'] as $row) {</php>
                        <dd>
                            {pigcms{$row['name']} <if condition="$row['spec']"><i>({pigcms{$row['spec']})</i></if>
                            <div class="fr"><span class="on">{pigcms{$config['Currency_symbol']}{pigcms{$row['price']|floatval}</span> <span>{pigcms{$row['num']|floatval}/{pigcms{$row['unit']}</span></div> 
                        </dd>
                        <php>}</php>
                    </dl>
                    <a  class="more"></a>
                </div>
                <div class="bottom"></div>
            </li>
            <php>}</php>
            </volist>
            <volist name="must_list" id="must">
            <li class="clr">
                <div class="Clist_left">
                    <h2>{pigcms{$must['name']} <i>必点</i></h2>
                </div>
                <div class="Clist_right">
                    <div class="MenuPrice">
                        <i>{pigcms{$config['Currency_symbol']}</i>{pigcms{$must['price']|floatval}    
                    </div>
                    <div class="Addsub">
                        <span>{pigcms{$must['num']|floatval}{pigcms{$must['unit']}</span>
                    </div>
                </div>
            </li>
            </volist>
            <!--  新增html -->
        </ul>
    </div>
    </if>

    </if>
    <if condition="$order['status'] lt 3">
        <div class="Reservation">
            <div class="textarea" style="margin-bottom: 8px;padding: 20px 10px;margin-top: 0px;">
               
                <div class="edit_note flex flex-align">
                    <div class="flex-grow" style="font-size: 16px;">订单备注</div>
                        <div style="padding: 0 15px;">
                            <if condition="$order['note']">
                                {pigcms{$order['note']}
                            <else/>
                                请填写备注
                            </if>
                        </div>
                    <div>
                    <img src="{pigcms{$static_path}/images/jiantou.png" style="width: 10px;margin-left: 5px;float: right;">
               
                    </div>
                </div>
            </div>
        </div>
    <else/> 
        <if condition="$order['note']">
            <div class="Reservation">
                <div class="textarea" style="margin-bottom: 8px;padding: 20px 10px;margin-top: 0px;">
                    <div class="flex flex-align">
                        <div class="flex-grow" style="font-size: 16px;">订单备注</div>
                            <div style="padding: 0 15px;">
                                {pigcms{$order['note']}
                            </div>
                        <div>
                    </div>
                </div>
            </div>
        </if>
    </if>
    <if condition="$order['status'] neq 5">
        <div class="Serving">
            <dl>
                <!-- <dt>总计：{pigcms{$price|floatval}{pigcms{$config['Currency_txt']}</dt> -->
                <dd class="vegetables clr">
                    <a href="{pigcms{:U('Foodshop/menu', array('order_id' => $order['real_orderid'], 'store_id' => $order['store_id']))}" class="add" <if condition="$is_add_menu eq 0">style="display:none"</if>>加菜</a>
                    <a href="javascript:void(0)" class="notice" <if condition="$is_call_store eq 0">style="display:none"</if>>通知上菜</a>
                    <a href="javascript:void(0)" class="call" <if condition="$order['status'] gt 2">style="display:none"</if>>呼叫服务</a>
                    <!--  预定状态可以修改桌台 -->
                    <if condition="$order['status'] eq 1 AND $store['is_choice_table']"> 
                    <a href="javascript:void(0)" class="change_table">更换桌台号</a>
                    </if>
                </dd>
            </dl>
        </div>
    </if>
</section>

<if condition="$order['status'] neq 5">
    <if condition="$order['book_time']">
    <if condition="empty($old_goods_list) AND empty($goods_list)">
    <section class="Success"><span>座位详情！</span></section>
    </if>
    <section class="Sudetails">
        <ul>
            <li class="Su_zh">
                <dl>
                    <dd>{pigcms{$order['book_time_show']}</dd>
                    <dd>{pigcms{$order['book_num']}人 | {pigcms{$order['table_type_name']}  <span class="Su_sit"><if condition="$order['status'] eq 0"><b style="color:red">未付</b><else/>已付</if>定金:{pigcms{$config['Currency_symbol']}{pigcms{$order['book_price']|floatval}</span></dd>
                    <dd>{pigcms{$order['name']} <if condition="$order['sex'] eq 1">先生<else />女士</if> {pigcms{$order['phone']}</dd>
                </dl>
            </li>
        </ul>
    </section>
    </if>
</if>
<!--  新增html --> 
<section class="cartetails">
    <div class="cartetails_list">
        <h1>订单详情</h1>
        <ul>
            <if condition="$order['status'] lt 3 AND $order['status'] gt 1">
                <if condition="$tablesDiscount">
                    <li class="clr">
                        <i>桌次折扣</i>
                        <span class="fr"><span style="color: red">{pigcms{$tablesDiscount.mer_discount|floatval}折</span> (部分商品不参加折扣) <input  class="mt" type="checkbox" id="check_table" <?php if($order['table_or_vipcard_discount'] < 1){ ?> checked <?php } ?> ></span>
                    </li>
                </if>
                <if condition="$card_discount">
                    <li class="clr">
                        <i>会员卡折扣</i>
                        <span class="fr"><span style="color: red">{pigcms{$card_discount}折</span> <input type="checkbox" class="mt" id="check_card" <?php if($order['table_or_vipcard_discount'] == 1){ ?> checked <?php } ?> ></span>
                    </li>
                </if>
            </if>
			<if condition="$coupon_discount.discount_value gt 0">
				<li class="clr">
					<i>平台折扣券</i>
					<span class="fr"><span style="color: red">{pigcms{$coupon_discount.discount_value|floatval}折</span> (部分商品不参加折扣)</span>
				</li>
            </if>
            <if condition="$plat_discount lt 10">
            <li class="clr">
                <i>商家折扣</i>
                <span class="fr"><span style="color: red">{pigcms{$plat_discount}折</span> (部分商品不参加折扣)</span>
            </li>
            </if>

            <if condition="$order['fetch_number']">
            <li class="clr">
                <i>取单号</i>
                <span class="fr"><span>{pigcms{$order['fetch_number']}</span></span>
            </li>
            </if>
            <li class="clr">
                <i>订单编号</i>
                <span class="fr">{pigcms{$order['real_orderid']}</span>
            </li>
            <li class="clr">
                <i>餐台类型</i>
                <span class="fr">{pigcms{$order['table_type_name']}</span>
            </li>
            <li class="clr">
                <i>餐台名称</i>
                <span class="fr">{pigcms{$order['table_name']}</span>
            </li>
			<li class="clr">
                <i>{pigcms{$store['table_service_fee_alias']}</i>
                <span class="fr">{pigcms{$order['table_service_payment']}元</span>
            </li>
            <if condition="$order['mer_table_discount'] AND $config['is_open_merchant_foodshop_discount']">
            <li class="clr">
                <i>桌台折扣</i>
                <span class="fr">{pigcms{$order['mer_table_discount']/10}折</span>
            </li>
            </if>
            <li class="clr">
                <i>下单时间</i>
                <span class="fr">{pigcms{$order['create_time']|date='Y-m-d H:i', ###}</span>
            </li>
            <if condition="$order['book_pay_time']">
            <li class="clr">
                <i>预订支付时间</i>
                <span class="fr">{pigcms{$order['book_pay_time']|date='Y-m-d H:i', ###}</span>
            </li>
            </if>
            <if condition="$order['book_pay_type']">
            <li class="clr">
                <i>预订支付方式</i>
                <span class="fr">{pigcms{$order['book_pay_type']}</span>
            </li>
            </if>
            <if condition="$order['pay_time']">
            <li class="clr">
                <i>买单时间</i>
                <span class="fr">{pigcms{$order['pay_time']|date='Y-m-d H:i', ###}</span>
            </li>
            </if>
            <if condition="$order['pay_type']">
            <li class="clr">
                <i>买单支付方式</i>
                <span class="fr">{pigcms{$order['pay_type']}</span>
            </li>
            </if>
            <if condition="$order['status'] lt 3">
            <!-- <li class="clr">
                <i>订单备注</i>
                <span class="fr edit_note">{pigcms{:L_('点击添加订单备注')}<img src="{pigcms{$static_path}/images/jiantou.png" style="width: 10px;margin-left: 5px;"></span>
            </li> -->
            </if>
        </ul>
    </div>
</section>
<div style="padding-bottom: 65px;"></div>
 <!--  新增html -->
<div class="Total clr pay" <if condition="$is_pay eq 0"> style="display:none" </if>>
    <div class="Total_left">总计<span>{pigcms{$config['Currency_symbol']}<i id="total_price">{pigcms{$price|floatval}<i><if condition="$config.open_extra_price AND $extra_price GT 0">+{pigcms{$extra_price}{pigcms{$config.extra_price_alias_name}</if></i></span></div>
    <if condition="!$config['foodshop_cash_only_clerk']">
    <a class="Check go_pay">去买单</a>
    </if>
</div>

<!--  预定状态可以取消订单 -->
<if condition="$order['status'] eq 1"> 
<div class="Total clr " >
    <a class="cancel">取消订单</a>
</div> 
</if>

<div class="Total clr comment" <if condition="$order['status'] eq 3 AND $order['uid'] eq $userid"> <else />style="display:none" </if>>
    <div class="Total_left">已买单</div>
    <a href="{pigcms{:U('My/foodshop_feedback', array('order_id' => $order['order_id']))}" class="Check">去评价</a>
</div>
<div class="Total clr" <if condition="$order['status'] eq 5"> <else />style="display:none" </if>>
<div class="Total_left">已取消</div>
</div>

<div id="cover"></div>
<div id="guide"><img src="{pigcms{$static_path}images/guide1.png"></div>


<!--  桌台列表 -->
<if condition="$order['status'] eq 1 AND $store['is_choice_table']">
    <div class="change_table_html" style="text-align: center;z-index: 1000">
        <div class="table_title">更换桌台号</div>
        <div class="table_list"> 
            <select  class="" style="margin-right: 10px;" id="table_sel" >
                <volist name="table_arr" id="vo">
                    <option value="{pigcms{$vo['id']}" <if condition="$order['table_id'] eq $vo['id']">selected</if>>{pigcms{$vo['name']}</option>
                </volist>
            </select>
        </div>
        <div class="Sufrom_c">
            <a href="javascript:void(0);" class="cancel">取消</a>
            <a href="javascript:void(0);" class="do_change">确定</a>
        </div>
    </div>
</if>
<section class="Tcancel" style="z-index: 1000">
    <div class="Tcancel_top">请选择取消预订原因</div>
    <div class="Reason">
        <ul>
            <li><i></i><span>行程取消</span></li>
            <li><i></i><span>改去其他餐厅</span></li>
            <li><i></i><span>预订信息填写错误</span></li>
            <li><i></i><span>行程有变</span></li>
        </ul>
    </div>
    <div class="Sufrom_n">
        <a href="javascript:void(0);" class="cancel">放弃取消</a>
        <a href="javascript:void(0);" class="complete">确认取消</a>
    </div>
</section>

<div class="edit_note_content" style="text-align: center;z-index: 1000">
    <div class="table_title">填写备注</div>
    <div class="table_list">
        <textarea id="order_note" placeholder="请填写备注" style="width: 220px;height: 130px">{pigcms{$order.note}</textarea>
    </div>
    <div class="Sufrom_c">
        <a href="javascript:void(0);" class="cancel">取消</a>
        <a href="javascript:void(0);" class="do_change">确定</a>
    </div>
</div>
<div class="Mask"></div>

<script id="allData" type="text/html">
{{# for (var uid in d.goodsList) { }}
    {{# for (var u in d.userList) { }} 
    {{# if (uid == 'a_' + u) { }}
    <li class="clr" style='paddong:0px'>
    <div class="Clist_left">
        {{# if (d.userList[u].avatar.length>0) { }}
        <img src="{{ d.userList[u].avatar }}" style="border-radius: 50%; width: 30px;  height: 30px;">
        {{# } else { }}
        <img src="{{ STATIC_PATH }}images/user_header.png" style="border-radius: 50%; width: 30px;  height: 30px;">
        {{# } }}
    </div>
    <div style="padding-left: 10px;padding-top: 5px;  float: left;">
        <span style="font-weight:bold;color:#000000">{{ d.userList[u].nickname }}</span>
        {{# if ('a_' + d.orderUid == uid) { }}
        <span style="padding: 5px 10px;background: #bb0b0b;margin-left: 10px;color: #fff;">下单人</span>
        {{# } }}
    </div>
    </li>
    {{# } }}
    {{# } }}
    {{# for (var i in d.goodsList[uid].goodsList) { }}
    <li class="clr packages">
        <div class="Clist_left">
            <h2>{{ d.goodsList[uid].goodsList[i].name }}</h2>
            {{# if (d.goodsList[uid].goodsList[i].spec.length > 0) { }}
            <span>({{ d.goodsList[uid].goodsList[i].spec }})</span>
            {{# } }}
        </div>
        {{# if (d.goodsList[uid].goodsList[i].isNew == 1) { }}
        <div class="phpo">
            <img src="{{ STATIC_PATH }}images/dengdai.png">
        </div>
        {{# } }}
        <div class="Clist_right">
            <div class="MenuPrice">
                <i>{pigcms{$config['Currency_symbol']}</i>{{ parseFloat(d.goodsList[uid].goodsList[i].price) }} 
                {{# if (open_extra_price > 0 && d.goodsList[uid].goodsList[i].extra_price > 0) { }}
                +{{ parseFloat(d.goodsList[uid].goodsList[i].extra_price) }}{{ extra_price_alias_name }}
                {{# } }}
            </div>
            <div class="Addsub">
                <span>{{ d.goodsList[uid].goodsList[i].num }}{{ d.goodsList[uid].goodsList[i].unit }}</span>
            </div>
        </div>
    </li>
{{# } }}
{{# } }}


{{# for (var uid in d.packageList) { }}
    {{# for (var u in d.userList) { }}
    {{# if (uid == 'a_' + u) { }}
    <li class="clr" style='paddong:0px'>
    <div class="Clist_left">
        {{# if (d.userList[u].avatar.length>0) { }}
        <img src="{{ d.userList[u].avatar }}" style="border-radius: 50%; width: 30px;  height: 30px;">
        {{# } else { }}
        <img src="{{ STATIC_PATH }}images/user_header.png" style="border-radius: 50%; width: 30px;  height: 30px;">
        {{# } }}
    </div>
    <div style="padding-left: 10px;padding-top: 5px;  float: left;">
        <span style="font-weight:bold;color:#000000">{{ d.userList[u].nickname }}</span>
    </div>
    </li>
    {{# } }}
    {{# } }}
    {{# for (var i in d.packageList[uid].goodsList) { }}
    <li class="clr on">
        <div class="clr Package">
            <div class="Clist_left">
                <h2 style="color: #29c7a2;">{{ d.packageList[uid].goodsList[i].name }}</h2>
            </div>
            {{# if (d.packageList[uid].goodsList[i].isNew == 1) { }}
            <div class="phpo">
                <img src="{{ STATIC_PATH }}images/dengdai.png">
            </div>
            {{# } }}
            <div class="Clist_right">
                <div class="MenuPrice">
                    <i>{pigcms{$config['Currency_symbol']}</i>{{ parseFloat(d.packageList[uid].goodsList[i].price) }}
                </div>
                <div class="Addsub">
                    <span>{{ d.packageList[uid].goodsList[i].num }}份</span>
                </div>
            </div>
        </div>
        <div class="Package_end">
            <dl>
                {{# for (var l in d.packageList[uid].goodsList[i].list) { }}
                <dd>
                    {{ d.packageList[uid].goodsList[i].list[l].name }}
                    {{# if (d.packageList[uid].goodsList[i].list[l].spec.length > 0) { }}
                    <i>({{ d.packageList[uid].goodsList[i].list[l].spec }})</i>
                    {{# } }}
                    <div class="fr"><span class="on">{pigcms{$config['Currency_symbol']}{{ parseFloat(d.packageList[uid].goodsList[i].list[l].price) }}</span> <span>{{ d.packageList[uid].goodsList[i].list[l].num }}/{{ d.packageList[uid].goodsList[i].list[l].unit }}</span></div> 
                </dd>
                {{# } }}
            </dl>
            <a class="more"></a>
        </div>
        <div class="bottom"></div>
    </li>
{{# } }}
{{# } }}

{{# for (var m in d.mustList) { }}
<li class="clr">
    <div class="Clist_left">
        <h2>{{ d.mustList[m].name }}<i>必点</i></h2>
    </div>
    <div class="Clist_right">
        <div class="MenuPrice">
            <i>{pigcms{$config['Currency_symbol']}</i>{{ parseFloat(d.mustList[m].price) }}
        </div>
        <div class="Addsub">
            <span>{{ parseFloat(d.mustList[m].num) }}{{ d.mustList[m].unit }}</span>
        </div>
    </div>
</li>
{{# } }}
</script>
<script>
window.shareData = {
    "moduleName":"Foodshop",
    "moduleID":"0",
    "imgUrl": "{pigcms{$store['image']}", 
    "sendFriendLink": "{pigcms{$share_url}",
    "tTitle": "一起点餐吧",
    "tContent": "我正在【{pigcms{$store['name']}】就餐，邀您来点餐"
};
</script>

<if condition="$is_app_browser">
	<script type="text/javascript">
		window.lifepasslogin.shareLifePass("一起点餐吧", "我正在【{pigcms{$store['name']}】就餐，邀您来点餐", "{pigcms{$store['image']}", "{pigcms{$share_url}");
	</script>
</if>
{pigcms{$shareScript}
</body>
<script>
    $('#check_table').change(function(){
        if($(this).prop('checked')){
            $.get(use_card_or_table,{use:0},function(response){
                if (response.err_code == true) {
                    $(this).removeAttr('checked');
                } else {
                    $('#check_card').removeAttr('checked');
                }
            }, 'json');
        }
    });
    $('#check_card').change(function(){
        if($(this).prop('checked')){
            $.get(use_card_or_table,{use:1},function(response){
                if (response.err_code == true) {
                    $(this).removeAttr('checked');
                } else {
                    $('#check_table').removeAttr('checked');
                }
            }, 'json');
        }
    });
    $(".change_table_html .Sufrom_c .cancel").click(function(){
        $(".change_table_html").hide();
        $(".Mask").hide();
    });
    // 更换桌台
    $('.change_table').click(function(){
        $('.change_table_html').show()
        $(".Mask").show();
    });

    $('.change_table_html .do_change').click(function(){
        var table_id = $('#table_sel').val();
        console.log( table_id)
        $.post("{pigcms{:U('change_table')}",{order_id:"{pigcms{$order['order_id']}",table_id:table_id},function(response){
            if (response.err_code == true) {
                $('.change_table_html').hide()
                $(".Mask").hide();
                motify.log(response.msg);
            } else { 
                $('.change_table_html').hide()
                $(".Mask").hide();
                motify.log(response.msg);
                setTimeout(function(){
                    window.location.reload();
                },2000)
            }
        }, 'json');
    });

    // 取消订单
    //背景单窗高度  
    $(".Mask").css({"height": $(document).height(),'z-index': 999});
    //弹窗
    $(".Total .cancel").click(function(){
        $(".Tcancel").show();
        $(".Mask").show();
    });
    $(".Sufrom_n .cancel").click(function(){
        $(".Tcancel").hide();
        $(".Mask").hide();
    });

    var cancel_order = false;
    $(".Sufrom_n .complete").click(function(){
        if (cancel_order) return false;
        cancel_order = true;

        var cancel_reason = '', pre = '';
        $('.Reason .on').each(function(){
            cancel_reason += pre + $(this).find('span').text();
            pre = ',';
        });

        if (cancel_reason.length < 1) {
            cancel_order = false;
            motify.log('请选择您的取消理由');
            return false;
        }
        $.post('{pigcms{:U("Foodshop/cancel_book", array("order_id" => $order["order_id"]))}', {'cancel_reason':cancel_reason}, function(response){
            cancel_order = false;
            if (response.err_code) { 
                $(".Tcancel").hide();
                $(".Mask").hide();
                motify.log(response.msg);
            } else {
                location.href=response.url;
            }
        }, 'json');
    });
    $(".Reason li").click(function(){
        if (!$(this).hasClass("on")) {
            $(".Reason li").removeClass("on");
            $(this).addClass("on");
        }else{
            $(this).removeClass("on");
        }
        $(".Reason li").each(function(){
            if ($(this).hasClass("on") || $(this).siblings().hasClass("on")) {
                $(".Sufrom_n .complete").css("background", "#f03c3c");        
            } else if (!$(this).hasClass("on") && !$(this).siblings().hasClass("on")) { 
                $(".Sufrom_n .complete").css("background", "#c8c8c8");
            }
        });
    });
    $(document).on('click','.edit_note',function(){
        $(".edit_note_content").show();
        $(".Mask").show();
    })

    $(".edit_note_content .Sufrom_c .cancel").click(function(){
        $(".edit_note_content").hide();
        $(".Mask").hide();
    });

    $('.edit_note_content .do_change').click(function(){
        var note = $('#order_note').val();
        $.post("{pigcms{:U('change_note')}",{order_id:"{pigcms{$order['order_id']}",note:note},function(response){
            if (response.err_code == true) {
                $('.edit_note_content').hide()
                $(".Mask").hide();
                motify.log(response.msg);
            } else { 
                $('.edit_note_content').hide()
                $(".Mask").hide();
                motify.log(response.msg);
                setTimeout(function(){
                    window.location.reload();
                },2000)
            }
        }, 'json');
    });
</script>
</html>