<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo C('DEFAULT_CHARSET');?>" />
		<title>网站后台管理</title>
		<script type="text/javascript">
			var kind_editor=null,static_public="<?php echo ($static_public); ?>",static_path="<?php echo ($static_path); ?>",system_index="<?php echo U('Index/index');?>",choose_province="<?php echo U('Area/ajax_province');?>",choose_city="<?php echo U('Area/ajax_city');?>",choose_area="<?php echo U('Area/ajax_area');?>",choose_circle="<?php echo U('Area/ajax_circle');?>",choose_market="<?php echo U('Area/ajax_market');?>",choose_map="<?php echo U('Map/frame_map',array('city_id'=>$map_default_city_id));?>",get_firstword="<?php echo U('Words/get_firstword');?>",frame_show=<?php if($_GET['frame_show']){ ?>true<?php }else{ ?>false<?php } ?>;
            var  meal_alias_name = "<?php echo ($config["meal_alias_name"]); ?>",parentShowHelpParam = [],parentShowIndex = false,choose_provincess="<?php echo U('Area/ajax_province');?>",choose_cityss="<?php echo U('Area/ajax_city');?>";
		    var choose_street="<?php echo U('Area/ajax_street');?>",choose_community="<?php echo U('Area/ajax_community');?>",choose_house_village="<?php echo U('Area/ajax_house_village');?>";
        </script>
		<link rel="stylesheet" type="text/css" href="<?php echo ($static_path); ?>css/style.css" />
		<script type="text/javascript" src="<?php echo C('JQUERY_FILE');?>?t=1"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/jquery.form.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/jquery.cookie.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/jquery.validate.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/jquery.colorpicker.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/layer/layer.js"></script>
		<script type="text/javascript" src="<?php echo ($static_public); ?>js/layer/extend/layer.ext.js"></script>
		<script type="text/javascript" src="<?php echo ($static_path); ?>js/common.js?t=13101"></script>
		<script type="text/javascript" src="<?php echo ($static_path); ?>js/area_adver.js?t=13"></script>
		<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
		<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	</head>
	<script>
		//window.top.contentScrollToTop()
	</script>
	<body width="100%" <?php if($bg_color){ ?>style="background:<?php echo ($bg_color); ?>;"<?php } ?>>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<?php if($_GET['property_id']){ ?><a href="<?php echo U('House/property');?>" class="">返回物业列表</a><?php } ?>
					<a href="<?php echo U('House/village',array('property_id'=>$_GET['property_id']));?>" class="on"><?php echo ($config['house_name']); ?>列表</a>

					<?php if($_GET['property_id']){ ?><a href="javascript:void(0);" onclick="window.top.artiframe('<?php echo U('House/village_add',array('property_id'=>$_GET['property_id']));?>','添加<?php echo ($config['house_name']); ?>',1100,350,true,false,false,addbtn,'add',true);">添加<?php echo ($config['house_name']); ?></a><?php } ?>
