<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Hardware/faceHardware')}" class="on">人脸门禁列表</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Hardware/faceHardware')}" method="get">
                    <input type="hidden" name="c" value="Hardware"/>
                    <input type="hidden" name="a" value="faceHardware"/>
                    筛选:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                         <option value="device_sn" <if condition="$_GET['searchtype'] eq 'device_sn'">selected="selected"</if>>设备编号/ID</option>
                         <option value="village_id" <if condition="$_GET['searchtype'] eq 'village_id'">selected="selected"</if>>小区ID</option>
                         <option value="device_name" <if condition="$_GET['searchtype'] eq 'device_name'">selected="selected"</if>>设备名称</option>
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
                    <th>设备类型</th>
                    <th>设备状态</th>
                    <th>标识</th>
                    <th>设备品牌</th>
                    <th>小区ID</th>
                    <th>心跳刷新</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($hardwareList)">
                    <volist name="hardwareList" id="vo">
                        <tr>
                            <td>{pigcms{$vo.device_sn}</td>
                            <td>{pigcms{$vo.device_name}</td>
                            <td>{pigcms{$vo.device_type_name}</td>
                            <td>
                                <if condition="$vo.device_status eq 1">
                                    <div class="tagDiv" style="color:green;">正常</div>
                                    <elseif condition="$vo.device_status eq 2" />
                                    <div class="tagDiv" style="color:red;">停止/待机</div>
                                <else/>
                                    -
                                </if>
                            </td>
                            <td>
                                <if condition="$vo.device_type eq 88">
                                        <div class="tagDiv" style="color:green;">进&出</div>
                                    <elseif condition="$vo.device_direction eq 1" />
                                        <div class="tagDiv" style="color:green;">出</div>
                                    <elseif condition="$vo.device_direction eq 2" />
                                        <div class="tagDiv" style="color:green;">进&出</div>
                                    <elseif condition="$vo.device_direction eq 0" />
                                        <div class="tagDiv" style="color:green;">进</div>
                                  <else/>
                                    -
                                </if>
                            </td>
                            <td>{pigcms{$vo.device_brand_name}</td>
                            <td>
                                <a href="{pigcms{:U('House/v20_village_login',array('village_id'=>$vo['village_id']))}" target="_blank">
                                    {pigcms{$vo.villageName}({pigcms{$vo.village_id})
                                </a>
                            </td>
                            <td>{pigcms{$vo.notify_date}</td>
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