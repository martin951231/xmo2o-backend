<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">业主管理</a>
            </li>
            <li class="active">业主列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" action="{pigcms{:U('Cashier/meter_reading')}" id="find-form">
                    <input type="hidden" name="c" value="Cashier"/>
                    <input type="hidden" name="a" value="meter_reading"/>
<!--					<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">-->
<!--						<option value="4" <if condition="$find_type eq 4">selected="selected"</if>>楼号</option>-->
<!--						<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>房间号</option>-->
<!--						<option value="2" <if condition="$find_type eq 2">selected="selected"</if>>收费项目</option>-->
<!--						<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>单元名称</option>-->
<!---->
<!---->
<!--					</select>-->
<!--					<input value="{pigcms{$find_value}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>-->
					&nbsp;&nbsp;
                    地址:
                    <select class=""  name="single_id" id="single" style="height:42px;">
                        <option value="">请选择楼栋</option>
                        <volist name='single_list' id='single_data'>
                            <option <if condition="$_GET['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                        </volist>
                    </select>
                    <select class=""  name="floor_id" id="floor" style="height:42px;">
                        <option <if condition="$_GET['floor_id'] eq '0'">selected="selected"</if> value="0">请选择单元</option>
                    </select>
                    <select class=""  name="layer_id" id="layer" style="height:42px;">
                        <option <if condition="$_GET['layer_id'] eq '0'">selected="selected"</if> value="0">请选择楼层</option>
                    </select>
                    <select class=""  name="vacancy_id" id="vacancy" style="height:42px;">
                        <option <if condition="$_GET['vacancy_id'] eq '0'">selected="selected"</if> value="0">请选择房间</option>
                    </select>
                    收费项目：
                    <select name="charge_name" style="margin-right:10px;height:42px;">
                        <option value="" <if condition="$_GET['charge_name'] eq ''">selected="selected"</if>>所有</option>
                        <option value="水费" <if condition="$_GET['charge_name'] eq '水费'">selected="selected"</if>>水费</option>
                        <option value="电费" <if condition="$_GET['charge_name'] eq '电费'">selected="selected"</if>>电费</option>
                        <option value="燃气费" <if condition="$_GET['charge_name'] eq '燃气费'">selected="selected"</if>>燃气费</option>
                    </select>
					
					时间筛选：
					<input type="text" name="property_endtime_start" class="input-text" value="{pigcms{$property_endtime_start}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="开始"/>
					&nbsp;—&nbsp;
					<input type="text" name="property_endtime_end" class="input-text" value="{pigcms{$property_endtime_end}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
 

					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('Cashier/meter_reading')}'">重置</a>
				</form>
			</div>
        	<button class="btn btn-success" onclick="addUser()" <if condition="!in_array(92,$house_session['menus'])">disabled="disabled"</if>>录入数据</button>&nbsp;
        	<button class="btn btn-success" onclick="importUser()" <if condition="!in_array(92,$house_session['menus'])">disabled="disabled"</if>>导入抄表单</button>&nbsp;

			&nbsp;
			<a onclick="location.href='{pigcms{:U('user_export',$_GET)}'" class="btn btn-success">导出抄表单</a>


<!--            <button class="btn btn-success" onclick="sync_user_data()">同步钥匙</button>-->


            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>
                                	<th width="3%" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
									<th width="5%">楼号</th>
									<th width="8%">单元名称</th>
                                    <th width="8%">楼层</th>
                                    <th width="8%">房间号</th>

                                    <th width="8%">收费项目</th>
                                    <th width="8%">收费单价</th>
                                    <th width="8%">时间（开始 - 结束）</th>
                                    <th width="4%">起度</th>
                                    <th width="5%">止度</th>
                                    <th width="8%">倍率</th>
                                    <th width="5%">公摊</th>
<!--                                    <th width="5%">损耗</th>-->
                                    <th width="5%">数量</th>
                                    <th width="8%">添加时间</th>
                                    <th width="5%">备注</th>
                                    <th class="button-column" width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">

                                            <td align="center"><input type="checkbox" name="delCheckbox[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.pigcms_id}" onclick="return Dcheckbox($(this));"></td>
								            <td><div class="tagDiv">{pigcms{$vo.single_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.layer_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.room}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.charge_name}</div></td>
								<td><div class="tagDiv">{pigcms{$vo.unit_price}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.start_name}&nbsp;至&nbsp;{pigcms{$vo.last_name}</div></td>

                                            <td><div class="tagDiv">{pigcms{$vo.start_ammeter}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.last_ammeter}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.rate}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.pool_ammeter}</div></td>
