<include file="Public:header"/>
<div class="main-content">
    <style>
        .mainnav_title {
            margin-top: 20px;
        }

        .mainnav_title ul a {
            padding: 15px 20px;
        }

        ul, ol {
            margin-bottom: 15px;
        }

        .mainnav_title span {
            color: #7EBAEF;

        }

        .mainnav_title a.on div {
            color: #C1BEBE;
        }

        .all {
            border-collapse: collapse;
            border: none;
        }

        .all td {
            border: solid #000 1px;
            border-color: "#cccc99";
            height: 20px;
            text-align: center;
        }

        .all th {
            border: solid #000 1px;
            border-color: "#cccc99";
            height: 20px;
        }

        button {
            padding: 6px;
            background-color: rgb(241, 235, 235);;
            box-sizing: border-box;
            border-width: 1px;
            border-style: solid;
            border-color: rgba(121, 121, 121, 1);
            border-radius: 2px;
            -moz-box-shadow: none;
            -webkit-box-shadow: none;
            box-shadow: none;
            font-size: 14px;
            color: #666666;
            cursor: pointer;

        }

        #myform div {
            margin-top: 10px;
        }
    </style>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-sm-12">
                    <div style="margin:10px;">
                        <div class="info" style="font-size:16px;font-family: 'Arial Negreta','Arial';font-weight: 700;">
                            {pigcms{:L_("收入记录")}
                        </div>
                        <form id="myform" method="get" action="{pigcms{:U('Merchant_money/qiye_income_list')}" style="display:inline;">
                            <input type="hidden" name="c" value="Merchant_money"/>
                            <input type="hidden" name="a" value="qiye_income_list"/>
                            <div>
                                {pigcms{:L_("业务类型：")}
                                <select name="order_type">
                                    <option value=""</if>请选择</option>
                                    <volist name="orderType" id="vo">
                                        <option value="{pigcms{$key}" <if condition="$key eq $_GET['order_type']">selected="selected"</if>>{pigcms{$vo}</option>
                                    </volist>
                                </select>
                            </div>
                            <div>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("店铺：")}<select id="store_id" name="store_id" onchange="javascript:frmselect.submit()">
                                    <option value="">{pigcms{:L_("选择店铺")}</option>
                                    <volist name="store_list" id="vo">
                                        <option value="{pigcms{$vo.store_id}" <if condition="$_GET['store_id'] eq $vo['store_id']" >selected="selected"</if>>{pigcms{$vo.name}</option>
                                    </volist>
                                </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </div>
                            <div>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("订单：")}
                                <input type="text" name="order_id" value="{pigcms{$Think.get.order_id}" placeholder="{pigcms{:L_("输入订单号")}" >
                            </div>
                            <div>
                                <div style="float:left"><font color="#000">{pigcms{:L_("时间筛选 ：")}</font></div>
                                <input type="text" class="input fl" name="begin_time" id="d4311"
                                       value="{pigcms{$Think.get.begin_time}"
                                       onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
                                <input type="text" class="input fl" name="end_time" id="d4312"
                                       value="{pigcms{$Think.get.end_time}"
                                       onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
                                <input type="submit" value="{pigcms{:L_(" 查询")}">
                            </div>
                        </form>
                    </div>
                    <div class="tabbable" >
                        <div class="row">
                            <div class="col-xs-13">
                                <div class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th>{pigcms{:L_("店铺名称")}</th>
                                            <th>{pigcms{:L_("订单号")}</th>
                                            <th>{pigcms{:L_("订单类型")}</th>
                                            <th>{pigcms{:L_("详情")}</th>
                                            <th>{pigcms{:L_("订单总额")}</th>
                                            <th>{pigcms{:L_("总额")}</th>
                                            <th>{pigcms{:L_("平台佣金")}<font color="red" size="1">({pigcms{:L_("提现代表手续费")})</font></th>
                                            <th>{pigcms{:L_("支付时间")}</th>
                                            <th>{pigcms{:L_("当前商家余额")}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="$income_list">
                                            <volist name="income_list" id="vo">
                                                <tr>
                                                    <td>{pigcms{$vo.store_name|default='---'}</td>
                                                    <td>{pigcms{$vo.order_id}</td>
                                                    <td>
                                                        <php>
                                                            echo isset($type_name[$vo['type']])?$type_name[$vo['type']]:$vo['type'];
                                                        </php>
                                                    </td>
                                                    <td>{pigcms{:msubstr($vo['desc'],0,80,true,'utf-8')}</td>
                                                    <td>{pigcms{$vo.total_money|floatval}</td>
                                                    <td>
                                                        <if condition="$vo.income eq 1">
                                                            <font color="#2bb8aa">+{pigcms{$vo.money|floatval}</font>
                                                        <elseif condition="$vo.income eq 2" />
                                                            <font color="#f76120">-{pigcms{$vo.money|floatval}</font>
                                                        </if>
                                                    </td>
                                                    <td>
                                                        <font color="#5167de">{pigcms{$vo.system_take}{pigcms{:L_("（抽成比例")} {pigcms{$vo.percent} %）</font></td>
                                                    <td><if condition="$vo['use_time'] gt 0">{pigcms{$vo.use_time|date="Y-m-d H:i:s",###}</if></td>
                                                    <td><font color="#5167de">{pigcms{$vo.now_mer_qiye_money}</font></td>
                                </tr>
                                </volist>


                                <tr class="odd">
                                    <td colspan="13" id="show_count"></td>
                                </tr>
                                <tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
                                <else />
                                <tr class="odd"><td class="textcenter red" colspan="13" >{pigcms{:L_("暂时还没有收入记录")}</td></tr>
                                </if>
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"></script>
<include file="Public:footer"/>
