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
                            {pigcms{:L_("消费记录")}
                        </div>
                        <form id="myform" method="get" action="{pigcms{:U('Analysis/orders')}" style="display:inline;">
                            <input type="hidden" name="c" value="Analysis"/>
                            <input type="hidden" name="a" value="orders"/>
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
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("店铺：")}
                                <span style="background: #0a8ddf;padding: 5px 10px;color: #fff;border-radius: 5px;line-height: 1.5rem" onclick="select_store()">点击选择</span>
                                <p id="store_name" style="margin-left:6rem;padding: 10px 10px">
                                    {pigcms{$Think.get.store_name|default=''}
                                </p>
                                <input type="hidden" name="store_id" value="{pigcms{$Think.get.store_id}"/>
                                <input type="hidden" name="store_name" value="{pigcms{$Think.get.store_name}"/>
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

                                <a onclick="export_excel()" style="margin-left: 2rem">{pigcms{:L_("导出EXCEL")}</a>
                            </div>
                        </form>
                    </div>
                    <script type="text/javascript">
                        function export_excel() {
                            var order_type = $('select[name=order_type] option:selected').val();
                            var store_id = $('input[name=store_id]').val();
                            var order_id = $('input[name=order_id]').val();
                            var begin_time = $('input[name=begin_time]').val();
                            var end_time = $('input[name=end_time]').val();
                            var url = "{pigcms{:U('Analysis/exportOrders')}"
                            url += '&order_type=' + order_type;
                            url += '&store_id=' + store_id;
                            url += '&order_id=' + order_id;
                            url += '&begin_time=' + begin_time;
                            url += '&end_time=' + end_time;
                            window.open(url);
                        }
                    </script>
                    <div class="tabbable">
                        <div class="row">
                            <div class="col-xs-13">
                                <div class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th>{pigcms{:L_("支付时间")}</th>
                                            <th>{pigcms{:L_("员工名称")}</th>
                                            <th>{pigcms{:L_("消费金额")}</th>
                                            <th>{pigcms{:L_("消费类型")}</th>
                                            <th>{pigcms{:L_("店铺名称")}</th>
                                            <th>{pigcms{:L_("订单编号")}</th>
                                            <th>{pigcms{:L_("当前员工补贴金额")}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <volist name="orders" id="o">
                                                <tr>
                                                <td>{pigcms{$o.log_date}</td>
                                                <td>{pigcms{$o.staff_name}</td>
                                                <td>{pigcms{$o.money}</td>
                                                <td>
                                                    <php>
                                                        if(isset($orderType[$o['order_type']])){
                                                            echo $orderType[$o['order_type']];
                                                        }else{
                                                            echo '--';
                                                        }
                                                    </php>
                                                </td>
                                                <td>{pigcms{$o.store_name}</td>
                                                <td>{pigcms{$o.orderid}</td>
                                                <td>{pigcms{$o.now_money}</td>
                                                </tr>
                                            </volist>
                                        </tbody>
                                    </table>
                                    {pigcms{$pagebar}
                                </div>
                            </div>
                            <!--div class="col-xs-2" style="margin-top: 15px;">
                                <a class="btn btn-success" href="#">{pigcms{:L_("导出成")}excel</a>
                            </div-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script>
    function select_store() {
        art.dialog.open("{pigcms{:U('Analysis/stores')}", {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'select_store',
            title: '选择店铺',
            padding: 0,
            width: '900px',
            height: '560px',
            lock: true,
            resize: false,
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '38.2%',
            ok: function () {
                var win = this.iframe.contentWindow;
                var obj = win.$('input[name=store_id]:checked');
                if(obj != undefined){
                    $('#store_name').html(obj.attr('data-store-name'));
                    $('input[name=store_id]').val(obj.val());
                    $('input[name=store_name]').val(obj.attr('data-store-name'));
                }
            }
        });
    }
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"></script>
<include file="Public:footer"/>
