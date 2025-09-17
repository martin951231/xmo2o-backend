<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Library/visitor_list')}">访客登记</a>
			</li>
			<li class="active">添加访客</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                            	<div class="form-group">
									<label class="col-sm-1"><label for="visitor_type">选择访客分类：</label></label>
									<select name="visitor_type" id="visitor_type" class="col-sm-2">
                                        <volist name='visitor_type' id='vo'>
                                    	<option value="{pigcms{$key}">{pigcms{$vo}</option>
                                        </volist>
                                    </select>
								</div>
                                
                                <div class="form-group">
									<label class="col-sm-1"><label for="owner_phone">业主手机号码：</label></label>  <!--oninput="if(value.length>11)value=value.slice(0,11)"-->
									<input class="col-sm-2" size="20" name="owner_phone" id="owner_phone" type="number" min="1" onkeyup="this.value=this.value.replace(/\D/g,'')" value=""/>
                                    <label class="col-sm-3"><span class="red" id="owner_info"></span></label>
								</div>
                                <div class="form-group" id="single_floor_vacancy">
                                    <label class="col-sm-1"><label for="floor_name">业主住址：</label></label>
                                    <select class="col-sm-1"  name="single_id" id="single" style="width: 118px;">
                                        <option value="">请选择<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?></option>
                                        <volist name='single_list' id='single_data'>
                                            <option value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                                        </volist>
                                    </select>
                                    <select class="col-sm-1"  name="floor_id" id="floor" style="width: 118px;">
                                        <option value="">请选择<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?></option>
                                    </select>
                                    <select class="col-sm-1"  name="layer_id" id="layer" style="width: 118px;">
                                        <option value="">请选择楼层</option>
                                    </select>
                                    <select class="col-sm-1"  name="vacancy_id" id="vacancy" style="width: 118px;">
                                        <option value="">请选择<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?></option>
                                    </select>
                                    <input class="col-sm-2" size="20" name="owner_bind_id" id="owner_bind_id" type="hidden" value=""/>
                                    <input class="col-sm-2" size="20" name="user_id" id="user_id" type="hidden" value=""/>
                                </div>
                                <div id="input_html">

                                </div>
                                <div class="form-group owner-name" style=" display:none"> <!--style=" display:none"-->
									<label class="col-sm-1"><label for="owner_name">业主姓名：</label></label>
									<input class="col-sm-2" size="20" name="owner_name" id="owner_name" type="text" value="" readOnly="true"/>
                                    <label class="col-sm-3"><span class="red">可通过业主手机号或业主住址进行信息确认</span></label>
								</div>
<!--                                <div class="form-group owner-address" style=" display:none">-->
<!--									<label class="col-sm-1"><label for="owner_address">业主住址：</label></label>-->
<!--									<input class="col-sm-2" size="20" name="owner_address" id="owner_address" type="text" value="" readOnly="true"/>-->
<!--								</div>-->
								<div class="form-group">
									<label class="col-sm-1"><label for="visitor_name">访客姓名：</label></label>
									<input class="col-sm-2" size="20" name="visitor_name" id="visitor_name" type="text" value=""/>
                                    <label class="col-sm-3"><span class="red">&nbsp;&nbsp;可不填写</span></label>
								</div>
                                <div class="form-group">
									<label class="col-sm-1"><label for="visitor_phone">访客手机号码：</label></label>
									<input class="col-sm-2" size="20" name="visitor_phone" id="visitor_phone" type="text" value=""/>
								</div>
                                
                                <div class="form-group">
									<label class="col-sm-1">是否放行：</label>
									
										<label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="2" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
										<label style="padding-left:0px;"><input type="radio" class="ace" checked="checked" value="0" name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
								</div>
                                
                                <div class="form-group">
                                	<label class="col-sm-1"><label for="memo">备　　注：</label></label>
									<textarea id="memo" name="memo"style="width:281px; height:100px"></textarea>
                                    <span class="red">&nbsp;&nbsp;可不填写</span>
								</div>
							</div>

						
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
	var ajax_get_owner_info_url = "{pigcms{:U('ajax_get_owner_info')}";
	// $('#owner_phone').blur(function(){
	// 	$.post(ajax_get_owner_info_url,{'owner_phone':$('#owner_phone').val()},function(data){
	// 		var html='';
	// 		if(!data){
	// 			html += '<span class="red">无业主信息</span>';
	// 			$('#owner_name').val('');
	// 			$('.owner-name').hide();
	// 			$('#owner_info').empty().html(html);
	// 		}else{
	// 			if(data['status']){
	// 				$('.owner-name').show();
	// 				$('#owner_name').val(data['info']['name']);
	// 				$('#owner_info').empty();
	// 			}else{
	// 				html += '<span class="red">无业主信息</span>';
	// 				$('#owner_address,#owner_name').val('');
	// 				$('.owner-address,.owner-name').hide();
	// 				$('#owner_info').empty().html(html);
	// 			}
	// 		}
	// 	},'json')
	// })
    $('#owner_phone').blur(function(){
        var phone = $('#owner_phone').val();
        if(phone.length!=11){
            get_single();
            var obj=document.getElementById('input_html');obj.innerHTML='';
            $('.owner-name').hide();
            $('#owner_name,#owner_bind_id,#user_id').val('');
            layer.alert('请输入正确的手机号');
        }
        if(phone && phone.length==11) {
            $.post("{pigcms{:U('ajax_get_user_info_address')}", {'owner_phone': $('#owner_phone').val()}, function (data) {
                var html = '<option value="0">data["data"]["single_text"]</option>';
                var html_floor = '<option value="0">data["data"]["floor_text"]</option>';
                var html_layer = '<option value="0">data["data"]["layer_text"]</option>';
                var html_vacancy = '<option value="0">data["data"]["room_text"]</option>';
                var html_input = '<input type="hidden" name="single_id" value="'+data['data']['single_id'] +'">'
                    +'<input type="hidden" name="floor_id" value="'+data['data']['floor_id'] +'">'
                    +'<input type="hidden" name="layer_id" value="'+data['data']['layer_id'] +'">'
                    +'<input type="hidden" name="vacancy_id" value="'+data['data']['vacancy_id'] +'">';
                if (data.status == 1) {
                    $('.owner-name').show();
                    $('#owner_name').val(data['data']['name']);
                    $('#owner_phone').val(data['data']['phone']);
                    $('#owner_bind_id').val(data['data']['pigcms_id']);
                    $('#user_id').val(data['data']['uid']);
                    $('#input_html').html(html_input);
                    $('#owner_info').empty();
                    get_single();
                } else {
                    // $('.owner-name').hide();
                    var obj=document.getElementById('input_html');obj.innerHTML='';
                    $('#owner_name,#owner_bind_id,#user_id').val('');
                    layer.alert('暂无住户');
                }
            }, "JSON");
        }else{
            if(phone.length==11){
                get_single();
            }
            var obj=document.getElementById('input_html');obj.innerHTML='';
            $('.owner-name').hide();
            $('#owner_name,#owner_bind_id,#user_id').val('');
        }

    });
    //获取楼栋
    function get_single() {
        $.post("{pigcms{:U('get_single')}",{'owner_phone':$('#owner_phone').val()},function(data){
            var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>"+'</option>';
            var html_floor = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?>"+'</option>';
            var html_layer = '<option value="0">请选择楼层</option>';
            var html_vacancy = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>"+'</option>';
            if(data['status'] == 1){
                var obj=document.getElementById('single');obj.options.length=0;
                var obj=document.getElementById('floor');obj.options.length=0;
                var obj=document.getElementById('layer');obj.options.length=0;
                var obj=document.getElementById('vacancy');obj.options.length=0;
                var cat_list = data.single_list;
                for(var i in cat_list){
                    html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['single_name'] + '</option>';
                }
                $('#single').html(html);
                $('#floor').html(html_floor);
                $('#layer').html(html_layer);
                $('#vacancy').html(html_vacancy);
            } else {
                $("#single").html(html);
                $('#floor').html(html_floor);
                $('#layer').html(html_layer);
                $('#vacancy').html(html_vacancy);
            }
        },'JSON');
    }
    $('#single').change(function(){
        var single_id = $('#single').val();
        console.log(single_id)
        if(!single_id){
            return;
        }
        get_son_floor(single_id);
    });
    //获取单元
    function get_son_floor(single_id){
        var url = "{pigcms{:U('ajax_get_floor')}";
        var phone = $('#owner_phone').val()
        $.post(url,{'single_id':single_id,phone: phone},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?>"+'</option>';
            var htmls = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>"+'</option>';
            if(result['status'] == 1){
                var cat_list = result.cat_list;
                for(var i in cat_list){
                    html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
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
        var floor_id = $('#floor').val();
        if(!floor_id){
            return;
        }
        get_new_layer(floor_id);
    });
    //获取层
    function get_new_layer(floor_id){
        var url = "{pigcms{:U('ajax_get_layer')}";
        var phone = $('#owner_phone').val();
        $.post(url,{'floor_id':floor_id,phone: phone},function(result){
            console.log("result")
            console.log(result)
            var shtml = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>"+'</option>';
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
    $('#layer').change(function(){
        var layer_id = $('#layer').val();
        console.log(layer_id)
        if(!layer_id){
            return;
        }
        get_vacancy(layer_id);
    });
    //获取房间
    function get_vacancy(layer_id){
        var url = "{pigcms{:U('ajax_get_vacancy')}";
        var owner_phone = $('#owner_phone').val();
        $.post(url,{'layer_id':layer_id,phone: owner_phone},function(result){
            console.log("result")
            console.log(result)
            var html = '<option value="0">请选择'+"<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间')?>"+'</option>';
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
    //根据房间获取用户信息
    $('#vacancy').change(function(){
        var single_id = $('#single').val();
        var floor_id = $('#floor').val();
        var layer_id = $('#layer').val();
        var vacancy_id = $('#vacancy').val();
        var owner_phone = $('#owner_phone').val();
        if(vacancy_id){
            $.post("{pigcms{:U('ajax_get_user_info')}", {single_id:single_id,floor_id:floor_id,layer_id:layer_id,vacancy_id: vacancy_id}, function(data) {
                console.log("============");
                console.log(data);
                if(data.status==1){
                    $('.owner-name').show();
                    $('#owner_name').val(data['data']['name']);
                    $('#owner_phone').val(data['data']['phone']);
                    $('#owner_bind_id').val(data['data']['pigcms_id']);
                    $('#user_id').val(data['data']['uid']);
                    var obj=document.getElementById('input_html');obj.innerHTML='';
                    $('#owner_info').empty();
                }else{
                    $('.owner-name').hide();
                    $('#owner_name,#owner_phone,#owner_bind_id,#user_id').val('');
                    layer.alert('暂无住户');
                }
            },"JSON");
        }

    });

	function check_submit(){
		
		if($('#visitor_phone').val()==''){
			alert('访客手机号码不能为空！');
			return false;
		}
		
		
		if($('#owner_phone').val()==''){
			alert('业主手机号不能为空！');
			return false;
		}
		
		if($('#owner_name').val()==''){
			alert('业主不存在！');
			return false;
		}
		
		
		
		
		if(confirm('确认保存？')){
			return true;
		}else{
			return false;
		}
	}
</script>
<style>
    input::-webkit-outer-spin-button,

    input::-webkit-inner-spin-button { -webkit-appearance: none; }

    input[type="number"]{ -moz-appearance: textfield; }
</style>
<include file="Public:footer"/>