<!--                                            <td><div class="tagDiv">{pigcms{$vo.charge_name}</div></td>-->
                                            <td><div class="tagDiv">{pigcms{$vo.cost_num}</div></td>
                                            <td><div class="tagDiv">
<!--                                                    {pigcms{$vo.add_time}-->
                                                    <if condition="!empty($vo.add_time) && $vo.add_time neq 0">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}<else/> --</if>
                                                </div></td>
								            <td><div class="tagDiv">{pigcms{$vo.note}</div></td>
                                            <td class="button-column">
<!--                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('User/edit',array('pigcms_id'=>$vo['pigcms_id']))}">查看</a>&nbsp;-->



												<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条信息？')){location.href=\'{pigcms{:U('meter_reading_del',array('id'=>$vo['id']))}\'}">删除</a>
                                           </td>
                                        </tr>
                                    </volist>
<!--                                    <if condition="in_array(269,$house_session['menus'])">-->
<!--                                    <tr>-->
<!--                                    	<td colspan="14">&nbsp;&nbsp;&nbsp;<button class=" btn delete_class">删除选中</button></td>-->
<!--                                    </tr>-->
<!--                                	</if>-->
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <div style="float: right; padding-left: 5px; ">
	                        <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
	                        <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
	                        <script>
	                        	function checkPage(){
	                        		var page = $("#page").val();
	                        		if(page == ''){
	                        			return false;
	                        		}
	                        		  location.href = "{pigcms{:U('Cashier/meter_reading')}"+"&page="+page;

	                        	}
	                        </script>
                        </div>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	var static_path = "{pigcms{$static_path}";
</script>

<style>
	.modal-content{
		width:400px;	
	}
	.modal-body{
		text-align:center;
		padding:20px 15px;
	}
</style>
<!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title" id="myModalLabel">
					警告提示
				</h4>
			</div>
			<div class="modal-body">
				删除业主后，业主下的<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if>会被一起删除，绑定的房间会被释放，确定删除？
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary class-delete">
					确定
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<div class="modal fade" id="myModal_alert" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="myModalLabel">提示信息</h4>
			</div>
			<div class="modal-body">
				请先选择要执行的业主！
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary class-tip">确定</button>
			</div>
		</div>
	</div>
</div>
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
                <td style="width: 190px;"><label id="cunstom_money"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <tr class="under_line" >
                <th style="width: 190px;">合计：</th>
                <td style="width: 190px;"><label id="total_money"></label>&nbsp;{pigcms{$config['Currency_txt']}</td>
            </tr>
            <if condition="in_array(118,$house_session['menus'])">
            <tr class="under_line" >
                <td colspan="2" style="width: 190px;text-align: center;">
                	<a  class="label label-sm label-info" title="欠费明细" href="javascript:pay_detail();">每月账单欠费明细</a>
                </td>
                
            </tr>
        	</if>
        </tbody>
    </table>
    <input type="hidden" id="now_id" value="">
    <input type="hidden" id="usn" value="">
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" language="javascript">

$(".checkbox_all").on('click',function(){
	
	if($(this).is(':checked')){
		$("input[name='delCheckbox[]']").prop("checked",true);  
	}else{
		$("input[name='delCheckbox[]']").prop("checked",false);  	
	}	
		
});

function pay_detail(){
	var usernum = $("#usn").val();
	var url = "{pigcms{:U('User/pay_detail')}";
	window.location.href = url + '&usernum='+usernum;
}
$('.detail').click(function(){
        //获得选中费用
        var pigcms_id = $(this).attr("pid");
        var usernum = $(this).attr("usn");
        $("#now_id").val(pigcms_id);
        $("#usn").val(usernum);
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
                $('#total_money').html(result.data.total);
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

$(".class-tip").on('click',function(){
	$('#myModal_alert').modal('hide');	
});

$(".delete_class").on('click',function(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		$('#myModal').modal({
			keyboard: false,
			backdrop: 'static'
		})	
	}
});	

 var addbtn = [
    {
        name:'添加',
        callback:function () {
            var iframe = this.iframe.contentWindow;
            if (iframe.document.body) {
                var submits=iframe.document.getElementById('dosubmit');
                submits.click();
                var that = this;
                setTimeout(function(){
                    that.close();
                    var win = art.dialog.open.origin;//来源页面  
                    win.location.reload();  
                }, 1000); 
                return false;
            }else{
                return false;
            }
        },
        focus:true
    },
    {name:'关闭',
        callback:function () {
           window.location.reload(); 
        }}
];

function payment_add(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		var length = $("input[name='delCheckbox[]']").length;
		var value="";
		for(var i=0;i<length;i++){
			if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;	
		}

		var href = "{pigcms{:U('payment_add',array('pigcms_id'=>$info['pigcms_id']))}"+'uid='+value;
		art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'查看',
			padding: 0,
			width: 900,
			height: 603,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
	}
}

function property_time_edit(){
	var len = $("input[name='delCheckbox[]']:checked").length;
	if(len<=0){
		$('#myModal_alert').modal({
			keyboard: false,
			backdrop: 'static'
		})
	}else{
		var length = $("input[name='delCheckbox[]']").length;
		var value="";
		for(var i=0;i<length;i++){
			if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;	
		}

		var href = "{pigcms{:U('property_time_edit')}"+'&idList='+value;
		art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'批量修改物业时间',
			padding: 0,
			width: 700,
			height: 500,
			lock: true,
			button: addbtn,
		});
	}
}


$('.bind_info').click(function(){
	
	return false;
});



$(".class-delete").on('click',function(){
	$('#myModal').modal('hide');
	var length = $("input[name='delCheckbox[]']").length;
	var value="";
	for(var i=0;i<length;i++){
		if($("input[name='delCheckbox[]']")[i].checked) value += "," + $("input[name='delCheckbox[]']")[i].value;	
	}
	value = value.substring(1);
	if(value){
		var village_id = "{pigcms{$village_info.village_id}";
		var url = "{pigcms{:U('User/ajaxDelete')}";
		$.post(url,{'village_id':village_id,'arr_pigcms_id':value},function(data){
			if(data['status']){
				location.reload();
			}else{
				alert(data['msg']);
			}
		},'json')
	}	
		
});

function importUser(){
	window.location.href = "{pigcms{:U('Cashier/meter_reading_import')}";
}

function addUser(){
	window.location.href = "{pigcms{:U('Cashier/meter_reading_add')}";
}


function send_property_one(pigcms_id , usernum){
	if(confirm('确认发送微信消息？')){
		var url = "{pigcms{:U('User/send_property')}";
		$.post(url,{'pigcms_id':pigcms_id,'usernum':usernum},function(data){
			if(data['status']){
				alert(data['msg']);
			}
		},'json')
	}
}

function sync_user_data() {
    layer.open({
        title: '提示'
        ,type:1
        ,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
    });

    $.post('{pigcms{:U("Face_door/sync_user_data")}',{type:'all'},function(data){
        console.log(data)
        layer.closeAll();
        if (data.code==1) {
            that.html('已同步')
        }else{
            layer.alert(data.msg)
        }
    },'json')
    return false;
}


$('.bind_info').click(function(){
	art.dialog.open($(this).attr('href'),{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'查看',
		padding: 0,
		width: 800,
		height: 603,
		lock: true,
		resize: false,
		background:'black',
		button: null,
		fixed: false,
		close: null,
		left: '50%',
		top: '38.2%',
		opacity:'0.4'
	});
	return false;
});
</script>
<script type="text/javascript">
    var cat_id = $('#single').val();
    console.log("++++",cat_id);
    get_son_category(cat_id);

    var floor_id = "{pigcms{$_GET['floor_id']}";
    console.log("============",floor_id);
    get_new_layer(floor_id);

    var layer_id = "{pigcms{$_GET['layer_id']}";
    console.log("============",layer_id);
    get_vacancy(layer_id)



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
            var htmls = '<option value="0">请选择房间</option>';
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

                $('#vacancy').html(htmls);
            } else {
                $("#floor").html(html);
                $('#vacancy').html(htmls);
            }
        },'json');
    }


    $('#floor').change(function(){

        var cat_id = $('#floor').val();

        console.log(cat_id)
        if(!cat_id){
            return;
        }
//        get_vacancy(cat_id);
        get_new_layer(cat_id);

    });

    function get_vacancy(cat_id){

        console.log("guojie")
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
                    console.log("get_new_layer");
                    console.log(cat_list[i]['id']);
                    console.log("{pigcms{$_GET['layer_id']}");

                    if (cat_list[i]['id'] == "{pigcms{$_GET['layer_id']}" ) {
                        console.log("get_new_layer");
                        console.log(cat_list[i]['id']);
                        html +='<option value="'+ cat_list[i]['id'] +'" selected="selected">' + cat_list[i]['layer_name'] + '</option>';
                    }else{
                        html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
                    }
                }
                $('#layer').html(html);
                $('#vacancy').html(shtml);
            } else {
                $("#layer").html(html);
                $('#vacancy').html(shtml);
            }
        },'json');
    }



</script>
<include file="Public:footer"/>
