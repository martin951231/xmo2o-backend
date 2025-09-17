<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox" style="padding-left: 10px;padding-right: 10px;padding-bottom: 5rem;">
    <h2 style="text-align: center">商家入驻协议</h2>
    <p style="text-align: right">协议编号:<font style="text-decoration: underline">{pigcms{$contract.contract_number}</font></p>
    <p>甲方：{pigcms{$contract.content_data.partner_a.name}</p>
    <p>乙方：{pigcms{$contract.content_data.partner_b.name}</p>
    <p style="text-indent: 2rem;margin-top: 1rem;margin-bottom: 1px">
        {pigcms{$contract.content_data.contract_content}
    </p>
    <hr/>
    <p>扣点百分比：{pigcms{$contract.content_data.rate}%</p>
    <div style="display: inline-block;width: 50%;float: left;position: relative">
        <p>乙方：{pigcms{$contract.content_data.partner_b.name}</p>
        <p>代表：{pigcms{$contract.content_data.partner_b.represent}</p>
        <p>电话：{pigcms{$contract.content_data.partner_b.phone}</p>
        <p>地址：{pigcms{$contract.content_data.partner_b.address}</p>
        <p>日期：{pigcms{$contract.content_data.partner_b.date}</p>
        <if condition="$contract['content_data']['partner_b']['sign_pic']">
        <img style="position: float: left;margin:-190px 50px 0 120px;max-width: 200px;object-fit:contain" src="{pigcms{$contract.content_data.partner_b.sign_pic}"/>
        </if>
    </div>
    <div style="display: inline-block;width: 50%;float: left;position: relative">
        <p>甲方：{pigcms{$contract.content_data.partner_a.name}</p>
        <p>法人：{pigcms{$contract.content_data.partner_a.represent}</p>
        <p>电话：{pigcms{$contract.content_data.partner_a.phone}</p>
        <p>地址：{pigcms{$contract.content_data.partner_a.address}</p>
        <p>日期：{pigcms{$contract.content_data.partner_a.date}</p>
        <if condition="$contract['sign_name_base64']">
        <img style="position: float: left;margin:-190px 50px 0 120px;max-width: 200px;object-fit:contain" src="{pigcms{$contract.sign_name_base64}"/>
        </if>
    </div>
</div>
<include file="Public:footer"/>