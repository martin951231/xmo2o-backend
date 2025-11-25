<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('door_list')}">门禁设置</a>
            </li>
			<li class="active">查看门禁开门记录</li>
        </ul>
    </div>

<!--	<div class="alert alert-info" style="margin:10px;">-->
<!--		<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>搜索到蓝牙数，仅在搜索失败时可能为正数。可以结合“手机型号+手机版本+搜索蓝牙数”，来查看是否是安卓手机需要开启定位才能使用。<br/>-->
<!--	</div>-->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Face_door/face_door_list')}" method="get" >
                    <input type="hidden" name="c" value="Face_door"/>
                    <input type="hidden" name="a" value="face_door_list"/>
                    <input type="hidden" name="device_id" value="{pigcms{$_GET['device_id']}"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;width: 6%">
                        <option value="name" <if condition="$_GET['find_type'] eq 'name'">selected="selected"</if>>姓名</option>
                        <option value="phone" <if condition="$_GET['find_type'] eq 'phone'">selected="selected"</if>>手机号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;width: 10%"/>
                    设备编号：
                    <input value="{pigcms{$_GET['device_sn']}"  name="device_sn" id="device_sn" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    设备名称：
                    <input value="{pigcms{$_GET['device_name']}"  name="device_name" id="device_name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    状态：
                    <select name="status" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['status']) || $_GET['status'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>成功</option>
                        <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>失败</option>
                    </select>
                    时间筛选： 
	                <?php 
	                    if (isset($start_time) && isset($end_time)){
		                    $start_time = date('Y-m-d',$start_time);
		                    $end_time = date('Y-m-d',$end_time);
	                    }else{
		                    $start_time = $_GET['start_time'];
		                    $end_time  = $_GET['end_time'];
	                    }
	                ?>
                    <input type="text" name="start_time" class="input-text" value="{pigcms{$start_time}"  style="width: 7%;height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                    &nbsp;—&nbsp;
                    <input type="text" name="end_time" class="input-text" value="{pigcms{$end_time}"  style="width: 7%;height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
                    <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Face_door/face_door_list',array('device_id'=>$_GET['device_id']))}'">重置</a>
                </form>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="5%">姓名</th>
                                    <th width="12%">身份证号</th>
                                    <th width="12%">操作地点/房屋地址</th>
                                    <th width="8%">设备编号</th>
                                    <th width="12%">设备名称</th>
                                    <th width="5%">设备事件</th>
                                    <th width="5%">操作状态</th>
                                    <th width="8%">开门时间</th>
                                    <th width="12%">图片</th>
                                    <if condition="$is_temp">
                                        <th width="10%">用户体温（单位：℃）</th>
                                    </if>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$door_list">
                                    <volist name="door_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                           <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.village_bind_name}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.id_card}</div></td>
                                             <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.village_bind_address}</div></td>
                                             <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_sn}</div></td>
                                             <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_name}</div></td>
                                             <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.log_from_txt}</div></td>
											<td>
												<if condition="$vo.log_status eq 0">
													<div class="tagDiv" style="color:green;">成功</div>
                                                <elseif condition="$vo.log_status eq 1 AND $vo.log_from eq 1"/>
                                                    <div class="tagDiv" style="color:red;">比对失败</div>
                                                <elseif condition="$vo.log_status eq 1 AND $vo.log_from eq 2"/>
                                                    <div class="tagDiv" style="color:red;">开门失败</div>
                                                <elseif condition="$vo.log_status eq 1"/>
                                                    <div class="tagDiv" style="color:red;">比对失败</div>
												<elseif condition="$vo.log_status eq 2"/>
													<div class="tagDiv" style="color:red;">无模版</div>
												<elseif condition="$vo.log_status eq 3"/>
													<div class="tagDiv" style="color:red;">无效卡</div>
                                                <elseif condition="$vo.log_status eq 4"/>
                                                    <div class="tagDiv" style="color:red;">无现场照</div>
                                                <elseif condition="$vo.log_status eq 5"/>
                                                    <div class="tagDiv" style="color:red;">非活体</div>
                                                <elseif condition="$vo.log_status eq 6"/>
                                                    <div class="tagDiv" style="color:red;">证件过期</div>
                                                <elseif condition="$vo.log_status eq 7"/>
                                                    <div class="tagDiv" style="color:red;">超过时间限制</div>
                                                <elseif condition="$vo.log_status eq 8"/>
                                                    <div class="tagDiv" style="color:red;">超过此数限制</div>
                                                <elseif condition="$vo.log_status eq 100"/>
                                                    <div class="tagDiv" style="color:red;">未知错误</div>
												</if>
											</td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.log_time}</div></td>
                                            <if condition="!empty($vo['log_detail'])">
                                           <td style="word-break:break-all">
                                               <php>if ($vo['log_detail'] && $vo['log_detail']['face_img']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{:replace_file_domain($vo['log_detail']['face_img'])}" style="width:50px;height:50px;" class="view_msg"/>
                                                   </div>
                                               <php>} else if ($vo['log_detail'] && $vo['log_detail']['imgUrl']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{$vo['log_detail']['imgUrl']}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>} else if ($vo['log_detail'] && $vo['log_detail']['mediaUrls']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{$vo['log_detail']['mediaUrls']}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>} else if ($vo['log_detail'] && $vo['log_detail']['pictureURL']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{$vo['log_detail']['pictureURL']}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>} else if($vo['log_detail'] && $vo['log_detail']['faceCapture']){</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{$vo['log_detail']['faceCapture'][0]['bkgUrl']}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>} else if ($vo['log_detail'] && $vo['log_detail']['face_img_url']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{:replace_file_domain($vo['log_detail']['face_img_url'])}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>} else if ($vo['faceImg1']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{:replace_file_domain($vo['faceImg1'])}" style="width:50px;height:50px;" class="view_msg"/>
                                                   </div>
                                               <php>} else if ($vo['cut_image']) {</php>
                                                   <div class="tagDiv">
                                                       抓拍照片：<img src="{pigcms{:replace_file_domain($vo['cut_image'])}" style="width:50px;height:50px;" class="view_msg" />
                                                   </div>
                                               <php>}</php>
                                               
                                               <php>if ($vo['log_detail'] && $vo['log_detail']['capture_img_url']) {</php>
                                                   <div class="tagDiv">
                                                       人脸图片：<img src="{pigcms{:replace_file_domain($vo['log_detail']['capture_img_url'])}" style="width:50px;height:50px;" class="view_msg"/>
                                                   </div>
                                               <php>}</php>
                                               
                                               <php>if ($vo['log_detail'] && $vo['log_detail']['card_data_picture_url']) {</php>
                                                   <div class="tagDiv">
                                                       证件照片：<img src="{pigcms{:replace_file_domain($vo['log_detail']['card_data_picture_url'])}" style="width:50px;height:50px;" class="view_msg"/>
                                                   </div>
                                               <php>}</php>
                                           </td>
                                            </if>

                                            <if condition="$is_temp">
                                                <td style="word-break:break-all">
                                                    <if condition="$vo['temperature'] && $vo['temperature'] gt $normal_temperature">
                                                        <div style="color: red;">{pigcms{$vo['temperature']}</div>
                                                        <elseif condition="$vo['temperature'] gt 0"/>
                                                        <div style="color: green;">{pigcms{$vo['temperature']}</div>
                                                        <else/>
                                                        --
                                                    </if>
                                                </td>
                                            </if>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >暂无数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    //预览大图
   $('.view_msg').click(function () {
        var img = $(this).attr('src');
       layer.open({
           type: 1,
           offset: "10px",
           area: ['1250px', '750px'],
           content: '<img style="height: 100%;" src="'+img+'"/>'
       });
       $('.layui-layer-content').css('text-align','center');
	});
</script>
<include file="Public:footer"/>
