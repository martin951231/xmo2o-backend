//显示一级分类
function show_employee_category_first(){
	
	
	$.post(employee_category,function(result){

        if (result.status==0) {
            alert(result.info);
        } else {
            result = $.parseJSON(result);
            if(result.errorCode == 0){
                var employee_category = result.result.employee_category;

                var cat_id_dom = '<select id="choose_cat_id" data-role="cat_id" name="cat_id" class="weui-select choose_category" style="text-align:center;margin-right:1%;padding-right: 0px;">';
                cat_id_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_id') ? 'selected="selected"' : '')+'>请选择分类</option>';
                $.each(employee_category,function(i,item){
                    cat_id_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_id') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
                });
                cat_id_dom+= '</select>';
                $('#choose_category').prepend(cat_id_dom);
                show_employee_category_second($('#choose_cat_id').find('option:selected').attr('value'),$('#choose_cat_id').find('option:selected').html());
                $('#choose_cat_id').change(function(){
                    show_employee_category_second($(this).find('option:selected').attr('value'),$(this).find('option:selected').html());
                });

            }else{
                alert(result.errorMsg);
            }
		}
	});
}
//显示二级分类

function show_employee_category_second(cat_fid,cat_name){
	$.post(employee_category,{cat_fid:cat_fid,cat_name:cat_name},function(result){
        if (result.status==0) {
           alert(result.info);
		} else {
            result = $.parseJSON(result);
            if(result.errorCode == 0){
                var employee_category = result.result.employee_category;
                var cat_fid_dom = '<select id="choose_cat_fid" data-role="cat_fid" name="cat_fid" class="weui-select choose_category" style="text-align:center;margin-right:1%;padding-right: 0px;">';
                cat_fid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_fid') ? 'selected="selected"' : '')+'>请选择分类</option>';
                $.each(employee_category,function(i,item){
                    cat_fid_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_fid') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
                });
                cat_fid_dom+= '</select>';
                if(document.getElementById('choose_cat_fid')){
                    $('#choose_cat_fid').replaceWith(cat_fid_dom);
                }else if(document.getElementById('choose_cat_id')){
                    $('#choose_cat_id').after(cat_fid_dom);
                }else{
                    $('#choose_cat_fid').prepend(cat_fid_dom);
                }

                if ($('#choose_cat_fid').find('option:selected').attr('value') > 0) {
                    show_employee_category_third($('#choose_cat_fid').find('option:selected').attr('value'),cat_fid,$('#choose_cat_fid').find('option:selected').html());
                } else {
                    show_employee_category_third_default();
                }
                $('#choose_cat_fid').change(function(){
                	if ($(this).find('option:selected').attr('value') > 0) {
                        show_employee_category_third($(this).find('option:selected').attr('value'),$('#choose_cat_id').find('option:selected').attr('value'),$(this).find('option:selected').html());
                    }
                });

            }else{
                alert(result.errorMsg);
            }
		}
	});
}

