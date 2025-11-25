<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content" style="height: auto !important;">
        <div class="page-content-area">
            <style type="text/css">
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div >
                    <form method="get" action="{pigcms{:U('WisdomQrcode/position_record_list')}">
                        <input type="hidden" value="WisdomQrcode" name="c">
                        <input type="hidden" value="position_record_list" name="a">
                        <input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">
                        <if condition="$from_type lt 1">
                        <div style="width:72px;line-height: 37px;" class="col-sm-1">负责人:</div>
                        <input type="text" style="margin-right:10px;font-size:18px;" id="name" name="name" class="col-sm-2" value="{pigcms{$_GET['name']}">
                        </if>
                        <div style="height:42px; width:59px;line-height: 37px;" class="col-sm-1">时间:</div>
                        <input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间" name="begin_time" class="col-sm-2" style="margin-right:10px;font-size:16px; width: 120px;" value="{pigcms{$_GET['begin_time']}" />
                        <input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间" name="end_time" class="col-sm-2" style="margin-right:10px;font-size:16px;width: 120px;" value="{pigcms{$_GET['end_time']}" />
                        <input type="submit" value="查找" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;
                        <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('WisdomQrcode/position_record_list')}&cate_id={pigcms{$_GET[\'cate_id\']}'">重置</button>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th >ID</th>
                                <th >负责人</th>
                                <th >巡检开始时间</th>
                                <if condition="$from_type eq 1">
                                    <th >巡检点名称</th>
                                </if>
                                <th >巡检结束时间</th>
                                <th >巡检状态</th>
                                <th >巡检时长</th>
                                <if condition="$from_type lt 1 AND $no_line_path lt 1">
                                <th >巡检路线</th>
                                </if>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$dataList">
                                <volist name="dataList" id="vo">
                                    <td><div class="tagDiv">{pigcms{$vo.index_id}</div></td>
                                    <if condition="$from_type eq 1">
                                        <td style="width: 170px;"><div class="tagDiv">机器人：{pigcms{$robot_device.name}<br/>机器人编号：{pigcms{$robot_device.robot_code}</div></td>
                                     <else/>
                                        <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                    </if>
                                    <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                    <if condition="$from_type eq 1">
                                       <td>{pigcms{$vo.point_name}</td>
                                    </if>
                                    <td><div class="shopNameDiv">{pigcms{$vo.update_time}</div></td>
                                    <td>
                                        <if condition="$vo['status'] eq 1">
                                            <div class="tagDiv" style="color: #87b87f">已完成</div>
                                            <else/>
                                            <div class="tagDiv" style="color: red">进行中</div>
                                        </if>

                                    </td>
                                    <td><div class="tagDiv">{pigcms{$vo.duration}</div></td>
                                    <if condition="$from_type lt 1 AND $no_line_path lt 1">
                                        <td class="button-column">
                                           
                                            <a style="width:60px;" class="label label-sm label-info handle_btn" title="查看详情" href="{pigcms{:U('WisdomQrcode/position_map_trail',array('cate_id'=>$vo['cate_id'],'index_id'=>$vo['id'],'title'=>$vo['name']))}">详情</a>
                                             
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
<script>


</script>
<include file="Public:footer"/>
