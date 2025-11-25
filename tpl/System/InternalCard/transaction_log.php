<include file="Public:header"/>
<div class="mainbox">
    <include file="card_manage_header"/>

    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('InternalCard/transaction_log')}" method="get">
                    <input type="hidden" name="c" value="InternalCard"/>
                    <input type="hidden" name="a" value="transaction_log"/>
                    卡ID: <input type="text" name="card_id" class="input-text" value="{pigcms{$_GET['card_id']}"/>
                    用户ID: <input type="text" name="uid" class="input-text" value="{pigcms{$_GET['uid']}"/>
                    卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                    店铺: <input type="text" name="store_name" class="input-text" value="{pigcms{$_GET['store_name']}"/>
                    店铺ID: <input type="number" name="store_id" class="input-text" value="{pigcms{$_GET['store_id']}"/>
                    业务类型: 
                    <select name='business_type'>
                        <option value="">所有</option>
                        <volist name="business_type" id="type">
                            <option value="{pigcms{$key}" <if condition="$_GET['business_type'] eq $key">selected</if>>{pigcms{$type}</option>
                        </volist>
                    </select>
                    <font color="#000">交易时间范围：</font>
                    <input type="text" class="input-text" name="start_add_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_add_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_add_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_add_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <font color="#000">卡余额范围：</font>
                    <input type="text" class="input-text" name="start_balance" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_balance}" />
                    <input type="text" class="input-text" name="end_balance" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_balance}" />

                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/><br/>
                    <span>汇总金额：{pigcms{$totalMoney|default=0}</span>
                    <span>当月交易金额：{pigcms{$monthMoney|default=0}</span>
                    <a href="javascript:void(0)" onclick="exportData()" class="button" style="margin-right: 10px;">导出Excel</a>

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
                    <col  align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>序号</th>
                    <th>时间</th>
                    <th>店铺</th>
                    <th>卡名称</th>
                    <th>卡号</th>
                    <th>区域</th>
                    <th>用户姓名</th>
                    <th>业务类型</th>
                    <th>详情</th>
                    <th>原金额</th>
                    <th>现金额</th>
                    <th>订单号</th>
                    <th>交易金额</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($log_list)">
                    <volist name="log_list" id="vo" key="serial">
                        <tr>
                            <td>{pigcms{$serial}</td>
                            <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
                            <td>{pigcms{$vo.store_name}</td>
                            <td>{pigcms{$vo.card_name}</td>
                            <td>{pigcms{$vo.card_id}</td>
                            <td>{pigcms{$vo.region_str}</td>
                            <td>{pigcms{$vo.user_name}</td>
                            <td>{pigcms{$vo.business_type}</td>
                            <td>{pigcms{$vo.describe}</td>
                            <td>{pigcms{$vo.before_balance}</td>
                            <td>{pigcms{$vo.after_balance}</td>
                            <td>{pigcms{$vo.order_id}</td>
                            <td>{pigcms{$vo.money}</td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="16">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="16">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
<include file="Public:footer"/>
    <script type="text/javascript">
        function exportData() {
            window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
        }
    </script>