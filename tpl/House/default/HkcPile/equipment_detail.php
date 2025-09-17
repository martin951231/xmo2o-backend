<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('HkcPile/charg_equipment_list')}">智能充电桩(好快充)</a>
            </li>
            <li class="active">充电桩详情</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div style="margin-left: 83%">
                <button type="button" class="layui-btn" onclick="reload();">刷新</button>
                <button type="button" class="layui-btn" onclick="download_code('{pigcms{$info.id}','{pigcms{$info.equipment_num}');">批量下载二维码</button>
            </div>
            <div class="row" style="width: 100%;margin-top: 20px">
                <table class="layui-table">
                    <thead>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">设备名称</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$info.equipment_name}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">设备16进制编码</th>
                        <th lay-data="{field:'city'}">{pigcms{$info.equipment_num}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">设备10进制编码</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$info.equipment_sn}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">设备所属区域名称</th>
                        <th lay-data="{field:'city'}">{pigcms{$info.area_name}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">安装时间</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$info.add_time_txt}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">插座数</th>
                        <th lay-data="{field:'city'}">{pigcms{$info.socket_num}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">当前插座编码</th>
                        <th lay-data="{field:'username', width:80}"><span id="port">01</span></th>
                        <th lay-data="{field:'sex', width:80, sort: true}">当前插座状态</th>
                        <th lay-data="{field:'city'}"><span id="status">正常</span></th>
                    </tr>
                    </thead>
                </table>
            </div>
            <div class="bottom" style="width: 100%;height: 270px;border: 1px solid darkgrey;border-radius: 5px;margin-top: 20px">
                <div style="width: 40%;float: left;margin-left: 2%;margin-top: 2%">
                    <div><b><span style="font-size: 20px">充电桩插座</span></b></div>
                    <div style="margin-top: 20px">
                        <volist name="ports" id="vo">
                            <div style="float: left;text-align: center;margin-right: 10px;cursor: pointer;" alt="{pigcms{$vo.status_des}" title="{pigcms{$vo.status_des}">
                                <if condition="$vo['status'] == 0">
                                    <div class="ports"><img src="{pigcms{$static_public}images/chazuo.png" onclick="selected('{pigcms{$vo.pname}',0)" style="height: 40px;width: 40px"></div>
                                 <elseif condition="$vo['status'] == 2" />
                                    <div class="ports"><img src="{pigcms{$static_public}images/chazuo5.png" onclick="selected('{pigcms{$vo.pname}',0)" style="height: 40px;width: 40px"></div>
                                 <elseif condition="$vo['status'] == 3" />
                                    <div class="ports"><img src="{pigcms{$static_public}images/chazuo2.png" onclick="selected('{pigcms{$vo.pname}',0)" style="height: 40px;width: 40px"></div>
                                 <elseif condition="$vo['status'] == 1 OR $vo['status'] == 5 OR $vo['status'] == 170" />
                                    <div class="ports"><img src="{pigcms{$static_public}images/chazuo4.png" onclick="selected('{pigcms{$vo.pname}',1)" style="height: 40px;width: 40px"></div>
                                <else />
                                    <div class="ports"><img src="{pigcms{$static_public}images/chazuo3.png" onclick="selected('{pigcms{$vo.pname}',-1)" style="height: 40px;width: 40px"></div>
                                </if>
                                <div><span style="font-size: 16px">{pigcms{$vo.pname}</span></div>
                            </div>
                        </volist>
                    </div>
                </div>
                <div style="width: 20%;float: left;text-align: center; margin-top: 10px;margin-right: 40px;">
                    <img src="{pigcms{$info.qrcode}" style="width:250px;height:250px;" />
                </div>
                <!--
                <div style="width: 50%;float: left;margin-top: 6%;margin-left: 5%">

                    <div style="text-align: center;float: left;margin-left: 30px">
                        <div><img src="{pigcms{$static_public}images/huifu.png" onclick="updateStatus('{pigcms{$info.id}','{pigcms{$info.equipment_num}',1)"></div>
                        <div><span style="font-size: 16px">恢复</span></div>
                    </div>
                    <div style="text-align: center;float: left;margin-left: 30px">
                        <div><img src="{pigcms{$static_public}images/stop.png" onclick="updateStatus('{pigcms{$info.id}','{pigcms{$info.equipment_num}',2)"></div>
                        <div><span style="font-size: 16px">停止</span></div>
                    </div>

                </div>
                --->
                <div style="width: 30%;float: left;text-align: center;margin-top: 6%">
                    <div style="text-align: center;float: left;margin-left: 20px">
                        <div style="color: green"><span style="font-size: 20px">{pigcms{$a_count}</span></div>
                        <div><span style="font-size: 16px">空闲</span></div>
                    </div>
                    <div style="text-align: center;float: left;margin-left: 20px">
                        <div style="color: #BC12ED"><span style="font-size: 20px">{pigcms{$e_count}</span></div>
                        <div><span style="font-size: 16px">未充占用</span></div>
                    </div>
                    <div style="text-align: center;float: left;margin-left: 20px">
                        <div style="color: #CDCDCD"><span style="font-size: 20px">{pigcms{$b_count}</span></div>
                        <div><span style="font-size: 16px;">工作</span></div>
                    </div>
                     <div style="text-align: center;float: left;margin-left: 20px">
                        <div style="color: dodgerblue"><span style="font-size: 20px">{pigcms{$d_count}</span></div>
                        <div><span style="font-size: 16px">充满占用</span></div>
                    </div>
                    <div style="text-align: center;float: left;margin-left: 20px">
                        <div style="color: red"><span style="font-size: 20px">{pigcms{$c_count}</span></div>
                        <div><span style="font-size: 16px">故障</span></div>
                    </div>
                </div>
            </div>
            <div class="row" style="width: 100%;margin-top: 20px">
                <div><b><span style="font-size: 20px">设备详情</span></b></div>
                <table class="layui-table">
                    <thead>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">设备SIM卡类型</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.module}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">小区ID</th>
                        <th lay-data="{field:'city'}">{pigcms{$device_info.village_id}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">最新心跳时间</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.time_str}</th>
                        <th lay-data="{field:'id', width:80, sort: true}">设备id</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.id}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">信号强度</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.signals}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">设备温度</th>
                        <th lay-data="{field:'city'}">{pigcms{$device_info.sidetemp}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">设备版本号</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.version}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">流量卡号</th>
                        <th lay-data="{field:'city'}">{pigcms{$device_info.simCode}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">IMEI号</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.IMEI}</th>
                        <th lay-data="{field:'sex', width:80, sort: true}">设备状态</th>
                        <th lay-data="{field:'city'}">{pigcms{$device_info.state}</th>
                    </tr>
                    <tr>
                        <th lay-data="{field:'id', width:80, sort: true}">设备归属系列</th>
                        <th lay-data="{field:'username', width:80}">{pigcms{$device_info.deviceFamily}</th>
                    </tr>
                    </thead>
                </table>
            </div>
            <div class="row" style="width: 100%;margin-top: 20px">
                <div><b><span style="font-size: 20px">收费标准详情</span></b></div>
                <table class="layui-table">
                    <thead>
                    <tr>
                        <th >收费标准名称</th>
                        <th >计费类型</th>
                        <th lay-data="{field:'id', width:80, sort: true}">详细</th>
                    </tr>

                     <tr>
                         <th lay-data="{field:'id', width:50, sort: true}">{pigcms{$priceStandard.name}</th>
                         <th lay-data="{field:'id', width:30, sort: true}">{pigcms{$priceStandard.rate_type}</th>
                         <th lay-data="{field:'username', width:80}">{pigcms{$priceStandard.rate_detail_str}</th>
                      </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>

    //预览大图
    function view_img(id) {
        var socket = $('#port').html();
        var url = "{pigcms{:U('HkcPile/getNumCode')}";
        $.post(url,{'equipment_id':id,'socket':socket},function(data){
            if(data['status']){
                window.top.art.dialog({
                    padding: 0,
                    title: '大图',
                    content: '<a href="'+data['url']+'" target="_blank"><img src="'+data['url']+'" style="width:300px;height:300px;" /></a>',
                    lock: true
                });
            }else{
                alert(data['msg']);
            }
        },'json');
    }
    selected('01',{pigcms{$status});
    function selected(id,status) {
        document.getElementById('port').innerHTML = id;
        if(status == 0){
            document.getElementById('status').innerHTML = '空闲';
        }
        else if(status == 1){
            document.getElementById('status').innerHTML = '充电中';
        }
        else{
            document.getElementById('status').innerHTML = '故障';
        }
        /*
        document.getElementById('erweima').innerHTML = id+'号插座二维码';
        //document.getElementsByClassName('ports').style.backgroundColor='white';
        $(".ports").css('background', 'white');
        document.getElementsByClassName('ports')[id-1].style.backgroundColor='lightgray';

            */
    }
    function updateStatus(id,simId,status){
        var url = "{pigcms{:U('HkcPile/updateStatus')}";
        $.post(url,{'equipment_id':id,'status':status,'simId':simId},function(data){
            if(data['status']){
                alert(data['msg']);
                location.reload();
            }else{
                alert(data['msg']);
            }
        },'json')
    }
    function download_code(id,equipment_num) {
        var url = "{pigcms{:U('HkcPile/downloadCode')}";
        $.post(url,{'equipment_id':id,'equipment_num':equipment_num},function(data){
            if(data['status']){
                var a = document.createElement('a');          // 创建一个a节点插入的document
                var event = new MouseEvent('click')       ;    // 模拟鼠标click点击事件
                a.download = '123' ;                 // 设置a节点的download属性值
                a.href = data['url'];                                 // 将图片的src赋值给a节点的href
                a.dispatchEvent(event)
                alert(data['msg']);
            }else{
                alert(data['msg']);
            }
        },'json');
    }
    //刷新当前页面
    function reload() {
        location.reload();
    }
</script>
<include file="Public:footer"/>
