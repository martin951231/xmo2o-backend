<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="renderer" content="webkit">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="stylesheet" href="{pigcms{$static_path}/layer/css/layui.css"  media="all">
        <link rel="stylesheet" href="{pigcms{$static_path}/css/test.css" type="text/css">
        <script src="{pigcms{$static_path}/propery_group/js/jquery-2.1.0.min.js"></script>
        <style></style>
    </head>
    <body>
    <div class="">
        <if condition="!$no_upload_face">
            <div class="header">
                1、请上传1张脸部轮廓清晰的照片，确保为本人照片，否则影响开门<br/>
                2、露出额头及耳朵，人脸正面免冠近期大头照<br/>
                3、照片白底、无逆光、无PS，无过度美颜处理
            </div>
		<else/>
			<div class="header">请让用户在微信端自行录入脸部照片</div>
		</if>
		<div class="choicesf media-list" id="medialist">
			<input type="hidden" id="imglist" name="pigcms_id" value="{pigcms{$pigcms_id}">
			<volist name="dataImg" id="vo">
				<dd class="andArea filee" >
					<div class="upload-pre-item">
						<i onclick="deleteImg($(this),{pigcms{$vo.id})" style="margin-left: 80px"  class="layui-icon"></i>
						<a href="{pigcms{$vo['img_url']}" target="_blank" title="查看原图"><img src="{pigcms{:thumb($vo['img_url'], 96, 96, 'fill')}" class="img" style='width:100px;height:85px;margin-top:0px;'/></a>
					</div>
				</dd>
			</volist>
			<if condition="!$no_upload_face">
				<div class="choices" id="choices" <if condition="count($dataImg) egt 5"> style='display:none' </if>>
					<div class="andArea">
						<div class="filePickers" onclick="bounceds({pigcms{$pigcms_id})">拍照上传</div>
                        <if condition="$face_door_set eq 1">
						<div class="filePicker picupload" id="picUpload" style="">选择文件</div>
                        </if>
						<input class="picupload" style="display: none;" id="fileImage" type="file"  size="30" name="fileselect" multiple/>
					</div>
				</div>
			</if>
		</div>
		
        <div class="fonts" style="">
            <if condition="$dataImg[0] neq ''">
                <if condition="$info['face_img_status'] eq 0">正在同步
                    <elseif condition="$info['face_img_status'] eq 1" />同步成功
                    <elseif condition="$info['face_img_status'] eq 2"/><span style="color: red">同步失败!
                    <if condition="$info['face_img_reason'] neq '' ">失败原因：{pigcms{$info['face_img_reason']}</if> </span>
                </if>
            </if>
            <br/><br/>
            人脸上传成功后可点击下方同步人脸按钮同步至门禁设备中
        </div>
        <div class="footer" style="">
            <div class="filePickerddd" onclick="syn_device_img({pigcms{$pigcms_id})" style="">同步人脸</div>
        </div>
    </div>

    <if condition="$faceSyncs">
        <style>
            .page-content {
                background-color: #fff !important;
                padding: 12px;
                position: relative;
                margin: 0;
            }
            .page-content-area {
                background-color: white;
                padding: 10px;
            }
            .row {
                margin: 0;
                text-align: center;
            }
            .col-xs-12 {
                padding: 0;
            }
            .col-xs-12 {
                width: 100%;
            }
            .table>thead>tr {
                border-top: none;
                border-bottom: 1px solid #e5e5e5;
                background: #f2f2f2;
                color: black;
            }
            .table.table-bordered>thead>tr>th:first-child {
                border-left-color: #ddd;
            }
            .table-striped>tbody>tr {
                border-bottom: 1px solid #e5e5e5;
            }
            .table-striped>tbody>tr:nth-child(odd)>td, .table-striped>tbody>tr:nth-child(odd)>th {
                background-color: white;
            }
            .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th {
                padding: 12px 8px;
            }
        </style>
        <div class="page-content" style="margin-top: 10px;margin-bottom: 10px;">
            <div class="page-content-area">
                <div style="text-align: center;width: 100%;font-size: 15px;margin-bottom: 5px;">同步信息列表</div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="grid-view">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th width="2%">设备ID</th>
                                    <th width="3%">设备sn</th>
                                    <th width="8%">设备名称</th>
                                    <th width="5%">同步时间</th>
                                    <th width="5%">同步状态</th>
                                    <th width="10%">同步信息</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <volist name="faceSyncs" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.device_id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.device_sn}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.device_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.sync_time}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.statusText}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.msg}</div></td>
                                        </tr>
                                    </volist>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </if>
    <script src="{pigcms{$static_path}/layer/layer.js" charset="utf-8"></script>
    <script>
        function ChooseAdidValues(v,id) {
            if (v != "") {
                var dd = $("#medialist > dd")
                if(dd.length >=4)
                {
                    $('#choices').attr('style','display:none');
                }
                $('.media-list').prepend('<dd class="andArea filee" ><div class="upload-pre-item"><i onclick="deleteImg($(this),'+ id +')" style="margin-left: 80px;"  class="layui-icon"></i><img src="' + v + '" class="img" style=\'width: 100px;height: 75px\'><input type="hidden" name="case_images[]" value="" /></div></dd>');
                // $('#imglist').val(res.img_str);
            }
        }
         function bounceds(id) {
             var index = layer.open({
                 type: 2,
                 area: ['650px', '450px'],
                 fixed: true, //不固定
                 // maxmin: true,
                 title: '人脸上传照片',
                 content: '/shequ.php?g=House&c=User&a=cap&pigcms_id='+id
             });
             layer.style(index, {
                 top: '20px'
             });
         }
         $(".filePicker").bind("click", function(e){
             $("#fileImage").click();

         });
         //上传图片js开始
         $(function () {
             var names = [];
             $('body').on('change', '.picupload', function (event) {
                 var files = event.target.files;
                 for (var i = 0; i < files.length; i++) {
                     var file = files[i];
                     names.push($(this).get(0).files[i].name);
                     var picReader = new FileReader();
                     picReader.fileName = file.name;
                     var pigcms_id = $('#imglist').val();
                     picReader.addEventListener("load", function (event) {
                         var picFile = event.target;
                         var formData = new FormData();
                         formData.append("file", file);
                         formData.append("pigcms_id", pigcms_id);
                          $.ajax({
                             url: "{pigcms{:U('User/add_face_img')}", //Server script to process data
                             type: 'POST',
                             data: formData,
                             dataType:'JSON',
                             contentType: false,
                             processData: false,
                             success: function (res) {
                                 console.log(res);
                                 console.log('resdata',res.data);
                                 if (res.error == 2) {
                                     var dd = $("#medialist > dd")
                                     console.log(dd);
                                     if(dd.length >=4)
                                     {
                                         $('#choices').attr('style','display:none');
                                     }

                                     $('.media-list').prepend('<dd class="andArea filee" ><div class="upload-pre-item"><i onclick="deleteImg($(this),' + res.data.id + ')" style="margin-left: 80px;"  class="layui-icon"></i><img src="' + picFile.result + '" class="img" style=\'width: 100px;height: 75px\'><input type="hidden" name="case_images['+picFile.result+']" value="' + picFile.result + '" /></div></dd>');
								}else{
									alert(res.msg);
								}
                             }
                         });
                     });
                     picReader.readAsDataURL(file);
                 }
             });
         })
		function deleteImg(obj,id){
             //删除页面信息
             // obj.parent().parent('.filee').remove();
             // var dd = $("#medialist > dd")
             // console.log(dd);
             // if(dd.length <=4)
             // {
             //     $('#choices').attr('style','display:');
             // }
			$.ajax({
				url:"{pigcms{:U('User/house_face_img_del')}",
				type:"post",
				data:{id:id},
				dataType: "Json",
				success:function(d){
					if (d.error == 2) {
						obj.parent().parent('.filee').remove();
						var dd = $("#medialist > dd")
						console.log(dd);
						if(dd.length <=4)
						{
							$('#choices').attr('style','display:');
						}
					}
				}
			});
        }
		function syn_device_img(pigcms_id) {
            var loading = layer.msg('同步命令正在处理中...', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
			$.ajax({
				url:"{pigcms{:U('User/syn_device_img')}",
				type:"post",
				data:{"pigcms_id":pigcms_id},
				dataType: "Json",
				success:function(d){
					if (d.errorCode==0) {
						//alert(d.errorMsg);
						alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
						window.location.reload();
					} else {
						alert(d.errorMsg);
						window.location.reload();
					}
					console.log(d,d.errorCode);
				}
            });
		}
    </script>
    <script src="{pigcms{$static_path}/dist/zoomify.min.js"></script>
    <script type="text/javascript">
        // $('.upload-pre-item img').zoomify();
    </script>
    </body>
</html>
