<include file="Public:header"/>
<style>
    .maintenan li{
        display: inline !important;
    }
</style>
<div class="mainbox">
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup><col> <col> <col><col><col><col><col><col width="180" align="center"> </colgroup>
                <thead>
                <tr>
                    <th>小区名称</th>
                    <th>地址</th>
                    <th>设备名称</th>
                    <th>故障现象</th>
                    <th>发生时间</th>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($list)">
                    <volist name="list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.village_name}</td>
                            <td>{pigcms{$vo.address}</td>
                            <td>{pigcms{$vo.device_name}</td>
                            <td>{pigcms{$vo.reason}</td>
                            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar maintenan" colspan="5">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="5">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>