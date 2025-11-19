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
				<form method="get" action="{pigcms{:U('MeterRead/record_index')}" id="find-form">
                    <input type="hidden" name="c" value="MeterRead"/>
                    <input type="hidden" name="a" value="record_index"/>
<!--					<select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">-->
<!--						<option value="4" <if condition="$find_type eq 4">selected="selected"</if>>楼号</option>-->
<!--						<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>房间号</option>-->
<!--						<option value="2" <if condition="$find_type eq 2">selected="selected"</if>>收费项目</option>-->
<!--						<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>单元名称</option>-->
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
                    <input value="{pigcms{$_GET['project_name']}" name="project_name"  type="text" style="margin-right:10px;font-size:18px;height:42px;"/>

					
					时间筛选：
					<input type="text" name="property_endtime_start" class="input-text" value="{pigcms{$property_endtime_start}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="开始"/>
					&nbsp;—&nbsp;
					<input type="text" name="property_endtime_end" class="input-text" value="{pigcms{$property_endtime_end}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
 

					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('MeterRead/record_index')}'">重置</a>
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('move_export',$_GET)}'">Excel导出</a>
				</form>
			</div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>
									<th width="5%">楼号</th>
                                    <th width="10%">单元名称</th>
                                    <th width="10%">楼层</th>
                                    <th width="8%">房间号</th>

                                    <th width="10%">收费项目</th>
                                    <th width="15%">收费单价</th>
                                    <th width="12%">创建时间</th>
                                    <th width="4%">起度</th>
                                    <th width="5%">止度</th>
                                    <th width="8%">倍率</th>
                                    <th width="8%">公摊</th>

                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
								            <td><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.layer_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.room}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.project_name}</div></td>
								            <td><div class="tagDiv">{pigcms{$vo.unit_price}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>

                                            <td><div class="tagDiv">{pigcms{$vo.start_num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.end_num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.rate}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.pool}</div></td>
<!--                                            <td><div class="tagDiv">{pigcms{$vo.charge_name}</div></td>-->
<!--                                            <td><div class="tagDiv">{pigcms{$vo.cost_num}</div></td>-->
<!--								            <td><div class="tagDiv">{pigcms{$vo.note}</div></td>-->
<!--                                            <td class="button-column">-->
<!--                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('User/edit',array('pigcms_id'=>$vo['pigcms_id']))}">查看</a>&nbsp;-->



<!--												<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条信息？')){location.href=\'{pigcms{:U('meter_reading_del',array('id'=>$vo['id']))}\'}">删除</a>-->
<!--                                           </td>-->
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
