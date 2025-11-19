<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
						<a href="{pigcms{:U('Bd/bd_list')}">业务经理列表</a>
						<a href="{pigcms{:U('Bd/bd_son_list')}">业务员列表</a>
						<a href="{pigcms{$this_url}" class="on">{pigcms{$title}推荐总佣金记录</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td style="display: flex; justify-content: space-between; align-items: center;">
						<form action="{pigcms{:U('Bd/commission_record')}" method="get">
							<input type="hidden" name="c" value="Bd"/>
							<input type="hidden" name="a" value="commission_record"/>
							<input type="hidden" name="bd_id" value="{pigcms{$current_bdid}">
							<input type="hidden" name="pbd_id" value="{pigcms{$current_pbdid}">
								搜索：
								<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="" />
								<select name="searchtype">
								<option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>								
								<option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>店铺名称</option>
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<div style="display:inline"><font color="#000">佣金结算时间筛选 ：</font></div>
							<input type="text" class="input" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="text" class="input" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							业务类型：
							<select name="order_type">
								<volist name="get_alias_c_name" id="v">
                                <option value="{pigcms{$key}" <if condition="$_GET['order_type'] eq $key">selected="selected"</if>>{pigcms{$v}</option>
                            	</volist>
                            </select>&nbsp;&nbsp;
							<input type="submit" value="查询" class="button"/>
						</form>
            <span style="margin-right: 20%; "><input type="button" value="导出" class="button" id="recordExport"/></span>
					</td>
				</tr>
			</table>
			<div style="margin:10px 0 10px 0;width:100%;height:40px;background-color:#81d2cf; line-height: 40px; font-weight: bold; font-size: 16px; color:#fff;padding-left: 30px;">
					推荐商家总数：{pigcms{$merchant_nums}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 佣金总额：{pigcms{$config['Currency_symbol']}{pigcms{$all_commission}				
			</div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
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
						</colgroup>
						<thead>
							<tr>
								<th>业务类型</th>
								<th>订单号</th>
								<th>商家名称</th>
								<th>店铺名称</th>
								<th>获得佣金</th>
								<th>佣金结算时间</th>
							</tr>
						</thead>
						<tbody>
					
							<if condition="$data">
								<volist name="data" id="vo">
									<tr>
										<td>{pigcms{$vo.order_type_txt}</td>
										<td>{pigcms{$vo.order_id}</td>
										<td>{pigcms{$vo.mer_name}</td>
										<td>{pigcms{$vo.store_name}</td>
										<td><span style="color:green;">+{pigcms{$vo.commission}</span></td>
										<td>{pigcms{$vo.addtime}</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="11">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="11">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script>
  var export_url = "{pigcms{:U('Bd/commission_record')}"

  $(document).ready(function(){
    $("#recordExport").click(function(){
      get_str = $('form').serialize();
      get_str = get_str.replace(/c=\w+&/,'',get_str)
      get_str = get_str.replace(/a=\w+&/,'',get_str)
      get_str = get_str+'&export=1'
      $.post(export_url,get_str,function(result){
        let msgTimes = layer.msg('加载中,请耐心等待,数量越多时间越长。', {
          icon: 16
          ,shade: [0.3, '#000']
          ,time:0
          ,offset:'50px'
        });
        if(result.code == 200){
          layer.close(msgTimes)
          window.open(result.url)
        } else 
        if(result.code == 500){
          layer.close(msgTimes)
          layer.msg(result.error_msg)
        } else {
          setTimeout(function (msgTimes){
            layer.close(msgTimes)
          },5000)
        }
      })
    });
  });  
  
</script>
<include file="Public:footer"/>