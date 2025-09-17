  <include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tasks"></i>
	            <a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
            </li>
	        <li class="active">二维码记录</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
			
			<div style="border:1px solid #c5d0dc;padding:10px;" class="form-group">
				<button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('cate_index')}'" >智慧二维码类别</button>
				<form method="get" action="{pigcms{:U('WisdomQrcode/qrcode_record_index')}">
					<input type="hidden" value="WisdomQrcode" name="c">
					<input type="hidden" value="qrcode_record_index" name="a">
				
					<select style="margin-right:10px;height:42px; width:120px" class="col-sm-1" id="find_type" name="find_type">
						<option value="0" <if condition="$_GET['find_type'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>二维码名称</option>
						<option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>二维码类别</option>
						<option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>负责人姓名</option>
						<option value="4" <if condition="$_GET['find_type'] eq 4">selected="selected"</if>>负责人联系方式</option>
					</select>
					<input type="text" style="margin-right:10px;font-size:18px;height:42px;width: 250px;" id="find_value" name="find_value" class="col-sm-2" value="{pigcms{$_GET['find_value']}">
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间" name="begin_time" class="col-sm-2" style="margin-right:10px;font-size:18px;height:42px;width: 180px;" value="{pigcms{$_GET['begin_time']}" />
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间" name="end_time" class="col-sm-2" style="margin-right:10px;font-size:18px;height:42px; width: 180px;" value="{pigcms{$_GET['end_time']}" />
					<input type="submit" value="查找" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;
                    <if condition="$is_property_robot_jsdhwy">
                    <select style="margin-right:10px;height:42px; width:170px" class="col-sm-1" id="find_type1" name="view_type">
                        <option value="0" <if condition="$_GET['view_type'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['view_type'] eq 1">selected="selected"</if>>已查看</option>
						<option value="2" <if condition="$_GET['view_type'] eq 2">selected="selected"</if>>未查看</option>
						<option value="3" <if condition="$_GET['view_type'] eq 3">selected="selected"</if>>已生成工单（机器人数据）</option>
						<option value="4" <if condition="$_GET['view_type'] eq 4">selected="selected"</if>>未生成工单（机器人数据）</option>
					</select>

                    <select style="margin-right:10px;height:42px; width:170px" class="col-sm-1" id="find_type2" name="patrol_type">
                        <option value="0" <if condition="$_GET['patrol_type'] eq 0">selected="selected"</if>>全部巡检</option>
                        <option value="1" <if condition="$_GET['patrol_type'] eq 1">selected="selected"</if>>系统巡检</option>
                        <option value="2" <if condition="$_GET['patrol_type'] eq 2">selected="selected"</if>>智能巡检</option>
						<option value="3" <if condition="$_GET['patrol_type'] eq 3">selected="selected"</if>>AI识别巡检</option>
						
					</select>
                    </if>
					<a onclick="location.href='{pigcms{:U('WisdomQrcode/qrcode_record_index')}'" class="btn btn-success">重置</a>
<!--					<button class="btn btn-success fr" style="margin-top: -35px;" onclick="download()">导出二维码记录</button>-->
					<a onclick="location.href='{pigcms{:U('download_record_list')}&begin_time={pigcms{$_GET['begin_time']}&end_time={pigcms{$_GET['end_time']}&find_type={pigcms{$_GET['find_type']}&find_value=<?php echo urlencode($_GET['find_value']); ?>'" class="btn btn-success">导出二维码记录</a>
                    <if condition="$qrcodeRecordExportDetails">
                        <a  class="qrcodeRecordExport btn btn-success" href="javascript:;" style="margin-left: 15px;">导出巡检明细表</a>
                    </if>
				</form>

			</div>
			<span style="color:red;">注: 请先按二维码类别筛选出记录结果，再点击导出二维码记录按钮统一导出</span>

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="10%">二维码名称</th>
                                    <th width="10%">二维码类别</th>
									<th width="10%">负责人姓名</th>
									<th width="10%">负责人联系方式</th>
									<th width="10%">记录时间</th>
                                     <if condition="$is_property_robot_jsdhwy">
                                      <th width="8%" style="text-align: center">是否已查看</th>
                                      <th width="8%" style="text-align: center">工单序号</th>
                                      <th width="8%" style="text-align: center">巡检来源</th>
                                     </if>
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
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <if condition="$is_property_robot_jsdhwy AND $vo['view_time'] gt 0">
                                                <td style="text-align: center">已查看</td>
                                                <elseif condition="$is_property_robot_jsdhwy AND $vo['view_time'] lt 1"/>
                                                <td style="text-align: center">未查看</td>
                                            </if>
                                            <if condition="$is_property_robot_jsdhwy">
                                                    <td style="text-align: center">{pigcms{$vo.work_order_id}</td>
                                                    <td style="text-align: center">{pigcms{$vo.patrol_type_str}</td>
                                            </if>
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
				width: 1300,
				height: 750,
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
    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
    var loadExport = false;
   $('.qrcodeRecordExport').click(function () {
        if (loadExport) {
            layer.msg('当前导出中，请稍后再试!',{icon: 0,time:0,shade: [0.5]});
            return false
        }
        var _this = $(this);
        _this.html('数据导出中...');
        _this.removeClass('btn-success');

        //下载完成后触发，用来关闭提示框
        disload = function() {
            layer.closeAll();
            loadExport = false;
            _this.html('导出巡检明细表');
            _this.addClass('btn-success');
        }
        layer.msg('正在为您导出数据，如果数据多时间会较长，请耐心等待!',{icon: 0,time:0,shade: [0.5]});
        // 下载使用的方法
        getDownload = function(url) {
            var xhr = new XMLHttpRequest();
            if(typeof(xhr) !="undefined") {
                // 现在使用的方法 可在下载完成的时候关闭load弹窗
                xhr.open('POST', url, true);    // 也可用POST方式
                xhr.responseType = "blob";
                xhr.onload = function () {
                    if (this.status === 200) {
                        var blob = this.response;
                        console.log('blob',blob);
                        var headerName = xhr.getResponseHeader("Content-disposition");
                        headerName=decodeURIComponent(headerName);
                        console.log(headerName);
                        if (navigator.msSaveBlob == null) {
                            var a = document.createElement('a');
                            a.download =headerName.substring(20);
                            a.href = URL.createObjectURL(blob);
                            $("body").append(a);    // 修复firefox中无法触发click
                            a.click();
                            URL.revokeObjectURL(a.href);
                            $(a).remove();
                        } else {
                            navigator.msSaveBlob(blob,headerName.substring(20));
                        }
                    }
                    disload();
                };
                xhr.send();
            }
            else {
                window.location.href = url;
            }
        };

        getDownload('{pigcms{:U('download_person_ten_record',$_GET)}');
        loadExport = true;
    });
</script>
<include file="Public:footer"/>
