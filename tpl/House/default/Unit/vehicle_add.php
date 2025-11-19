<include file="Public:header"/>
<style>
    .binding_status{
        display: none;
    }
    .bin_room_user{
        margin-left: 80px !important;
    }
    .bin_room_user span:first-child{
        display: inline-block;min-width: 138px;margin-right:5px;
    }
</style>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/vehicle_management')}">车辆管理</a>
            </li>
            <li class="active">添加车辆</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                
								<div class="form-group">
									<label class="col-sm-1"><label for="car_number">车辆类型</label></label>
									<select name="car_type" id="car_type" class="fl">
                                        <volist name="car_type_arr" id="item">
                                            <option value="{pigcms{$item['value']}">{pigcms{$item['name']}</option>
                                        </volist>
									</select>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="car_number">车牌号码</label></label>
									<select name="province" id="province" class="fl">
										<option value="">--请选择省份--</option>
										<volist name="city_arr" id="vo">
										<option value="{pigcms{$vo}">{pigcms{$vo}</option>
										</volist>
									</select>
									<input class="col-sm-2" size="20" name="car_number" id="car_number" type="text"  value="" />
									<label style="margin-left:20px;"><span class="red">*必填 仅支持一个车牌号</span></label>
								</div>

                                <!--  todo 开启新版-->
                                <if condition="$new_charge_time eq 1">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="car_number">绑定对象</label></label>
                                    <select name="binding_type" id="binding_type" class="fl" <if condition="$source eq user" >disabled</if>>
                                        <option value="0">请选择</option>
                                        <option value="1" >房间</option>
                                        <option value="2" <if condition="$source eq user" >selected</if> >业主</option>
                                    </select>
                                </div>
                                <div class="form-group binding_status" id="binding_room" style="position: relative">
                                    <label class="col-sm-1"><label for="car_number">绑定房间</label></label>
                                    <cascader id="binding_obj"></cascader>
                                    <label ><span class="red">*必填</span></label>
                                </div>
                                <div class="form-group bin_room_user" ></div>
                                <div class="binding_status" id="binding_owner">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="car_user_phone">车主手机号</label></label>
                                        <input class="col-sm-2" size="20" name="car_user_phone" id="car_user_phone" type="text"  value="" />
                                        <label style="margin-left:20px;"><span class="red">*必填</span></label>
                                    </div>
                                    <if condition="$new_charge_time eq 1">
                                    <div class="form-group" style="position: relative;">
                                        <div id="searchBox" class="" style="background-color: #1686ef;color: white;width: 190px;margin-left:60px;position: absolute;top: -12px;left: 35px;" ></div>
                                    </div>
                                    </if>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="car_user_name">车主姓名</label></label>
                                        <input class="col-sm-2" size="20" name="car_user_name" id="car_user_name" type="text"  value="" />
                                        <label style="margin-left:20px;"><span class="red">*必填</span></label>
                                    </div>
                                </div>
                                    <else/>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="car_user_phone">车主手机号</label></label>
                                        <input class="col-sm-2" size="20" name="car_user_phone" id="car_user_phone" type="text"  value="" />
                                        <label style="margin-left:20px;"><span class="red">*必填</span></label>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="car_user_name">车主姓名</label></label>
                                        <input class="col-sm-2" size="20" name="car_user_name" id="car_user_name" type="text"  value="" />
                                        <label style="margin-left:20px;"><span class="red">*必填</span></label>
                                    </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="garage_num">与车主关系:</label></label>
                                    <select name="relationship" id="relationship" class="fl col-sm-2">
                                        <option value="" style="display: none;">请选择与车主关系</option>
                                        <volist name="relationship" id="vo">
                                            <option value="{pigcms{$key}" >{pigcms{$vo}</option>
                                        </volist>
                                    </select>
                                    <label><span class="red">*必填</span></label>
                                </div>

								<div class="form-group">
									<label class="col-sm-1"><label for="garage_num">车库</label></label>
									<select name="garage_id" id="garage_id" class="fl">
										<option value="">--请选择车库--</option>
										<volist name="garage_list" id="vo">
										<option value="{pigcms{$vo.garage_id}">{pigcms{$vo.garage_num}</option>
										</volist>
									</select>
								</div>
								
								<div class="form-group position_div" style="position: relative;display:none;">
									<label class="col-sm-1"><label for="position_num">车位号</label></label>
									<div class="col-sm-2" style="padding:0px ;position:relative">
										<input class="col-sm-2" size="20" name="position_num" id="position_num" type="text"  value="" autocomplete="off" style="width:100%" placeholder="输入车位号搜索"/>
										<input id="car_position_id" type="hidden" value="" />
										<div id="dropdown-menu" class="dropdown-menus " style="display:none">
										</div>
									</div>
									<label style="margin-left:20px;">选填</label>&nbsp;&nbsp;&nbsp;<span style="color:red;">输入车位号弹出蓝色弹层选择一个对应绑定车位号就会对应显示切换输入值支持多个选择绑定多个车位</span>
								</div>
                                <div class="form-group position_div" style="position: relative;display:none;">
                                    <label class="col-sm-1"><label for="position_num">绑定车位号</label></label>
                                    <div class="col-sm-2" style="padding:0px ;position:relative">
                                        <input type="text" readonly id="car_re" value="" style="width: 300px;">
                                        <input type="hidden" id="position_ids" name="car_position_id_path" value="" style="width: 300px;">
                                    </div>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="car_stop_num">停车卡号</label></label>
									<input class="col-sm-2" size="20" name="car_stop_num" id="car_stop_num" type="text"  value="" />
									<label style="margin-left:20px;">选填</label>
								</div>

                                <input  name="obj_id" id="obj_id" type="hidden"  value=""/>
                                <if condition="$new_charge_time eq 0">
								<div class="form-group" id="form_group_uid">
									<label class="col-sm-1"><label for="position_num">绑定业主</label></label>
									<input  name="find_value" id="find_value" type="text" autocomplete="off" value="" style="width:100px;margin-right:10px;"/>
									<input  name="user_id" id="user_id" type="hidden"  value="{pigcms{$user_id}"/>
									<label style="margin-left:20px;"><span class="red">请输入手机号检索</span></label>
								</div>
                                <div class="form-group" style="position: relative;">
                                    <div id="searchBox" class="" style="background-color: #1686ef;color: white;width: 190px;margin-left:60px;position: absolute;top: -12px;left: 35px;" ></div>
                                </div>
                                </if>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="end_time">停车到期时间</label></label>
                                        <input  type="text" name="end_time" id="end_time" class="col-sm-2 input-text" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="停车到期时间"/>
                                    </div>

                                <if condition="$village_park_config['park_versions'] eq 2">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="garage_num">停车卡类:</label></label>
                                        <select name="parking_car_type" id="parking_car_type" class="fl col-sm-2">
                                            <option value="" style="display: none;">--请选择收费类型--</option>
                                            <volist name="parking_car_type_arr" id="vo">
                                                <option value="{pigcms{$key}">{pigcms{$vo}</option>
                                            </volist>
                                        </select>
                                    </div>
                                </if>

								<div class="form-group">
									<label class="col-sm-1"><label for="garage_num">车辆颜色:</label></label>
									<select name="car_color" id="car_color" class="fl">
										<option value="">--请选择车辆颜色--</option>
										<volist name="car_color_list" id="vo">
											<option value="{pigcms{$vo.id}">{pigcms{$vo.lable}</option>
										</volist>
									</select>
								</div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="car_stop_num">品牌型号</label></label>
                                        <input class="col-sm-2" size="20" name="car_brands" id="car_brands" type="text"  value="" />
                                        <label>选填</label>
                                    </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="car_stop_num">车辆设备号</label></label>
									<input class="col-sm-2" size="20" name="equipment_no" id="equipment_no" type="text"  value="" />
									<label>选填</label>
								</div>
							</div>
							
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info submit_info" type="button">
										<i class="ace-icon fa fa-check bigger-110"></i>
										增加
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<style type="text/css">
	#searchBox{
		font-family: Monospaced Number,Chinese Quote,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Hiragino Sans GB,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,sans-serif;
		line-height: 1.5;
		color: rgba(0,0,0,.65);
		margin: 0;
		padding: 0;
		list-style: none;
		-webkit-box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		border-radius: 4px;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		outline: none;
		font-size: 14px;
		min-width: auto;right:0px;left:0px;
		/*top:350px;*/
        top:300px;
		position:absolute;
		z-index: 10;
		display: block;
		background: #fff;
		width: 12%;
		margin-left: 8%;
	}
	#searchBox .ant-select-dropdown-menu-item-active {
		background-color: #e6f7ff;
	}
	#searchBox .ant-select-dropdown-menu-item {
		position: relative;
		display: block;
		padding: 5px 12px;
		line-height: 22px;
		font-weight: 400;
		color: rgba(0,0,0,.65);
		white-space: nowrap;
		cursor: pointer;
		overflow: hidden;
		text-overflow: ellipsis;
		-webkit-transition: background .3s ease;
		transition: background .3s ease;
	}
	#searchBox .ant-select-dropdown-menu-item{line-height:35px;list-style: none;border:none !important;}
	#searchBox .ant-select-dropdown-menu-item:hover {
		background-color: #e6f7ff !important;
	}

	#searchBox .ant-select-dropdown-menu-item:first-child {
		border-radius: 4px 4px 0 0;
	}

	#searchBox .ant-select-dropdown-menu-item:last-child {
		border-radius: 0 0 4px 4px
	}

	#bind-user{
		overflow: hidden;
	}

