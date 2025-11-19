<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('import_village')}">物业管理</a>
            </li>
            <li class="active">房间管理</li>
        </ul>
    </div>

    <!-- 内容头部 -->
    <div class="page-content">
        <div class="tips">
            <span>房间列表，IC卡绑定当前只支持狄耐克设备</span>
        </div>
        <div class="page-content-area">

            <if condition="!$base_third_config && !$base_third_config['third_id'] && $limit_info['room_limit_num'] gt 0">
                <div style="background-color: #dff0d8;padding: 10px;margin-bottom: 10px;color: red;">
                    剩余导入房间数量为{pigcms{$limit_info['surplus_room_num']}
                </div>
            </if>
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Unit/import_village')}" method="get" >
                    <input type="hidden" name="c" value="Unit"/>
                    <input type="hidden" name="a" value="import_village"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>物业编号</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>姓名</option>
                        <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>手机号</option>
                        <if condition="$now_house['write_iccard']">
							<option value="5" <if condition="$_GET['find_type'] eq 5">selected="selected"</if>>门禁卡编号</option>
                        </if>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    状态：
                    <select name="status" class="" style=" margin-right:10px;height:42px;">
						<option value="" <if condition="!isset($_GET['status']) || $_GET['status'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>空置</option>
                        <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>审核中</option>
                        <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已绑定业主</option>
                        <option value="0" <if condition="$_GET['status'] eq '0'">selected="selected"</if>>关闭</option>
                    </select>
                    房间:
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
                    &nbsp;&nbsp;
                    <input class="btn btn-success" type="submit" id="find_submit" value="查找房间" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Unit/import_village')}'">重置</a>
                    <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(38,$house_session['menus']) && $config['houseRoomExportImportSwitch'] eq 1">
                        <a href="{pigcms{:U('roomExportImport')}" class="btn btn-success " style="margin-left: 20px;">变更导入</a>&nbsp;
                    </if>
                    <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(38,$house_session['menus'])">
                        <a onclick="exports();" class="btn btn-success " >导出数据</a>
                        <else/>
                        <button class="btn btn-success disabled" title="{pigcms{$base_third_title}" disabled="disabled" >导出数据</button>
                    </if>
                    <if condition="!$base_third_config && !$base_third_config['third_id'] && in_array(38,$house_session['menus'])">
                        <a href="{pigcms{:U('import_village_add')}" class="btn btn-success ">导入数据</a>
                        <if condition="C('config.customized_meter_reading')">
                        <a href="{pigcms{:U('import_village_num')}" class="btn btn-success fr">导入三表</a>&nbsp;
                        </if>
                    <else/>
                        <button class="btn btn-success disabled" title="{pigcms{$base_third_title}" disabled="disabled">导入数据</button>
                    </if>
                </form>
            </div>

        	
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <if condition="!$base_third_config && !$base_third_config['third_id']">
                                        <th width="5%">选择房间</th>
                                    </if>
                                    <th width="5%">排序</th>
                                   <!-- <th width="5%">楼层编号</th>-->
                                    <th width="8%">物业编号</th>
                                    <th width="5%">楼号</th>
                                    <th width="5%">单元名称</th>
									<th width="5%">层号</th>
									<th width="5%">房间号</th>
<!--                                    <th width="5%">房屋面积</th>-->
<!--                                    <th width="5%">姓名</th>-->
                                    <th width="8%">使用状态</th>
<!--                                    <th width="8%">手机号</th>-->
<!--                                    <th width="15%">住址</th>-->
                                    <th width="10%">已解绑住户记录</th>
                                    <th width="8%">IC卡管理</th>
                                    <if condition="$result['new_charge_time']">
                                        <th width="15%">物业服务时间</th>
                                    </if>
                                    <th width="10%">状态</th>
                                    <if condition="!$base_third_config && !$base_third_config['third_id']">
                                        <th class="button-column" width="22%">操作</th>
                                    </if>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$result['list']">
                                    <volist name="result['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                <td>
                                                    <div class="tagDiv">
                                                        <if condition="$vo['status'] eq 1">
                                                            <input name="is_remind" value="{pigcms{$vo.pigcms_id}" type="checkbox">
                                                        <else/>
                                                            <input name="is_remind_false" value="{pigcms{$vo.pigcms_id}" disabled="disabled" type="checkbox">
                                                        </if>

                                                    </div>
                                                </td>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
                                           <!-- <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>-->
                                            <td>
                                                <if condition="$vo['property_number']">
                                                    <div class="tagDiv">{pigcms{$vo['property_number']}</div>
                                                    <else/>
                                                    <div class="tagDiv">{pigcms{$vo.usernum}</div>
                                                </if>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.layer}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.room}</div></td>
<!--                                            <td><div class="tagDiv">{pigcms{$vo.housesize}</div></td>-->
<!--                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>-->
                                            <td>
                                                <div class="tagDiv">
                                                <if condition='$vo["user_status"] eq 1'>
                                                    <span class="">业主入住</span>
                                                <elseif condition='$vo["user_status"] eq 2' />

                                                    <span class="">未入住</span>
                                                <elseif condition='$vo.user_status eq 3' />

                                                    <span class="">租客入住</span>
                                                <else/>
                                                    <span class="">无</span>
                                                </if>
                                                </div>
                                            </td>
<!--                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>-->
<!--                                            <td><div class="tagDiv">{pigcms{$vo.adress}</div></td>-->
                                            <td><div class="tagDiv"><a href="javascript:;" style="width: 60px;color:lightseagreen;" class="" title="" onclick="record({pigcms{$vo.pigcms_id})">点击查看</a></div></td>
                                           <td><div class="tagDiv"><a href="javascript:;" style="width: 60px;color:lightseagreen;" class="" title="" onclick="card_list({pigcms{$vo.pigcms_id})">点击查看</a></div></td>
                                            <if condition="$result['new_charge_time']">
                                                <td width="15%">{pigcms{$vo.service_cycle}</td>
                                            </if>

                                            <td><div class="tagDiv">
												<if condition='$vo["status"] eq 1'>
													<span class="green">空置</span>
												<elseif condition='$vo["status"] eq 2' />
													<span class="green">审核中</span>
												<elseif condition='$vo["status"] eq 3' />
													<span class="green">已绑定业主 <if condition="$vo['uid']==0">&nbsp;<span class="red">[未注册]</span></if></span>
												<else />
													<span class="red">关闭</span>
												</if>
											</div></td>
                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                <td class="button-column">
                                                    <div class="tpl-table-black-operation">
                                                        <if condition="in_array(39,$house_session['menus'])">
                                                            <a title="编辑" href="{pigcms{:U('import_village_edit',array('id'=>$vo['pigcms_id'],'page'=>$page))}">编辑</a>
                                                        </if>
                                                        <if condition="in_array(40,$house_session['menus'])">
                                                            &nbsp;&nbsp;<a title="删除" class="item-delete tpl-table-black-operation-del" onclick="delItemData('{pigcms{$vo.room}','{pigcms{$vo.pigcms_id}')" href="javascript:;" >删除</a>
                                                        </if>
                                                    </div>
                                                </td>
                                            </if>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何信息。</td></tr>
                                </if>
                            </tbody>
                        </table>

                        <if condition="!$base_third_config && !$base_third_config['third_id']">
                            <input type="button" value="全选" class="btn" id="selectAll">&nbsp;
                            <input type="button" value="全不选" class="btn" id="unSelect">  &nbsp;
                            <input type="button" value="反选" class="btn" id="reverse">  &nbsp;
                            <input type="button" value="删除" class="btn" id="getValue">&nbsp;
                        </if>
                        <div style="float: right;">
                            <input type="text" name="pageNumber" id="pageNumber" onkeyup="value=value.replace(/[^\d]/g,'')" value="" class="input-sm" style="width: 50px;">&nbsp;
                            <input type="button" class="btn btn-sm btn-default" value="跳转" onclick='flipPage()' class="btn" id="uuu">
                        </div>
                    
    
                        {pigcms{$result.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .tips {
        margin-left: 10px;
        position: relative;
        padding: 10px 10px 10px 15px;
        color: rgba(0, 0, 0, 0.65);
        line-height: 1.5;
        border-radius: 2px;
        background-color: #e6f7ff;
        border: 1px solid #91d5ff;
    }
</style>
<script>
    var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
    var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
    var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose.js?t=001"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
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


    // var deposit_id = '';
    // $("input[name='is_check']").each(function () {
    //     if($(this).attr("checked")){
    //         deposit_id += $(this).val()+',';
    //     }
    // });
    //
    // deposit_id=deposit_id.substring(0,deposit_id.length-1);
    //
    // if(!deposit_id){
    //     layer.msg('请选择您要删除的信息。');
    //     return false;
    // }

</script>
<script>

    function record(id) {
        localStorage.removeItem('choose_floor');
        var url = "{pigcms{:U('record_list')}";
        var href = url + '&vacancy_id='+id;
        layer.open({
            type: 2,
            title:'住户记录（该住户记录仅记录该房间已解绑的用户）',
            content: href,
            area: ['750px', '485px'],
            maxmin: false
        });
    }

    function card_list(id) {
        localStorage.removeItem('choose_floor');
        var url = "{pigcms{:U('card_list')}";
        var href = url + '&vacancy_id='+id;
        layer.open({
            type: 2,
            title:'IC卡管理(仅展示该房间绑定的IC卡，不显示业主/家属/租客绑定的IC卡)',
            content: href,
            area: ['750px', '485px'],
            maxmin: false
        });
    }
    // 翻页
    function flipPage(){
        if($("#pageNumber").val() > parseInt("{pigcms{$result.pageCount}")){
            alert('请输入正确的页码。');
        }else{
            location.href="{pigcms{:U('import_village')}&page="+$("#pageNumber").val();
        }
    }

    $("#selectAll").click(function () {
       $("input[name='is_remind']").each(function () {
            $(this).attr("checked", true);
        }); 
    });

    $("#unSelect").click(function () {  
       $("input[name='is_remind']").each(function () {
            $(this).attr("checked", false);
        });
    });

    $("#reverse").click(function () { 
        $("input[name='is_remind']").each(function () {
            this.checked=!this.checked;
        });
    });

    $("#getValue").click(function () { 
        if(confirm('确认删除选中的信息？删除时如果房间有未缴的账单将被作废。')){
            var pigcms_id = '';
            $("input[name='is_remind']").each(function () {
                if($(this).attr("checked")){
                    pigcms_id += $(this).val()+',';
                }
            });

            pigcms_id=pigcms_id.substring(0,pigcms_id.length-1);

            if(!pigcms_id){
                alert('请选择您要删除的信息。');
                return false;
            }

            var del_url = "{pigcms{:U('import_village_del_many')}";

            $.post(del_url,{'pigcms_id':pigcms_id},function(data){
                if(data.status == 1){
                    alert(data.msg);
                    location.href = location.href;
                }else{
                    alert(data.msg)
                }
            },'json');
        }
    });

    var village_del_url= "{pigcms{:U('import_village_del')}";
    function delItemData(room,idd){
        layer.confirm('确认删除房间号是【'+room+'】这条数据？<br/>一旦删除无法恢复，请谨慎操作。<br/>删除时如果房间有未缴的账单将被作废。', {
            btn: ['确定','取消'] //按钮
        }, function(){
            window.location.href=village_del_url+'&id='+idd;
        });
    }

    $(".transfrom-room").on('click' , function(){
    	
    	if(confirm("您确定将当前{pigcms{$config['house_name']}业主的房间信息导入进来吗？")){
    			
			art.dialog.open("{pigcms{:U('updata_old_village_room_info',array('is_true_old_user'=>$is_old_data))}",{lock:true,title:'导入房间',width:760,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    		
    		//location.href="{pigcms{:U('updata_old_village_room_info',array('is_true_old_user'=>$is_old_data))}";
    		
    	}else{
    		return flse;	
    	}
    		
    });

    // function importAdd(){
    // 	window.location.href = "{pigcms{:U('import_village_add')}";
    // }

    function importUserDetail(){
    	window.location.href = "{pigcms{:U('User/detail_import')}";
    }
    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Unit/roomExport')}"
</script>

<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
