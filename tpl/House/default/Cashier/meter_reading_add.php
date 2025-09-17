<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">业主管理</a>
            </li>
            <li class="active">添加业主</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <style>
		.line-span { line-height:34px;}
		.none { display:none}
	</style>
    <div class="page-content">
        <div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">


<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="floor_name">单元名称：</label></label>-->
<!--								<select class="col-sm-2"  name="floor_id" id="floor_id">-->
<!--									<volist name='floor_list["list"]' id='floor_info'>-->
<!--										<option value="{pigcms{$floor_info.floor_id}">{pigcms{$floor_info.floor_name}&nbsp;&nbsp;--&nbsp;&nbsp;{pigcms{$floor_info.floor_layer}</option>-->
<!--									</volist>-->
<!--								</select>-->
<!--							</div>-->
<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="layer_room">层号房间号：</label></label>-->
<!--								<!--input class="col-sm-2" size="20" name="layer_num" id="layer_num" type="text" value=""/-->
<!--								<select class="col-sm-2" name="layer_num" id="layer_room" >-->
<!--									<option value="0">请选择</option>-->
<!--								</select>-->
<!---->
<!--							</div>-->
							<div class="form-group">
								<label class="col-sm-1"><label for="floor_name">选择房间：</label></label>

								<select class="col-sm-1"  name="single_id" id="single">
									<option value="">请选择楼栋：</option>
									<volist name='single_list' id='single_data'>
										<option value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
									</volist>
								</select>

								<select class="col-sm-1"  name="floor_id" id="floor">
									<option value="">请选择单元：</option>
								</select>
								<select class="col-sm-1"  name="layer_id" id="layer">
									<option value="">请选择楼层：</option>
								</select>

								<select class="col-sm-1"  name="vacancy_id" id="vacancy">
									<option value="">请选择房间：</option>
								</select>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="property_starttime">开始时间：</label></label>
								<input type="text" name="start_time" class="col-sm-2" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="开始时间"/>
							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="property_endtime">结束时间：</label></label>
								<input type="text" name="last_time" class="col-sm-2" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="结束时间"/>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="layer_room">收费项目：</label></label>

								<select class="col-sm-2" name="charge_name" id="charge_room" >
									<option value="0">请选择</option>

								</select>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">单价：</label></label>
								<input class="col-sm-2" size="20" name="unit_price" id="money" type="text" readonly="" value=""/>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="phone">起度：</label></label>
								<input class="col-sm-2" size="20" name="start_ammeter" id="start_ammeter" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="phone">止度：</label></label>
								<input class="col-sm-2" size="20" name="last_ammeter" id="last_ammeter" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="phone">倍率：</label></label>
								<input class="col-sm-2" size="20" name="rate" id="rate" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="phone">公摊：</label></label>
								<input class="col-sm-2" size="20" name="pool_ammeter" id="pool_ammeter" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="phone">备注：</label></label>
								<input class="col-sm-2" size="20" name="note" id="note" type="text" value=""/>

							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										添加
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	var cat_id = $('#single').val();
	get_son_category(cat_id);

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
		$.post(url,{'cat_id':cat_id},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择单元</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
				}
				$('#floor').html(html);
			} else {
				$("#floor").html(html);
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
        get_unit_fee(cat_id);

	});

	function get_vacancy(cat_id){
		var url = "{pigcms{:U('Service/ajax_get_vacancy')}";
		$.post(url,{'cat_id':cat_id},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择房间</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['pigcms_id'] +'">' + cat_list[i]['room'] + '</option>';
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
					html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
				}
				$('#layer').html(html);
				$('#vacancy').html(shtml);
			} else {
				$("#layer").html(html);
				$('#vacancy').html(shtml);
			}
		},'json');
	}

	function get_unit_fee(floor_id) {
        var fee_url = "{pigcms{:U('get_unit_fee')}";
        $.post(fee_url,{'floor_id':floor_id},function(data){
            var list = data['list'];
            var shtml = '';
            if(list){
                shtml += '<option>请选择</option>'
                for(var i in list){
                    shtml += '<option value="'+list[i]['title']+'" data-money="'+list[i]['money']+'">'+list[i]['nickname']+'</option>'
                }
            }else{
                shtml += '<option>请选择</option>'
            }
            $('#charge_room').html(shtml)
        },'json')
    }


</script>
<script type="text/javascript">


$('input[name="park_flag"]').change(function(){
	if($(this).val()==1){
		$('input[name="park_price"]').parent().show();
	}else{
		$('input[name="park_price"]').parent().hide();
	}
})


$('#usernum').blur(function(){
	var usernum = $(this).val();
	var url = "{pigcms{:U('ajax_user_bind')}";
	$.post(url,{'usernum':usernum},function(data){
		if(data.status==1){
			$('#usernum').next('span').show();
		}else{
			$('#usernum').next('span').hide();
		}
	},'json')
});


var url = "{pigcms{:U('ajax_get_layer')}";
$('#floor_id').change(function(){
	var floor_id = $(this).val();
	$.post(url,{'floor_id':floor_id},function(data){
		if(data['status'] == 0){
			alert(data.msg);
		}else{
			var list = data['list'];
			var shtml = '';
			
			if(list){
				shtml += '<option>请选择</option>'
				for(var i in list){
					shtml += '<option data-layer="'+list[i]['layer']+'" data-room="'+list[i]['room']+'" data-housesize="'+list[i]['housesize']+'" value="'+list[i]['pigcms_id']+'">'+list[i]['layer']+'&nbsp;&nbsp;--&nbsp;&nbsp;'+list[i]['room']+'</option>'
				}
			}else{
				shtml += '<option>请选择</option>'
			}
			$('#layer_room').html(shtml)
			$("#layer_num").val(0);
			$("#room_num").val(0);
			$("#vacancy_id").val(0);
		}
	},'json');
}).trigger('change');

$('#layer_room').change(function(){
	$('#housesize').val($(this).find(':selected').data('housesize'));
	$('#layer_num').val($(this).find(':selected').data('layer'));
	$('#room_num').val($(this).find(':selected').data('room'));
	$('#vacancy_id').val($(this).find(':selected').val());
})

$("#charge_room").change(function(){
	$('#money').val($(this).find(':selected').data('money'));
});

function check_submit(){
	if($('#usernum').val()==''){
		alert('业主编号不能为空！');
		return false;
	}
	
	if($('#user_name').val()==''){
		alert('业主名不能为空！');
		return false;
	}
	
	if($('#phone').val()==''){
		alert('手机号不能为空！');
		return false;
	}
	
	if($('#floor_name').val()==''){
		alert('单元名称不能为空！');
		return false;
	}
	
	if($('#floor_layer').val()==''){
		alert('楼号不能为空！');
		return false;
	}
	
	/* if($('#layer_num').val()==''){
		alert('层号不能为空！');
		return false;
	} 
	
	if($('#room_num').val()==''){
		alert('门牌号不能为空！');
		return false;
	}*/
	
	if($('#housesize').val()=='' || $('#housesize').val()<0){
		alert('房子平方不能为空 / 格式不正确');
		return false;
	}
	
	if($('#water_price').val()<0){
		alert('水费总欠费格式不正确');
		return false;
	}
	
	if($('#electric_price').val()<0){
		alert('电费总欠费格式不正确');
		return false;
	}
	
	if($('#gas_price').val()<0){
		alert('燃气费总欠费格式不正确');
		return false;
	}
	
	if($('#park_price').val()<0){
		alert('停车费总欠费格式不正确');
		return false;
	}
}
</script>
<include file="Public:footer"/>
