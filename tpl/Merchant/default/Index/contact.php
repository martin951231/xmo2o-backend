<include file="Public:header"/>
<div class="main-content" style="padding:2rem;background: #FFFFFF">
    <h2 style="text-align: center">商家入驻协议</h2>
    <p style="text-align: right">协议编号:<font style="text-decoration: underline">{pigcms{$contract.contract_number}</font></p>
    <p>合同期限：{pigcms{$contract.date_info}</p>
    <p>甲方：{pigcms{$contract.partner_a.name}</p>
    <p>乙方：{pigcms{$contract.partner_b.name}</p>
    <p style="text-indent: 2rem;margin-top: 1rem;margin-bottom: 1px">
        {pigcms{$contract.contract_content}
    </p>
    <hr/>
    <p>扣点百分比：{pigcms{$contract.rate}%</p>
    <div style="display: inline-block;width: 50%;float: left;position: relative">
        <p>乙方：{pigcms{$contract.partner_b.name}</p>
        <p>代表：{pigcms{$contract.partner_b.represent}</p>
        <p>电话：{pigcms{$contract.partner_b.phone}</p>
        <p>地址：{pigcms{$contract.partner_b.address}</p>
        <p>日期：{pigcms{$contract.partner_b.date}</p>
        <if condition="$contract['partner_b']['sign_pic']">
            <img style="position: absolute;top:0;right: 2px;max-width: 200px" src="{pigcms{$contract.partner_b.sign_pic}"/>
        </if>
    </div>
    <div style="display: inline-block;width: 50%;float: left;position: relative">
        <p>甲方：{pigcms{$contract.partner_a.name}</p>
        <p>法人：{pigcms{$contract.partner_a.represent}</p>
        <p>电话：{pigcms{$contract.partner_a.phone}</p>
        <p>地址：{pigcms{$contract.partner_a.address}</p>
        <p>日期：{pigcms{$contract.partner_a.date}</p>
        <if condition="$contract['partner_a']['sign_pic']">
            <img style="position: absolute;top:0;right: 2px;max-width: 200px" src="{pigcms{$contract.partner_a.sign_pic}"/>
        </if>
    </div>
    <p style="clear: both;height: 30rem"></p>
    <if condition="$merchant['is_sign_contract'] eq 0">
    <div style="margin-top: 10rem;position: fixed;bottom: 1rem;text-align: center;width: 100%">
        <p class="sign_btn gray" style="text-align: center;width: 30%;margin-left: 35%;line-height: 3rem;height: 3rem;border-radius: 3rem;color: #FFFFFF">去签署合同（30s）</p>
        <!--<p class="cancel_sign" style="text-align: center;color: #808080">放弃签署</p>-->
    </div>
    </if>
</div>
<script type="text/javascript">
    var left_second = 30;

    //签字倒计时
    function sign_timer() {
        var signTimer = setInterval(function () {
            left_second--;
            if (left_second == 0) {
                $('.sign_btn').addClass('yellow').html(L_('去签署合同'))
                clearInterval(signTimer);
            } else {
                $('.sign_btn').html(L_('去签署合同') + '（' + left_second + 's）')
            }
        }, 1000);
    }
    sign_timer();
    
    $('.sign_btn').on('click',function () {
        if($(this).hasClass('yellow')){
            layer.open({
                title: '在线签名',
                type: 2,
                offset: '10px',
                area: ['600px', '600px'], //宽高
                content: '{pigcms{:U("Index/saveSign?canvasWidth=600&canvasHeight=600")}',
                zIndex: layer.zIndex,
                cancel: function () {

                }
            });
        }
    })
    
    $('.cancel_sign').on('click',function () {
        var index = parent.layer.getFrameIndex(window.name);
        parent.layer.close(index);
    })
</script>
<style type="text/css">
    .gray{
        background: #808080;
    }
    .yellow{
        background: #FFA200;
    }
</style>

<include file="Public:footer"/>