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
        <div class="header">
            需上传3-5张脸部照片
        </div>
        <div class="choicesf media-list" id="medialist">
            <input type="hidden" class="img_id" name="id" value="{pigcms{$img_id}"/>
            <input type="hidden" id="imglist" name="id" value="">
            <volist name="dataImg" id="vo">
            <dd class="andArea filee" >
                <div class="upload-pre-item">
                    <i onclick="deleteImg($(this),{pigcms{$vo.id})" style="margin-left: 80px"  class="layui-icon"></i>
                    <img src="{pigcms{$vo.img_url}" class="img" style='width: 100px;height: 75px'>
                    <input type="hidden" name="" value="" />
                </div>
            </dd>
            </volist>
            <div class="choices" id="choices" <if condition="count($dataImg) egt 5"> style='display:none' </if>>
                <div class="andArea">
                    <div class="filePickers" onclick="bounceds({pigcms{$pigcms_id})">拍照上传</div>
                    <div class="filePicker picupload" id="picUpload" style="">选择文件</div>
                    <input class="picupload" style="display: none;" id="fileImage" type="file"  size="30" name="fileselect" multiple>
                </div>
            </div>
        </div>
        <div class="footer" style="margin-top: 180px;">
            <div class="filePickerddd" onclick="returnPass()" style="">确定</div>
        </div>
    </div>
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
             layer.open({
                 type: 2,
                 area: ['650px', '450px'],
                 fixed: true, //不固定
                 // maxmin: true,
                 title: '人脸上传照片',
                 content: '/shequ.php?g=House&c=User&a=cap&pigcms_id='+id
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
                     picReader.addEventListener("load", function (event) {
                         var picFile = event.target;
                         var formData = new FormData();
                         formData.append("file", file);
                         // formData.append("pigcms_id", $('#imglist').val());
                          $.ajax({
                             url: "{pigcms{:U('add_face_img')}", //Server script to process data
                             type: 'POST',
                             data: formData,
                             dataType:'json',
                             contentType: false,
                             processData: false,
                             success: function (res) {
                                 console.log(res);
                                 if (res.error == 2) {
                                     if($('.img_id').val()) {
                                         var img_id = res.data.id + ',' + $('.img_id').val();
                                     }else{
                                         var img_id = res.data.id;
                                     }
                                     var dd = $("#medialist > dd")
                                     console.log(dd);
                                     if(dd.length >=4)
                                     {
                                         $('#choices').attr('style','display:none');
                                     }

                                     $('.media-list').prepend('<dd class="andArea filee" ><div class="upload-pre-item"><i onclick="deleteImg($(this),' + res.data.id + ')" style="margin-left: 80px;"  class="layui-icon"></i><img src="' + picFile.result + '" class="img" style=\'width: 100px;height: 75px\'><input type="hidden" name="case_images['+picFile.result+']" value="' + picFile.result + '" /><input type="hidden" class="img_id" name="id" value="'+img_id+'"/></div></dd>');
                                     // $('#imglist').val(res.img_str);
                                     $('#imglist').val(res.data.pigcms_id);
                                     console.log(res.data.pigcms_id);
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
             $.ajax({
                 url:"{pigcms{:U('house_face_img_del')}",
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
         function returnPass() {
            var img_id = $('.img_id').val();
            console.log('rererer',$('.img_id').val());
             parent.ChooseAdidValuePass(img_id); //这是父页面函数
             var index = parent.layer.getFrameIndex(window.name);
             parent.layer.close(index);
         }
    </script>
    <script src="{pigcms{$static_path}/dist/zoomify.min.js"></script>
    <script type="text/javascript">
        // $('.upload-pre-item img').zoomify();
    </script>
    </body>
</html>
