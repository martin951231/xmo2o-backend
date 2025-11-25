<include file="Public:header"/>
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
            <col width="180" align="center"/>
        </colgroup>
        <thead>
            <tr>
                <th width="50">序号</th>
                <th width="100">SN码(中奖号)</th>
                <th class="textcenter" width="50">奖项</th>
                <th class="textcenter" width="50">已发奖</th>
                <th class="textcenter" width="100">奖品发送时间</th>
                <th class="textcenter" width="100">中奖者手机号</th>
                <th class="textcenter" width="200">收货地址</th>
                <th width="100" class="textcenter">中奖时间</th>
                <th width="100" class="textcenter">操作</th>
            </tr>
        </thead>
        <tbody>
            <if condition="$record">
                <volist name="record" id="record">
                    <tr>
                        <td>{pigcms{$i}</td>
                        <td>{pigcms{$record.sn}</td>
                        <td class="textcenter">{pigcms{$record.prize}</td>
                        <td class="textcenter"><if condition="$record['sendstutas'] eq 0">否<else/>是</if></td>
                        <td class="textcenter"><if condition="$record['sendtime'] eq 0"> <else/>{pigcms{$record.sendtime|date='Y-m-d H:i:s',###}</if></td>
                        <td class="textcenter">{pigcms{$record.phone}</td>
                        <td class="textcenter">{pigcms{$record.address}</td>
                        <td class="textcenter"><if condition="$record['time'] neq 0"> {pigcms{$record.time|date='Y-m-d H:i:s',###}</if></td>
                        <td class="textcenter">
                            <if condition="$record['sendstutas'] eq 0">
                                <a href="{pigcms{:U($activeType .'/sendprize',array('id'=>$record['id']))}">发奖</a>
                            <else/>
                                <a href="{pigcms{:U($activeType .'/sendnull',array('id'=>$record['id']))}">取消发奖</a>
                            </if>
                            | <a title="删除" class="red" style="padding-right:8px;" href="{pigcms{:U($activeType .'/snDelete',array('id'=>$record['id']))}">删除</a>
                        </td>
                    </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
            <else/>
                <tr class="odd"><td class="button-column" colspan="9" >无内容</td></tr>
            </if>
        </tbody>
    </table>
</div>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('确定要删除这条数据吗?不可恢复。')) return false;
	});
});
</script>
<include file="Public:footer"/>
