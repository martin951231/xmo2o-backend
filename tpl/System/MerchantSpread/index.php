<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MerchantSpread/audit')}">已审核推广商</a>|
            <a href="{pigcms{:U('MerchantSpread/notAudit')}">待审核推广商</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('MerchantSpread/setting')}','推广商奖励设置',550,300,true,false,false,editbtn,'edit',true);">推广商奖励设置</a>|
            <a href="{pigcms{:U('MerchantSpread/index')}" class="on">推广商申请设置</a>
        </ul>
    </div>
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>推广商类型</th>
                    <th>申请表单设置</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>vip推广商</td>
                    <td><a href="javascript:void(0)" onclick="cue_field_setting(1)">填写项设置</td>
                </tr>
                <tr>
                    <td>城市推广商</td>
                    <td><a href="javascript:void(0)" onclick="cue_field_setting(2)">填写项设置</td>
                </tr>
                </tbody>
            </table>
        </div>
</div>
<script type="text/javascript">
    function cue_field_setting(type){
        window.o2oTop.artiframe('{pigcms{:U('MerchantSpread/cueField')}'+'&type='+type,'表单填写项',580,420,true,false,false,false,'detail',true);
    }
 </script>
<include file="Public:footer"/>