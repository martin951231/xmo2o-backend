<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Park/parkPlateResultLogList')}">审核业主</a>
            </li>
            <li class="active">业主审核列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form method="get" id="find-form">
                    <input type="hidden" name="c" value="Park"/>
                    <input type="hidden" name="a" value="parkPlateResultLogList"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>车道设备编号</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>车牌</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" placeholder="" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>

                    识别日期：
                    <input type="text" name="begin_time" class="input-text" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间"/>-
                    <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间"/>&nbsp;&nbsp;

                    <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Park/parkPlateResultLogList')}'">重置</a>
                </form>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="8%">识别车牌</th>
                                <th width="10%">车道设备编号</th>
                                <th width="8%">识别时间</th>
                                <th width="8%">进出</th>
                                <th width="5%">设备归属</th>
                                <th width="8%">识别大图</th>
                                <th width="8%">识别小图</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$data['list']">
                                <volist name="data['list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.car_number}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.channel_id}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.add_time_text}</div></td>
                                        <td><div class="shopNameDiv"><if condition="$vo.park_type eq '1' ">进场<else />出场</if></div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.park_sys_type}</div></td>
                                        <td>
                                            <div class="tagDiv">
                                                <img style="width:260px;height:80px;" src="{pigcms{$vo.bigImage}" onclick="view_img_big('{pigcms{$vo.bigImage}')">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="tagDiv">
                                                <img style="width:260px;height:80px;" src="{pigcms{$vo.smallImage}" onclick="view_img('{pigcms{$vo.smallImage}')">
                                            </div>
                                        </td>
                                    </tr>
                                </volist>
                            <else/>
                                <tr class="odd"><td class="button-column" colspan="8" >当前没有识别记录。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$data.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
    <script type="text/javascript" language="javascript">
        function view_img(img_url) {
            var index = layer.open({
                type: 1,
                area: ['360px', '355px'],
                fixed: true, //不固定
                // maxmin: true,
                title: '查看识别小图',
                content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
            });
            layer.style(index, {
                top: '100px',
                'text-align': 'center'
            });
        }
        function view_img_big(img_url) {
            var index = layer.open({
                type: 1,
                area: ['1250px', '820px'],
                fixed: true, //不固定
                // maxmin: true,
                title: '查看识别大图',
                content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:1200px;height:800px;" /></a>',
            });
            layer.style(index, {
                top: '100px',
                'text-align': 'center'
            });
        }
    </script>
    <include file="Public:footer"/>