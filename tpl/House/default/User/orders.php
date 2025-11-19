<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">业主管理</a>
            </li>
            <li class="active">已缴费列表</li>
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
                    <button class="btn btn-success" onclick="print_select()">打印账单</button>
                    <div class="alert alert-info" style="margin:10px 0;">
                        <b>总金额：{pigcms{$totalmoney|floatval}</b>　
                    </div>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                    <th width="5%">订单编号</th>
                                    <th width="5%">收银台订单编号</th>
                                    <th width="5%">缴费项</th>
                                    <th width="5%">已缴金额</th>
                                    <th width="10%">支付时间</th>
									<th width="10%">物业服务周期</th>
									<th width="10%">赠送物业服务时间</th>
									<th width="10%">服务时间</th>
                                    <th width="10%">账单状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$order_list">
                                    <volist name="order_list['order_list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td align="center"><input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.order_id}" onclick="return Dcheckbox($(this));"></td>
                                            <td>{pigcms{$vo.order_id}</td>
                                            <td><if condition="$vo['cashier_id']">{pigcms{$vo.cashier_id}<else/>--</if></td>
                                            <td><div class="tagDiv">{pigcms{$vo.order_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.money}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}</div></td>
											<td><div class="tagDiv"><if condition='$vo["property_month_num"]'>{pigcms{$vo.property_month_num}<else /><span class="red">暂无</span></if></div></td>
											<td><div class="tagDiv"><if condition='$vo["presented_property_month_num"]'>{pigcms{$vo.presented_property_month_num}<else /><span class="red">暂无</span></if></div></td>
											<td><div class="tagDiv"><if condition='$vo["property_time_str"]'>{pigcms{$vo.property_time_str}<elseif condition='$vo["custom_time_str"]'/>{pigcms{$vo.custom_time_str}<elseif condition='$vo["order_type"] eq park'/>{pigcms{$vo.park_time_str}<else /><span class="red">暂无</span></if></div></td>
                                            <td>
                                                <if condition="$vo.order_status eq 1">
                                                    <span style="color: green">正常</span>
                                                    <else />
                                                    <span style="color: red">已退款</span>
                                                </if>
                                            </td>
                                </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >您没有任何缴费记录。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$order_list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="print_template" style="display: none;">
    <table class="table table-striped table-bordered table-hover">
        <tbody>
            <tr >
                <th>打印模板：</th>
                <td>
                    <select name="template" id="template">
                        <option value="0">请选择模板</option>
                        <volist name="print_template" id="vo">
                            <option value="{pigcms{$vo.template_id}">{pigcms{$vo.title}</option>
                        </volist>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
$(".checkbox_all").on('click',function(){
    if($(this).is(':checked')){
        $("input[name='delCheckbox[]']").prop("checked",true);  
    }else{
        $("input[name='delCheckbox[]']").prop("checked",false);     
    }   
        
});
function Dcheckbox(e){
    var n=0;
    var len = $("input[name='delCheckbox[]']").length;
    for(var i=0;i<len;i++){
        if($("input[name='delCheckbox[]']")[i].checked) n++;        
    }
    if(n==len){
        $(".checkbox_all").prop("checked",true); 
    }else{
        $(".checkbox_all").prop("checked",false); 
    }
        
}
    
function print_select(obj){

    var len = $("input[name='delCheckbox[]']:checked").length;
    if(len<=0){
        layer.alert('请选择要打印的账单');
    }else{
        var length = $("input[name='delCheckbox[]']").length;
        var value="";
        for(var i=0;i<length;i++){
            if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;   
        }

        var href = "{pigcms{:U('payment_add',array('pigcms_id'=>$info['pigcms_id']))}"+'ids='+value;
        var post_url = '{pigcms{:U('print_start')}';
        art.dialog({
            content: document.getElementById('print_template'),
            id: 'handle',
            title:'选择打印模板',
            padding: 0,
            width: 260,
            height: 120,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            okVal:'确定',
            cancelVal:'取消',
            left: '50%',
            top: '50px',
            opacity:'0.4',
            ok:function (argument) {
                var template_id = $('#template').val();
                if (!template_id) {
                    alert('请选择打印模板');
                    return false;
                }
                art.dialog.open(post_url+"&ids="+value+"&template_id="+template_id,  
                {  
                    id: "mydialog",  
                    width: 900,  
                    height: 600,  
                    title: "打印预览",//如果不想要弹窗的标题，可以直接赋值为false  
                    lock: true,  
                    button:[{name:'关闭'}],
                    top: '50px',
                });  
                // window.open("post_url+"&cashier_id="+cashier_id+"&template_id="+template_id);
            }
        });
    }
}
</script>
<include file="Public:footer"/>