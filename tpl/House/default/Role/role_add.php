<include file="Public:header"/>

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
								<a href="#powerinfo" data-toggle="tab">权限设置</a>
							</li>
						</ul>
					</div>
					<form  class="form-horizontal" method="post" action="__SELF__" onsubmit="return check_submit()">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <input  name="wid" id="order_refund_check_wid" type="hidden"  value="0" />
                                <input   id="is_order_refund_check_open" type="hidden"  value="{pigcms{$is_order_refund_check_open}" />
								<div class="form-group">
									<label class="col-sm-1"><label for="worker">管理员</label> <span style="color: red">*</span></label>
									<div class="col-sm-2" style="padding:0px ;position:relative">
										<input class="col-sm-2" size="20" name="worker" id="worker" type="text"  value="" autocomplete="off" style="width:100%" placeholder="输入姓名或手机号搜索" />
										<div id="searchBox" style="display: none;border:1px solid #F59942;position:absolute;left:0px;width:100%;max-height:300px;overflow-y:auto">
	                            		</div>
										<div id="dropdown-menu" class="dropdown-menus " style="display:none"></div>
									</div>
									<span style="display:inline-block;margin-left:20px"><if condition="$is_order_refund_check_open gt 0"> <span style="color:red;"> 开启了修改物业服务时间审核或者订单作废审核、退款审核，必须从组织架构中添加管理员！</span> <else/> 从组织架构中添加管理员 </if> </span>
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
									<input type="hidden" name="admin_id" value="{pigcms{$admin.id}"/>
									<tr>
										<th width="160px" style="border-right: 1px solid #ddd;">
											<label style="font-size: 18px;color:#999">权限分组</label>
										</th>
										<td>
											<div class="form-group" style="margin-bottom: 0px;">
												<select name="group_id" id="group_id" style="width: 250px;margin-left: 10px;" onchange="select_group(this)">
							                        <option value="0">不选择分组</option>
													<volist name="group_list" id="vo" >
							                        <option value="{pigcms{$vo.group_id}">{pigcms{$vo.name}</option>
							                    	</volist>
							                    </select>
												<span class="form_tips">选择权限分组后，该管理员的权限以分组中的权限为准。</span>
											</div>
										</td>
									</tr>
									<tr class="menus"><td colspan="2"><label><input type="checkbox" id="all"/> 全选</label></td></tr>
									<volist name="menus" id="rowset">
										<tr class="menus">
											<th width="160px" style="border-right: 1px solid #ddd;">
												<label style="font-size: 18px;color:#999"><input type="checkbox" class="menu_{pigcms{$rowset['id']} father_menu" value="{pigcms{$rowset['id']}" name="menus[]">　{pigcms{$rowset['name']}</label>
											</th>
											<td>
											<volist name="rowset['child']" id="row" key="k">
												<ul>
													<if condition="$k eq $rowset['count']">
														<li>
													<else/>
														<li style="border-bottom: 1px solid #ddd;">
													</if>
														<label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#2b7dbc">
															<input type="checkbox" class="child_menu_{pigcms{$row['fid']} cf_menu_{pigcms{$row['id']} child_menu" value="{pigcms{$row['id']}"  name="menus[]" data-fid="{pigcms{$row['fid']}" data-id="{pigcms{$row['id']}"/>
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
                                                                                <input type="checkbox" class="child_menu2_{pigcms{$level_3['fid']} cf_menu_{pigcms{$level_3['id']} child_menu2" value="{pigcms{$level_3['id']}"  name="menus[]" data-fid="{pigcms{$level_3['fid']}"  data-id="{pigcms{$level_3['id']}"/>　{pigcms{$level_3['name']}
                                                                            </label>
                                                                            <php>if ($level_3['child']) {</php>
                                                                            <div style="padding-left: 30px;">
                                                                                <php>
                                                                                    foreach($level_3['child'] as $level_4) {
                                                                                </php>
                                                                                <label>
                                                                                    <input type="checkbox" class="child_menu3_{pigcms{$level_3['fid']} child_menu3_{pigcms{$level_4['fid']} child_menu3" value="{pigcms{$level_4['id']}"  name="menus[]" data-fid="{pigcms{$level_4['fid']}"/>　{pigcms{$level_4['name']}
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
<script type="text/javascript">

$(document).ready(function(){
	$('#all').click(function(){
        if ($(this).attr('checked')) {
            $('.father_menu, .child_menu, .child_menu2, .child_menu3').attr('checked', true);
        } else {
            $('.father_menu, .child_menu, .child_menu2, .child_menu3').attr('checked', false);
        }
	});
	$('.father_menu').click(function(){
		var fid = $(this).val();
		if ($(this).attr('checked')) {
			$('.child_menu_' + fid).attr('checked', true);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val()
				$('.child_menu2_' + id).attr('checked', true);
                $('.child_menu3_' + id).attr('checked', true);
			});
		} else {
			$('.child_menu_' + fid).attr('checked', false);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val()
			    $('.child_menu2_' + id).attr('checked', false);
                $('.child_menu3_' + id).attr('checked', false);
			});
		}
	});
    $('.child_menu').click(function(){
        var fid = $(this).attr('data-fid');
        var id = $(this).attr('data-id');
        if ($(this).attr('checked')) {
            $('.menu_' + fid).attr('checked', true);
            $('.child_menu2_' + id).attr('checked', true);
            $('.child_menu3_' + id).attr('checked', true);
        } else {
            var flag = false;
            $('.child_menu_' + fid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            $('.menu_' + fid).attr('checked', flag);
            $('.child_menu2_' + id).attr('checked', false);
            $('.child_menu3_' + id).attr('checked', false);
        }
    });
    $('.child_menu2').click(function(){
        var fid = $(this).attr('data-fid');
        var id = $(this).attr('data-id');
        if ($(this).attr('checked')) {
            $('.cf_menu_' + fid).attr('checked', true);
            var ffid = $('.cf_menu_' + fid).attr('data-fid');
            $('.menu_' + ffid).attr('checked', true);
            $('.child_menu3_' + id).attr('checked', true);
        } else {
            var flag = false;
            $('.child_menu2_' + fid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            $('.cf_menu_' + fid).attr('checked', flag);

            var flag = false;
            var ffid = $('.cf_menu_' + fid).attr('data-fid');
            $('.child_menu_' + ffid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            $('.menu_' + ffid).attr('checked', flag);
            $('.child_menu3_' + id).attr('checked', false);
        }
    });

    $('.child_menu3').click(function(){
        var fid = $(this).attr('data-fid');
        console.log('fid3', fid);
        if ($(this).attr('checked')) {
            $('.cf_menu_' + fid).attr('checked', true);
            var ffid = $('.cf_menu_' + fid).attr('data-fid');
            $('.cf_menu_' + ffid).attr('checked', true);
            var fffid = $('.cf_menu_' + ffid).attr('data-fid');
            $('.menu_' + fffid).attr('checked', true);
        } else {
            var flag = false;
            $('.child_menu3_' + fid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            console.log('child_menu3',flag,fid);
            $('.cf_menu_' + fid).attr('checked', flag);
            var ffid = $('.cf_menu_' + fid).attr('data-fid');
            flag = false;
            $('.child_menu2_' + ffid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            $('.cf_menu_' + ffid).attr('checked', flag);

            flag = false;
            var fffid = $('.cf_menu_' + ffid).attr('data-fid');
            $('.child_menu_' + fffid).each(function(){
                if ($(this).attr('checked')) {
                    flag = true;
                }
            });
            $('.menu_' + fffid).attr('checked', flag);
        }
    });
	select_group();
});

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
    var is_order_refund_check_open=$('#is_order_refund_check_open').val()||0;
    var check_wid=$('#order_refund_check_wid').val()||0;
    is_order_refund_check_open=$.trim(is_order_refund_check_open);
    if((is_order_refund_check_open==1 || is_order_refund_check_open=='1') && ( check_wid==0 || check_wid=='0' || check_wid=='')){
    	layer.alert('开启了修改物业服务时间审核或者订单作废审核、退款审核，必须从组织架构中添加管理员');
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
        var wid = $(this).attr('value');
        if(!wid){
            wid=0;
        }
        $('#account').val(phone);
        $('#phone').val(phone);
        $('#realname').val(name);
        $('#worker').val(name);
        $('#order_refund_check_wid').val(wid);
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
        $.post("{pigcms{:U('Index/ajax_get_worker')}",{'search':search},function(data){

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

<include file="Public:footer"/>