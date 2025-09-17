<include file="Public:header"/>
<style type="text/css">
.mark_box{
	display:block;
	position: relative;
	margin: 10px 0;
}
.mark_box i.mark{
	display: block;
    position: absolute;
    line-height: 16px;
    top: -6px;
    background: #f00;
    color: #fff;
    padding: 0 5px;
    left: 25px;
    border-radius: 10px;
    border-bottom-left-radius: 0;
    font-size: 11px;
}
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<if condition="empty($_GET['frame_show'])">
						<a href="{pigcms{:U('Slider/index')}">导航分类列表</a>|
					</if>
					<a href="{pigcms{:U('Slider/slider_list',$_GET)}" class="on">{pigcms{$now_category.cat_name} - 导航列表</a>|
					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Slider/slider_add',array('cat_id'=>$now_category['cat_id'],'from_page'=>$_GET['from_page']))}','添加导航',600,520,true,false,false,addbtn,'add',true);">添加导航</a>
				</ul>
			</div>
			<div class="page_tips">导航建议尺寸：{pigcms{:str_replace(' ','&nbsp;',$now_category['size_info'])}</div>
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
							<col width="180" align="center"/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th width="5%">排序</th>
								<th width="5%">编号</th>
								<th width="10%">名称</th>
                                <if condition="C('customization.open_view_multi_city') neq 1">
								<th width="10%">城市</th>
                                </if>
								<th width="10%">链接地址</th>
								<th width="10%">导航图片</th>
                                <if condition="C('customization.open_view_multi_city') eq 1 ">
                                    <th width="10%">指定区域</th>
                                </if>
								<th width="10%" class="textcenter">最后操作时间</th>
								<th width="15%" class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($slider_list)">
								<volist name="slider_list" id="vo">
									<tr>
										<td>{pigcms{$vo.sort}</td>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.name}</td>
                                        <if condition="C('customization.open_view_multi_city') neq 1">
										<td>{pigcms{$vo.cityname}</td>
                                        </if>
										<td><a href="{pigcms{$vo.url}" target="_blank">访问链接</a></td>
										<td>
											<if condition="$vo['pic']">
												<if condition="!empty($vo['mark_name']) && !empty($vo['mark_bg'])">
													<span class='mark_box'>
														<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$vo.pic}" style="width:50px;height:50px;" class="view_msg"/>
														<i class='mark' style="background-color:{pigcms{$vo.mark_bg}">{pigcms{$vo.mark_name}</i>
													</span>
												<else/>
													<img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$vo.pic}" style="width:50px;height:50px;" class="view_msg"/>
												</if>
											<else/>
												没有图片
											</if>
										</td>
                                        <if condition="C('customization.open_view_multi_city') eq 1 ">
                                            <td>
                                                <if condition="$vo['cityname'] eq '通用'">
                                                    <font color="green">--</font>
                                                    <else/>
                                                    <a href="{pigcms{:U('Index/check_area',array('id' => $vo['id'],'type'=>'slider','cat_key'=>$_GET['cat_key'],'cat_id'=>$now_category['cat_id']))}">
                                                        <font color="red">指定区域</font>
                                                    </a>
                                                </if>
                                            </td>
                                        </if>
										<td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Slider/slider_edit',array('id'=>$vo['id'],'frame_show'=>true,'from_page'=>$_GET['from_page']))}','查看信息',800,600,true,false,false,false,'add',true);">查看</a> | <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Slider/slider_edit',array('id'=>$vo['id'],'from_page'=>$_GET['from_page']))}','编辑信息',800,600,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Slider/slider_del')}">删除</a></td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>