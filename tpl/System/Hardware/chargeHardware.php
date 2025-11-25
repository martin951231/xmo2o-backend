<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Hardware/chargeHardware')}" class="on">智能充电桩列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Hardware/chargeHardware')}" method="get">
                    <input type="hidden" name="c" value="Hardware"/>
                    <input type="hidden" name="a" value="chargeHardware"/>
                    筛选:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="equipment_sn" <if condition="$_GET['searchtype'] eq 'equipment_sn'">selected="selected"</if>>设备编号/ID</option>
                        <option value="equipment_serial" <if condition="$_GET['searchtype'] eq 'equipment_serial'">selected="selected"</if>> 设备唯一编码</option>
                        <option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>小区ID</option>
                        <option value="equipment_name" <if condition="$_GET['searchtype'] eq 'equipment_name'">selected="selected"</if>>设备名称</option>
                    </select>
                    <input type="submit" value="查询" class="button"/>
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
                    <col/>
                    <col/>
                    <col/>

                </colgroup>
                <thead>
                <tr>
                    <th>设备SN/编号</th>
                    <th>设备唯一编码</th>
                    <th>设备名称</th>
                    <th>对接协议</th>
                    <th>设备状态</th>
                    <th>小区ID</th>
                    <th>心跳刷新</th>
                    <th>查看二维码</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($hardwareList)">
                    <volist name="hardwareList" id="vo">
                        <tr>
                            <td>{pigcms{$vo.equipment_sn}</td>
                            <td>{pigcms{$vo.equipment_serial}</td>
                            <td>{pigcms{$vo.equipment_name}</td>
                            <td>
                                <if condition="$vo.type == 2">
                                    TCP
                                   <else />
                                    HTTP
                                </if>
                            </td>
                            <td>
                                <if condition="$vo.statusHeart == 44">
                                        <div class="tagDiv" style="color: red">设备离线</div>
                                    <elseif condition="$vo.status == 1" />
                                        <div class="tagDiv" style="color: green">正常</div>
                                    <elseif  condition="$vo.status == 2" />
                                        <div class="tagDiv" style="color: red">禁用</div>
                                    <elseif  condition="$vo.status == 3" />
                                        <div class="tagDiv" style="color: red">恢复中</div>
                                    <elseif  condition="$vo.status == 4" />
                                        <div class="tagDiv" style="color: red">停止中</div>
                                    <else />
                                        <div class="tagDiv" style="color: red">重启中</div>
                                </if>
                            </td>
                            
                            
                            <td>
                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                    {pigcms{$vo.villageName}({pigcms{$vo.village_id})
                                </a>
                            </td>
                            <td>{pigcms{$vo.statusHeartTime}</td>
                            <td>
                                <if condition="$vo['qrcode'] neq ''">
                                    <a title="查看二维码" style="cursor: pointer" onclick="view_img('{pigcms{$vo.qrcode}')">查看二维码</a>
                                    <else/>
                                    -
                                </if>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    function view_img(img_url) {
        var index = layer.open({
            type: 1,
            area: ['360px', '355px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '查看二维码',
            content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
        });
        layer.style(index, {
            top: '100px',
            'text-align': 'center'
        });

    }
</script>

<include file="Public:footer"/>