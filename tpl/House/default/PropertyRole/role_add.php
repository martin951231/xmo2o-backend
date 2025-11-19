<include file="NewPublic:header"/>

<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Role/role_list')}">权限管理</a>
			</li>
			<li class="active">添加管理员</li>
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
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本设置</a>
							</li>
							<li>
								<a href="#powerinfo" data-toggle="tab">小区权限设置</a>
							</li>
                            <li>
                                <a href="#propertypowerinfo" data-toggle="tab">物业权限设置</a>
                            </li>
						</ul>
					</div>
					<form  class="form-horizontal" method="post" action="__SELF__" onsubmit="return check_submit()">
						<div class="tab-content">
                            <div id="propertypowerinfo" class="tab-pane">
                                <div class="alert alert-info" style="margin:10px;">设置普通管理员时，可根据员工管理方向设置不同的功能权限，物业后台及小区后台的功能都可自定义进行设置<br/>
                                </div>
                                <table class="table table-border" width="100%">
                                    <input type="hidden" name="admin_id" value="{pigcms{$id}"/>
                                    <tr class="menus"><td colspan="2"><label><input type="checkbox" id="all"/>全选</label></td></tr>
                                    <volist name="menus" id="rowset">
                                        <tr class="menus">
                                            <th width="160px" style="border-right: 1px solid #ddd">
                                                <label style="font-size: 18px;color:#999"><input type="checkbox" class="menu_{pigcms{$rowset['id']} father_menu" value="{pigcms{$rowset['id']}" name="menus[]" <if condition="in_array($rowset['id'],$role['menus'])">checked</if>>　{pigcms{$rowset['name']}</label>
                                            </th>
                                            <td>
                                                <volist name="rowset['child']" id="row" key="k">
                                                    <ul>
                                                        <if condition="$k eq $rowset['count']">
                                                            <li>
                                                                <else/>
                                                            <li style="border-bottom: 1px solid #ddd">
                                                        </if>
                                                        <label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#2b7dbc">
                                                            <input type="checkbox" class="child_menu_{pigcms{$row['fid']} cf_menu_{pigcms{$row['id']} child_menu" value="{pigcms{$row['id']}"  name="menus[]" data-fid="{pigcms{$row['fid']}" data-id="{pigcms{$row['id']}" <if condition="in_array($row['id'],$role['menus'])">checked</if> >
                                                            　{pigcms{$row['name']}
                                                        </label>　
                                                        <if condition="$row['child']">
                                                            <div class="" style="padding-left: 30px;">
                                                                <volist name="row['child']" id="level_3" key="k3">
                                                                    <ul>
                                                                        <php>if ($k3==$row['count']) {</php>
                                                                        <li>
                                                                            <php>} else {</php>
                                                                        <li style="border-bottom: 1px solid #ddd;">
                                                                            <php>}</php>
                                                                            <label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#33cc7b;">
                                                                                <input type="checkbox" class="child_menu2_{pigcms{$level_3['fid']} cf_menu_{pigcms{$level_3['id']} child_menu2" value="{pigcms{$level_3['id']}"  name="menus[]" data-fid="{pigcms{$level_3['fid']}"  data-id="{pigcms{$level_3['id']}" <if condition="in_array($level_3['id'],$role['menus'])">checked</if> />　{pigcms{$level_3['name']}
                                                                            </label>
                                                                            <php>if ($level_3['child']) {</php>
                                                                            <div style="padding-left: 30px;">
                                                                                <php>
                                                                                    foreach($level_3['child'] as $level_4) {
                                                                                </php>
                                                                                <label>
                                                                                    <input type="checkbox" class="child_menu3_{pigcms{$level_3['fid']} child_menu3_{pigcms{$level_4['fid']} child_menu3" value="{pigcms{$level_4['id']}"  name="menus[]" data-fid="{pigcms{$level_4['fid']}" <if condition="in_array($level_4['id'],$role['menus']) || $is_village_auth">checked</if> />　{pigcms{$level_4['name']}
                                                                                </label>　
                                                                                <php>
                                                                                    }
                                                                                </php>
                                                                            </div>
                                                                            <php>}</php>
                                                                        </li>
                                                                    </ul>
                                                                </volist>
                                                            </div>
                                                        </if>
                                                        </li>
                                                    </ul>
                                                </volist>
                                            </td>
                                        </tr>
                                    </volist>
                                </table>
                            </div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="worker">管理员</label></label>
									<div class="col-sm-2" style="padding:0px ;position:relative">
										<input class="col-sm-2" size="20" name="worker" id="worker" type="text"  value="" autocomplete="off" style="width:100%" placeholder="输入姓名或手机号搜索" />
										<div id="searchBox" style="display: none;border:1px solid #F59942;position:absolute;left:0px;width:100%;max-height:300px;overflow-y:auto">
	                            		</div>
										<div id="dropdown-menu" class="dropdown-menus " style="display:none"></div>
									</div>
									<label class="col-sm-2">从组织架构中添加管理员</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="account">登录账号</label>&nbsp;<span style="color: red">*</span></label>
									<input class="col-sm-2" size="20" name="account" id="account" type="text"  value="" autocomplete="off"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pwd">登录密码</label>&nbsp;<span style="color: red">*</span></label>
									<input class="col-sm-2" size="20" name="pwd" id="pwd" type="password"  value="" autocomplete="new-password"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="repwd">确认密码</label>&nbsp;<span style="color: red">*</span></label>
									<input class="col-sm-2" size="20" name="repwd" id="repwd" type="password"  value="" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="realname">姓名</label></label>
									<input class="col-sm-2" size="20" name="realname" id="realname" type="text"  value="" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">手机号</label></label>
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text"  value="" />
								</div>
								<!-- <div class="form-group">
									<label class="col-sm-1"><label for="email">邮箱</label></label>
									<input class="col-sm-2" size="20" name="email" id="email" type="text"  value="" />
								</div> -->
								<div class="form-group">
									<label class="col-sm-1"><label for="remarks">备注</label></label>
									<textarea name="remarks" id="remarks"  style="width:400px; height:150px"></textarea>
								</div>
							</div>
							<div id="powerinfo" class="tab-pane">
								<table cellpadding="0" cellspacing="0" class="table table-border" width="100%">

									<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
											<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
											<!--					<span id="choose_cityarea" class=''></span>-->
											<input value="{pigcms{$village_name}" class="col-sm-2" name="village_name" id="village_name" type="text" placeholder="请输入小区名称" style="margin-right:10px;font-size:16px;height:30px;"/>
											&nbsp;&nbsp;
											<input class="btn btn-success" type="button" id="search" value="查询" />&nbsp;
											<a class="btn btn-success" id="reset">重置</a>
                                            <a class="btn btn-success" onclick="addPropertyRole(0,{pigcms{:$id})">批量权限分配</a>

									</div>

									<div class="row" id="search_list">
										<div class="col-xs-12">
												<table class="table table-striped table-bordered table-hover" style="position:relative;">
													<thead id="show_list">
														<tr>
															<th width="2%" id="quanbu"><input type="checkbox" id="all_village"/> 全选</th>
															<th width="5%">小区名称</th>

															<th width="10%">小区地址</th>
                                                            <th width="10%">分配权限</th>
														</tr>
													</thead>
<!--													<tbody class="class-checkbox">-->
<!--													<if condition="$dataList">-->
<!--													<volist name="dataList" id="vo">-->
<!---->
<!--													<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">-->
<!--													<td><input type="checkbox" class="menu_{pigcms{$vo['village_id']} father_menu" value="{pigcms{$vo['village_id']}" name="menus[]"></td>-->
<!--													<td><div class="tagDiv">{pigcms{$vo.village_name}</div></td>-->
<!---->
<!--													<td><div class="tagDiv">{pigcms{$vo.village_address}</div></td>-->
<!--													</tr>-->
<!---->
<!--													</volist>-->
<!---->
<!--													<else/>-->
<!--													<tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>-->
<!--													</if>-->
<!--													</tbody>-->

												</table>

											</div>
									</div>
								</table>
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
	</div>
<style>
	#dropdown-menu{
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
		top:33px;
		position:absolute;
		z-index: 10;
		display: block;
		background: #fff;
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

<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>

<script type="text/javascript" src="/tpl/System/Static/js/area_pca.js"></script>
<script>

	var static_path = "{pigcms{$static_path}";
	//	var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
	var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
	var ajax_check_village_add = "{pigcms{:U('NewProperty/ajax_check_village_add')}"
	var property_ids = "{pigcms{$property_id}"


	$("#search").on("click",function(){
		$.post("{pigcms{:U('PropertyRole/ajax_village_list')}",{'village_name':$("#village_name").val(),'province_id':$("#choose_provincess").val(),'city_id':$("#choose_cityss").val(),'area_id':$("#choose_areass").val()},function(data){
            if(data && data.length>0){

				$("#all_village").css('display','none')
               data = JSON.parse(data)

				var str = '<tbody class="class-checkbox" id="hide_list">';
				for(var i in data){

					str +='<tr class="even" style="width: 100%;"><td><input type="checkbox" class="menu_'+ data[i]["village_id"]+' father_menu_village " value="'+data[i]["village_id"]+'" name="menus_village[]"></td><td><div class="tagDiv">'+data[i]["village_name"]+'</div></td><td><div class="tagDiv">'+data[i]["village_address"]+'</div></td><td><div class="tagDiv"><a onclick="addPropertyRole('+data[i]["village_id"]+')" style="cursor: pointer;color: #0a94ff">分配权限</a></div></td></tr>';
				}

				str +='</tbody>';
				$("#hide_lists").remove("");
				$("#hide_list").remove("");

				$("#show_list").after(str);


			}
		})
	});
	$("#reset").on("click",function(){
        $('#choose_provincess').val(0);
        $('#choose_cityss').val(0);
        $('#choose_areass').val(0);
		$("#village_name").attr("value","");
		$("#hide_list").remove("");
		$.post("{pigcms{:U('PropertyRole/ajax_village_list')}",{'village_name':$("#village_name").val(),'province_id':$("#choose_provincess").val(),'city_id':$("#choose_cityss").val(),'area_id':$("#choose_areass").val()},function(data){
			if(data && data.length>0){

				$("#all_village").css('display','none')
				data = JSON.parse(data)

				var str = '<tbody class="class-checkbox" id="hide_list">';
				for(var i in data){

					str +='<tr class="even" style="width: 100%;"><td><input type="checkbox" class="menu_'+ data[i]["village_id"]+' father_menu_village" value="'+data[i]["village_id"]+'" name="menus_village[]"></td><td><div class="tagDiv">'+data[i]["village_name"]+'</div></td><td><div class="tagDiv">'+data[i]["village_address"]+'</div></td><td><div class="tagDiv"><a onclick="addPropertyRole('+data[i]["village_id"]+')" style="cursor: pointer;color: #0a94ff">分配权限</a></div></td></tr>';
				}

				str +='</tbody>';
				$("#hide_lists").remove("");
				$("#show_list").after(str);


			}
		})

	});
</script>
<script type="text/javascript">

$(document).ready(function(){
    $('#all_village').click(function(){
        if ($(this).attr('checked')) {
            $('.father_menu_village').attr('checked', true);
        } else {
            $('.father_menu_village').attr('checked', false);
        }
    });
	$('#all').click(function(){
		if ($(this).attr('checked')) {
			$('.father_menu, .child_menu, .child_menu2').attr('checked', true);
		} else {
			$('.father_menu, .child_menu, .child_menu2').attr('checked', false);
		}
	});
	$('.father_menu').click(function(){
		var fid = $(this).val();
		if ($(this).attr('checked')) {
			$('.child_menu_' + fid).attr('checked', true);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val()
				$('.child_menu2_' + id).attr('checked', true);
			});
		} else {
			$('.child_menu_' + fid).attr('checked', false);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val()
			$('.child_menu2_' + id).attr('checked', false);
			});
		}
	});
	$('.child_menu').click(function(){
		var fid = $(this).attr('data-fid');
		var id = $(this).attr('data-id');
		if ($(this).attr('checked')) {
			$('.menu_' + fid).attr('checked', true);
			$('.child_menu2_' + id).attr('checked', true);
		} else {
			var flag = false;
			$('.child_menu_' + fid).each(function(){
				if ($(this).attr('checked')) {
					flag = true;
				}
			});
			$('.menu_' + fid).attr('checked', flag);
			$('.child_menu2_' + id).attr('checked', false);
		}
	});
	$('.child_menu2').click(function(){
		var fid = $(this).attr('data-fid');
		if ($(this).attr('checked')) {
			$('.cf_menu_' + fid).attr('checked', true);
			var ffid = $('.cf_menu_' + fid).attr('data-fid');
			$('.menu_' + ffid).attr('checked', true);
		} else {
			// var flag = false;
			// $('.child_menu2_' + fid).each(function(){
			// 	if ($(this).attr('checked')) {
			// 		flag = true;
			// 	}
			// });
			// $('.cf_menu_' + fid).attr('checked', flag);

			// var flag = false;
			// var ffid = $('.cf_menu_' + fid).attr('data-fid');
			// $('.child_menu_' + ffid).each(function(){
			// 	if ($(this).attr('checked')) {
			// 		flag = true;
			// 	}
			// });
			// $('.menu_' + ffid).attr('checked', flag);
		}
	});
	//select_group();


	$.post("{pigcms{:U('PropertyRole/ajax_village_list')}",{'village_name':$("#village_name").val(),'province_id':$("#choose_provincess").val(),'city_id':$("#choose_cityss").val(),'area_id':$("#choose_areass").val()},function(data){
		if(data && data.length>0){
			//$("#all_village").css('display','none')
			data = JSON.parse(data)

			var str = '<tbody class="class-checkbox" id="hide_lists">';
			for(var i in data){

				str +='<tr class="even" style="width: 100%;"><td><input type="checkbox" class="menu_'+ data[i]["village_id"]+' father_menu_village" value="'+data[i]["village_id"]+'" name="menus_village[]"></td><td><div class="tagDiv">'+data[i]["village_name"]+'</div></td><td><div class="tagDiv">'+data[i]["village_address"]+'</div></td><td><div class="tagDiv"><a onclick="addPropertyRole('+data[i]["village_id"]+')" style="cursor: pointer;color: #0a94ff">分配权限</a></div></td></tr>';


			}

			str +='</tbody>';

			$("#show_list").after(str);

		}
	})

});

function addPropertyRole(village_id){
    console.log(village_id);
    if(village_id == 0){
        var village_id=[];
        $("input[name='menus_village[]']:checked").each(function(){
            village_id.push($(this).val());
        });
        console.log('123',village_id);
    }
    var url = "{pigcms{:U('PropertyRole/village_role_list')}";
    var href = url + '&village_id='+village_id+'&admin_id=0';
    layer.open({
        type: 2,
        title:'权限设置',
        content: href,
        area: ['1150px', '785px'],
        maxmin: false
    });
}

function submitCallBack(info){
		window.top.msg(1,info,true);
	  top.art.dialog({id:"menu"}).close();
}

function check_submit(){
	var account = $('#account').val();
	var pwd = $('#pwd').val();
	var repwd = $('#repwd').val();
	if (account=='') {
		layer.alert('请填写账号');
		return false;
	}
	if (pwd=='') {
		layer.alert('请填写密码');
		return false;
	}
	if (pwd!=repwd) {
		layer.alert('两次输入的密码不一样');
		return false;
	}
}


$('#worker').keyup(function() {
		var search= $.trim(this.value);
		get_worker(search);
	}).blur(function(){
		 $("#searchBox").html("").hide(); //输入框失去焦点的时候就隐藏搜索框
	});

	$('#worker').focus(function() {
		var search= $.trim(this.value);
		get_worker(search);
	})

	$(document).on("click",'.ant-select-dropdown-menu-item',function(e){
        position_id = $(this).val();
        $('#car_position_id').val(position_id);
        var name = $(this).attr('name');
        var phone = $(this).attr('phone');
        $('#account').val(phone);
        $('#phone').val(phone);
        $('#realname').val(name);
        $('#worker').val(name);
        
        $("#dropdown-menu").html("").hide(); //输入框失去焦点的时候就隐藏搜索框

    })
    $(document).bind("click",function(e){
        var target=$(e.target);
        if(target.closest("#dropdown-menu").length==0){
            $("#dropdown-menu").html("").hide();
        }
    })

function get_worker(search){
	if(search!=""){//检测键盘输入的内容是否为空，为空就不发出请求
        $.post("{pigcms{:U('PropertyRole/ajax_get_worker')}",{'search':search},function(data){

            if (data && data!=null && data.length> 0) {//检测返回的结果是否为空
                var str = '<div><ul class="findtype-menu" style="    overflow-y: auto;max-height: 400px;">';
                for(var i in data){ 
                    str += '<li class="ant-select-dropdown-menu-item " value="'+data[i]["wid"]+'" phone="'+data[i]["phone"]+'" name="'+data[i]["name"]+'">'+data[i]["name"]+'|'+data[i]["phone"]+'</li>';
                }; 

                str+="</ul></div>";  
				console.log(str);
                $("#dropdown-menu").html(str).show();//将搜索到的结果展示出来
            } else {
                $("#dropdown-menu").html("").hide();
            }  
        },'json');
    }else{
       $("#dropdown-menu").html("").hide();  //没有查询结果就隐藏搜索框
    }
}

// 切换权限分组
function select_group(){
	var group_id = $('#group_id').val();
	if (group_id>0) {
		$('.menus').hide();
	}else{
		$('.menus').show();
	}
}

</script>

<include file="NewPublic:footer"/>