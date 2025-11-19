<include file="Public:header" />
<div class="mainbox" style="padding-top: 1rem;">
    <font color="#000">账单日期 &nbsp;&nbsp;</font>
    <input type="text" class="input-text" name="bill_date" style="width:120px;padding: 0.4rem;" value="" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" />
    <button style="padding: 0.3rem 0.4rem;width: 4rem;margin-top:10px" onclick="downloadBill()">下载</button>

    <div style="margin-top: 20px;">
        <h4>文件说明：</h4>
        <p>订单文件格式（文件名：order_yyyyMMdd.csv）</p>
        <p>订单请求格式（文件名：request_yyyyMMdd.csv）</p>
        <p>交易明细格式（支付+退款）（文件名：detail_yyyyMMdd.csv）</p>
        <p>分账明细格式（文件名：split_yyyyMMdd.csv）</p>
        <p>通道手续费（文件名：app_fee_yyyyMMdd.csv）</p>
        <p>手续费（文件名：fee_no_channel_yyyyMMdd.csv）</p>
        <p>自动结算（文件名：auto_settle_yyyyMMdd.csv）</p>
        <p>退款预充值（文件名：refund_predeposit_yyyyMMdd.csv）</p>
    </div>
</div>
<script>
    function downloadBill() {
        var bill_date = $("input[name='bill_date']").val();
        if (bill_date.length == 0) {
            alert('请选择账单日期');
            return false;
        }

        var url = "{pigcms{:U('Merchant/downloadLakalaBill')}&export=1&bill_date=" + bill_date;
        $.get(url, function (data) {
            if (data.url) {
                window.open(data.url);
            } else {
                alert('下载失败');
            }
        });
    }
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer" />