</style>
<style>
	#dropdown-menu{
		font-family: Monospaced Number,Chinese Quote,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Hiragino Sans GB,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,sans-serif;
		line-height: 1.5;
		color: rgba(0,0,0,.65);
		margin: 0;
		list-style: none;
		-webkit-box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		border-radius: 4px;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		outline: none;
		font-size: 14px;
		min-width: auto;right:0px;left:0px;
		/*top:33px;*/
		position:absolute;
		z-index: 10;
		display: block;
        padding: 10px;
        margin-top: 40px;
        background-color: #1686ef;
        color: white;
        cursor: pointer;
	}
	#dropdown-menu .ant-select-dropdown-menu-item-active {
		background-color: #e6f7ff;
	}
	#dropdown-menu .ant-select-dropdown-menu-item {
		position: relative;
		display: block;
		padding: 5px 12px;
		line-height: 22px;
		font-weight: 400;
		color: rgba(0,0,0,.65);
		white-space: nowrap;
		cursor: pointer;
		overflow: hidden;
		text-overflow: ellipsis;
		-webkit-transition: background .3s ease;
		transition: background .3s ease;
	}
	#dropdown-menu .ant-select-dropdown-menu-item{line-height:35px;list-style: none;border:none !important;}
	#dropdown-menu .ant-select-dropdown-menu-item:hover {
		background-color: #e6f7ff
	}

	#dropdown-menu .ant-select-dropdown-menu-item:first-child {
		border-radius: 4px 4px 0 0;
	}

	#dropdown-menu .ant-select-dropdown-menu-item:last-child {
		border-radius: 0 0 4px 4px
	}

