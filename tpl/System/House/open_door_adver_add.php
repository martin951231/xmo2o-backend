<include file="Public:header"/>
<style>
    .radio_185{
        display: none;
    }
    #video_click div{
        height: 100% !important;
    }
    .td_style {
        display: flex;
        flex-direction: row;
        align-items: center;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.md5.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}webuploader/webuploader.css">
<script type="text/javascript" src="{pigcms{$static_path}webuploader/webuploader.js"></script>
<script src="{pigcms{$static_path}layui/lay/modules/layer.js"></script>

<form id="myform" method="post" action="{pigcms{:U('House/open_door_adver_add')}" enctype="multipart/form-data">
    <input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">智能硬件</th>
            <td>
                <select name="intelligent_hardware_type" id="intelligent_hardware_type">
                    <option value="" style="display: none;">请选择智能硬件类型</option>
                    <volist name="intelligent_hardware_type_arr" id="item_type">
                        <option value="{pigcms{$key}">{pigcms{$item_type}</option>
                    </volist>
                </select>
            </td>
        </tr>
        <tr class="equipment_type_arr_box" style="display: none;">
            <th width="80">设备类型</th>
            <td>
                <volist name="equipment_type_arr" id="type_arr">
                    <if condition="$type_arr && is_array($type_arr) && $type_arr[0] && $type_arr[1]">
                        <select name="equipment_type_change" id="equipment_type_{pigcms{$key}" class="equipment_type_box equipment_type_{pigcms{$key}" style="display: none;">
                            <option value="" style="display: none;">请选择对应设备类型</option>
                            <volist name="type_arr" id="val">
                                <option value="{pigcms{$val['value']}">{pigcms{$val['name']}</option>
                            </volist>
                        </select>
                    <elseif condition="$type_arr && is_array($type_arr) && $type_arr[0] && !$type_arr[1]"/>
                        <select id="equipment_type_{pigcms{$key}" class="equipment_type_box equipment_type_{pigcms{$key}" style="display: none;">
                            <option value="{pigcms{$type_arr[0]['value']}">{pigcms{$type_arr[0]['name']}</option>
                        </select>
                    <elseif condition="$type_arr && $type_arr['name']"/>
                        <select id="equipment_type_{pigcms{$key}" class="equipment_type_box equipment_type_{pigcms{$key}" style="display: none;">
                            <option value="{pigcms{$type_arr['value']}">{pigcms{$type_arr['name']}</option>
                        </select>
                    </if>
                </volist>
                <input type="hidden" name="equipment_type">
                <p style="display: none;" class="equipment_type_31_tips">
                    1. 西函门禁只支持展示2张屏保图片，所以只有生效小区最新添加的2张图片对应生效；<br/>
                    2. 上传的图片建议尺寸为640*945；
                </p>
            </td>
        </tr>
        <tr>
            <th width="80">广告名称</th>
            <td><input type="text" class="input fl" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
        </tr>
        <tr>
            <th width="80">广告类型</th>
            <td>
                <select name="advert_type" id="advert_type">
                    <option value="" style="display: none;">请选择广告类型</option>
                    <volist name="advert_type_arr" id="types">
                        <option value="{pigcms{$key}" id="typesBox_{pigcms{$key}">{pigcms{$types}</option>
                    </volist>
                </select>
                <em class="notice_tips" tips="蓝牙门禁、停车管理、智能充电桩暂不支持视频"></em>
            </td>
        </tr>

        <tr class="radio_185">
            <th width="80">广告展示</th>
            <td>
                <select name="radio_185_sort" class="valid">
                    <option value="android_pic" selectd>上图片下视频</option>
                    <option value="android_video" >上视频下图片</option>
                </select>
                <em class="notice_tips" tips="仅支持A185"></em>
            </td>
        </tr>
        <tr class="img_box" style="display: none;">
            <th width="80">图片展示</th>
            <td><img scr="" id="img" style="width:200px;height:100px;"></td>
        </tr>
        <input type="hidden" name="android_pic" class="input input-image" value="">

        <input type="hidden" name="android_video"  value="">

        <!--图片-->
        <tr class="advert_file_tip_box advert_file_tip_0" style="display: none;">
            <th width="80">
                广告图片
            </th>
            <td>
                <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
                <volist name="advert_file_tip[0]" id="tip0">
                    <span class="advert_tip file_0_{pigcms{$tip0['key']}" style="display: none;">({pigcms{$tip0['tip']})</span>
                </volist>
            </td>
        </tr>
        <!--视频-->
        <tr class="advert_file_tip_box advert_file_tip_1" style="display: none;">
            <th width="80">
                广告视频
            </th>
            <td >
                <div  style="display:inline-block;">
                    <div id="uploader" class="wu-display">
                        <div id="video_click" style="z-index: 9999 !important;">上传视频</div>
                    </div>
                </div>
                <volist name="advert_file_tip[1]" id="tip1">
                    <span class="advert_tip file_1_{pigcms{$tip1['key']}" style="display: none;margin-left: 2px">({pigcms{$tip1['tip']})</span>
                    <input type="hidden" value="{pigcms{$tip1['max_size']}" id="video_max_size_1_{pigcms{$tip1['key']}">
                </volist>
                <!--                    <a href="javascript:void(0)" class="btn btn-sm btn-success"  id="select_video"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传视频</a>-->

                <div class="video_box" style="padding: 10px 0;margin: 0;">
                    <div class="box" style='display:none;'>
                        <video controls="controls">
                            您的浏览器不支持 video 标签。
                        </video>
                    </div>
                    <div id="theList"></div>
                </div>
            </td>
        </tr>

        <tr class="ly_box begin_end_time_box">
            <th width="160">广告有效期</th>
            <td>
                <input type="text" class="input fl" name="begin_time" style="margin-right:5px" size="20" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                <input type="text" class="input fl" name="end_time"  size="20" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" tips="广告有效期起始结束时间">
            </td>
        </tr>
        <tr class="ly_box">
            <th width="80">播放时间</th>
            <td>
                <input type="text" class="input fl" name="play_time" id="play_time" style="width:200px;" placeholder="播放时间，单位/秒" validate="maxlength:200"/>
                <em class="notice_tips" tips="播放时间是到时间就会轮播下一条"></em>
            </td>
        </tr>
        <tr class="equipment_type_box equipment_type_0 equipment_type_3" style="display: none;">
            <th width="80">链接地址</th>
            <td>
                <input type="text" class="input fl" name="url" id="url" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,url:true"/>

                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>

            </td>
        </tr>
        <tr>
            <th width="80">广告状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
                <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
            </td>
        </tr>

    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<style>
    .video_box li {
        list-style: none;
    }
    #select_video {
        /*text-align: -webkit-center;*/
        /*outline: none;*/
        /*background: #87b87f!important;*/
        /*border-color: #87b87f;*/
        /*color: #fff;*/
        /*padding: 0px 12px;*/
        /*height: 30px;*/
        /*line-height: 30px;*/
    }
    .webuploader-pick{
        /*border: 1px solid #ffffff;*/
        padding: 6px 12px;
        /*width: 50px;*/
        /*background-color: #87b87f;*/
        /*color: #ffffff;*/
    }

    .webuploader-pick + div{
        width: 73px !important;
    }
    .webuploader-pick + div{
        width: 73px !important;
    }
    .advert_file_tip_1 a{
        text-decoration: none;
        border-bottom: 1px solid #ffffff;
    }
    .webuploader-pick-hover .btn{
        background-color: #629b58!important;
        border-color: #87b87f;
    }
    .video_box{margin-left:8%;}
    .video_box video{width:300px;}
    #theList{}
    #theList .upload_li span.weui-btn{
        display: inline-block;
        padding: 2px 8px;
        margin: 4px 0;
        margin-right: 8px;
        background: #0099cc;
        color: #fff;
        cursor: pointer;
    }
</style>
<!--<script>-->
<!--    var uploaderHas = false;-->
<!--    var uploaderHas2 = false;-->
<!--    var swf='{pigcms{$static_public}js/Uploader.swf';-->
<!--    //视频上传的地址-->
<!--    var _backEndUrl="{pigcms{:U('House/vupload')}";-->
<!--    var fileSingleSizeLimit = 10 * 1024 * 1024; // 默认限制在10M-->
<!--    //删除上传中的缓存视频-->
<!--    var del_video_cache_url="{pigcms{:U('House/del_video_cache')}";-->
<!--</script>-->
<!--<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>-->
<!--<script type="text/javascript" src="{pigcms{$static_path}js/video_upload.js?11"></script>-->


<script>
    $(function () {
        var  loading='', $list = $('#thelist'),
            $ctlBtn = $('#ctlBtn'),
            $resetBtn = $('#resetBtn'),
            state = 'pending',
            uploader;
        // 初始化WebUploader插件
        uploader = WebUploader.create({

            // swf文件路径， 需要修改为你自己存放的路径
            swf: '{pigcms{$static_path}webuploader/Uploader.swf',
            // 文件接收服务端。  // 需要修改为你的后端地址
            server: '{pigcms{:U(\'House/upload_video\')}',
            // dnd 指定Drag And Drop拖拽的容器，如果不指定，则不启动
            // 禁用全局拖拽，否则在没有启动拖拽容器的情况下，视频拖进来后会直接在浏览器内播放。
            disableGlobalDnd: true,

            // 选择文件的按钮。可选。内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: {
                id: '#video_click',                     // 对应 html 中的 picker
                innerHTML: '上传视频',   // 按钮上显示的文字
                multiple: false,                  // 多文件选择
            },

            // 允许视频和图片类型的文件上传。
            accept: {
                title: 'Video',
                extensions: 'mp4,gif,jpg,jpeg,bmp,png',      // 可以多个后缀，以逗号分隔， 不要有空格
                mimeTypes: 'video/*,image/*'
            },

            // 只允许选择图片文件。
            //accept: {
            // title: 'Images',
            //  extensions: '',
            //  mimeTypes: ''
            //}

            // thumb配置生成缩略图的选项， 此项交由后台完成， 所以前台未配置

            // 自动上传暂时关闭，使用多文件队列上传， 如果值为true，那么在选择完文件后，将直接开始上传文件，因为我还要做一些其他处理，故选择false。
            auto: true,

            //是否允许在文件传输时提前把下一个文件准备好。 对于一个文件的准备工作比较耗时，比如图片压缩，md5序列化。 如果能提前在当前文件传输期处理，可以节省总体耗时。
            prepareNextFile: true,

            // 可选，是否要分片处理大文件上传
            chunked: true,
            // 如果要分片，分多大一片？这里我设置为2M, 如需更大值，可能需要需修改php.ini等配置
            chunkSize:2*1024*1024,
            // 如果某个分片由于网络问题出错，允许自动重传多少次
            chunkRetry: 3,
            // 上传并发数，允许同时上传最大进程数，默认3
            threads:20,

            // formData {Object} [可选] [默认值：{}] 文件上传请求的参数表，每次发送都会发送此对象中的参数。 其实就是post中的表单数据，可自定义字段。
            formData: {
                // md5:str_md5
            },
            //[可选] 验证文件总数量, 超出9个文件则不允许加入队列。
            fileNumLimit: 9,
            // 验证文件总大小是否超出限制（2G）, 超出则不允许加入队列。根据需要进行设置。除了前面几个，其它都是可选项
            fileSizeLimit: 20*1024*1024,
            // 验证单个文件大小是否超出限制（2G）, 超出则不允许加入队列。
            fileSingleSizeLimit: 1024*1024*1024*2,
            // [可选] 去重， 根据文件名字、文件大小和最后修改时间来生成hash Key.
            duplicate: true,
            // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
            // resize: false,
            // 压缩选项
            compress: {
                // 如果压缩后比源文件大，则不压缩，图片有可能压缩后比原文件还大，需设置此项
                noCompressIfLarger: true,
            },
        });

        // 当有文件被添加进队列的时候触发，用于显示加载进度条
        uploader.on( 'fileQueued', function( file ) {
            loading = layer.msg('正在加载', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
            var $li = $( '#'+file.id );
            // 生成文件的MD5值， 可以用来实现秒传， 如不需要，可以忽略（数据库中保存md5值，如果存在相同md5，直接在文件服务器复制一份，不需再次分片上传以及合并，极快）
            uploader.md5File( file )
            // 及时显示进度
                .progress(function(percentage) {
                    $percent = $li.find('.state');
                    $('.layui-layer-content').html('<i class="layui-layer-ico layui-layer-ico16"></i>'+'加载中 '+  Math.round(percentage * 100)  + '%');

                    // $li.find('p.state').text('加载中 '+  Math.round(percentage * 100)  + '%');
                    // console.log('Percentage:', percentage);
                })
                // 完成
                .then(function(md5) {});
        });

        // 文件上传过程中创建进度条实时显示。
        // 显示进度条
        uploader.on( 'uploadProgress', function( file, percentage ) {
            var $li = $( '#'+file.id ),
                $percent = $li.find('.progress .progress-bar');

            // 避免重复创建
            if ( !$percent.length ) {
                $percent = $('<div class="progress progress-striped active">' +
                    '<div class="progress-bar" role="progressbar" style="width: 0%">' +
                    '</div>' +
                    '</div>').appendTo( $li ).find('.progress-bar');
            }

            $('.layui-layer-content').html('<i class="layui-layer-ico layui-layer-ico16"></i>'+'上传中'+ Math.round(percentage * 100)  + '%');

            // $li.find('p.state').text('上传中'+ Math.round(percentage * 100)  + '%' );
            if(Math.round(percentage * 100) == 100)
            {
                $('.layui-layer-content').html('<i class="layui-layer-ico layui-layer-ico16"></i>即将完成');
                // $li.find('p.state').text('即将完成');
            }

            $percent.css( 'width', percentage * 100 + '%' );
        });

        uploader.on('uploadAccept', function (file, response) {
            console.log(response)
            var url=$('input[name=android_video]').val();
            if(response.status == true){
                if(url != response.data){
                    $('input[name=android_video]').val(response.data);
                    $('.video_box .box').show().find(' video').attr('src',response.data);
                }
            }
            return true;
        });
        uploader.on('beforeFileQueued', function (file) {
            var timestamp=new Date().getTime(), str_md5=$.md5(timestamp.toString());
            uploader.option("formData",{
                ... {"md5": str_md5}
            });
            if(file.size > (20*1024*1024)){
                layer.msg('视频大小不能超过20M', {icon: 2});
            }
        });

        // 监听上传成功
        uploader.on( 'uploadSuccess', function( file ,response) {
            // $( '#'+file.id ).find('p.state').text('已上传');
            layer.close(loading);
            if(response.status == true){
                $('input[name=android_video]').val(response.data);
                layer.msg('上传成功', {icon: 6});
                return false;
            }else{
                if(response.data){
                    layer.msg(response.data, {icon: 2});
                }
            }
            // console.log(response)
        });
        // 监听上传失败
        uploader.on( 'uploadError', function( file ) {
            // alert("上传出错")
            $('.layui-layer-content').html('<i class="layui-layer-ico layui-layer-ico16"></i>上传出错');
            // $( '#'+file.id ).find('p.state').text('上传出错');
            layer.close(loading);
        });
        // 监听上传完成，不论失败还是成功
        uploader.on( 'uploadComplete', function( file ) {
            $( '#'+file.id ).find('.progress').fadeOut();
            layer.close(loading);
            // console.log(uploader.getStats());
        });
    })

</script>

<script type="text/javascript">
    KindEditor.ready(function(K){
        var site_url = "{pigcms{$config.site_url}";
        var editor = K.editor({
            allowFileManager : true
        });
        $('.J_selectImage').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        $('input[name=android_pic]').val(url);
                        $('.img_box').show();
                        $('#img').attr('src',site_url+url);
                        console.log('图片')
                        editor.hideDialog();
                    }
                });
            });
        });
        $('.J_selectFile').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('House/ajax_upload_file')}";
            editor.loadPlugin('insertfile', function(){
                editor.plugin.fileDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        upload_file_btn.siblings('.input-image').val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

    });

    $(function(){

        $("select[name='intelligent_hardware_type']").change(function(){
            var val = $(this).val();
            if (0==val || 3==val) {
                $('.ly_box').hide();
            } else {
                $('.ly_box').show();
            }
            $('.equipment_type_arr_box').show();
            $('.equipment_type_box').hide();
            $('.equipment_type_'+val).show();
            // 处理对应广告类型
            var equipment_type = $("#equipment_type_"+val).val();
            if (equipment_type) {
                if(equipment_type == '61'){
                    $('.advert_file_tip_box').show();
                    $('.advert_file_tip_box .advert_tip').show();
                    $('.advert_file_tip_0,.advert_file_tip_1').show();
                }else{
                    $('input[name="equipment_type"]').val(equipment_type);
                    $('.advert_type_box.advert_type_'+equipment_type).show();
                    var advert_type = parseInt($("select[name='advert_type']").val());
                    if (!isNaN(advert_type)) {
                        change_equipment_type(advert_type,equipment_type)
                    }
                }
            }
        });

        $("select[name='equipment_type_change']").change(function(){
            var val = $(this).val();
            $('.advert_type_box').hide();
            if (val) {
                if (val == 31) {
                    $('#typesBox_1').hide();
                    $('#typesBox_2').hide();
                    $('.begin_end_time_box').hide();
                    $('.equipment_type_31_tips').show();
                } else {
                    $('#typesBox_1').show();
                    $('#typesBox_2').show();
                    $('.begin_end_time_box').show();
                    $('.equipment_type_31_tips').hide();
                }
                if(val == 61){
                    fileSingleSizeLimit = 20 * 1024 * 1024; // 限制在20M
                }else{
                    fileSingleSizeLimit = 10 * 1024 * 1024; // 限制在10M
                }
                $('input[name="equipment_type"]').val(val);
                $('.advert_type_box.advert_type_'+val).show();
                var advert_type = parseInt($("select[name='advert_type']").val());
                if (!isNaN(advert_type)) {
                    change_equipment_type(advert_type,val)
                }
            }
        });

        $("select[name='advert_type']").change(function(){
            var val = $(this).val();
            var intelligent_hardware_type = $("select[name='intelligent_hardware_type']").val();
            var equipment_type = $("#equipment_type_"+intelligent_hardware_type).val();
            if($('#equipment_type_1').val() != '61' && val == 2){
                alert('仅A185支持图片+视频！');
                $(this).val(0);
                return false;
            }
            if (intelligent_hardware_type==0 && val==1) {
                alert('蓝牙门禁暂不支持视频！');
                $(this).val(0);

                change_equipment_type(0,equipment_type);
            } else if (intelligent_hardware_type==3 && val==1) {
                alert('停车管理暂不支持视频！');
                $(this).val(0);
                change_equipment_type(0,equipment_type);
            } else if (intelligent_hardware_type==4 && val==1) {
                alert('智能充电桩暂不支持视频！');
                $(this).val(0);
                var equipment_type = $("#equipment_type_"+intelligent_hardware_type).val();
                change_equipment_type(0,equipment_type);
            }else {
                var equipment_type = $("#equipment_type_"+intelligent_hardware_type).val();
                change_equipment_type(parseInt(val),equipment_type);
                console.log('rr'+equipment_type)
            }
        });


    })

    function change_equipment_type(advert_type,equipment_type) {

        if(equipment_type == '61' && advert_type == 2){
            $('.radio_185').show();
            $('.advert_file_tip_box').show();
            $('.file_0_'+equipment_type).show();
            advert_type--;
        }else{
            $('.radio_185').hide();
            $('.advert_file_tip_box').hide();
            $('.advert_file_tip_box .advert_tip').hide();
            $('.advert_file_tip_'+advert_type).show();
        }
        if (equipment_type) {
            console.log('.file_'+advert_type+'_'+equipment_type)
            $('.file_'+advert_type+'_'+equipment_type).show();
            var video_max_size = $('#video_max_size_' + advert_type + '_' + equipment_type).val();
            if (parseInt(video_max_size)>0) {
                fileSingleSizeLimit = video_max_size;
            }
        }

    }


</script>
<script>
    function addLink(domid, iskeyword, type){
        art.dialog.data('domid', domid);
        if (type == 1) {
            art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
        } else {
            art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
        }
    }
    $("#yes").click(function(){
        $("#adver_region").hide();
    })
    $("#no").click(function(){
        $("#adver_region").show();
    })
</script>
<include file="Public:footer"/>