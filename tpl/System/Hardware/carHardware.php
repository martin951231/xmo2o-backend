<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Hardware/carHardware')}" class="on">停车设备列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Hardware/carHardware')}" method="get">
                    <input type="hidden" name="c" value="Hardware"/>
                    <input type="hidden" name="a" value="carHardware"/>
                    筛选:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="passage_name" <if condition="$_GET['searchtype'] eq 'passage_name'">selected="selected"</if>>车道名称</option>
                        <option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>小区ID</option>
                        <option value="channel_number" <if condition="$_GET['searchtype'] eq 'channel_number'">selected="selected"</if>>通道号</option>
                        <option value="device_number" <if condition="$_GET['searchtype'] eq 'device_number'">selected="selected"</if>>设备ID/编号</option>
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

                </colgroup>
                <thead>
                <tr>
                    <th>设备ID/编号</th>
                    <th>车道名称</th>
                    <th>归属区域</th>
                    <th>通道号</th>
                    <th>设备状态</th>
                    <th>通道类型</th>
                    <th>小区ID</th>
                    <th>心跳刷新</th>
                    <th>设备类型</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($hardwareList)">
                    <volist name="hardwareList" id="vo">
                        <tr>
                            <td>{pigcms{$vo.device_number}</td>
                            <td>{pigcms{$vo.passage_name}</td>
                            <td>{pigcms{$vo.area_name}</td>
                            <td>{pigcms{$vo.channel_number}</td>
                            <td>{pigcms{$vo.status_txt}</td>
                            <td>{pigcms{$vo.passage_direction_txt}</td>
                            <td>
                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                    {pigcms{$vo.villageName}({pigcms{$vo.village_id})
                                </a>
                            </td>
                            <td>{pigcms{$vo.last_heart_time}</td>
                            <td>{pigcms{$vo.park_sys_type}</td>
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

<include file="Public:footer"/>