//显示三级分类
function show_employee_category_third(cat_fid,cat_pfcid,cat_name){
	$.post(employee_category,{cat_fid:cat_fid,cat_pfcid:cat_pfcid,cat_name:cat_name},function(result){
		if (result.status==0) {
			alert(result.info);
		} else {
			result = $.parseJSON(result);
			if(result.errorCode == 0){
				var employee_category = result.result.employee_category;
				var cat_pfcid_dom = '<select id="choose_cat_pfcid" data-role="cat_pfcid" name="cat_pfcid" class="weui-select choose_category" style="text-align:center;padding-right: 0px;">';
				cat_pfcid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>请选择分类</option>';
				$.each(employee_category,function(i,item){
					cat_pfcid_dom+= '<option value="'+item.cat_id+'" '+(item.cat_id==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>'+item.cat_name+'</option>';
				});
				cat_pfcid_dom+= '</select>';
				if(document.getElementById('choose_cat_pfcid')){
					$('#choose_cat_pfcid').replaceWith(cat_pfcid_dom);
				}else if(document.getElementById('choose_cat_fid')){
					$('#choose_cat_fid').after(cat_pfcid_dom);
				}else{
					$('#choose_cat_pfcid').prepend(cat_pfcid_dom);
				}

                //模拟更新网址栏，防止用户刷新时，还是原页面
                var stateObj = { foo: category };
                history.replaceState(stateObj, "", category);


                $('#choose_cat_pfcid').change(function(){
                    if ($(this).find('option:selected').attr('value') > 0 && load) {
                        write_field_info(cat_fid, cat_pfcid);
                     }
                });
                load = true;

			}else{
				alert(result.errorMsg);
			}
		}
	});
}


//显示三级分类默认的
function show_employee_category_third_default(){
	var cat_pfcid_dom = '<select id="choose_cat_pfcid" data-role="cat_pfcid" name="cat_pfcid" class="weui-select choose_category" style="text-align:center;padding-right: 0px;">';
	cat_pfcid_dom+= '<option value="0" '+(0==$('#choose_category').attr('cat_pfcid') ? 'selected="selected"' : '')+'>请选择分类</option>';
	cat_pfcid_dom+= '</select>';
	if(document.getElementById('choose_cat_pfcid')){
		$('#choose_cat_pfcid').replaceWith(cat_pfcid_dom);
	}else if(document.getElementById('choose_cat_fid')){
		$('#choose_cat_fid').after(cat_pfcid_dom);
	}else{
		$('#choose_cat_pfcid').prepend(cat_pfcid_dom);
	}
}
// <div class="weui-cell">
//     <div class="weui-cell__hd">
//     <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
// </div>
// <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
//     <div class="weui-cell__bd">
//     <input data-role="text" name="write_field[{pigcms{$kk}][value]" class="weui-input" placeholder="请填写{pigcms{$vv['name']}<if condition="!$vv['iswrite']">（选填）</if>" type="text" value="" <if condition="$vv['iswrite']">data-required="required"</if>/>
//     <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
//     <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
//     <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
//     <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
//     <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
//     <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
//     </div>
//     </div>


// 处理显示问题
function write_field_info(cat_fid, cat_pfcid) {
    $.post(employee_category,{cat_fid:cat_fid,cat_pfcid:cat_pfcid},function(result){
        if (result.errorCode == 0) {
            var write_field = result.result.write_field;
            if (write_field) {
                var write_html = '';
                var upload_key = [];
                for (var i in write_field) {
                    var val = write_field[i];
                    var is_write = '';
                    var is_required = '';
                    if (val['iswrite']) {
                        is_required = 'data-required="required"';
                    } else {
                        is_write = '（选填)';
                    }
                    if (val && val['type'] == 0) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="text" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" type="text" value="" ' + is_required + '/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';

                    } else if (val && val['type'] == 1) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <textarea data-role="textarea" class="weui-textarea" rows="2" name="write_field[' + i + '][value]" placeholder="请填写' + val['name'] + is_write + '"  value="" ' + is_required + '></textarea>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 2) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="position" data-key="' + i + '" class="weui-input position_info position_key_' + i + '" type="text" name="write_field[' + i + '][value]" placeholder="点击选择' + val['name'] + is_write + '" readonly="readonly" ' + is_required + '/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][long]"  class="long_' + i + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][lat]"  class="lat_' + i + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][address]"  class="address_' + i + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';

                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">补充地址</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '     <input data-role="position-desc" data-key="' + i + '" class="weui-input position_desc position_desc_key_' + i + '" type="text" name="write_field[' + i + '][value-desc]" placeholder="选择地图后填写详细地址' + is_write + '" ' + is_required + '/>';
                        write_html += '  </div>';
                        write_html += '</div>';

                    } else if (val && val['type'] == 3) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <select class="weui-select" name="write_field[' + i + '][value]" data-role="select">';

                        if (val['use_field']) {
                            for (var j in val['use_field']) {
                                if (val['use_field'][j]) {
                                    write_html += '<option value="' + val['use_field'][j] + '">' + val['use_field'][j] + '</option>';
                                }
                            }
                        }

                        write_html += '    </select>';

                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 4) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="number" type="number" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value="" onkeyup="value=clearNoNum(this.value)"/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 5) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="email" type="email" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 6) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="date" type="date" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 7) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="time" type="time" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 8) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input style="float: left;width: 60%;" data-role="date" type="date" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input style="float: left;width: 40%;" data-role="time" type="time" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 9) {
                        write_html += '<div class="weui-cell">';
                        write_html += '  <div class="weui-cell__hd">';
                        if (val['iswrite']) {
                            write_html += '<strong style="color:red;">*</strong>';
                        } else {
                            write_html += ' ';
                        }
                        write_html += '  </div>';
                        write_html += '  <div class="weui-cell__hd"><label class="weui-label">' + val['name'] + '</label></div>';
                        write_html += '  <div class="weui-cell__bd">';
                        write_html += '    <input data-role="phone" type="tel" name="write_field[' + i + '][value]" class="weui-input" placeholder="请填写' + val['name'] + is_write + '" value=""/>';
                        write_html += '    <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '    <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '  </div>';
                        write_html += '</div>';
                    } else if (val && val['type'] == 10) {
                        write_html += '<div class="weui-cell">';
                        write_html += '<div class="weui-cell__bd">';
                        write_html += '  <div class="weui-uploader">';
                        write_html += '     <div class="weui-uploader__hd">';
                        write_html += '         <p class="weui-uploader__title">上传照片</p>';
                        write_html += '         <div class="weui-uploader__info" style="font-size:12px;">最多上传8张照片（可不上传）</div>';
                        write_html += '     </div>';
                        write_html += '     <div class="weui-uploader__hd">';
                        write_html += '         <ul class="weui-uploader__files" id="uploaderFiles_'+i+'"></ul>';
                        write_html += '         <div class="weui-uploader__input-box">';
                        write_html += '             <input id="uploaderInput_' + i + '" class="weui-uploader__input" type="file"/>';
                        write_html += '             <input name="write_field[' + i + '][name]"  value="' + val['name'] + '"  type="hidden" />';
                        write_html += '             <input name="write_field[' + i + '][type]"  value="' + val['type'] + '"  type="hidden" />';
                        write_html += '             <input name="write_field[' + i + '][sort]"  value="' + val['sort'] + '"  type="hidden" />';
                        write_html += '             <input name="write_field[' + i + '][iswrite]"  value="' + val['iswrite'] + '"  type="hidden" />';
                        write_html += '             <input name="write_field[' + i + '][cat_id]"  value="' + val['cat_id'] + '"  type="hidden" />';
                        write_html += '             <input name="write_field[' + i + '][alias_name]"  value="' + val['alias_name'] + '"  type="hidden" />';
                        write_html += '         </div>';
                        write_html += '     </div>';
                        write_html += '  </div>';
                        write_html += '</div>';
                        write_html += '</div>';

                        // 图片初始化
                        upload_key.push(i);
                    }
                    $('.write_box').html(write_html);

                    for (var m in upload_key) {
                        if (upload_key[m]) {
                            console.log('upload_key_'+m, upload_key[m]);
                            img_info(upload_key[m]);
                        }
                    }

                    $('input[data-role="position"]').click(function(){
                        var height_info = $('.write_box').css('height');
                        height_info = height_info.replace('px', '');
                        key = $(this).data('key');
                        console.log('key', key);
                        var winHeight = $(window).height() + parseInt(height_info)/2; //获取当前页面高度
                        $('#service-position').css({'z-index':'999999999','opacity':1, height: winHeight + 'px'}).show();
                        $('html,body').animate({scrollTop: '0px'}, 500);
                        $('.page__bd').hide();
                        selectPosition($(this));
                    });
                    $('input[data-role="position-desc"]').keyup(function(){
                        key = $(this).data('key');
                        $('.address_'+key).val($('.position_key_' + key).val()+' '+$(this).val());
                    });
                }
            } else {
                layer.open({
                    content: '该分类没有自定义填写项，请联系管理员添加'
                    ,btn: ['确定']
                    ,yes: function(index){
                        layer.close(index);
                    }
                });
            }
        }
    }, 'json');
}




