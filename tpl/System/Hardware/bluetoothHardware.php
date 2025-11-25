<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Hardware/bluetoothHardware')}" class="on">蓝牙门禁列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Hardware/bluetoothHardware')}" method="get">
                    <input type="hidden" name="c" value="Hardware"/>
                    <input type="hidden" name="a" value="faceHardware"/>
                    筛选:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="door_device_id" <if condition="$_GET['keyword'] eq 'door_device_id'">selected="selected"</if>>设备编号/ID</option>、
                        <option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>小区ID</option>
                        <option value="door_name" <if condition="$_GET['searchtype'] eq 'door_name'">selected="selected"</if>>设备名称</option>
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
                
                </colgroup>
                <thead>
                <tr>
                    <th>设备ID/编号</th>
                    <th>设备名称</th>
                     <th>小区ID</th>
                    <th>最后开门时间</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($hardwareList)">
                    <volist name="hardwareList" id="vo">
                        <tr>
                            <td>{pigcms{$vo.door_device_id}</td>
                            <td>{pigcms{$vo.door_name}</td>
                             <td>
                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                    {pigcms{$vo.villageName}({pigcms{$vo.village_id})
                                </a>
                            </td>
                            <td>{pigcms{$vo.last_open_date}</td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
  
<include file="Public:footer"/>