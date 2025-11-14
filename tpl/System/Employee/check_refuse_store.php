<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Employee/store')}">店铺列表</a>|
            <a href="{pigcms{:U('Employee/check_store')}">审核店铺列表</a>|
            <a href="{pigcms{:U('Employee/check_refuse_store')}" class="on">审核拒绝店铺列表</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Employee/store_add')}','添加店铺',620,440,true,false,false,addbtn,'store_add',true);">添加店铺</a>
        </ul>
    </div>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup><col> <col> <col><col><col><col><col><col><col width="180" align="center"> </colgroup>
                <thead>
                <tr>
                    <th>编号</th>
                    <th>店铺名称</th>
                    <th>联系电话</th>
                    <th>最后编辑时间</th>
                    <th>平台点击数</th>
                    <th>状态</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($store_list)">
                    <volist name="store_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.store_id}</td>
                            <td>{pigcms{$vo.name}</td>
                            <if condition="!isset($config['not_o2o_demo'])">
                                <td>{pigcms{$vo.phone}</td>
                            </if>
                            <td><if condition="$vo['last_time']">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}<else/>无</if></td>
                            <td>{pigcms{$vo.hits}</td>
                            <td>
                                <if condition="$vo['claim_status'] eq 1">
                                    <font color="green">启用</font>
                                    <elseif condition="$vo['claim_status'] eq 2"/>
                                    <font color="red">审核中</font>
                                    <elseif condition="$vo['claim_status'] eq 3"/>
                                    <font color="red">审核拒绝</font>
                                    <else/>
                                    <font color="red">关闭</font>
                                </if>
                            </td>
                            <td class="textcenter">
                               <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Employee/store_detail',array('store_id'=>$vo['store_id'],'frame_show'=>true))}','查看详情',720,600,true,false,false,false,'detail',true);">查看详情</a>
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
<include file="Public:footer"/>