function img_info(key) {
    var photoct = $('#photo');
    var imgpop = $('#popctrl');
    var uploadinput = $('#uploaderInput_'+key);
    var max_upload_num = 8;
    var boxer = $('#uploaderFiles_'+key);



    var URL = window.URL || window.webkitURL;
    var blobURL;
    var file;
    uploadinput.on("change", function () {
        photoct.cropper('destroy').cropper({
            minContainerHeight: 320,
            autoCropArea:1
        });
        var files = this.files;
        if (!photoct.data('cropper')) {
            return;
        }
        if (files && files.length) {
            file = files[0];
            if (/^image\/\w+$/.test(file.type)) {
                blobURL = URL.createObjectURL(file);
                photoct.one('built.cropper', function () {
                    URL.revokeObjectURL(blobURL);
                }).cropper('reset').cropper('replace', blobURL);
                uploadinput.val('');
                imgpop.popup();
            } else {
                $.toptip('Please choose an image file.', 'error');
            }
        }
    });

    $('.pub_funcbar a').each(function () {
        var btn = $(this);
        var mtd = btn.attr('data-method');
        btn.on('click', function () {
            if (mtd == 'destroy') {
            } else if (mtd == 'confirm') {
                result = photoct.cropper('getCroppedCanvas');
                photo = result.toDataURL('image/jpeg');
                var img=photo.split(',')[1];
                img=window.atob(img);
                var ia = new Uint8Array(img.length);
                for (var i = 0; i < img.length; i++) {
                    ia[i] = img.charCodeAt(i);
                }
                var bfile = new Blob([ia], {type:"image/jpeg"});


                compress(bfile, function(cmp_photo){
                    var img = cmp_photo.split(',')[1];
                    img = window.atob(img);
                    var ia = new Uint8Array(img.length);
                    for (var i = 0; i < img.length; i++) {
                        ia[i] = img.charCodeAt(i);
                    }
                    var blob = new Blob([ia], {type:"image/jpeg"});
                    var formdata=new FormData();
                    formdata.append('filename','file');
                    formdata.append('file',blob);

                    $.ajax({
                        type: 'post',
                        url: ajaxImgUpload,
                        data :  formdata,
                        processData : false,
                        contentType : false,
                        success: function (data) {
                            if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
                            if(data.indexOf('success')!==-1){
                                var html = '<li class="weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="write_field[' + key + '][value][]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';
                                $('#loadingimg').remove();
                                boxer.append(html);
                            } else {
                                $('#loadingimg').remove();
                                tip_common(data);
                            }
                        }
                    });
                });

                boxer.append('<li id="loadingimg" class="weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="{pigcms{$static_path}classifynew/images/loading.gif"/></div></li>');


            } else {
                var opt = btn.attr('data-option');
                photoct.cropper(mtd, opt);
            }
        });
    });
}


