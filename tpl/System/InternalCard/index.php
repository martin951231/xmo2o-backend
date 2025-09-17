<include file="Public:header"/>
		<div class="mainbox">
            <include file="card_manage_header"/>
			<table class="search_table" width="100%">
				<tr>
					<td>
                        <form action="{pigcms{:U('InternalCard/index')}" method="get">
                            <input type="hidden" name="c" value="InternalCard"/>
                            <input type="hidden" name="a" value="index"/>
                            <if condition="$admin_area_id eq 0">
                                区域：
                                <div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>
                            卡ID: <input type="text" name="card_id" class="input-text" value="{pigcms{$_GET['card_id']}"/>
                            卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                            用户ID: <input type="text" name="uid" class="input-text" value="{pigcms{$_GET['uid']}"/>
                            姓名: <input type="text" name="user_name" class="input-text" value="{pigcms{$_GET['user_name']}"/>
                            手机号: <input type="text" name="phone" class="input-text" value="{pigcms{$_GET['phone']}"/>
                            归属单位: <input type="text" name="company_owner" class="input-text" value="{pigcms{$_GET['company_owner']}"/>
                            <font color="#000">激活日期范围：</font>
                            <input type="text" class="input-text" name="start_active_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_active_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_active_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_active_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <font color="#000">卡余额范围：</font>
                            <input type="text" class="input-text" name="start_balance" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_balance}" />
                            <input type="text" class="input-text" name="end_balance" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_balance}" />
                            状态:
                            <select name="status">
                                <option value="" <if condition="$_GET['status'] eq ' '">selected="selected"</if>>全部</option>
                                <option value="1" <if condition="$_GET['status'] eq '1'">selected="selected"</if>>正常</option>
                                <option value="0" <if condition="$_GET['status'] eq '0'">selected="selected"</if>>禁用</option>
                                <option value="2" <if condition="$_GET['status'] eq '2'">selected="selected"</if>>未审核</option>
                            </select>

                            <input type="submit" style="margin-right:10px;" value="查询" class="button"/>
                            <input type="submit" style="margin-right:10px;" value="单卡新建" class="button" id="add_card"/>

                            <a href="javascript:void(0);" class="button" onclick="window.top.artiframe('{pigcms{:U('InternalCard/names', ['from' => 'card'])}','一卡通类型汇总',1200,600,true,false,false,false,'',false);">一卡通类型汇总</a>
                            <a href="javascript:void(0)" onclick="exportCard()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>

                            <notempty name="export">
                                <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出用户</a>
                            </notempty>
                        </form>
					</td>
				</tr>
			</table>
            <!-- <p>
                总用户数：{pigcms{$sumStatistics['card_count']|getFormatNumber=###}&nbsp;&nbsp;&nbsp;&nbsp;
                卡内总余额：{pigcms{$sumStatistics['card_total_money']|getFormatNumber=###}&nbsp;&nbsp;&nbsp;&nbsp;
                总充值余额：{pigcms{$sumStatistics['card_recharge_money']|getFormatNumber=###}
            </p> -->
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
							<col width="180" align="center"/>
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
                                <th>归属单位区域</th>
                                <!-- <th>关联店铺名称</th> -->
                                <th>激活时间</th>
								<th>最后修改时间</th>
								<th class="textcenter">卡余额 (合计：<span style="color: #ff5100;">￥{pigcms{$card_money}</span>)</th>
								<th class="textcenter">状态</th>
								<th class="textcenter">编辑</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($card_list)">
								<volist name="card_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.card_name}</td>
                                        <td>{pigcms{$vo.card_id}</td>
										<td>
                                            <empty name="vo.uid">
                                                <font color="red">没有绑定用户</font>
                                            <else /> 
                                                <if condition="$system_session['level'] == 2">
                                                    <font color="blue">{pigcms{$vo.uid}<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);">查看</a></font>
                                                <else/>
                                                    <font color="blue">{pigcms{$vo.uid}<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,false,false,false,'','edit',false);">查看</a></font>
                                                </if>
                                            </empty>
                                        </td>
                                        <td>{pigcms{$vo.user_name}</td>
                                        <td>{pigcms{$vo.phone}</td>
                                        <td>{pigcms{$vo.company_owner}</td>
                                        <td>{pigcms{$vo.region_str}</td>
                                        <!-- <td>{pigcms{$vo.store_name}<br/>【ID:{pigcms{$vo.store_id}】</td> -->

                                        <td><empty name="vo.active_time"><else />{pigcms{$vo.active_time|date='Y-m-d H:i:s',###}</empty></td>
										<td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
										<td class="textcenter">{pigcms{$config['Currency_symbol']}{pigcms{$vo.balance_money|floatval=###}
                                            <if condition="$vo['status'] neq 0">
                                                <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/recharge_balance',array('card_id'=>$vo['card_id']))}','充值',500,300,true,false,false,submitbtn,'recharge_balance',true);"> 充值</a>&nbsp;|&nbsp;
                                            </if>
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/recharge_records',array('card_id'=>$vo['card_id']))}','交易记录',1000,560,true,false,false,null,'money_list',true);"> 充值记录</a>&nbsp;|&nbsp;
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/transaction_records',array('card_id'=>$vo['card_id']))}','交易记录',1000,560,true,false,false,null,'money_list',true);"> 交易记录</a> 
                                        </td>

                                        <td class="textcenter"><if condition="$vo['status'] eq 1"><font color="green">正常</font><elseif condition="$vo['status'] eq 2" /><font color="red">未审核</font><else /><font color="red">禁用</font></if></td>
										<td class="textcenter">
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/edit_card',array('card_id'=>$vo['card_id']))}','编辑实体卡信息',680,560,true,false,false,editbtn,'add',true);"><if condition="($vo['status'] eq 2) or ($vo['status'] eq 0)">审核<else />编辑</if></a>&nbsp;|&nbsp;
                                            <a href="javascript:void(0);" class="delete_row" parameter="id=10" url="{pigcms{:U('InternalCard/del_card',array('card_id'=>$vo['card_id']))}">删除</a>&nbsp;|&nbsp;
                                            <if condition="in_array($vo['status'], [0, 1])">
                                                <a href="javascript:void(0);" class="delete_row" parameter="card_id={pigcms{$vo.card_id}" url="{pigcms{:U('InternalCard/statusChange')}" not-del="1" tip="您确定要{pigcms{$vo['status'] ? '禁用' : '恢复'}{pigcms{$vo.card_id}一卡通吗？">{pigcms{$vo['status'] ? '禁用' : '恢复'}</a>&nbsp;|&nbsp;
                                            </if>
                                            <if condition="$vo['status'] == 1">
                                                <a href="javascript:void(0);" class="transfer-card" card-id="{pigcms{$vo.card_id}">转移</a>
                                            </if>
                                        </td>

									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<include file="Public:footer"/>

<if condition="$admin_area_id eq 0">
    <script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
</if>

<script type="text/javascript">
    transfer_card_id = '';
    $(function() {
        $('.transfer-card').on('click', transferCardMoney)
    })

     function exportCard() {
         window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
     }

     function transferCardMoney() {
         var _self = $(this);
         var before_card_id = $(this).attr('card-id');
         var load_index;

         layer.prompt({
             title: '请输入转移后的一卡通ID',
             placeholder: '请输入转移后的一卡通ID',
             formType: 0,
             value: transfer_card_id,
             cancel: function() {
                 transfer_card_id = '';
             },
         }, function(after_card_id, index){
             transfer_card_id = after_card_id;
             $.ajax({
                 type: 'post',
                 url: "{pigcms{:U('InternalCard/transferCardMoney')}",
                 data: {before_card_id: before_card_id, after_card_id: after_card_id},
                 beforeSend: function() {
                     load_index = layer.load(0);
                 },
                 success: function(suc) {
                     layer.close(load_index);
                     if (suc.status == 0) {
                         layer.msg(suc.info, {icon: 2, shade: [0.3, '#000'], shadeClose: true, end: function() {
                                _self.trigger('click');
                             }
                         });
                     } else {
                         transfer_card_id = '';
                         layer.close(index);
                         window.location.reload();
                     }
                 },
                 error: function(err) {
                     layer.close(load_index)
                     layer.msg('网络错误，请稍后重试', {icon: 2, shade: [0.3, '#000'], shadeClose: true, end: function() {
                             _self.trigger('click');
                         }
                     });
                 }
             })
         });
     }
    //新建
    $("#add_card").click(function(event){ 
        event.preventDefault()
        window.top.artiframe('{pigcms{:U('InternalCard/addCard')}','单卡新建',780,400,true,false,false,addbtn,'add',true)
    });
</script>