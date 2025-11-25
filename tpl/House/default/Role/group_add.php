<include file="Public:header"/>

<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Role/group_list')}">权限分组列表</a>
			</li>
			<li class="active">添加分组</li>
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
					<form  class="form-horizontal" method="post" action="__SELF__">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">分组名称</label>&nbsp;<span style="color: red">*</span></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text"  value="{pigcms{$role.name}" />
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="remarks">备注</label></label>
									<textarea name="remarks" id="remarks"  style="width:400px; height:150px">{pigcms{$role.remarks}</textarea>
								</div>
								<!-- <div class="form-group">
									<label class="col-sm-4"><label for="status">状态</label>
									<label style="padding-left:90px;padding-right:20px;"><input name="status" value="1" type="radio" class="ace" checked/><span class="lbl" style="z-index: 1" >开启</span></label>
									<label style="padding-left:0px;"><input name="status" value="2" type="radio" class="ace"/><span class="lbl" style="z-index: 1">关闭</span></label>
								</div> -->
							</div>
							<div id="powerinfo" class="tab-pane">
								<table cellpadding="0" cellspacing="0" class="table table-border" width="100%">
									<input type="hidden" name="admin_id" value="{pigcms{$admin.id}"/>
									<tr><td colspan="2"><label><input type="checkbox" id="all"/> 全选</label></td></tr>
									<volist name="menus" id="rowset">
										<tr>
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
														<label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#2b7dbc;">
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
				var id = $(this).val();
                $('.child_menu2_' + id).attr('checked', true);
                $('.child_menu3_' + id).attr('checked', true);
			});
		} else {
			$('.child_menu_' + fid).attr('checked', false);
			$('.child_menu_' + fid).each(function(){
				var id = $(this).val();
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
});

function submitCallBack(info){
    window.top.msg(1,info,true);
    top.art.dialog({id:"menu"}).close();
}
</script>

<include file="Public:footer"/>