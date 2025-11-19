<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('visitor_list')}">功能库</a>
			</li>
			<li class="active">访客登记</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
        	
            <div class="row">
                <div class="col-xs-12">
                
                <if condition='0 && !$has_visitor'>
					<div style="margin-top:10px; cursor:pointer" class="alert alert-danger" onClick="window.open('{pigcms{:U('Index/config')}')">
                        <button data-dismiss="alert" class="close" type="button"><i class="ace-icon fa fa-times"></i></button>
                        还未开启&nbsp;&nbsp;<span style="font-weight:bold">访客登记</span>&nbsp;&nbsp;功能，请先到&nbsp;&nbsp;<span style="font-weight:bold">小区管理 - 基本信息 - 功能库配置&nbsp;&nbsp;</span>开启相应配置。
                    </div>
            	</if>
           <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Park/village_visitor_tmp_parking')}" method="get" id="myidform">
                    <input type="hidden" name="c" value="Park"/>
                    <input type="hidden" name="a" value="village_visitor_tmp_parking"/>
                    添加时间：
                    <input type="text" name="begin_time" class="input-text" id="begin_time" value="{pigcms{$_GET['begin_time']}"  style="height:42px" onfocus="bindWdatePicker('begin_time')" placeholder="开始时间" autocomplete="off" />-
                    <input type="text" name="end_time" id="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="bindWdatePicker('end_time')" placeholder="结束时间" autocomplete="off"/>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Park/village_visitor_tmp_parking')}'">重置</a>
                </form>
            </div>
                
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <!--<th width="5%">ID</th>-->
                                    <th width="10%">车牌号</th>
                                    <th width="10%">姓名</th>
                                    <th width="10%">联系方式</th>
                                    <th width="10%">添加时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list['list']">
                                    <volist name="list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                           <!-- <td><div class="tagDiv">{pigcms{$vo.id}</div></td>-->
                                            <td><div class="tagDiv">{pigcms{$vo.car_id}</div></td>
                                           	<td><div class="tagDiv">{pigcms{$vo.visitor_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.visitor_phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div></td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="9" >没有任何访客记录。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$('#search').live('click',function(){
	var search_url = "{pigcms{:U('visitor_search')}"
			art.dialog.open(search_url,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'搜索访客',
				padding: 0,
				width: 720,
				height: 400,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});
  function bindWdatePicker(domid){
        if(domid=='begin_time'){

            var end_date=$('#end_time').val();
            if(end_date) {
                WdatePicker({isShowClear: true, readOnly: true, dateFmt: 'yyyy-MM-dd',maxDate:'#F{$dp.$D(\'end_time\')}'});
            }else{
                WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'});
            }
        }else if(domid=='end_time'){
            var start_date=$('#begin_time').val();
            if(start_date){
                WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd',minDate:'#F{$dp.$D(\'begin_time\')}'});
            }else{
                WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'});
            }

        }else{
            WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'});
        }
    }
</script>

<include file="Public:footer"/>