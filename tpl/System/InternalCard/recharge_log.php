<include file="Public:header"/>
<div class="mainbox">
    <empty name="deduction_batch_no">
    <include file="recharge_header"/>

    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('InternalCard/recharge_log')}" method="get">
                    <input type="hidden" name="c" value="InternalCard"/>
                    <input type="hidden" name="a" value="recharge_log"/>
                    卡ID: <input type="text" name="card_id" class="input-text" value="{pigcms{$_GET['card_id']}"/>
                    用户ID: <input type="text" name="uid" class="input-text" value="{pigcms{$_GET['uid']}"/>
                    卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                    操作人员ID: <input type="text" name="system_id" class="input-text" value="{pigcms{$_GET['system_id']}"/>
                    <font color="#000">充值时间范围：</font>
                    <input type="text" class="input-text" name="start_add_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_add_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_add_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_add_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <font color="#000">卡余额范围：</font>
                    <input type="text" class="input-text" name="start_balance" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_balance}" />
                    <input type="text" class="input-text" name="end_balance" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_balance}" />
                    充值类型:
                    <select name="recharge_type">
                        <option value="">全部</option>
                        <option value="1" <if condition="$_GET['recharge_type'] == 1">selected="selected"</if>>用户充值</option>
                        <option value="2" <if condition="$_GET['recharge_type'] == 2">selected="selected"</if>>平台充值</option>
                    </select>

                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                    <br/>
                    <span>汇总金额：{pigcms{$totalMoney}</span>
                    <span>当月充值金额：{pigcms{$monthMoney}</span>
                    <a href="javascript:void(0)" onclick="exportData()" class="button" style="margin-right: 10px;">导出Excel</a>
                </form>
            </td>
        </tr>
    </table>
    <else/>
        <p>汇总金额：{pigcms{$totalMoney}</p>
    </empty>
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
                    <col  align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>一卡通名称</th>
                    <th>一卡通ID</th>
                    <th>区域</th>
                    <th>用户姓名</th>
                    <th>充值金额</th>
                    <th>操作人员</th>
                    <th>描述</th>
                    <th>原金额</th>
                    <th>现金额</th>
                    <th>添加时间</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($log_list)">
                    <volist name="log_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.card_name}</td>
                            <td>{pigcms{$vo.card_id}</td>
                            <td>{pigcms{$vo.region_str}</td>
                            <td>{pigcms{$vo.user_name}</td>
                            <td>{pigcms{$vo.money}</td>
                            <td>{pigcms{$vo.admin_name}</td>
                            <td>{pigcms{$vo.describe}</td>
                            <td>{pigcms{$vo.before_balance}</td>
                            <td>{pigcms{$vo.after_balance}</td>
                            <td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
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
<include file="Public:footer"/>

<script type="text/javascript">
    function exportData() {
        window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
    }
</script>