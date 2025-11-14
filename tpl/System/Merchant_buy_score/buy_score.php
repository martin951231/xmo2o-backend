<include file="Public:header"/>

<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant_buy_score/buy_score')}" class="on">商家平台积分</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Merchant_buy_score/buy_score')}" method="get">
                    <input type="hidden" name="c" value="Merchant_buy_score"/>
                    <input type="hidden" name="a" value="buy_score"/>
                    <input type="hidden" name="type" value="buy"/>
                    筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家ID</option>
                        <option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>
                    </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color="#000">日期筛选：</font>
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    状态:
                    <select name="set_state">
                        <option value="" <if condition="$_GET['set_state'] eq ''">selected="selected"</if>>请选择状态</option>
                        <option value="1" <if condition="$_GET['set_state'] eq '1'">selected="selected"</if>>支付成功增加</option>
                        <option value="2" <if condition="$_GET['set_state'] eq '2'">selected="selected"</if>>用户消费消耗</option>
                        <option value="3" <if condition="$_GET['set_state'] eq '3'">selected="selected"</if>>管理员操作增加</option>
                        <option value="4" <if condition="$_GET['set_state'] eq '4'">selected="selected"</if>>管理员操作减少</option>
                    </select>

                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/>

                </form>
                <div>
                    支付成功增加总积分：<span style="color: #38CDBE;"><if condition="$sore_num['pay_score_add_num']">{pigcms{$sore_num['pay_score_add_num']}<else/>0</if>积分</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    用户消费消耗总积分：<span style="color: red;"><if condition="$sore_num['user_score_num']">{pigcms{$sore_num['user_score_num']}<else/>0</if>积分</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    管理员操作增加总积分：<span style="color: #38CDBE;"><if condition="$sore_num['admin_score_add_num']">{pigcms{$sore_num['admin_score_add_num']}<else/>0</if>积分</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    管理员操作减少总积分：<span style="color: red;"><if condition="$sore_num['admin_score_reduce_num']">{pigcms{$sore_num['admin_score_reduce_num']}<else/>0</if>积分</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>

            </td>

            <td>
                <a href="{pigcms{:U('Merchant_buy_score/buy_score_export',$_GET)}" class="button" style="float:right;margin-right: 10px;">导出信息</a>
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
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>编号</th>
                    <th>订单号</th>
                    <th>商家名称</th>
                    <th>支付金额</th>
                    <th>购买/消耗积分数</th>
                    <th>积分获得者</th>
                    <th>添加时间</th>
                    <th>支付时间</th>
                    <th>操作方式</th>
                    <th>操作类型</th>
                    <th>状态</th>
                    <th>备注</th>
<!--                    <th class="textcenter">操作</th>-->
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($orderList)">
                    <volist name="orderList" id="vo">
                        <tr>
                            <td>{pigcms{$vo.order_id}</td>
                            <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.orderid}</if></td>
                            <td>{pigcms{$vo.name}</td>
                            <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.payment_money}</if></td>
                            <td><if condition="$vo['set_type'] eq '1'"><span style="color: red;">- {pigcms{$vo.sore_count}</span><else/><span style="color: #38CDBE;">+ {pigcms{$vo.sore_count}</span></if></td>
                            <td>
                                <if condition="$vo['set_type'] eq '1'">
                                    昵称： {pigcms{$vo.nickname}<br>
                                    <else/>-</if>
                            </td>
                            <td>{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</td>
                            <td><if condition="$vo['set_type'] eq '1'">-<else/>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</if></td>
                            <td>
                                <if condition="$vo['operation_type'] eq '1'">
                                    管理员操作
                                    <elseif condition="$vo['business_type']"/>
                                    用户消耗
                                    <else/>
                                    <if condition="$vo['pay_type'] eq 'yue'">在线充值<elseif condition="$vo['pay_type'] eq 'weixin'" />微信-在线充值<elseif condition="$vo['pay_type'] eq 'system'" />管理员操作</if>
                                </if>
                             </td>
                            <td><if condition="$vo['set_type'] eq '1'"><span style="color: red;">减少</span><else/><span style="color: #38CDBE;">增加</span></if></td>

                            <td>
                                <if condition="$vo['operation_type'] eq '1'">
                                    管理员操作
                                    <elseif condition="$vo['business_type']"/>
                                    -
                                    <else/>
                                    <if condition="$vo['paid'] eq '0'">未支付<elseif condition="$vo['paid'] eq '1'" />支付成功<else/>管理员操作</if>
                                </if>
                            </td>
                            <td>{pigcms{$vo.remark}</td>

<!--                            <td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);"><if condition="$vo['status'] eq 2">审核<else />编辑</if></a></td>-->
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>

<include file="Public:footer"/>
