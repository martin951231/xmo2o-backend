<include file="Public:header"/>
	<style type="text/css">
		.form-box{
			margin-top: 30px;
			border-bottom: 1px solid #e5e5e5;
		    padding-bottom: 20px;
		    margin-bottom: 10px;
		}
		.form-box  a{
			vertical-align: middle;
			margin-left: 20px;
    		line-height: 25px;
		}
		.left-box{
			display: inline-block;
			vertical-align: middle;
		}
		.item-title{
			font-size: 16px;
			vertical-align: middle;
		}
	</style>
	<div class="mainbox">
		<if condition="$_GET['cat_key'] == 'app_index_center_four' OR $_GET['cat_key'] == 'app_index_center_four_big_and_small' OR $_GET['cat_key'] == 'app_index_center_nine_grid'">
			<form id="myform" method="post" action="/admin.php?g=System&c=Config&a=amend" refresh="true"> 
			  	<div class="form-box">
                    <label class="item-title">广告图布局展示形式：</label>
                    <div class="left-box">
                        <label>
							<input class="input_radio" type="radio" name="app_index_center_four_show_type" value="0" <php>if(C('config.app_index_center_four_show_type')==0){</php>checked="checked"<php>}</php>/>
							<span>4张平铺式</span>&nbsp;&nbsp;&nbsp;&nbsp;
						</label>
						<label>
							<input class="input_radio" type="radio" name="app_index_center_four_show_type" value="1" <php>if(C('config.app_index_center_four_show_type')==1){</php>checked="checked"<php>}</php>/>
							<span>1大3小式</span>&nbsp;&nbsp;&nbsp;&nbsp;
						<label>
						<label>	
							<input class="input_radio" type="radio" name="app_index_center_four_show_type" value="2" <php>if(C('config.app_index_center_four_show_type')==2){</php>checked="checked"<php>}</php>/>
							<span>9宫格</span>
						<label>
                        <a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Viewpage/image_show')}','示例图',1000,800,true,false,false,'','show',true);" style="color:blue">示例图</a>
					</div>

				   <div class="btn" style="display: none;"> 
						<input type="submit" name="dosubmit" value="提交" class="button" /> 
			   		</div>
			  		
			  	</div>
						
			</form> 
		</if>
		<div id="nav" class="mainnav_title">
			<ul>
				<if condition="empty($_GET['frame_show'])">
					<a href="{pigcms{:U('Adver/index')}">广告分类列表</a>|
				</if>
				<a href="{pigcms{:U('Adver/adver_list',$_GET)}" class="on">{pigcms{$now_category.cat_name} - 广告列表</a>|
				<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Adver/adver_add',array('cat_id'=>$now_category['cat_id'],'from_page'=>$_GET['from_page'],'shop_id'=>$shop_id,'cat_key'=>$_GET['cat_key']))}','添加广告',800,600,true,false,false,addbtn,'add',true);">添加广告</a>
			</ul>
		</div>
		<div class="page_tips">广告建议尺寸：{pigcms{:str_replace(' ','&nbsp;',$now_category['size_info'])}</div>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<colgroup>
						<col/>
						<col/>
						<if condition="$many_city eq 1">
							<col/>
						</if>
						<col/>
						<col/>
						<col width="180" align="center"/>
						<col width="180" align="center"/>
					</colgroup>
					<thead>
						<tr>
							<th width="5%">排序</th>
							<th width="10%">名称</th>
                            <if condition="C('customization.open_view_multi_city') neq 1">
							<if condition="$many_city eq 1">
								<th width="15%">城市</th>
							</if>
                            </if>
							<th width="5%">补齐</th>
							<th width="10%">链接地址</th>
							<th width="10%">广告图片</th>
							<th width="20%" class="textcenter">最后操作时间</th>
                            <if condition="C('customization.open_view_multi_city') eq 1">
                            <th width="15%">指定区域</th>
                            </if>
							<th width="5%">状态</th>
							<th width="15%" class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($adver_list)">
							<volist name="adver_list" id="vo">
								<tr>
									<td>{pigcms{$vo.sort}</td>
									<td>{pigcms{$vo.name}</td>
                                    <if condition="C('customization.open_view_multi_city') neq 1">
									<if condition="$many_city eq 1">
										<if condition="$vo['city_id'] eq '通用'">
											<td style="color:red;">{pigcms{$vo.city_id}</td>
										<else/>
											<td>{pigcms{$vo.city_id}</td>
										</if>
									</if>
                                    </if>
									<td><if condition="$vo.complete eq 1"><span style="color:red;">是</span><else/>否</if></td>
									<td><a href="{pigcms{$vo.url}" target="_blank">访问链接</a></td>
									<td>
										<img src="{pigcms{:file_domain()}/upload/adver/{pigcms{$vo.pic}" style="width:300px;height:80px;" class="view_msg"/>
									</td>
									<td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
                                    <if condition="C('customization.open_view_multi_city') eq 1">
                                    <td>
                                        <if condition="$vo['city_id'] eq '通用'">
                                            <font color="green">--</font>
                                            <else/>
                                            <a href="{pigcms{:U('Index/check_area',array('id' => $vo['id'],'type'=>'adver','cat_key'=>$_GET['cat_key'],'cat_id'=>$now_category['cat_id']))}">
                                                <font color="red">指定区域</font>
                                            </a>
                                        </if>
                                    </td>
                                    </if>
                                    <td>
									<if condition="$vo['status'] eq 1">
										<font color="green">正常</font>
									<elseif condition="$vo['status'] eq 0"/>
										<font color="red">关闭</font>
									</if>
                                    </td>
									<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Adver/adver_edit',array('id'=>$vo['id'],'frame_show'=>true,'from_page'=>$_GET['from_page']))}','查看广告信息',800,600,true,false,false,false,'add',true);">查看</a> | <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Adver/adver_edit',array('id'=>$vo['id'],'from_page'=>$_GET['from_page']))}','编辑广告信息',800,600,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Adver/adver_del')}">删除</a></td>
								</tr>
							</volist>
						<else/>
							<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		$(()=>{
			window.parent.a()
		});
		$('.input_radio').click(function(argument) {
			var app_index_center_four_show_type = $('input[name=app_index_center_four_show_type]:checked').val();
			var cat_key = 'app_index_center_four';
			if (app_index_center_four_show_type==0) {
				cat_key = 'app_index_center_four';
			}else if (app_index_center_four_show_type==2) {
				cat_key = 'app_index_center_nine_grid';
			}else{
				cat_key = 'app_index_center_four_big_and_small';
			}

			$.post('/admin.php?g=System&c=Config&a=amend',{app_index_center_four_show_type:app_index_center_four_show_type},function(res){
				if (res.status==1) {
					window.location.href = "{pigcms{:U('Adver/adver_list')}&frame_show=1"+'&cat_key='+cat_key
					//刷新最外层iframe请调用这个方法 来自common.js里面
					window.mainiReloadIframe && window.mainiReloadIframe();
				}
			})
		})
	</script>
<include file="Public:footer"/>