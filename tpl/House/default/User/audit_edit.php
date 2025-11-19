<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('User/audit_index')}">业主审核管理</a>
			</li>
			<li class="active">审核业主设置</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
                .col-sm-2.width-10 {
                    width: 10% !important;
                }
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" id="edit_form" action="__SELF__" onsubmit="return chk_submit()">
						<input  name="pigcms_id" type="hidden"  value="{pigcms{$info.pigcms_id}"/>
						<input  name="usernum" type="hidden"  value="{pigcms{$info.usernum}"/>
						<input  name="referer" type="hidden"  value="{pigcms{$_GET.referer}"/>
						<input type="hidden" name="layer_num" value="{pigcms{$info.layer}" />
						<input type="hidden" name="room_num" value="{pigcms{$info.room}" />
						<input type="hidden" name="type" value="{pigcms{$info.type}" />
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								
                                <if condition="$info['bind_number']">
                                    <div class="form-group">
                                        <label class="col-sm-2 width-10" style="text-align: right;"><label for="bind_number">用户编号</label></label>
                                        <input class="col-sm-4" size="20" id="bind_number" type="text" style="border:none;background:white!important;" readonly  value="{pigcms{$info.bind_number}"/>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-2 width-10" style="text-align: right;"><label for="usernum">用户编号</label></label>
                                        <input class="col-sm-4" size="20" id="usernum" style="border:none;background:white!important;"  value="{pigcms{$info.usernum}" type="text" readonly>
                                    </div>
                                </if>

								
								<div class="form-group">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="user_name">姓名</label></label>
									<input class="col-sm-4" size="20" name="user_name" id="user_name" type="text" value="{pigcms{$info.name}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="phone">联系方式</label></label>
									<input class="col-sm-4" size="20" name="phone" id="phone" type="text" value="{pigcms{$info.phone}" />
								</div>
                                <if condition="$chinapopinOpen && $info['third_type']=='ezc_chinapopin'">
                                    <div class="form-group">
                                        <label class="col-sm-2 width-10" style="text-align: right;"><label for="address">住址</label></label>
                                        <select name="single_id" id="single">
                                            <volist name="single_list" id="single_data">
                                                <option <if condition="$info['single_id'] eq $single_data['id']">selected="selected"</if> value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>
                                            </volist>
                                        </select>

                                        <select name="floor_id" id="floor">
                                            <option <if condition="$info['floor_id'] eq '0'">selected="selected"</if> value="0">请选择单元</option>
                                        </select>

                                        <select name="layer_id" id="layer">
                                            <option <if condition="$info['layer_id'] eq '0'">selected="selected"</if> value="0">请选择楼层</option>
                                        </select>

                                        <select name="vacancy_id" id="vacancy">
                                            <option <if condition="$info['vacancy_id'] eq '0'">selected="selected"</if> value="0">请选择房间</option>
                                        </select>
                                    </div>
                                    <else/>
                                    <div class="form-group">
                                        <label class="col-sm-2 width-10" style="text-align: right;"><label for="address">住址</label></label>
                                        <input class="col-sm-4" size="20" name="address" id="address" type="text" style="border:none;background:white!important;" readonly="readonly" value="{pigcms{$info.address}" />
											<input type="hidden" name="single_id" value="{pigcms{$info.single_id}" />
											<input type="hidden" name="floor_id" value="{pigcms{$info.floor_id}" />
											<input type="hidden" name="layer_id" value="{pigcms{$info.layer_id}" />
											<input type="hidden" name="vacancy_id" value="{pigcms{$info.vacancy_id}" />
                                    </div>
                                </if>

                                <div class="form-group">
                                    <label class="col-sm-2 width-10" style="text-align: right;"><label for="housesize">房屋面积</label></label>
                                    <input class="col-sm-4" size="20" name="housesize" id="housesize" type="text" value="{pigcms{$info.housesize}" <if condition='$info["status"] eq 3'>readonly="readonly"</if>/>
                                </div>
								
								<div class="form-group">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="memo">备注</label></label>
									<textarea class="col-sm-4" size="10" name="memo" id="memo" />{pigcms{$info.memo}</textarea>
								</div>
								
								<div class="form-group">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">房屋类型</label></label>
                                    <if condition='$info["house_type"] eq 1'>
                                        <div class="shopNameDiv">住宅</div>
                                        <elseif condition='$info["house_type"] eq 2' />
                                        <div class="shopNameDiv">商品</div>
                                        <else />
                                        <div class="shopNameDiv">办公</div>
                                    </if>
                                </div>
								
								<!--<div class="form-group">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">关系</label></label>
									<input class="col-sm-4" size="20" value="{pigcms{$info.relation_val}" type="text" style="border:none;background:white!important;" readonly="readonly">
								</div>-->
								<if condition="$chinapopinOpen && $info['third_type']=='ezc_chinapopin'">
                                    <div class="form-group">
                                        <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">关系</label></label>
                                        <label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" name="type_new" value="0" class="ace"><span class="lbl" style="z-index: 1">业主</span></label>
                                        <label style="padding-left:0px;"><input type="radio" name="type_new" value="1" class="ace"><span class="lbl" style="z-index: 1" >家属</span></label>
                                        <select name="relatives_type">
                                            <volist name="relatives_type_info" id="item">
                                                <option value="{pigcms{$item.relatives_type}">{pigcms{$item.name}</option>
                                            </volist>
                                        </select>
                                        <label style="padding-left:0px;padding-left:20px;"><input type="radio" name="type_new" value="2" class="ace"><span class="lbl" style="z-index: 1" >租客</span></label>
                                    </div>
                                    <if condition="$info['third_type_text'] && $info['third_type_text'] neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">数据来源</label></label>
                                            <input class="col-sm-4" size="20" value="{pigcms{$info.third_type_text}" type="text" style="border:none;background:white!important;" readonly="readonly">
                                        </div>
                                    </if>
                                    <if condition="$info['third_user_type_text'] && $info['third_user_type_text'] neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">智慧平安人员类型</label></label>
                                            <input class="col-sm-4" size="20" value="{pigcms{$info.third_user_type_text}" type="text" style="border:none;background:white!important;" readonly="readonly">
                                        </div>
                                    </if>
                                    <if condition="$info['third_type'] && $info['third_type'] eq 'ezc_chinapopin' && $info['old_address']">
                                        <div class="form-group">
                                            <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">智慧平安户室</label></label>
                                            <input class="col-sm-4" size="20" value="{pigcms{$info.old_address}" type="text" style="border:none;background:white!important;" readonly="readonly">
                                        </div>
                                    </if>
                                    <if condition="$info['old_user_name'] && $info['old_user_name'] neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">替换业主姓名</label></label>
                                            <input class="col-sm-4" size="20" value="{pigcms{$info.old_user_name}" type="text" style="border:none;background:white!important;" readonly="readonly">
                                        </div>
                                    </if>
                                    <if condition="$info['old_user_phone'] && $info['old_user_phone'] neq ''">
                                        <div class="form-group">
                                            <label class="col-sm-2 width-10" style="text-align: right;"><label for="unittype">替换业主手机号</label></label>
                                            <input class="col-sm-4" size="20" value="{pigcms{$info.old_user_phone}" type="text" style="border:none;background:white!important;" readonly="readonly">
                                        </div>
                                    </if>
                                </if>
								<div class="form-group">
									<label class="col-sm-2 width-10" for="status" style="text-align: right;">审核状态</label>
									
									<label style="padding-left:0px;padding-right:20px;"><input type="radio" name="status" value="1" class="ace"><span class="lbl" style="z-index: 1">通过</span></label>
									<label style="padding-left:0px;"><input type="radio" name="status" value="-1" class="ace"><span class="lbl" style="z-index: 1" >禁止</span></label>
								</div>
								<div class="form-group reason" style="display: none;">
									<label class="col-sm-2 width-10" style="text-align: right;"><label for="reason">拒绝原因</label></label>
									<textarea class="col-sm-4" size="10" name="reason" id="reason" /></textarea>
								</div>
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
</div>
<script type="text/javascript" language="javascript">
    var cat_id = $('#single').val();
    get_son_category(cat_id);
    var floor_id = "{pigcms{$info['floor_id']}";
    var layer_id = "{pigcms{$info['layer_id']}";
    var vacancy_id = "{pigcms{$info['vacancy_id']}";
    function chk_submit(){
        var old_user_name = "{pigcms{$info.old_user_name}";
        var old_user_phone = "{pigcms{$info.old_user_phone}";
        var tip = "确认进行修改?";
        if  (old_user_name && old_user_phone) {
            tip = "当前房间已有业主【姓名："+old_user_name+"；手机号："+old_user_phone+"】,确认是否进行审核修改？"
        } else  if  (old_user_name) {
            tip = "当前房间已有业主【姓名："+old_user_name+"】,确认是否进行审核修改？"
        } else  if  (old_user_name) {
            tip = "当前房间已有业主【手机号："+old_user_phone+"】,确认是否进行审核修改？"
        }
        if(!confirm(tip)){
            return false;
        }
    }
    $('input[name=status]').click(function(){
        var status = $('input[name=status]:checked').val();
        if (status==-1) {
            $('.reason').show();
        }else{
            $('.reason').hide();
        }
    })


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
        var vacancy = '<option value="0">请选择房间</option>';
        if(result['status'] == 1){
            var cat_list = result.cat_list;
            var floor_now = 0;
            for(var i in cat_list){
                if (cat_list[i]['floor_id'] == "{pigcms{$info['floor_id']}" ) {
                    floor_now = floor_id;
                    console.log(cat_list[i]['floor_id'])
                    console.log("{pigcms{$info['floor_id']}")

                    html +='<option value="'+ cat_list[i]['floor_id'] +'" selected=selected>' + cat_list[i]['floor_name'] + '</option>';
                }else{
                    html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
                }
            }
            $('#floor').html(html);
            if (floor_now && floor_now>0) {
                get_new_layer(floor_now);
            } else {
                $('#layer').html(htmls);
                $('#vacancy').html(vacancy);
            }
        } else {
            $("#floor").html(html);
            $('#layer').html(htmls);
            $('#vacancy').html(vacancy);
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
    $.post(url,{'cat_id':cat_id,'type':11,'vacancy_id':vacancy_id},function(result){
        console.log("result")
        console.log(result)
        var html = '<option value="0">请选择房间</option>';
        if(result['status'] == 1){
            var cat_lists = result.cat_list;
            for(var j in cat_lists){

                if (cat_lists[j]['pigcms_id'] == "{pigcms{$info['vacancy_id']}") {
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
$('#vacancy').change(function(){
    var vacancy_id = $('#vacancy').val();
    var url = "{pigcms{:U('Service/get_vacancy')}";
    $.post(url,{'vacancy_id':vacancy_id},function(result){
        console.log("result_vacancy")
        console.log(result)
        if(result['status'] == 1){
            $('#housesize').val(result.housesize);
        }else{
            $('#housesize').val(0);
        }
    },'json');

});

function get_new_layer(cat_id){
    var url = "{pigcms{:U('Service/get_new_layer')}";
    $.post(url,{'cat_id':cat_id},function(result){
        console.log("result")
        console.log(result)
        var shtml = '<option value="0">请选择房间</option>';
        var html = '<option value="0">请选择楼层</option>';
        var layer_now = 0;
        if(result['status'] == 1){
            var cat_list = result.cat_list;
            for(var i in cat_list){
                if (cat_list[i]['id'] == "{pigcms{$info['layer_id']}" ) {
                    layer_now = layer_id;
                    html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['layer_name'] + '</option>';
                }else{
                    html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
                }
            }
            $('#layer').html(html);
            if (layer_now && layer_now>0) {
                get_vacancy(layer_now)
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