<include file="Public:header"/>
		<div class="mainbox">
            <include file="recharge_header"/>
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
                            <font color="#000">创建时间范围：</font>
                            <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            批次号: <input type="text" name="batch_no" class="input-text" value="{pigcms{$_GET['batch_no']}"/>
                            备注: <input type="text" name="remark" class="input-text" value="{pigcms{$_GET['remark']}"/>

                            <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                            <span>汇总金额：{pigcms{$data.totalMoney}</span>
                            <a href="javascript:void(0)" onclick="exportData()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>

                        </form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col width="10%"/>
							<col width="15%"/>
							<col width="15%"/>
							<col width="10%"/>
							<col width="15%"/>
							<col width="10%"/>
							<col width="10%"/>
							<col width="15%"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>批次号</th>
                                <th>区域</th>
                                <th>总金额</th>
                                <th>备注</th>
                                <th>操作员</th>
								<th>创建时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$data['list']">
								<volist name="data.list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.batch_no}</td>
                                        <td>{pigcms{$vo.region_str}</td>
                                        <td>{pigcms{$vo.total_money}</td>
                                        <td>{pigcms{$vo.remark}</td>
                                        <td>{pigcms{$vo.admin_name}</td>
                                        <td>{pigcms{$vo.create_time}</td>
										<td>
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/rechargeBatchRemarkSet',array('id'=>$vo['id']))}','修改备注',680,400,true,false,false,editbtn,'edit',true);">修改备注</a>
                                            <span>&nbsp;|&nbsp;</span>
                                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('InternalCard/recharge_log',array('deduction_batch_no'=>$vo['batch_no']))}','一卡通充值明细',1000,560,true,false,false,null,'money_list',true);">查看导入明细</a>
                                            <span>&nbsp;|&nbsp;</span>
                                            <a href="{pigcms{:U('InternalCard/recharge_log',array('deduction_batch_no'=>$vo['batch_no'], 'export' => 'excel'))}">导出导入明细</a>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="9">{pigcms{$data.pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
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