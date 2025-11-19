<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            </thead>
                            <tbody>
                            <if condition="$order_info">
                                <tr>
                                    <td><div class="tagDiv">消费订单编号：{pigcms{$order_info.order_no}</div></td>
                                    <td><div class="tagDiv">支付方式：{pigcms{$order_info.pay_type}</div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">应支付总金额：{pigcms{$order_info.use_money}</div></td>
                                    <td><div class="tagDiv">支付时间：{pigcms{$order_info.pay_time}</div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">实际消费金额：{pigcms{$order_info.use_money}</div></td>
                                    <td><div class="tagDiv">设备名称：{pigcms{$order_info.equipment_name}</div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">插头编码：<if condition="$order_info.order_type eq 1">{pigcms{$order_info.socket_no}<else />---</if></div></td>
                                    <td><div class="tagDiv">消费时长：<if condition="$order_info.order_type eq 1">{pigcms{$order_info.continued_time}<else />---</if></div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">消费电量：<if condition="$order_info.order_type eq 1">{pigcms{$order_info.use_ele}<else />---</if></div></td>
                                    <td><div class="tagDiv">消费结束时功率：<if condition="$order_info.order_type eq 1">{pigcms{$order_info.end_power}<else />---</if></div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">状态：<if condition="$order_info.order_type eq 1">{pigcms{$order_info.status}<else />---</if></div></td>
                                </tr>
                                <tr>
                                    <td><div class="tagDiv">消费开始时间：{pigcms{$order_info.pay_time}</div></td>
                                    <td><div class="tagDiv">消费结束时间：{pigcms{$order_info.end_time}</div></td>
                                </tr>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有功能属性。</td></tr>
                            </if>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function del(id) {
        var url = "{pigcms{:U('Pile/e_attribute_del')}";
        $.get(url,{'equipment_id':id},function(data){
            if(data['status']){
                location.reload();
            }else{
                alert(data['msg']);
            }
        },'json')
    }
</script>
<include file="Public:footer"/>