var map = null;
var marker = null;
var mapObjDom = null;
var key = null;
$(function(){
    //检测是否需要显示分类
    if(document.getElementById('choose_category')){
        show_employee_category_first();
    }

    // 删除事件
    $(document).on('click', '.weui-uploader__file_del', function () {
        var that = $(this);
        $.confirm(DELCONFIRM, function () {
            that.remove();
        }, function () {
        });
        return false;
    });

    $('#pageShopSearchBackBtn').click(function(){
        goBackPage();
    });

    $('#service_time').val();

    $('section').css('min-height','100%');

    $('.arrow-wrapper').click(function(){
        closeWin();
    });

    $('.yxc-time-con dt[data-role="date"]').click(function(){
        $('.yxc-time-con dt[data-role="date"]').removeClass('active');
        $(this).addClass('active');
        $('.date-'+$(this).data('date')).show().siblings('div').hide();
        if(time_gap!=-1){

        }else{
            $('#serviceJobTime').val($('.yxc-time-con dt[data-role="date"].active').data('text'));
            $('#service_date').val($('.yxc-time-con dt[data-role="date"].active').data('date'));
            $('#service_time').val($(this).data('date'));
            closeWin();
        }
    });

    $('.yxc-time-con dd[data-role="item"]').click(function(){

        if(!$(this).hasClass('disable')){
            $('.yxc-time-con dd[data-role="item"]').removeClass('active');
            $(this).addClass('active');
            $('#serviceJobTime').val($('.yxc-time-con dt[data-role="date"].active').data('text') + ' ' +$(this).data('peroid'));
            $('#service_date').val($('.yxc-time-con dt[data-role="date"].active').data('date'));
            $('#service_time').val($(this).data('peroid'));
            closeWin();
        }
    });

    $('textarea.ipt-attr').focus(function(){
        $(this).css('height','60px');
    }).blur(function(){
        if($(this).val() == ''){
            $(this).css('height','24px');
        }
    });

    $('.select select').change(function(){
        if($(this).val() != ''){
            $(this).css('color','black');
        }else{
            $(this).css('color','#999');
        }
    });



    $('input[data-role="position"]').click(function(){
        var height_info = $('.write_box').css('height');
        height_info = height_info.replace('px', '');
        key = $(this).data('key');
        console.log('key', key);
        var winHeight = $(window).height() + parseInt(height_info)/2; //获取当前页面高度
        $('#service-position').css({'z-index':'11111','opacity':1, height: winHeight + 'px'}).show();
        $('html,body').animate({scrollTop: '0px'}, 500);
        $('.page__bd').hide();
        console.log('selectPosition', $(this));
        selectPosition($(this));
    });
    $('input[data-role="position-desc"]').keyup(function(){
        key = $(this).data('key');
        $('.address_'+key).val($('.position_key_' + key).val()+' '+$(this).val());
    });

    $("#se-input-wd").bind('input',function(e){
        var address = $.trim($('#se-input-wd').val());
        if(address.length>0 && address !== '直接输入定位您的地址'){
            $('#addressShow').empty();
            $.get('/index.php?g=Index&c=Map&a=suggestion', {query:user_city+address}, function(data){
                if(data.status == 1){
                    getAdress(data.result);
                }
            });
        }
    });

    $('#addressShow').delegate("li","click",function(){
        var addressName = $(this).attr("address");
        var addressLongitude = $(this).attr("lng");
        var addressLatitude = $(this).attr("lat");
        var addressSugAddress = $(this).attr("sug_address");
        layer.open({
            title:['位置提示','background-color:#8DCE16;color:#fff;'],
            content:'您选择的位置是：'+addressName,
            btn: ['确定位置','重新选择'],
            yes:function(index){
                console.log('mapObjDom', mapObjDom);
                $('.long_'+key).val(addressLongitude);
                $('.lat_'+key).val(addressLatitude);
                $('.address_'+key).val(addressName);
                mapObjDom.data({'long':addressLongitude,'lat':addressLatitude,'address':addressName}).val(addressSugAddress);
                layer.close(index);
                closeWin();
            }
        });
    });


    $('#dosubmit').click(function(){
        var nowDom = $(this);
        var slA = $('#form').serializeArray();
        for(var i in slA){
            var tmpDom = $("[name='"+slA[i].name+"']");
            if(tmpDom.data('role')){
                if(tmpDom.data('role') == 'cat_id' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择一级分类', skin:'msg', time:2});
                    return false;
                }else if(tmpDom.data('role') == 'cat_fid' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择二级分类', skin:'msg', time:2});
                    return false;
                }else if(tmpDom.data('role') == 'cat_pfcid' && (slA[i].value == '' || slA[i].value == 0)){
                    layer.open({content:'请选择三级分类', skin:'msg', time:2});
                    return false;
                }
                if(tmpDom.data('required')){
                    if(tmpDom.data('role') == 'phone' && !/^0?1[3|4|5|7|8][0-9]\d{8}$/.test(slA[i].value)){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'text' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'position' && !tmpDom.data('long')){
                        console.log(1)
                        console.log(tmpDom.data('long'))
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'textarea' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'number' && !/^[0-9]*$/.test(slA[i].value)){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'date'  && slA[i].value == '' ){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'time' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'select' && slA[i].value == ''){
                        formError(tmpDom);
                        return false;
                    }else if(tmpDom.data('role') == 'email'  && !/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(slA[i].value)){
                        formError(tmpDom);
                        return false;
                    }
                }
            }
        }
        $("#dosubmit").attr("disabled", "disabled");
        $("#dosubmit").val("正在入驻...");
        $.post($('#form').attr('action'),slA,function(result){
            console.log('result', result);
            if(result.status == 1){
                layer.open({
                    title:'提示：'
                    ,content:result.info
                    ,btn:['好的']
                    ,shadeClose:false
                });
                history.go(0);
            }else{
                layer.open({
                    title:'提示：'
                    ,content:result.info
                    ,btn:['好的']
                    ,shadeClose:false
                });
                $("#dosubmit").removeAttr("disabled");
                $("#dosubmit").val("确认入驻");
            }
        });
        return false;
    });


});