<!--					<a href="javascript:void(0);" onclick="window.top.artiframe('<?php echo U('House/village_import');?>','添加<?php echo ($config['house_name']); ?>',450,150,true,false,false,importbtn,'add',true);">导入<?php echo ($config['house_name']); ?></a>-->
					<?php if($config['buy_sms'] == 1){ ?><a href="javascript:void(0);" onclick="window.top.artiframe('<?php echo U('House/sms_add_all');?>','添加短信',520,350,true,false,false,addbtn,'add',true);">添加短信</a><?php } ?>
						<!-- <a href="<?php echo U('House/door_notice_map');?>" target="_blank" ><?php echo ($config['house_name']); ?>开门状态监控</a> -->
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="<?php echo U('House/village');?>" method="get">
							<input type="hidden" name="c" value="House"/>
							<input type="hidden" name="a" value="village"/>
							筛选: 
							<?php if($admin_area != 3){ ?><div id="choose_street" province_idss="<?php echo ($_GET["province_idss"]); ?>" city_idss="<?php echo ($_GET["city_idss"]); ?>" area_id="<?php echo ($_GET["area_id"]); ?>" street_idss="<?php echo ($_GET["street_idss"]); ?>" community_idss="<?php echo ($_GET["community_idss"]); ?>" style="display:inline"></div><?php } ?>
							<input type="text" name="keyword" class="input-text" value="<?php echo ($_GET['keyword']); ?>"/>
							<select name="searchtype">
								<option value="village_name" <?php if($_GET['searchtype'] == 'village_name'){ ?>selected="selected"<?php } ?>><?php echo ($config['house_name']); ?>名称</option>
								<option value="property_name" <?php if($_GET['searchtype'] == 'property_name'){ ?>selected="selected"<?php } ?>>物业名称</option>
								<option value="property_phone" <?php if($_GET['searchtype'] == 'property_phone'){ ?>selected="selected"<?php } ?>>物业电话</option>
								<option value="account" <?php if($_GET['searchtype'] == 'account'){ ?>selected="selected"<?php } ?>><?php echo ($config['house_name']); ?>账号</option>
								<option value="village_id" <?php if($_GET['searchtype'] == 'village_id'){ ?>selected="selected"<?php } ?>><?php echo ($config['house_name']); ?>ID</option>
							</select>
							<input type="submit" value="查询" class="button"/>
                              <?php if($is_get_hik_info_vision_i_community == 1){ ?><a href="javascript:;" onclick="get_hik_info_vision_i_community()" class="button btn-success">拉取智慧社区管理平台小区数据</a><?php } ?>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
                                <th><?php echo ($config['house_name']); ?>名称</th>
                                <th>所属物业</th>
								<th>最后登录时间</th>
								<th>访问</th>
								<th>账单</th>
								<th><?php echo ($config["house_market_name"]); ?>账单</th>
                                <th class="textcenter">排序</th>
								<th class="textcenter">状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<?php if(is_array($village_list)){ if(is_array($village_list)){ $i = 0; $__LIST__ = $village_list;if( count($__LIST__)==0 ){ echo "" ; }else{ foreach($__LIST__ as $key=>$vo){ $mod = ($i % 2 );++$i;?><tr>
										<td><?php echo ($vo["village_id"]); ?></td>
										<td>
                                            <?php if($vo['status'] != 2){ ?><a href="<?php echo U('House/v20_village_login',array('village_id'=>$vo['village_id']));?>" target="_blank">
                                                    <?php echo ($vo["village_name"]); ?>
                                                </a>
                                                <?php }else{ ?>
                                                <?php echo ($vo["village_name"]); } ?>
                                            <?php if($vo['facilities_type_text'] && $vo['facilities_type_text'] != ''){ ?><span style="color:#2681f3;">[<?php echo ($vo["facilities_type_text"]); ?>]</span><?php } ?>
                                        </td>
                                        <td>
                                            <?php if($vo['property_name']){ ?><a onclick="go_property(<?php echo ($vo['property_id']); ?>)" style="cursor:pointer;" target="_blank"><?php echo ($vo["property_name"]); ?></a>
                                                <?php }else{ ?>
                                                暂无<?php } ?>
                                        </td>
										<td><?php if($vo['last_time']){ echo (date('Y-m-d H:i:s',$vo["last_time"])); }else{ ?>从未登录<?php } ?></td>
										<td>
											<?php if($vo['status'] != 2){ ?><a href="<?php echo U('House/v20_village_login',array('village_id'=>$vo['village_id']));?>" target="_blank">访问</a>
											<?php }else{ ?>
												禁止状态<?php } ?>
										</td>
										<td><a href="<?php echo U('House/village_money_list',array('village_id'=>$vo['village_id']));?>">查看账单</a></td>
										<td><a href="<?php echo U('House/market_order',array('village_id'=>$vo['village_id']));?>">查看<?php echo ($config["house_market_name"]); ?>账单</a></td>

                                        <td class="textcenter"><?php echo ($vo["village_sort"]); ?></td>
                                        <td class="textcenter"><?php if($vo['status'] == 1){ ?><font color="green">正常</font><?php }elseif($vo['status'] == 0){ ?><font color="red" title="等待<?php echo ($config['house_name']); ?>管理员登录社区后台完善信息">待完善信息</font><?php }else{ ?><font color="red">禁止</font><?php } ?></td>
										<td class="textcenter">
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('<?php echo U('House/village_edit',array('village_id'=>$vo['village_id']));?>','编辑<?php echo ($config['house_name']); ?>信息',960,600,true,false,false,editbtn,'edit',true);">编辑</a>
                                            <?php if($vo['is_del'] == 1 && (!$vo['is_saas_user'] || $vo['is_saas_user'] != 1)){ ?>|
                                                <a href="javascript:void(0);" class="delete_row1" data-name="<?php echo ($vo["village_name"]); ?>" parameter="village_id=<?php echo ($vo["village_id"]); ?>" url="<?php echo U('House/house_village_del');?>">删除</a><?php } ?>
                                        </td>

									</tr><?php } } } else { echo "" ;} ?>
								<tr><td class="textcenter pagebar" colspan="10"><?php echo ($pagebar); ?></td></tr>
							<?php }else{ ?>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr><?php } ?>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript" src="<?php echo ($static_public); ?>js/area_street.js?t=06"></script>

