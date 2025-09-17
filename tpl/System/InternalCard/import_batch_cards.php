<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCards/deductionCards')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
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
                <col/>
                <col/>
                <col/>
                <col  align="center"/>
            </colgroup>
            <thead>
            <tr>
                <th>ID</th>
                <th>一卡通名称</th>
                <th>一卡通ID</th>
                <th>用户ID</th>
                <th>用户姓名</th>
                <th>用户手机号</th>
                <th>归属单位</th>
                <th>区域</th>
                <th>关联店铺名称</th>
                <th>关联店铺id</th>
                <th>激活时间</th>
                <th>最后修改时间</th>
                <th class="textcenter">卡余额</th>
                <th class="textcenter">状态</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($data['list'])">
                <volist name="data.list" id="vo">
                    <tr>
                        <td>{pigcms{$vo.id}</td>
                        <td>{pigcms{$vo.card_name}</td>
                        <td>{pigcms{$vo.card_id}</td>
                        <td><empty name="vo.uid"><font color="red">没有绑定用户</font><else /> <font color="blue">{pigcms{$vo.uid}<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);">查看用户信息</a></font></empty></td>
                        <td>{pigcms{$vo.user_name}</td>
                        <td>{pigcms{$vo.phone}</td>
                        <td>{pigcms{$vo.unit_name}</td>
                        <td>{pigcms{$vo.region_str}</td>
                        <td>{pigcms{$vo.store_name}</td>
                        <td>{pigcms{$vo.store_id}</td>

                        <td><empty name="vo.active_time"><else />{pigcms{$vo.active_time|date='Y-m-d H:i:s',###}</empty></td>
                        <td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
                        <td class="textcenter">{pigcms{$config['Currency_symbol']}{pigcms{$vo.balance_money|floatval=###}</td>

                        <td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 2" /><font color="red">未审核</font><else /><font color="red">禁用</font></if></td>
                        <td class="textcenter">
                    </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="15">{pigcms{$data.pagebar}</td></tr>
                <else/>
                <tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
            </if>
            </tbody>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
