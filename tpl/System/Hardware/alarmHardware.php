<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Hardware/alarmHardware')}" class="on">报警器列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Hardware/alarmHardware')}" method="get">
                    <input type="hidden" name="c" value="Hardware"/>
                    <input type="hidden" name="a" value="alarmHardware"/>
                    筛选:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="device_id" <if condition="$_GET['searchtype'] eq 'device_id'">selected="selected"</if>>设备ID</option>
                        <option value="device_name" <if condition="$_GET['searchtype'] eq 'device_name'">selected="selected"</if>>设备名称</option>
                        <option value="device_serial" <if condition="$_GET['searchtype'] eq 'device_serial'">selected="selected"</if>>设备序号</option>
                        <option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>小区ID</option>
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

                </colgroup>
                <thead>
                <tr>
                    <th>设备ID/编号</th>
                    <th>设备名称</th>
                    <th>设备序号</th>
                    <th>设备品牌</th>
                    <th>支持设备协议</th>
                    <th>设备状态</th>
                    <th>同步状态</th>
                    <th>交互时间</th>
                    <th>小区ID</th>
                 </tr>
                </thead>
                <tbody>
                <if condition="is_array($list)">
                    <volist name="list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.device_id}</td>
                            <td>{pigcms{$vo.device_name}</td>
                            <td>{pigcms{$vo.device_serial}</td>
                            <td>{pigcms{$vo.brand_text}</td>
                            <td>{pigcms{$vo.thirdProtocolText}</td>
                            <td style="color: {pigcms{$vo['device_status_color']};">{pigcms{$vo.device_status_text}</td>
                            <td>{pigcms{$vo.cloud_text}</td>
                            <td>{pigcms{$vo.interaction_time_text}</td>
                            
                             <td>
                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                    {pigcms{$vo.villageName}({pigcms{$vo.village_id})
                                </a>
                            </td>
                         </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>

<include file="Public:footer"/>