<include file="Public:header"/>
		<div class="mainbox">
            <include file="internal_unit_menu"/>
			<table class="search_table" width="100%">
				<tr>
					<td>
                        <form method="get">
                            <input type="hidden" name="c" value="{pigcms{:MODULE_NAME}"/>
                            <input type="hidden" name="a" value="{pigcms{:ACTION_NAME}"/>
                            <if condition="$admin_area_id eq 0">
                                区域：
                                <div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                            </if>
                            归属单位名称: <input type="text" name="unit_name" class="input-text" value="{pigcms{$_GET['unit_name']}"/>
                            <font color="#000">余额范围：</font>
                            <input type="text" class="input-text" name="start_balance" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_balance}" />
                            <input type="text" class="input-text" name="end_balance" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_balance}" />

                            <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                            <span>总余额：<span style="color: #ff5100;">￥{pigcms{$sumInternalUnit['sumBalance']}</span></span>
                            <span>总收款余额：<span style="color: #ff5100;">(￥{pigcms{$sumInternalUnit['sumCollectMoney']})</span></span>
                            <span>总支出余额：<span style="color: #ff5100;">(￥{pigcms{$sumInternalUnit['sumUsedMoney']})</span></span>
                            <a href="javascript:void(0)" onclick="exportData()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>
                            <a href="javascript:void(0);" class="button" style="float:right;margin-right: 10px;" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/unitSet', ['id' => $vo['id']])}','添加归属单位',680,200,true,false,false,addbtn,'add',true);">添加</a>

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
								<th width="5%">ID</th>
								<th width="10%">归属单位名称</th>
								<th width="10%">区域</th>
								<th width="8%">联系人</th>
								<th width="10%">联系电话</th>
								<th width="10%">余额<span style="color: #ff5100;">(￥{pigcms{$balance_money})</span></th>
                                <th width="10%">总收款金额<span style="color: #ff5100;">(￥{pigcms{$collect_money})</span></th>
                                <th width="10%">总支出金额<span style="color: #ff5100;">(￥{pigcms{$used_balance})</span></th>
								<th width="8%">操作员</th>
								<th width="10%">创建时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($data['list'])">
								<volist name="data.list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.name}</td>
                                        <td>{pigcms{$vo.region_str}</td>
                                        <td>{pigcms{$vo.contact_name}</td>
                                        <td>{pigcms{$vo.contact_phone}</td>
                                        <td>{pigcms{$vo.balance}</td>
                                        <td>{pigcms{$vo.total_money}</td>
                                        <td>{pigcms{$vo.used_money}</td>
                                        <td>{pigcms{$vo.admin_name}</td>
                                        <td>{pigcms{$vo.create_time}</td>
										<td>
                                            <if condition="$system_session['level'] == 2">
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/unitSet', ['id' => $vo['id']])}','编辑归属单位信息',680,200,true,false,false,editbtn,'add',true);">编辑</a> &nbsp;|&nbsp;
                                            </if>
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/rechargeAdd', ['unit_id' => $vo['id']])}','添加收款记录',680,400,true,false,false,addbtn,'add',true);">收款</a>&nbsp;|&nbsp;
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/deductionAdd', ['unit_id' => $vo['id']])}','添加支出记录',680,400,true,false,false,addbtn,'add',true);">支出</a>&nbsp;|&nbsp;
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/rechargeRecords', ['unit_id' => $vo['id']])}','收款记录',850,600,true,false,false,false,'add',true);">收款记录</a>&nbsp;|&nbsp;
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('InternalUnit/deductionRecords', ['unit_id' => $vo['id']])}','支出记录',850,600,true,false,false,false,'add',true);">支出记录</a>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$data.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
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
    function exportData() {
        window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
    }
</script>