function formError(tmpDom){
    $('.form_error').removeClass('form_error');
    layer.open({content:tmpDom.attr('placeholder'), skin:'msg', time:2});
}

function selectPosition(objDom){
    $('.se-input-wd').val('');
    $('#addressShow').empty();
    mapObjDom = objDom;
    // 百度地图API功能
    if(objDom.data('long')){
        console.log('objDom1', 1);
        // setTimeout(function(){
        map.centerAndZoom(new BMap.Point(objDom.data('long'),objDom.data('lat')), 16);
        // },300);
    }else{
        console.log('objDom2', 2);
        map = new BMap.Map("allmap",{enableMapClick:false});
        console.log('map', map);
        if(user_long == 0){
            map.centerAndZoom(user_city, 16);
        }else{
            map.centerAndZoom(new BMap.Point(user_long,user_lat), 16);
        }
        // map.addControl(new BMap.ZoomControl()); //添加地图缩放控件

        map.addEventListener("dragend", function(e){
            $('#addressShow').empty();
            var centerMap = map.getCenter();
            getPositionInfo(centerMap.lat,centerMap.lng);
        });

        map.addEventListener("load", function(e){
            var centerMap = map.getCenter();
            getPositionInfo(centerMap.lat,centerMap.lng);
        });
    }
}

