  <include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">

        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
			
			<div style="border:1px solid #c5d0dc;padding:10px;" class="form-group">
			
				<form method="get" action="{pigcms{:U('WisdomQrcode/qrcode_record_ai')}">
					<input type="hidden" value="WisdomQrcode" name="c">
					<input type="hidden" value="qrcode_record_ai" name="a">
				
					<select style="margin-right:10px;height:42px; width:120px" class="col-sm-1" id="find_type" name="find_type">
						<option value="0" <if condition="$_GET['find_type'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>二维码名称</option>
						<option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>二维码类别</option>
					</select>
					<input type="text" style="margin-right:10px;font-size:18px;height:42px;width: 250px;" id="find_value" name="find_value" class="col-sm-2" value="{pigcms{$_GET['find_value']}">
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间" name="begin_time" class="col-sm-2" style="margin-right:10px;font-size:18px;height:42px;width: 180px;" value="{pigcms{$_GET['begin_time']}" />
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间" name="end_time" class="col-sm-2" style="margin-right:10px;font-size:18px;height:42px; width: 180px;" value="{pigcms{$_GET['end_time']}" />
					<input type="submit" value="查找" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;
                    <select style="margin-right:10px;height:42px; width:170px" class="col-sm-1" id="find_type1" name="view_type">
                        <option value="0" <if condition="$_GET['view_type'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['view_type'] eq 1">selected="selected"</if>>已查看</option>
						<option value="2" <if condition="$_GET['view_type'] eq 2">selected="selected"</if>>未查看</option>
						<option value="3" <if condition="$_GET['view_type'] eq 3">selected="selected"</if>>已生成工单（机器人数据）</option>
						<option value="4" <if condition="$_GET['view_type'] eq 4">selected="selected"</if>>未生成工单（机器人数据）</option>
					</select>

                    <select style="margin-right:10px;height:42px; width:170px" class="col-sm-1" id="find_type2" name="recognition_type">
                        <option value="" <if condition="$_GET['patrol_type'] eq  ''">selected="selected"</if>>全部识别类型</option>
                        <volist name="visionLogTyppeArr" id="vol">
                            <option value="{pigcms{$vol.type_key}" <if condition="$_GET['recognition_type'] eq  $vol['type_key']">selected="selected"</if> >{pigcms{$vol.type_name}</option>
                        </volist>
						
					</select>
					<a onclick="location.href='{pigcms{:U('WisdomQrcode/qrcode_record_ai')}'" class="btn btn-success">重置</a>
					<a onclick="location.href='{pigcms{:U('download_record_list',$_GET)}'" class="btn btn-success">导出二维码记录</a>
				</form>

			</div>
			<span style="color:red;">注: 请先按二维码类别筛选出记录结果，再点击导出二维码记录按钮统一导出</span>
            <a href="javascript:;" onclick="sendAiMessageSet()" class="btn btn-success" id="sendAiMessageSet" style="margin-left: 10px;">记录提醒设置</a>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="10%">二维码名称</th>
                                    <th width="10%">二维码类别</th>
									<th width="10%">负责人姓名</th>
									<th width="10%">记录时间</th>
                                      <th width="8%" style="text-align: center">是否已查看</th>
                                      <th width="8%" style="text-align: center">工单序号</th>
                                      <th width="8%" style="text-align: center">识别类型</th>
                                    <th class="button-column" width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.qrcode_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.cate_name}</div></td>
                                            <if  condition="isset($vo['from_type']) AND $vo['from_type'] eq 1">
                                               <td><div class="tagDiv"> 机器人：{pigcms{$vo.name}<br/>
                                                机器人编码：{pigcms{$vo.robot_code}
                                                </div></td>
                                            <else />
											    <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            </if>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <if condition="$vo['view_time'] gt 0">
                                                <td style="text-align: center">已查看</td>
                                                <elseif condition="$vo['view_time'] lt 1"/>
                                                <td style="text-align: center">未查看</td>
                                            </if>
                                                    <td style="text-align: center">{pigcms{$vo.work_order_id}</td>
                                                    <td style="text-align: center">{pigcms{$vo.recognition_type_str}</td>
                                            <td class="button-column">
                                            <if  condition="isset($vo['from_type']) AND $vo['from_type'] eq 1 AND $vo['work_order_id'] lt 1">
                                                <a style="width: 70px;height: 22px;line-height: 19px;" class="label label-sm label-info" title="生成工单" href="javascript:;" onclick="addWorkOrder({pigcms{$vo.id})" >生成工单</a>
                                            </if>
                                                <a style="width: 70px;height: 22px;line-height: 19px;" class="label label-sm label-info handle_btn" title="查看详情" href="{pigcms{:U('qrcode_record_info',array('id'=>$vo['id']))}" >详情</a>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >暂无数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>


	function download(){
		var url = "{pigcms{:U('WisdomQrcode/download_record')}";
		layer.open({
			type: 2,
			title:'导出',
			content: url,
			area: ['600px', '300px'],
			maxmin: false
		});
	}

	$(function(){
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'查看详情',
				padding: 0,
				width: 1200,
				height: 680,
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
		 
	});

    function addWorkOrder(logid){
			art.dialog.open("{pigcms{:U('qrecord_to_work_order')}"+"&log_id="+logid,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'生成工单',
				padding: 0,
				width: 680,
				height: 320,
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
    }

   function sendAiMessageSet(){
        // var open_url = parent.location.href.split("#")[0]+'#/village/village.chatSidebar/userInfo?pigcms_id=0';
         var open_url = parent.location.href.split("#")[0]+'#/village/village.houseWorker/sendAiMessageSetWorker';
			art.dialog.open(open_url,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'sendAiMessageSet',
				title:'记录提醒人员设置',
				padding: 5,
				width: 700,
				height: 550,
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
    }
    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
</script>
<include file="Public:footer"/>
