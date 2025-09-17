<include file="Public:header"/>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <title>退款详情</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
    <style>
        .repair_pic img{max-width:100%;}
    </style>
</head>
<body>
<table>

    <tr>
        <th width="15%">消费订单编号</th>
        <td width="35%">{pigcms{$order_info.order_no}</td>
        <th width="15%">支付订单编号</th>
        <td width="35%">{pigcms{$order_info.pay_orderid}</td>
    </tr>
	<tr>
		<th width="15%">第三方支付流水号</th>
		<td width="35%">{pigcms{$order_info.third_paid_orderid}</td>
		<th width="15%">订单类型</th>
		<td width="35%">{pigcms{$order_info.order_name}</td>
	</tr>
    <tr>
        <th width="15%">支付方式</th>
        <td width="35%">{pigcms{$order_info.pay_type}</td>
        <th width="15%">支付总金额</th>
        <td width="35%">{pigcms{$order_info.use_money}</td>
    </tr>
    <if condition="$order_info['paid_discount_amount']">
        <tr>
            <th width="15%">支付平台优惠金额</th>
            <td width="35%">{pigcms{$order_info.paid_discount_amount}</td>
            <th width="15%">支付平台实付金额</th>
            <td width="35%">{pigcms{$order_info.buyer_pay_amount}</td>
        </tr>
    </if>
    <tr>
        <th width="15%">支付时间</th>
        <td width="35%">{pigcms{$order_info.pay_time}</td>
        <th width="15%">实际消费金额</th>
        <td width="35%">{pigcms{$order_info.actual_money}</td>
    </tr>
    <tr>
        <th width="15%">设备名称</th>
        <td width="35%">{pigcms{$order_info.equipment_name}</td>
        <th width="15%">退款金额</th>
        <td width="35%">{pigcms{$order_info.refund_money}</td>
    </tr>
    <tr>
        <th width="15%">消费时长</th>
        <td width="35%">{pigcms{$order_info.continued_time}</td>
        <th width="15%">消费电量</th>
        <td width="35%">{pigcms{$order_info.use_ele}</td>
    </tr>
    <tr>
        <th width="15%">消费结束时的功率</th>
        <td width="35%">{pigcms{$order_info.end_power}</td>
        <th width="15%">结束原因</th>
        <td width="35%">{pigcms{$order_info.end_result}</td>
    </tr>
    <tr>
        <th width="15%">消费开始时间</th>
        <td width="35%">{pigcms{$order_info.pay_time}</td>
        <th width="15%">消费结束时间</th>
        <td width="35%">{pigcms{$order_info.end_time}</td>
    </tr>
    <tr>
        <th width="15%">插头编码</th>
        <td width="35%">{pigcms{$order_info.socket_no}</td>
        <th width="15%">退款时间</th>
        <td width="35%">{pigcms{$order_info.refund_time}</td>
    </tr>

    <tr>
        <th width="15%">退款原因</th>
        <td>{pigcms{$order_info.refund_reason}</td>
        <th width="15%">上报图例</th>
        <td width="85%" colspan="3" class="repair_pic">
            <if condition="$order_info.img_count neq 0 ">
                <volist name="order_info.img" id="p">
                    <img class="view_msg" src="{pigcms{:replace_file_domain($p)}" style="height: 50px;width: 50px" />
                </volist>
            </if>
        </td>
    </tr>

    <if condition="$order_info['is_refund']  eq 2">
        <tr>
            <th width="15%">审核状态</th>
            <td width="35%">
                <label style="padding-left:0px;padding-right:20px;"><input type="radio" name="status" value="3" class="ace" onclick="actionX(3)" checked="checked"><span class="lbl" style="z-index: 1">通过</span></label>
                <label style="padding-left:0px;"><input type="radio" name="status" value="4" class="ace" onclick="actionX(4)"><span class="lbl" style="z-index: 1" >拒绝</span></label>
            </td>
            <th width="15%" class="refundUnusual">填写变更金额</th>
            <td width="35%" class="refundUnusual"><input type="number" name="refund_money"/></td>
            <th width="15%" class="reason" style="display:none;">拒绝原因</th>
            <td width="35%" class="reason" style="display:none;"><textarea class="refund_reason"></textarea></td>
        </tr>
        <tr>
            <td align="center" colspan="4">
                <button id="suggent_submit" type="button" class="btn btn-info"><i class="ace-icon fa fa-check bigger-110"></i>提交</button>
            </td>
        </tr>
        <elseif condition="$order_info['is_refund']  eq 3" />
        <tr>
            <th width="15%">退款状态</th>
            <td width="35%">
                已退款
            </td>
        </tr>
        <elseif condition="$order_info['is_refund']  eq 4" />
        <tr>
            <th width="15%">退款状态</th>
            <td width="35%">
                未通过
            </td>
            <th width="15%">拒绝原因</th>
            <td width="35%">{pigcms{$order_info.refuse_reason}</td>
        </tr>
    </if>

</table>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">

    function actionX(type){
        if(type == 3){
            $('.reason').hide();
            $('.refundUnusual').hide();
        } else if(type!=-1){
            $('.reason').show();
            $('.refundUnusual').hide();
        } else if (type==-1) {
            $('.refundUnusual').show();
        }
    }

    $('.view_msg').click(function () {
        var img = $(this).attr('src');
        layer.open({
            type: 1,
            area: ['500px', '500px'],
            content: '<img style="width: 100%;height: 100%;" src="'+img+'"/>'
        });
    });

    $('#suggent_submit').click(function(){
        var url = "{pigcms{:U('HkcPile/refund')}";
        var order_id = "{pigcms{$order_info['id']}";
        var refund_reason = $('.refund_reason').val();
        var status = $("input[type='radio']:checked").val();

        if(status == 4 && refund_reason == ''){
            alert('请填写拒绝原因');
            return false;
        }
        $.post(url, {'order_id':order_id,'status':status,'refund_reason':refund_reason,'refund_from':'refund_info'}, function(data){
            alert(data.msg)
           
             parent.location.reload();
            
        },'json')
    });
</script>
</body>
</html>