</style>
<link href="{pigcms{$static_public}layui/css/layui.css" rel="stylesheet">
<script src="{pigcms{$static_public}layui/layui.all.js"></script>
<script>
    var source='{pigcms{$source}';
    var user_id='{pigcms{$user_id}';
    var user_name='{pigcms{$user_bind.name}';
    var user_phone='{pigcms{$user_bind.phone}';
    if(source == 'user'){
        $('#binding_room').hide();
        $("#binding_type").attr("disabled", true);
        $("#binding_type").val(2)
        $('#binding_owner').show();
        $('.bin_room_user').html('');
        $("#obj_id").val(user_id);
        $("#car_user_name").val(user_name);
        $("#car_user_phone").val(user_phone);
    }
</script>
<script>
    layui.config({
        base: '{pigcms{$static_public}layui/lay/ajaxCascader/'
        ,version: '1.6'
    });
    layui.use(['jquery','ajaxCascader'],function(){

        var $ = layui.jquery;
        var cascader = layui.ajaxCascader;

        <if condition="$new_charge_time eq 1">
            $("#binding_type").change(function(){
                var selected=$(this).children('option:selected').val();
                if(selected == 1){
                    $('#binding_room').show();
                    $('#binding_owner').hide();
                    // Ajax传参模式
                    cascader.load({
                        elem: '#binding_obj'
                        ,search: {
                            show: true,
                            // minLabel: 1,
                            placeholder: '请输入搜索词'
                        }
                        ,clear: true
                        ,value: 0
                        ,clicklast:true
                        ,showlast:false
                        ,getChildren: function(value,key,callback){
                            var data = [];
                            var str=value+'-'+key.length;
                            $.ajax({
                                url:"{pigcms{:U('get_village_single',array('pid'=>'"+str+"'))}",
                                type:'POST',
                                success:function(res){
                                    data = res.data;
                                    for(var i in data){
                                        data[i].value = data[i].id;
                                        data[i].label = data[i].name;
                                        delete data[i].id;
                                        delete data[i].name;
                                        data[i].hasChild = true;
                                    }
                                    callback(data);
                                }
                            });
                        },
                        // chooseData: ["6", "7"]
                    });
                    cascader.on('click', '#binding_obj', function(){
                        var keys=cascader.getChooseData('#binding_obj');
                        if(keys.length < 3){
                            layer.msg('暂无该绑定的房间!',{icon:2});
                            return false;
                        }
                        $.post("{pigcms{:U('get_room_user')}",{vacancy_id:keys[3]},function(data){
                            var str = '',rr=data.data;
                            if(rr == ''){
                                layer.msg('该房间暂无人员!',{icon:2});
                                return false;
                            }
                            for(var i in rr){
                                str+='<div><span>【姓名】'+rr[i]["name"]+'；</span><span>【手机号】'+rr[i]["phone"]+'</span></div>';
                            };
                            $(".bin_room_user").html(str).show();
                        },'json');

                    });

                    $("#car_user_phone").val('');
                    $("#car_user_name").val('');
                    $("#obj_id").val('');
                }else{
                    $('#binding_room').hide();
                    $('#binding_owner').show();
                    $('.bin_room_user').html('');
                }
            });
        </if>

        $(".month_member_sync").hide();

        $('input[name="park_status"]').each(function(i){

            $(this).click(function(){
                console.log($(this).val())
                if($(this).val() == 1){
                    $(".month_member_sync").show();
                }else{
                    $(".month_member_sync").hide();
                }
//			$('.diy_property_content').each(function(){
//				$(this).hide();
//			});
//			$('.diy_property_content').eq(i).show();
            });

        });


        $('.submit_info').click(function(){
            var car_number = $('#car_number').val();
            if(!car_number){
                layer.msg('车牌号不能为空!',{icon:2});
                return false;
            }
            var car_type = $('#car_type').val();//车辆类型
            var car_position_id = $('#car_position_id').val();//车位id
            var car_position_id_path = $('#position_ids').val();//车位id
            var car_stop_num = $('#car_stop_num').val();//停车卡号
            var equipment_no = $('#equipment_no').val();//停车卡号
            var car_user_name = $('#car_user_name').val();//车主姓名
            var car_user_phone = $('#car_user_phone').val();//车主电话
            var car_displacement = $('#car_displacement').val();//车辆排量
            var province = $('#province').val();//省份
            var store_value_car_type = $('#store_value_car_type').val();//
            var temporary_car_type = $('#temporary_car_type').val();//
            var car_color = $('#car_color').val();//
            var user_id = $('#user_id').val();//
            var end_time = $('#end_time').val();
            var parking_car_type = $('#parking_car_type').val();
            var binding_type=$("#binding_type").val();
            var car_brands = $('#car_brands').val();
            var relationship = $('#relationship').val()||0;
            var obj_id=$("#obj_id").val();
            var keys=[];
            var source = "{pigcms{$source}";
            if(relationship=='' || relationship=='0'|| relationship==0){
                layer.msg('请选择与车主关系!',{icon:2});
                return false;
            }

                <if condition="$new_charge_time eq 1">
                    if(!binding_type){
                        layer.msg('请选择绑定对象!',{icon:2});
                        return false;
                    }
                    if (binding_type == 2) {
                        if (!car_user_phone) {
                            layer.msg('请输入车主手机号！!', {icon: 2});
                            return false;
                        }
                        if (!car_user_name) {
                            layer.msg('请输入车主姓名！!', {icon: 2});
                            return false;
                        }
                    } else {
                        try {
                            keys = cascader.getChooseData('#binding_obj');
                            if (keys.length < 3) {
                                layer.msg('请选择绑定有效的房间！', {icon: 2});
                                return false;
                            }
                        }
                        catch(err) {}
                    }
                <else/>
                    if (!car_user_phone) {
                        layer.msg('请输入车主手机号！!', {icon: 2});
                        return false;
                    }
                    if (!car_user_name) {
                        layer.msg('请输入车主姓名！!', {icon: 2});
                        return false;
                    }
                </if>
            var index = layer.load(1, {shade: [0.1,'#000']});
            $.post("{pigcms{:U('vehicle_add')}",{'car_brands':car_brands,'relationship':relationship,'parking_car_type':parking_car_type,'car_number':car_number,'end_time':end_time,'car_position_id':car_position_id,'car_position_id_path':car_position_id_path,'car_stop_num':car_stop_num,'car_user_name':car_user_name,'car_user_phone':car_user_phone,'car_displacement':car_displacement,province:province,'store_value_car_type':store_value_car_type,'temporary_car_type':temporary_car_type,user_id:user_id,equipment_no:equipment_no,car_color:car_color,car_type:car_type,binding_type:binding_type,room_data:keys,obj_id:obj_id,'source':source},function(data){

                layer.close(index)
                if(data.code == 1){
                    layer.msg(data.msg,{icon: 1},function(){
                        // location.reload();
                        if(source === 'user'){
                            parent.car_id.push(data.car_id)
                            parent.layer.closeAll();
                        }
                        location.href='{pigcms{:U('Unit/vehicle_management')}';
                    });
                }
                if(data.code == 2){
                    layer.msg(data.msg,{icon: 2});
                }
            },'json');
        });

        $('#position_num').keyup(function() {
            var position_num= $.trim(this.value);
            get_position(position_num);
        }).blur(function(){
            $("#searchBox1").html("").hide(); //输入框失去焦点的时候就隐藏搜索框
        });

        $('#position_num').focus(function() {
            var position_num= $.trim(this.value);
            get_position(position_num);
        });

        $(document).on("click",'.ant-select-dropdown-menu-item1',function(e){
            position_id = $(this).val();
            $('#car_position_id').val(position_id);
            var position_num = $(this).attr('position_num');
            var car_re = $('#car_re').val();
            if(!car_re){
                $('#car_re').val(position_num);
            }else{
                var st_car_arr=car_re.split(";");
                let is_true = st_car_arr.indexOf(position_num);
                if(is_true<0){
                    $('#car_re').val(car_re+';'+position_num);
                }
            }
            let position_ids = $('#position_ids').val();
            if(!position_ids || position_ids == 0){
                $('#position_ids').val(position_id);
            }else{
                var position_id_arr=position_ids.split(",");
                let is_id_true = position_id_arr.indexOf(String(position_id));
                if(is_id_true<0){
                    $('#position_ids').val(position_id_arr+','+position_id);
                }
            }
            layer.msg('选择车位成功', {time: 1000});
            $('#position_num').val(position_num);

            $("#dropdown-menu").html("").hide(); //输入框失去焦点的时候就隐藏搜索框

        });
        $(document).bind("click",function(e){
            var target=$(e.target);
            if(target.closest("#dropdown-menu").length==0){
                $("#dropdown-menu").html("").hide();
            }
        });

        $('#garage_id').on('change',function() {
            var garage_id= $.trim(this.value);
            if (garage_id) {
                $('.position_div').show()
            }else{
                $('.position_div').hide();
            }
            $('#position_num').val('');
            $('#car_position_id').val(0);
        });

        function get_position(position_num){
            var garage_id=$('#garage_id').val();
            if (!garage_id) {
                layer.alert('请选择车库');
            }
            if(position_num!=""){//检测键盘输入的内容是否为空，为空就不发出请求
                $.post("{pigcms{:U('ajax_get_parking_list')}",{'position_num':position_num,'garage_id':garage_id},function(data){

                    if (data && data!=null && data.length> 0) {//检测返回的结果是否为空
                        var str = '<div><ul class="findtype-menu" style="    overflow-y: auto;max-height: 400px;">';
                        for(var i in data){
                            str += '<li class="ant-select-dropdown-menu-item1 more_car" value="'+data[i]["position_id"]+'" position_num="'+data[i]["position_num"]+'">'+data[i]["position_num"]+' - '+data[i]["garage_num"]+'【车位已绑'+data[i].count_car+'辆车】</li>';
                        };

                        str+="</ul></div>";
                        console.log(str);
                        layer.msg('请选择弹出的蓝色框中对应车位', {time: 1500});
                        $("#dropdown-menu").html(str).show();//将搜索到的结果展示出来
                    } else {
                        $("#dropdown-menu").html("").hide();
                    }
                },'json');
            }else{
                $("#dropdown-menu").html("").hide();  //没有查询结果就隐藏搜索框
            }
        };

        var  arr = [];
        var source='{pigcms{$source}';

        <if condition="$new_charge_time eq 1">
                $('#car_user_phone').live('keyup',function(){
            if(source == 'user'){
                return true;
            }
                    var find_type  = '2';//请求类型2 手机号
                    var find_value= $.trim(this.value);
                    if(find_value!=""){//检测键盘输入的内容是否为空，为空就不发出请求,type:'true'
                        $.post("{pigcms{:U('Cashier/ajax_user_list')}",{'find_type':find_type,'find_value':find_value},function(data){
                            if (data.user_list && data.user_list!=null && data.user_list.length> 0) {//检测返回的结果是否为空
                                var str = '<div><ul class="findtype-menu"  style="    overflow-y: auto;max-height: 255px; ">';
                                for(var i in data['user_list']){
                                    var id=data["user_list"][i]["pigcms_id"];
                                    var final= $.inArray(parseInt(id),arr);
                                    str += '<li class="ant-select-dropdown-menu-item " style="line-height: 20px;" value="'+data["user_list"][i]["pigcms_id"]+'" phone="'+data["user_list"][i]["phone"]+'" uid="'+data["user_list"][i]["uid"]+'">'+data["user_list"][i]["name"];
                                    if(final >= 0){
                                        str+='<span style="float:right;">已选</span></li>';
                                    }else{
                                        str+='</li>';
                                    }
                                };

                                str+="</ul></div>";

                                layer.msg('请选择弹出的蓝色框中对应业主', {time: 1500});
                                $("#searchBox").html(str).show();//将搜索到的结果展示出来

                            } else {
                                $("#searchBox").html("").hide();
                            }
                        },'json');
                    }else{
                        $("#searchBox").html("").hide();  //没有查询结果就隐藏搜索框
                    }
                });
        <else/>
            $('#find_value').live('keyup',function(){
                var find_type  = '2';//请求类型2 手机号
                var find_value= $.trim(this.value);
                if(find_value!=""){//检测键盘输入的内容是否为空，为空就不发出请求,type:'true'
                    $.post("{pigcms{:U('Cashier/ajax_user_list')}",{'find_type':find_type,'find_value':find_value},function(data){
                        if (data.user_list && data.user_list!=null && data.user_list.length> 0) {//检测返回的结果是否为空
                            var str = '<div><ul class="findtype-menu"  style="    overflow-y: auto;max-height: 255px; ">';
                            for(var i in data['user_list']){
                                var id=data["user_list"][i]["pigcms_id"];
                                var final= $.inArray(parseInt(id),arr);
                                str += '<li class="ant-select-dropdown-menu-item " style="line-height: 20px;" value="'+data["user_list"][i]["pigcms_id"]+'" phone="'+data["user_list"][i]["phone"]+'" uid="'+data["user_list"][i]["uid"]+'">'+data["user_list"][i]["name"];
                                if(final >= 0){
                                    str+='<span style="float:right;">已选</span></li>';
                                }else{
                                    str+='</li>';
                                }
                            };

                            str+="</ul></div>";

                            layer.msg('请选择弹出的蓝色框中对应业主', {time: 1500});
                            $("#searchBox").html(str).show();//将搜索到的结果展示出来

                        } else {
                            $("#searchBox").html("").hide();
                        }
                    },'json');
                }else{
                    $("#searchBox").html("").hide();  //没有查询结果就隐藏搜索框
                }
            });

        </if>

        $(document).on("click",'.ant-select-dropdown-menu-item',function(e){
            var pigcms_id = $(this).val();
            var name = $(this).text();
            var phone = $(this).attr('phone');
            var uid = $(this).attr('uid');
            var result = $.inArray(pigcms_id,arr);
            if(result<'0'){
                arr = [];
                arr.push(pigcms_id);
                res = JSON.stringify(arr);
//				$('#float_right_div').append('<div style="margin-bottom: 3px;">'+phone+'[<font style="color:#2b7dbc">'+name+'</font>]<span style="padding-left:20px" onclick="_remove(this,'+pigcms_id+')"><a href="javascript:void(0)" style="color:red;">删除</a></span><br/></div>');
                $("#searchBox").html("").hide(); //输入框失去焦点的时候就隐藏搜索框
                $(".selected").show();

                // $("#find_value").val(name);
                // $("#user_id").val(pigcms_id);

            <if condition="$new_charge_time neq 1">
                $("#find_value").val(name);
                $("#user_id").val(pigcms_id);
            </if>
                $("#car_user_phone").val(phone);
                $("#car_user_name").val(name);

                $("#obj_id").val(pigcms_id);
                layer.msg('选择业主成功', {time: 1000});
            }


        });
        $(document).bind("click",function(e){
            var target=$(e.target);
            if(target.closest("#dropdown-menu").length==0){
                $("#searchBox").html("").hide();
            }
        })
    })

</script>

<include file="Public:footer"/>