<script>
    function go_property(property_id){
        $.post("<?php echo U('House/property_login_v20');?>&property_id="+property_id,function(result){
            $.cookie('property_access_token',result.data.ticket)
            setTimeout(function(){
                window.open(result.data.url);
            },600)
        },'json');
    }

    $('.delete_row1').click(function(){
        var now_dom = $(this);
        window.top.art.dialog({
            icon: 'question',
            title: '请确认是否要删除['+now_dom.attr('data-name')+']小区',
            id: 'msg' + Math.random(),
            lock: true,
            fixed: true,
            opacity:'0.4',
            resize: false,
            content: '你确定这样操作吗？操作后可能不能恢复！',
            ok:function (){
                $.post(now_dom.attr('url'),now_dom.attr('parameter'),function(result){
                    if(result.status == 1){
                        window.top.msg(1,result.info,true);
                        window.location.reload();
                    }else{
                        window.top.msg(0,result.info);
                    }
                });
            },
            cancel:true
        });
        return false;
    });

    var get_hik_info_community_url = "<?php echo U('House/get_hik_info_community');?>"
    function get_hik_info_vision_i_community(){
        var ii_index= layer.msg('正在为您拉取智慧社区管理平台小区数据<br/><div style="color:#999;line-height:1.3;margin-top:10px;font-size:12px;">这个过程需要一些时间，请耐心等待...</div>', {
				  icon: 16
				  ,shade: [0.3, '#000']
				  ,time:0
				  ,offset:'50px'
		});
		$.post(get_hik_info_community_url,{},function(ret){
                layer.close(ii_index);
                if(ret.data.err_code==0){
                    layer.msg('数据拉取成功，你可以从编辑小区里对接了！',{icon: 0,time:3,shade: [0.5]});
                }else{
                    layer.msg(ret.data.msg,{icon: 2});
                }
		},'json');
    }
</script>
	</body>
	<?php if(empty($_GET['frame'])){ ?><script type="text/javascript">
			if(typeof parent.showHelpText == 'function'){
				parent.showHelpText(parentShowHelpParam);
			}
			if(typeof parent.showHelpType == 'function'){
				parent.showHelpType(parentShowIndex,'<?php echo GROUP_NAME;?>','<?php echo MODULE_NAME;?>','<?php echo ACTION_NAME;?>');
			}
			$(function(){
				parent.iframeRealHeight = $('body').height() + 40;

				if(typeof parent.setMainHeight == 'function'){
					parent.setMainHeight({iframeHeight:true});
				}
				
				// 新版框架
				if(typeof top.contentScrollToTop == 'function'){
					$.each($('.iframe-container iframe', parent.document), function(i, item){
						if($(item).css('display') != 'none'){
							console.log($(item).attr('src'), $(item).css('min-height'));
							if($(item).css('min-height') != '0' && $(item).css('min-height') != '0px'){
								$(item).css('min-height', $(parent.document).height() - 50);
								$(item).css('min-height', $('body').height() + 40 < $(parent.document).height() - 50 ? $(parent.document).height() - 50 : $('body').height() + 40);
							}
						}
					});
				}
				
				/* alert($(window.parent).scrollTop()); */
				/* parent.scrollTo(0,0); */
				// alert(parent.iframeRealHeight);
			});
		</script><?php } ?>
	<script type="text/javascript">
		setInterval(function(){
			$.get("<?php echo U('loginKeep');?>")
		},300000);
	</script>
</html>