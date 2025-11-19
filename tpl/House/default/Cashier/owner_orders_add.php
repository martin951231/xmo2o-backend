<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="javascript:void(0);">收银台</a>
            </li>
            <li class="active">未缴费用</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>

            <div class="row">
                <div class="col-xs-12">
                    <p style="font-size: 20px;margin-bottom: 12px;">收银台</p>
                    <li style="margin-bottom:30px;">
                        <label style="margin-right: 100px">编号：{pigcms{$user_info.usernum}</label>
                        <label style="margin-right: 100px">业主姓名：{pigcms{$user_info.name}</label>
                        <label style="margin-right: 100px">手机号：{pigcms{$user_info.phone}</label>
                        <label>地址：{pigcms{$user_info.address}</label>
                    </li>
                </div>
                <div class="col-xs-12">
                    <a href="javascript:void(0)" url="{pigcms{:U('owner_add',array('pigcms_id'=>$pigcms_id))}" class="btn btn-sm btn-success" id="addorder" data-toggle="modal" onclick="addOrder(this)">添加缴费项</a>

                    <div class="tab-pane active" id="txtstore">
                        <div id="shopList" class="grid-view">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="5%">收费项</th>
                                    <th width="5%">应缴金额</th>
                                    <th width="5%">物业服务周期</th>
                                    <th width="10%">赠送物业服务时间</th>
                                    <th width="8%">服务时间</th>
                                    <th width="5%">自定义缴费周期</th>
                                    <th width="5%">备注</th>
                                    <th width="5%">操作</th>
                                </tr>
                                </thead>
                                <tbody id="content">
                                <tr id="wei"><td colspan="9" style="text-align: center;color: red;font-size: 20px">暂未添加缴费项</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-11 col-md-9">
                            <button class="btn btn-info" type="submit" onclick="confirm()">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="font-size: 30px; padding: 15px;"></div>
<script type="text/javascript" src="{pigcms{$static_path}js/index.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    var addbtn = [
        {
            name:'添加',
            callback:function () {
                var iframe = this.iframe.contentWindow;
                if (iframe.document.body) {
                    var submits=iframe.document.getElementById('dosubmit');
                    submits.click();
                    var that = this;
                    return false;
                }else{
                    return false;
                }
            },
            focus:true
        },
       
    ];
    function addOrder(obj){
        var url = $(obj).attr('url');
        art.dialog.open(url, {
            title: "添加缴费",
            lock: true,
            width: 800,
            height: 600,
            button:addbtn,
        },true);
    }

    var datas = {};
    var m = 0;
    function add_order(index,data) {
        if(m==0)
            $("#wei").remove();
        datas[m] = data;
        m++;
        console.log(datas);
        if(index.type == 'property'){
            $.post("{pigcms{:U('get_property')}",{property_id:index.property_id,usernum:index.usernum,pigcms_id:index.pigcms_id},function(result){
                console.log(result);
                var html = '<tr id="'+m+'"><td>'+m+'</td>' +
                    '<td>物业费</td>' +
                    '<td>'+Math.round(result.now_property_info.total_price * 100) / 100+'</td>'+
                    '<td>'+result.now_property_info.property_month_num+'个月</td>'+
                    '<td>'+result.now_property_info.presented_property_month_num+'个月</td>'+
                    '<td>'+result.now_property_info.property_time_str+'</td>'+
                    '<td>—</td>'+
                    '<td>'+index.remarks+'</td>'+
                    '<td><a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0);" onclick="del_order(this)">移除</a></td></tr>'
                ;
                $('#content').append(html);
            },'json');
        }else if(index.type == 'custom'){
            var html =  '<tr id="'+m+'"><td>'+m+'</td>' +
                '<td>自定义缴费'+index.custom_remark+'</td>' +
                '<td>'+Math.round(index.custom_price * 100) / 100+'</td>'+
                '<td>—</td>'+
                '<td>—</td>'+
                '<td>—</td>'+
                '<td>—</td>'+
                '<td>'+index.remarks+'</td>'+
                '<td><a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0);" onclick="del_order(this)">移除</a></td></tr>'
            ;
            $('#content').append(html);
        }else if(index.type == 'custom_payment'){
            $.post("{pigcms{:U('get_payment')}",{pigcms_id:index.pigcms_id,order_name:index.payment_name},function(result){
                console.log(result);
                var html =  '<tr id="'+m+'"><td>'+m+'</td>' +
                    '<td>'+index.payment_name+'</td>' +
                    '<td>'+Math.round(index.payment_price * 100) / 100+'</td>'+
                    '<td>—</td>'+
                    '<td>—</td>'+
                    '<td>'+result.payment.info+'</td>'+
                    '<td>'+index.payment_paid_cycle+'/周期</td>'+
                    '<td>'+index.remarks+'</td>'+
                    '<td><a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0);" onclick="del_order(this)">移除</a></td></tr>'
                ;
                $('#content').append(html);
            },'json');
        }else if(index.type == 'park'){
            console.log(index)
            var html =  '<tr id="'+m+'"><td>'+m+'</td>' +
                '<td>停车费</td>' +
                '<td>'+Math.round(index.park_price * 100) / 100+'</td>'+
                '<td>'+index.property_month_num+'个月</td>'+
                '<td>'+index.presented_property_month_num+'个月</td>'+
                '<td>'+index.end_time+'</td>'+
                '<td>—</td>'+
                '<td>'+index.remarks+'</td>'+
                '<td><a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0);" onclick="del_order(this)">移除</a></td></tr>'
            ;
            $('#content').append(html);
        }else{
            $.post("{pigcms{:U('get_fee')}",{usernum:index.usernum},function(result){
                switch (index.type) {
                    case 'water':
                        var name = '水费';
                        result.fee = result.water_price;
                        break;
                    case 'electric':
                        var name = '电费';
                        result.fee = result.electric_price;
                        break;
                    case 'gas':
                        var name = '燃气费';
                        result.fee = result.gas_price;
                        break;
                    case 'park':
                        var name = '停车费';
                        result.fee = result.park_price;
                        break;
                }
                var html =  '<tr id="'+m+'"><td>'+m+'</td>' +
                    '<td>'+name+'</td>' +
                    '<td>'+index.remarks+'</td>'+
                    '<td>'+Math.round(result.fee.fee * 100) / 100+'</td>' +
                    '<td>—</td>' +
                    '<td>—</td>' +
                    '<td>—</td>' +
                    '<td>—</td>'+
                    '<td><a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0);" onclick="del_order(this)">移除</a></td></tr>';
                $('#content').append(html);
            },'json');
        }
    }

    function del_order(orderid){
        var m = $(orderid).parent().parent().attr("id");
        m = m-1;
        delete datas[m];
        console.log(datas)
        $(orderid).parent().parent().remove();
    }

    function confirm() {
        layer.confirm('确认生成订单？', {
            btn: ['确定', '取消'] //可以无限个按钮
            ,
        }, function(index, layero){
            var x;
            for(x in datas){
                $.post("{pigcms{:U('owner_orders_add')}",datas[x],function(result){
                },'json');
            }
            window.location.href = "{pigcms{:U('Cashier/personal_order_list',array('bind_id'=>$pigcms_id))}";
        }, function(index){
            //按钮【按钮二】的回调
        });
    }

    function aaaa(content){
        if(content==''){
            content = '<span style=" color:red; padding:38%;">暂无数据</span>';
        }
        layer.open({
            type: 1,
            area: ['420px', '240px'],
            content: '<div style=" font-size: 20px;  padding: 15px;">'+String(content)+'</div>'
        });
    }
    function checkall(){
        if($('input[name=checkall]').attr('checked')=='checked'){
            $("input[name='orderid[]']").attr('checked','true');
        }else{
            $("input[name='orderid[]']").removeAttr('checked');
        }

    }

    $('#pay_type_1').change(function(){
        if ($(this).val()=='1') {
            $('.under_line').hide();
            $('.wx_code').hide();
            $('#go_pay').html('生成二维码');
        } else if($(this).val()=='2'){
            $('.under_line').show();
            $('.wx_code').hide();
            $('#go_pay').html('确认收款');
        }

    })

    //两小数相加解决精度出现的问题
    var numAdd=function (num1, num2) {//要相加的两个数
        var baseNum, baseNum1, baseNum2;
        try {
            baseNum1 = num1.toString().split(".")[1].length;
        } catch (e) {
            baseNum1 = 0;
        }
        try {
            baseNum2 = num2.toString().split(".")[1].length;
        } catch (e) {
            baseNum2 = 0;
        }
        baseNum = Math.pow(10, Math.max(baseNum1, baseNum2));
        return (num1 * baseNum + num2 * baseNum) / baseNum;
    };

    $('#cashier_pay').click(function(){
        //获得选中费用
        var ids='',url;
        var totalmoney=0;
        $("input[name='orderid[]']:checkbox").each(function(){
            if ('checked' == $(this).attr("checked")) {
                ids += $(this).val()+',';
                // totalmoney += $(this).parent('td').siblings().children('label').text()*1;
                totalmoney = numAdd(totalmoney,$(this).parent('td').siblings().children('label').text()*1);
            }
        })
        $('#totalmoney').html(totalmoney);
        $('#real_money').val(totalmoney);
        $('#orderids').val(ids);
        console.log(remarks);
        if (ids=='') {
            alert('请选择要收款项');
            return false;
        }
        url = $(this).attr('url');
        url += '&orderids='+ids;

        art.dialog({
            content: document.getElementById('cashier_pay_html'),
            id: 'handle',
            title:'收款',
            padding: 0,
            width: 500,
            height: 350,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            button: false,
            left: '50%',
            top: '38.2%',
            opacity:'0.4',
            cancel:function(){
                window.location.reload();
            }
        });
        return false;
    })

    $('#go_pay').click(function(){
        var ids = $('#orderids').val();
        var remarks = $('#remarks').val();
        var pay_type_1 = $('#pay_type_1').val();
        var pay_type = $('#pay_type').val();
        var real_money = $('#real_money').val();
        $('#real_money').attr('disabled','disabled');
        $('#pay_type_1').attr('disabled','disabled');
        $('#pay_type_1').css({"cursor":"not-allowed"});
        if (pay_type_1=='1') {
            //线上扫码支付
            $('#go_pay').hide();
        }
        if (pay_type_1=='2') {
            if (pay_type=='0') {
                alert('请选择收款方式');
                return false;
            }
        }
        $.post("{pigcms{:U('ajax_cashier_pay',array('pigcms_id'=>$pigcms_id))}",{orderids:ids,is_online:pay_type_1,pay_type:pay_type,remarks:remarks,real_money:real_money},function(result){
            console.log(result)
            if (result.status>0) {
                alert(result.msg);return false;
            } else if (result.status==0){
                // window.location.reload();
                if (pay_type_1=='1') {
                    //线上扫码支付
                    // console.log(result)
                    if (result.data) {
                        var qrcode_id = result.data.cashier_id*1 + 4200000000;
                        $('#wx_code').prop('src',"{pigcms{$config.site_url}/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id="+qrcode_id+"}");

                        $('.wx_code').show();
                    }else{
                        window.location.href="{pigcms{:U('history_cashier_order',array('bind_id'=>$pigcms_id))}";
                    }
                }
                if (pay_type_1=='2') {
                    window.location.href="{pigcms{:U('history_cashier_order',array('bind_id'=>$pigcms_id))}";
                }
            }
        },'json');
    })

</script>
<include file="Public:footer"/>