function getPositionInfo(lat,lng){
    lat = 31.826578; lng = 117.233441;
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=renderReverse&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');
}

function getPositionAdress(result){
    if(result.status == 0){
        result = result.result;
        var re = [];
        re.push({'name':result.sematic_description,'address':result.formatted_address,'long':result.location.lng,'lat':result.location.lat});
        for(var i in result.pois){
            re.push({'name':result.pois[i].name,'address':result.pois[i].addr,'long':result.pois[i].point.x,'lat':result.pois[i].point.y});
        }
        getAdress(re);
    }else{
        alert('获取位置失败！');
    }
}
function getAdress(re){
    var addressHtml = '';
    for(var i=0;i<re.length;i++){
        addressHtml += '<li lng="'+re[i]['long']+'" lat="'+re[i]['lat']+'" sug_address="'+re[i]['name']+'" address="'+re[i]['address']+'">';
        addressHtml += '<div class="mapaddress-title"><span class="icon-location" data-node="icon"></span><span class="recommend">'+(i == 0 ? '[推荐位置]' : '')+''+re[i]['name']+'</span></div>';
        addressHtml += '<div class="mapaddress-body">'+re[i]['address']+'</div>';
        addressHtml += '</li>';
    }
    $('#addressShow').append(addressHtml);
}

function closeWin(){
    $('#service-type').hide();
    $('#service-date').hide();
    $('#service-position').css({'z-index':'-999','opacity':0});
    $('.page__bd').css('z-index','0').show();
}



function compress(file, callback){
    var reader = new FileReader();

    reader.onload = function (e) {

        var image = $('<img/>');
        image.on('load', function () {
            var square = 640;
            var canvas = document.createElement('canvas');


            if (this.width > this.height) {
                canvas.width = Math.round(square * this.width / this.height);
                canvas.height = square;
            } else {
                canvas.height = Math.round(square * this.height / this.width);
                canvas.width = square;
            }

            var context = canvas.getContext('2d');
            context.clearRect(0, 0, square, square);
            var imageWidth = canvas.width;
            var imageHeight = canvas.height;
            var offsetX = 0;
            var offsetY = 0;
            context.drawImage(this, offsetX, offsetY, imageWidth, imageHeight);
            var data = canvas.toDataURL('image/jpeg', 0.8);
            console.log([imageWidth,imageHeight]);
            callback(data);
        });

        image.attr('src', e.target.result);
    };

    reader.readAsDataURL(file);
}



