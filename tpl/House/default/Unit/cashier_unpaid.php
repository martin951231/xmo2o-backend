<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="javascript:void(0);">收费管理</a>
            </li>
            <li class="active">未缴账单</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
                .div-intro{ float:right; margin-top:20px}
                .div-intro-detail{width:10px; height:10px; background-color:red; float:left; margin-top:5px}
                .div-intro span{ float:left; margin-left:5px;}
            </style>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
            <table class="search_table" width="100%">
                    <tr>
                        <td>
                            <form action="{pigcms{:U('cashier_unpaid')}" method="get">
                                <input type="hidden" name="c" value="Unit"/>
                                <input type="hidden" name="a" value="cashier_unpaid"/>
                                住址:
                                <select class=""  name="single_id" id="single" style="height:42px;margin:10px;">
                                    <option value="">请选择楼栋</option>
                                    <volist name='single_list' id='single_data'>
                                        <option <if condition="$_GET['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                                    </volist>
                                </select>
                                <select class=""  name="floor_id" id="floor" style="height:42px;margin:10px;">
                                    <option <if condition="$_GET['floor_id'] eq '0'">selected="selected"</if> value="0">请选择单元</option>
                                </select>
                                <select class=""  name="layer_id" id="layer" style="height:42px;margin:10px;">
                                    <option <if condition="$_GET['layer_id'] eq '0'">selected="selected"</if> value="0">请选择楼层</option>
                                </select>
                                <select class=""  name="vacancy_id" id="vacancy" style="height:42px;margin:10px;">
                                    <option <if condition="$_GET['vacancy_id'] eq '0'">selected="selected"</if> value="0">请选择房间</option>
                                </select>
                                筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"  style="height:42px"/>&nbsp;&nbsp;
                                <select name="searchtype"  style="height:42px">
                                    <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option> 
                                    <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>业主姓名</option>
                                    <option value="usernum" <if condition="$_GET['searchtype'] eq 'usernum'">selected="selected"</if>>物业编号</option>
                                </select>&nbsp;&nbsp;
                                是否绑定微信：
                                <select name="is_bind_weixin" id="is_bind_weixin"  style="height:42px;">
                                    <option value="0" <if condition="$_GET['is_bind_weixin'] eq 0">selected="selected"</if>>全部</option>
                                    <option value="1" <if condition="$_GET['is_bind_weixin'] eq 1">selected="selected"</if>>是</option>
                                    <option value="2" <if condition="$_GET['is_bind_weixin'] eq 2">selected="selected"</if>>否</option>
                                </select>
                                &nbsp;&nbsp;
                                <button class="btn btn-success" type="submit">查询</button>&nbsp;&nbsp;
                                <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('cashier_unpaid')}'">重置</button>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>
            <button class="btn btn-success" onclick="send_weixin(1)" <if condition="!in_array(264,$house_session['menus'])">disabled="disabled"</if>>全部发送微信通知</button>&nbsp;
            <button class="btn btn-success" onclick="send_weixin(2)" <if condition="!in_array(265,$house_session['menus'])">disabled="disabled"</if>>群发微信通知</button>&nbsp;
            <button class="btn btn-success" onclick="location.href='{pigcms{:U('cashier_unpaid_export',$_GET)}'" <if condition="!in_array(267,$house_session['menus'])">disabled="disabled"</if>>导出全部账单</button>
           
            <div class="div-intro">
                <div class="div-intro-detail" style="background:orange"></div>
                <span>业主暂未绑定微信（注：暂时无法收到缴费提醒）</span>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div class="alert alert-info" style="margin:10px 0;">
                        固定统计不随筛选项改变：
                        <b>水费：{pigcms{$total.water_money|floatval}</b>　
                        <b>电费：{pigcms{$total.electric_money|floatval}</b>　
                        <b>燃气费：{pigcms{$total.gas_money|floatval}</b>　
                        <b>停车费：{pigcms{$total.park_money|floatval}</b>　　
                        <b>物业费：{pigcms{$total.property_money|floatval}</b>　
                        <b>自定义缴费项欠费汇总：{pigcms{$total.cunstom_money|floatval}</b>　
                        <b>合计：{pigcms{$total.total_money|floatval}</b>　
                    </div>
                        <div class="tab-pane active" id="txtstore">
                            <div id="shopList" class="grid-view">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
                                            <th width="5%">业主ID</th> 
                                            <th width="5%">业主名</th>
                                            <th width="5%">联系方式</th>
                                            <th width="10%">住址</th>
                                            <th width="8%">编号</th>
                                            <!-- <th width="5%">水费</th>
                                            <th width="5%">电费</th>
                                            <th width="8%">燃气费</th>
                                            <th width="5%">停车费</th> -->
                                            <th width="8%">合计</th>
                                            <th width="8%">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <if condition="$list['list']">
                                            <volist name="list['list']" id="vo">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <td align="center"><input type="checkbox" name="checkbox_one[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}" onclick="return Dcheckbox($(this));"></td>
                                                    <td>{pigcms{$vo.pigcms_id}</td>
                                                    <td>
                                                        <if condition='$vo["name"]'>
                                                            <if condition='$vo["openid"]'><div class="tagDiv">{pigcms{$vo.name}</div><else /><div class="tagDiv" style="color:orange">{pigcms{$vo.name}</div></if>
                                                        <else/>
                                                        --
                                                        </if>
                                                    </td>
                                                    <td><if condition='$vo["phone"]'>{pigcms{$vo.phone}<else/>--</if></td>
                                                    <td>{pigcms{$vo.address}</td>
                                                    <td>
                                                        <if condition="$vo['bind_number']">
                                                            {pigcms{$vo.bind_number}
                                                            <else/>
                                                            {pigcms{$vo.usernum}
                                                        </if>
                                                    </td>
                                                   <!--  <td>{pigcms{$vo.water_price}{pigcms{$config['Currency_txt']}</td>
                                                    <td>{pigcms{$vo.electric_price}{pigcms{$config['Currency_txt']}</td>
                                                    <td>{pigcms{$vo.gas_price}{pigcms{$config['Currency_txt']}</td>
                                                    <td>{pigcms{$vo.park_price}{pigcms{$config['Currency_txt']}</td> -->
                                                    <td style="color:red;">{pigcms{$vo.total}{pigcms{$config['Currency_txt']}</td>
                                                    <td> <a href="javascript:void(0);" class="label label-info detail" pid="{pigcms{$vo.pigcms_id}">明细</a>&nbsp;
                                                        <if condition="in_array(266,$house_session['menus'])">
                                                        <a class="label label-sm label-info" href="javascript:void(0);" onclick="send_weixin(3,this)" pid="{pigcms{$vo.pigcms_id}">发送微信通知</a>&nbsp;
                                                        </if>
                                                        <if condition="in_array(66,$house_session['menus'])">
                                                        <!-- <a  href="{pigcms{:U('personal_order_list',array('bind_id'=>$vo['pigcms_id']))}" style="width: 60px;" class="label label-sm label-info" title="去收款">去收款</a> -->
                                                        </if>

                                                    </td>
                                                </tr>
                                            </volist>
                                            <tr><td class="textcenter pagebar" colspan="11">{pigcms{$list.pagebar}</td></tr>
                                        <else />
                                            <tr class="odd"><td class="button-column" colspan="15" >没有任何数据。</td></tr>
                                        </if>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="font-size: 30px; padding: 15px;"></div>
<div id="fee_detail" class="grid-view" style="display: none;">
    <table class="table table-striped table-bordered table-hover">
        <tbody>
            <tr >
                <th style="width: 190px;">水费：</th>
                <td style="width: 190px;"><label id="water_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr >
                <th style="width: 190px;">电费：</th>
                <td style="width: 190px;"><label id="electric_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line">
                <th style="width: 190px;">燃气费：</th>
                <td style="width: 190px;"><label id="gas_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" >
                <th style="width: 190px;">停车费：</th>
                <td style="width: 190px;"><label id="park_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" >
                <th style="width: 190px;">物业费：</th>
                <td style="width: 190px;"><label id="property_price"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" >
                <th style="width: 190px;">自定义缴费项欠费汇总：</th>
                <td style="width: 190px;">
                    <label id="cunstom_money"></label>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div id="send_user_type_div" class="grid-view" style="display: none;">
    <table class="table table-striped table-bordered table-hover">
        <tbody>
            <tr >
                <th style="width: 150px; text-align: right;">发送给：</th>
                <td>
                    <select name="send_user_type" id="send_user_type" style="width: 250px">
                        <option value="1">仅业主</option>
                        <option value="2">业主和<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
                        <option value="3">仅<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- <img src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id={pigcms{$now_order['order_id']+700000000}" style="width:250px;height:250px;"/> -->
    <input type="hidden" name="pigcms_id" value="{pigcms{$vo.pigcms_id}">
</div>
<input type="hidden" name="checkedmoney" value="0">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    var Currency_txt  = "{pigcms{$config['Currency_txt']}";
    $(".checkbox_all").on('click',function(){
    
    if($(this).is(':checked')){
        $("input[name='checkbox_one[]']").prop("checked",true);  
    }else{
        $("input[name='checkbox_one[]']").prop("checked",false);     
    }   
        
});
    function Dcheckbox(e){
        var n=0;
        var len = $("input[name='checkbox_one[]']").length;
        for(var i=0;i<len;i++){
            if($("input[name='checkbox_one[]']")[i].checked) n++;        
        }
        if(n==len){
            $(".checkbox_all").prop("checked",true); 
        }else{
            $(".checkbox_all").prop("checked",false); 
        }
            
    }

     $('.detail').click(function(){
        //获得选中费用
        var pigcms_id = $(this).attr("pid");
        $.post("{pigcms{:U('Cashier/ajax_cashier_unpaid_detail')}",{pigcms_id:pigcms_id},function(result){
           if (result.status>0) {
                alert(result.msg);return false;
           } else if (result.status==0){
               $('#water_price').html(result.data.water_price);
               $('#electric_price').html(result.data.electric_price);
               $('#gas_price').html(result.data.gas_price);
               $('#park_price').html(result.data.park_price);
               $('#property_price').html(result.data.property_price);
               $('#cunstom_money').html(result.data.cunstom_money);

                var html = '';
                html += '<span style="color:orange">总金额：'+result.data.cunstom_money+'&nbsp;'+Currency_txt+'</span><br/>'
                if (result.data.cunstom_list.length > 0) {
                    var cunstom_list = result.data.cunstom_list;
                    for (var i = 0; i < cunstom_list.length; i++) {
                        var payment_money = cunstom_list[i]['payment_money'];
                        if (!payment_money) {
                            payment_money = 0;
                        }
                        html += ' <span>'+cunstom_list[i]['payment_name']+'：'+payment_money+'&nbsp;'+Currency_txt+'</span><br/>'
                    }
                    $('#cunstom_money').html(html)

                }
           }
        },'json');
        art.dialog({
            content: document.getElementById('fee_detail'),
            id: 'handle',
            title:'欠费详情',
            padding: 0,
            width: 450,
            height: 280,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            okVal:'确定',
            cancelVal:'取消',
            left: '50%',
            top: '38.2%',
            opacity:'0.4',
            ok:function (argument) {
            }
        });
        return false;
    })
    //发送微信通知
    var btns = false;
    function send_weixin(type,obj){
        var confirm_txt = "确认发送微信消息（缴费通知）";
        if (type==2) {
            var len = $("input[name='checkbox_one[]']:checked").length;
            if(len<=0){
               layer.alert('未选中任何业主');return false;
            }
        }
        // var send_user_type = 1;
         art.dialog({
            content: document.getElementById('send_user_type_div'),
            id: 'handle',
            title:'欠费详情',
            padding: 0,
            width: 450,
            height: 280,
            lock: true,
            resize: false,
            background:'black',
            fixed: false,
            okVal:'确定',
            cancelVal:'取消',
            left: '50%',
            top: '38.2%',
            opacity:'0.4',
            ok:function (argument) {
                if (btns) {
                    return false;
                }
                btns = true;
                layer.load(0, {shade: [0.6,'#fff']});
                var send_user_type = $('#send_user_type').val();
                if(type==1){
                    if(confirm(confirm_txt)){
                        var url = "{pigcms{:U('User/send_weixin_notice')}";
                        $.post(url , {'is_all':1,send_user_type:send_user_type},function(data){
                            btns = false;
                            layer.closeAll();
                            alert(data['msg']);
                        },'json')
                    } else {
                        btns = false;
                        layer.closeAll();
                    }
                } else if (type==2) {
                    var len = $("input[name='checkbox_one[]']:checked").length;
                    if(len<=0){
                        btns = false;
                        layer.closeAll();
                       layer.alert('未选中任何业主');return false;
                    }

                    var length = $("input[name='checkbox_one[]']").length;
                    var ids="";
                    for(var i=0;i<length;i++){
                        if($("input[name='checkbox_one[]']")[i].checked) ids += "," + $("input[name='checkbox_one[]']")[i].value;   
                    }

                    if(confirm(confirm_txt)){
                        var url = "{pigcms{:U('User/send_weixin_notice')}";
                        $.post(url , {'ids':ids,send_user_type:send_user_type},function(data){
                            btns = false;
                            layer.closeAll();
                            alert(data['msg']);
                        },'json')
                    } else {
                        btns = false;
                        layer.closeAll();
                    }
                } else if (type==3) {
                    if(confirm(confirm_txt)){
                        var pigcms_id = $(obj).attr("pid");
                        var url = "{pigcms{:U('User/send_weixin_notice')}";
                        $.post(url , {'pigcms_id':pigcms_id,send_user_type:send_user_type},function(data){
                            btns = false;
                            layer.closeAll();
                            alert(data['msg']);
                        },'json')
                    } else {
                        btns = false;
                        layer.closeAll();
                    }
                }
            }
        });
    }
</script>
<script  type="text/javascript" language="javascript">
    var cat_id = $('#single').val();
    get_son_category(cat_id);
    var floor_id = "{pigcms{$_GET['floor_id']}";
    console.log("============",floor_id);
    // get_new_layer(floor_id);

    var layer_id = "{pigcms{$_GET['layer_id']}";
    console.log("============",layer_id);
    // get_vacancy(layer_id)




    $('#single').change(function(){

        var cat_id = $('#single').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
        get_son_category(cat_id);

    });

    function get_son_category(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_floor')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择单元</option>';
            var htmls = '<option value="0">请选择楼层</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){

                    if (cat_list[i]['floor_id'] == "{pigcms{$_GET['floor_id']}" ) {
                        console.log(cat_list[i]['floor_id'])
                        console.log("{pigcms{$_GET['floor_id']}")

                        html +='<option value="'+ cat_list[i]['floor_id'] +'" selected=selected>' + cat_list[i]['floor_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
                    }
                }
                $('#floor').html(html);
                if (floor_id && floor_id>0) {
                    get_new_layer(floor_id);
                } else {
                    $('#layer').html(htmls);
                }
            } else {
                $("#floor").html(html);
                $('#layer').html(htmls);
            }
        },'json');
    }


    $('#floor').change(function(){

        var cat_id = $('#floor').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
//		get_vacancy(cat_id);
        get_new_layer(cat_id);

    });

    function get_vacancy(cat_id){
        var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
        $.post(url,{'cat_id':cat_id,'type':1},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择房间</option>';
            if(result['status'] == 1){
                var cat_lists = result.cat_list;
                for(var j in cat_lists){

                    if (cat_lists[j]['pigcms_id'] == "{pigcms{$_GET['vacancy_id']}") {
                        html +='<option value="'+ cat_lists[j]['pigcms_id'] +'" selected=selected>' + cat_lists[j]['room'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_lists[j]['pigcms_id'] +'">' + cat_lists[j]['room'] + '</option>';
                    }
                }
                $('#vacancy').html(html);
            } else {
                $("#vacancy").html(html);
            }
        },'json');
    }

    $('#layer').change(function(){

        var cat_id = $('#layer').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
        get_vacancy(cat_id);

    });

    function get_new_layer(cat_id){
        var url = "{pigcms{:U('Service/get_new_layer')}";
        $.post(url,{'cat_id':cat_id},function(result){
            console.log("result")
            console.log(result)
            var shtml = '<option value="0">请选择房间</option>';
            var html = '<option value="0">请选择楼层</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){
                    if (cat_list[i]['id'] == "{pigcms{$_GET['layer_id']}" ) {
                        html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['layer_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
                    }
                }
                $('#layer').html(html);
                if (layer_id && layer_id>0) {
                    get_vacancy(layer_id)
                } else {
                    $('#vacancy').html(shtml);
                }
            } else {
                $("#layer").html(html);
                $('#vacancy').html(shtml);
            }
        },'json');
    }

</script>
<include file="Public:footer"/>
