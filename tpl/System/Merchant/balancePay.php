<include file="Public:header"/>
<div class="mainbox">
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Merchant/balancePay')}" method="get">
                    <input type="hidden" name="c" value="Merchant"/>
                    <input type="hidden" name="a" value="balancePay"/>

                    商家名:
                    <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <font color="#000">日期：</font>
                    <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="submit" value="查询" class="button"/>　
                </form>
            </td>

            <td style="width:100px;">
                <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出统计</a>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>商家名称</th>
                    <th>平台余额使用总量</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($lists)">
                    <volist name="lists" id="vo">
                        <tr>
                            <td>{pigcms{$vo.mer_name}</td>
                            <td>{pigcms{$vo.total_balance}</td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="2">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="2">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    //导出
    function exports() {
        var keyword = $('#keyword').val();
        var begin_time = $('#begin_time').val();
        var end_time = $('#end_time').val();
        window.open('{pigcms{:U("Merchant/balancePay")}' + '&is_export=1&keyword=' + keyword + '&begin_time=' + begin_time + '&end_time=' + end_time);
    }
</script>
<include file="Public:footer"/>