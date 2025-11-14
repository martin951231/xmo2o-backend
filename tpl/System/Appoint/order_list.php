<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Appoint/product_list')}">预约列表</a>
            <a href="{pigcms{:U('Appoint/order_list', array('appoint_id'=>$now_appoint['appoint_id']))}" class="on">订单列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Appoint/order_list')}" method="get">
                    <input type="hidden" name="c" value="Appoint"/>
                    <input type="hidden" name="a" value="order_list"/>
                    <input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>
                    <if condition="$now_area['area_type'] neq 3">
                        <span class="fl">地区筛选：</span>
                        <div class="fl" id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                    </if>
                    搜索: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="order_id"<if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单ID")}</option>
                        <option value="real_orderid"<if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
                        <option value="orderid"<if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>订单流水号</option>
                        <option value="third_id"<if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>第三方支付流水号</option>
                        <option value="name"<if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
                        <option value="phone"<if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
                        <option value="nickname"<if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>下单人</option>
                    </select>
                    <font color="#000">日期筛选：</font>
                    <select name="searchTimeType">
                        <option value="order_time"<if condition="$_GET['searchTimeType'] eq 'order_time'">selected="selected"</if>>下单时间</option>
                        <option value="user_pay_time"<if condition="$_GET['searchTimeType'] eq 'user_pay_time'">selected="selected"</if>>尾款支付时间</option>
                        <option value="verify_time"<if condition="$_GET['searchTimeType'] eq 'verify_time'">selected="selected"</if>>验证服务时间</option>
                    </select>
                    <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <font color="#000">支付状态：</font>
                    <select name="paid">
                        <option value="-1"<if condition="$_GET['paid'] eq '-1'">selected="selected"</if>>全部</option>
                        <option value="-2"<if condition="$_GET['paid'] eq '0'">selected="selected"</if>>未支付</option>
                        <option value="1"<if condition="$_GET['paid'] eq '1'">selected="selected"</if>>已支付</option>
                        <option value="2"<if condition="$_GET['paid'] eq '2'">selected="selected"</if>>已退款</option>
                        <option value="3"<if condition="$_GET['paid'] eq '3'">selected="selected"</if>>已取消</option>
                        <option value="-10"<if condition="$_GET['paid'] eq '-10'">selected="selected"</if>>尾款待支付</option>
                    </select>

                    <font color="#000">服务状态：</font>
                    <select name="service_status">
                        <option value="-1"<if condition="$_GET['service_status'] eq '-1'">selected="selected"</if>>全部</option>
                        <option value="0"<if condition="$_GET['service_status'] eq '0'">selected="selected"</if>>未服务</option>
                        <option value="1"<if condition="$_GET['service_status'] eq '1'">selected="selected"</if>>已服务</option>
                        <option value="2"<if condition="$_GET['service_status'] eq '2'">selected="selected"</if>>已评价</option>
                    </select>

                    支付方式筛选:
                    <select id="pay_type" name="pay_type">
                        <option value=""<if condition="'' eq $pay_type">selected="selected"</if>>全部支付方式</option>
                        <volist name="pay_method" id="vo">
                            <option value="{pigcms{$key}"<if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
                        </volist>
                        <option value="balance"<if condition="'balance' eq $pay_type">selected="selected"</if>>余额支付</option>
                    </select>
                    城市筛选:
                    <select id="first" name="project_city_id" onchange="jump()">
                        <option value=""<if condition="'' eq $project_city_id">selected="selected"</if>>请城市名称</option>
                        <volist name="project_city_list" id="vo">
                            <option value="{pigcms{$vo['project_city_name']}"<if condition="$vo['project_city_name'] eq $project_city_name">selected="selected"</if>>{pigcms{$vo.project_city_name}</option>
                        </volist>

                    </select>
                    项目名称筛选:
                    <select id="second" name="project_id">
                        <option value="" selected="selected">请项目名称</option>
                    </select>

                    <input type="submit" value="查询" class="button"/>　
                </form>
            </td>
            <td>
                <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
            </td>
        </tr>
    </table>
    <div style="margin:15px 0;">
        <php>if($_GET['appoint_id']){</php>
        <b>商家ID：</b>{pigcms{$now_merchant.mer_id}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>商家名称：</b>{pigcms{$now_merchant.name}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>联系电话：</b>{pigcms{$now_merchant.phone}<br/><br/>
        <b>预约ID：</b>{pigcms{$now_appoint.appoint_id}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>预约名称：</b>{pigcms{$now_appoint.appoint_name}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>预约定金：</b>{pigcms{$config['Currency_symbol']}{pigcms{$now_appoint.payment_money|floatval=###}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>预约类型：</b>
        <switch name="now_appoint['appoint_type']">
            <case value="0">到店</case>
            <case value="1">上门</case>
        </switch>
        <php>}</php>
    </div>
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
                </colgroup>
                <thead>
                <tr>
                    <th>订单id</th>
                    <th>订单编号</th>
                    <th>订单信息</th>
                    <th>订单用户</th>
                    <th>查看用户信息</th>
                    <th>订单状态</th>
                    <th>支付方式</th>
                    <th>服务状态</th>
                    <th>支付详情</th>
                    <th>下单地址与电话名称</th>
                    <th>时间</th>
                    <th>项目名称</th>
                    <th>项目城市</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($order_list)">
                    <volist name="order_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.order_id}</td>
                            <td>{pigcms{$vo.real_orderid}</td>
                            <td>
                                <if condition='$vo["product_payment_price"] gt 0'>定金：{pigcms{$config['Currency_symbol']} {pigcms{$vo.product_payment_price}<else/>定金：{pigcms{$config['Currency_symbol']} {pigcms{$vo.payment_money}</if><br/>
                                <if condition='$vo["product_price"] gt 0'>总价：{pigcms{$config['Currency_symbol']} {pigcms{$vo.product_price}<else/>总价：{pigcms{$config['Currency_symbol']} {pigcms{$vo.appoint_price}</if><br>
                                类型：<if condition='$vo["type"] eq 1'><font class="red">自营</font><else/><font style="color:green">商家</font></if>
                            </td>
                            <td>
                                用户昵称：{pigcms{$vo.nickname}<br/>
                                手机号：{pigcms{$vo.phone}
                            </td>
                            <td><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','查看服务详情',680,560,true,false,false,false,'detail',true);">查看用户信息</a></td>
                            <td>
                                <if condition="$vo['paid'] eq 0"><span style="color:red">未支付</span>
                                    <elseif condition="$vo['paid'] eq 1"/>
                                    <php>if ((floatval($vo['payment_money']) || floatval($vo['product_payment_price'])) && $vo['payment_status']!=1) {</php>
                                    <span style="color:green">已支付</span>
                                    <php>} elseif (empty(floatval($vo['payment_money'])) && empty(floatval($vo['product_payment_price'])) && $vo['is_initiative']<=0) {</php>
                                    <span style="color:red">待支付</span>
                                    <php>} elseif ($vo['service_status']>0 && $vo['is_initiative']<=0) {</php>
                                    <span style="color:green">已服务</span><span style="color:red">尾款未支付</span>
                                    <php>} elseif ($vo['is_initiative']<=0) {</php>
                                    <span style="color:red">尾款待支付</span>
                                    <php>} else {</php>
                                    <span style="color:green">已支付</span>
                                    <php>}</php>
                                    <elseif condition="$vo['paid'] eq 2"/><span style="color:blue">已退款</span>
                                    <elseif condition="$vo['paid'] eq 3"/><span style="color:blue">已取消</span>
                                </if>
                                &nbsp;|&nbsp;
                                <if condition="$vo['is_del'] eq 0 AND ($vo['paid'] neq 3)"><span style="color:green">未取消</span>
                                    <elseif condition="($vo['is_del'] eq 1) || ($vo['is_del'] eq 5) || ($vo['paid'] eq 3)"/><span style="color:red">已取消【用户】</span>
                                    <elseif condition="$vo['is_del'] eq 2"/><span style="color:red">已取消【平台】</span>
                                    <elseif condition="$vo['is_del'] eq 3"/><span style="color:red">已取消【商家】</span>
                                    <elseif condition="$vo['is_del'] eq 4"/><span style="color:red">已取消【店员】</span>
                                </if>
                            </td>
                            <td>
                                {pigcms{$vo['pay_type_txt']}
                            </td>
                            <td>
                                <if condition="$vo['service_status'] eq 0"><span style="color:red">未服务</span>
                                    <elseif condition="$vo['service_status'] eq 1"/><span style="color:green">已服务</span>
                                    <elseif condition="$vo['service_status'] eq 2"/><span style="color:green">已评价</span>
                                </if>
                            </td>
                            <td>
                                平台余额支付：{pigcms{$vo['balance_pay']+$vo['product_balance_pay']} <br>
                                商家会员卡余额支付：{pigcms{$vo['merchant_balance']+$vo['product_card_give_money']+$vo['product_merchant_balance']+$vo['card_give_money']}<br>
                                在线支付金额：
                                <if condition="$vo['paid'] egt 1 && $vo['pay_money'] gt 0 && $vo['is_initiative'] gt 0">
                                    {pigcms{$vo['pay_money'] + $vo['user_pay_money']}
                                    <elseif condition="$vo['paid'] egt 1 && $vo['product_payment_price'] gt 0 && $vo['is_initiative'] gt 0"/>
                                    {pigcms{$vo['product_payment_price'] + $vo['user_pay_money']}
                                    <elseif condition="$vo['paid'] egt 1 && $vo['is_initiative'] elt 0 && $vo['pay_money'] gt 0"/>
                                    {pigcms{$vo['pay_money']}
                                    <elseif condition="$vo['paid'] egt 1 && $vo['is_initiative'] elt 0 && $vo['product_payment_price'] gt 0"/>
                                    {pigcms{$vo['product_payment_price']}
                                    <else/>
                                    0.00
                                </if>
                            </td>
                            <td>
                                <span>名称:{pigcms{$vo.info.showName}</span><br>
                                <span>地址：{pigcms{$vo.info.address}</span><br>
                                <span>电话:{pigcms{$vo.phone}</span>
                            </td>
                            <td>
                                下单时间：{pigcms{$vo.order_time|date="Y-m-d H:i:s",###}<br/>
                                <if condition="$vo.pay_time gt 0 && $vo.user_pay_time eq 0">付款时间：{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if>
                                <if condition="$vo.user_pay_time gt 0 && $vo.pay_time eq 0">付款时间：{pigcms{$vo.user_pay_time|date="Y-m-d H:i:s",###}</if>
                            </td>
                            <td>
                                <if condition="$vo['project_id'] eq ''">
                                    <span style="color:red"></span>
                                    <else/>
                                    <span style="color:green">{pigcms{$vo.project_id}</span>
                                </if>
                            </td>
                            <td>
                                <if condition="$vo['project_city_id'] eq ''">
                                    <span style="color:red"></span>
                                    <else/>
                                    <span style="color:green">{pigcms{$vo.project_city_id}</span>
                                </if>
                            </td>
                            <td class="textcenter">
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Appoint/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>false))}','查看服务详情',660,520,true,false,false,false,'detail',true);">查看详情</a>
                                <if condition='($vo["is_del"] eq 0) && ($vo["paid"] eq 0)'>
                                    &nbsp;|&nbsp;
                                    <a href="javascript:void(0);" onclick="cancel_order({pigcms{$vo['order_id']})">取消订单</a>
                                </if>
                                &nbsp;|&nbsp;<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Appoint/order_trace',array('order_id'=>$vo['order_id']))}','服务轨迹',680,680,true,false,false,closebtn,'content',true,false);">服务轨迹</a> 
                            </td>
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
<script>
    function cancel_order(order_id) {
        if (confirm('是否确认取消订单？')) {
            var url = "{pigcms{:U('ajax_merchant_del')}";
            $.post(url, {'order_id': order_id}, function (data) {
                alert(data.msg);
                if (data.status) {
                    location.reload();
                }
            }, 'json')
        }
    }

    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Appoint/export')}"
    
    function jump() {
        $.ajax({
            url: "{pigcms{:U('Appoint/ajax_city')}",
            type: 'post',
            data: {city: $("#first").val()},
            dataType: "json",
            success: function (res) {
                console.log(res);
                var html = '';
                html += '<option selected="selected" value="">请项目名称</option>';
                for (var i in res) {
                    html += '<option selected="selected" value="' + res[i]['project_name'] + '">' + res[i]['project_name'] + '</option>';
                }
                $("#second").html(html);
            }
        });
    }
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"></script>
<include file="Public:footer"/>