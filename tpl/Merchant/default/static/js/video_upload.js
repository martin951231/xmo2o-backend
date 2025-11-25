_extensions ='mp4';
_mimeTypes ='video/*,audio/*,application/*';

if(typeof video_name == 'undefined'){
    var video_name = 'video_url'
}

// jQuery cookie
jQuery.cookie = function (key, value, options) {
    if (arguments.length > 1 && (value === null || typeof value !== "object")){
        options = jQuery.extend({}, options);
        if (value === null) {
            options.expires = -1;
        }
        if (typeof options.expires === 'number'){
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }
        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? String(value) : encodeURIComponent(String(value)),
            options.expires ? '; expires=' + options.expires.toUTCString() : '',
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};

$(function(){
    var uploaderHas3=false;          
    var chunkSize = 400 * 1024;        //分块大小
    var uniqueFileName = 'cache_'+new Date().getTime();          //文件唯一标识符
    var md5Mark = null;
    var is_video=0;
    WebUploader.Uploader.register({
        "before-send-file": "beforeSendFile",
        "before-send": "beforeSend",
        "after-send-file": "afterSendFile"
    },{
        beforeSendFile: function(file){
            if(file.source._refer['0']['id']=='select_video'){
                is_video=1;
            }else{
                is_video=0;
                return false;
            }
            //秒传验证
            var task = new $.Deferred();
            var start = new Date().getTime();
            (new WebUploader.Uploader()).md5File(file, 0, 10*1024*1024).progress(function(percentage){
            }).then(function(val){ 
                md5Mark = val;
                $.ajax({
                    type: "POST",
                    url: _backEndUrl,
                    data: {
                        status: "md5Check",
                        md5: val
                    },
                    cache: false,
                    timeout: 1000, //todo 超时的话，只能认为该文件不曾上传过
                    dataType: "json"
                }).then(function(data, textStatus, jqXHR){
                    if(data.ifExist){   //若存在，这返回失败给WebUploader，表明该文件不需要上传
                        task.reject(); 
                        vuploader.skipFile(file);
                        file.path = data.path;
                        UploadComlate(file);
                    }else{
                        task.resolve();
                    }
                }, function(jqXHR, textStatus, errorThrown){    //任何形式的验证失败，都触发重新上传
                    task.resolve();
                });
            });
            return $.when(task);
        },
        beforeSend: function(block){
            if(!is_video){return false;}
            //分片验证是否已传过，用于断点续传
            var task = new $.Deferred();
            $.ajax({
                type: "POST",
                url: _backEndUrl,
                data: {
                    status: "chunkCheck",
                    name: uniqueFileName,
                    chunkIndex: block.chunk,
                    size: block.end - block.start
                },
                cache: false,
                timeout: 1000, //todo 超时的话，只能认为该分片未上传过
                dataType: "json"
            }).then(function(data, textStatus, jqXHR){
                if(data.ifExist){   //若存在，返回失败给WebUploader，表明该分块不需要上传
                    task.reject();
                }else{
                    task.resolve();
                }
            }, function(jqXHR, textStatus, errorThrown){    //任何形式的验证失败，都触发重新上传
                task.resolve();
            }); 
            return $.when(task);
        },
        afterSendFile: function(file){
            if(!is_video){return false;}            
            var chunksTotal = 0;
            if((chunksTotal = Math.ceil(file.size/chunkSize)) > 1){
                //合并请求
                var task = new $.Deferred();
                $.ajax({
                    type: "POST",
                    url: _backEndUrl,
                    data: {
                        status: "chunksMerge",
                        name: uniqueFileName,
                        chunks: chunksTotal,
                        ext: file.ext,
                        md5: md5Mark
                    },
                    cache: false,
                    dataType: "json"
                }).then(function(data, textStatus, jqXHR){ 
                    //todo 检查响应是否正常
                    task.resolve();
                    file.path = data.path;
                    UploadComlate(file); 
                },function(jqXHR, textStatus, errorThrown){
                    task.reject();
                }); 
                return $.when(task);
            }else{
                UploadComlate(file);
            }
        }
    });

    start_video_upload();
    uploaderHas3=true;
    //文件上传初始化
    function start_video_upload(){
        var vuploader = WebUploader.create({
            swf: swf,
            server: _backEndUrl,     //服务器处理文件的路径
            pick: "#select_video",        //指定选择文件的按钮，此处放的是id
            resize: false, 
            compress: false,
            prepareNextFile: true, 
            chunked: true, 
            chunkSize: chunkSize,
            chunkRetry: 2,    //[可选] [默认值：2]如果某个分片由于网络问题出错，允许自动重传多少次？
            threads: true,      //[可选] [默认值：3] 上传并发数。允许同时最大上传进程数。
            formData:{
                'uniqueFileName':uniqueFileName
            },
            fileNumLimit: 1, 
            fileSingleSizeLimit: 50 * 1024 * 1024,// 限制在50M
            duplicate: true,
            accept: {      
                title: L_('大文件上传'),  //文字描述
                extensions: _extensions,     //允许的文件后缀，不带点，多个用逗号分割。,jpg,png,
                mimeTypes: _mimeTypes,      //多个用逗号分割。image/*,
            },
        });
		
		setTimeout(function(){
			console.log($('#select_video').css('width'), $('#select_video').css('height'));
			$('#select_video div').last().css({width: $('#select_video').css('width'), height: $('#select_video').css('height')});
		},1000);
		
        /**
         * 验证文件格式以及文件大小
         */
        vuploader.on("error",function (type,handler){
            if (type=="Q_TYPE_DENIED"){
                $('.video_box').append('<p class="error_msg" style="color:red;">请上传MP4格式的视频</p>');
            }else if(type=="F_EXCEED_SIZE"){
                $('.video_box').append('<p class="error_msg" style="color:red;">视频大小不能超过50M哦</p>');
            }
        });
        //上传中
        vuploader.on("fileQueued", function(file){
            $('#theList').show();
            $("#theList").append('<li id="'+file.id+'" class="upload_li">' +
                ' <img /> <span class="file_name upload_li">'+file.name+'</span></li><li class="upload_li"><span class="itemUpload weui-btn weui-btn_mini weui-btn_primary">'+L_('上传')+'</span><span class="itemStop weui-btn weui-btn_mini weui-btn_default">'+L_('暂停')+'</span><span class="itemDel weui-btn weui-btn_mini weui-btn_warn">'+L_('删除')+'</span></li><li class="upload_li">' +
                '<div id="percentage'+file.id+'" class="percentage"><div class="weui-progress__bar"><div class="weui-progress__inner-bar js_progress" style="width: 0%;"></div> <b id="pers"></b> </div></div>' +
            '</li>'); 
            var $img = $("#" + file.id).find("img");
            
            vuploader.makeThumb(file, function(error, src){
                if(error){
                    $img.replaceWith("<span class='no_view'>'+L_('视频暂不能预览')+'</span>");
                }     
                $img.attr("src", src);
            }); 
            $("#theList").find('.itemUpload').click();           
        });
        //上传完成
        vuploader.on("uploadProgress", function(file, percentage){
            $(".percentage").find('.js_progress').css("width",percentage * 100 + "%");
            $(".percentage").find('#pers').text(parseInt(percentage * 100) + "%");
        });

        $("#theList").on("click", ".itemUpload", function(){
            vuploader.upload();
            //L_("上传")-->L_("暂停")
            $(this).hide();
            $(".itemStop").css('display','inline-block');
            $(".itemStop").show();
        });
     
        $("#theList").on("click", ".itemStop", function(){
            vuploader.stop(true); 
            //L_("暂停")-->L_("上传")
            $(this).hide();
            $(".itemUpload").show();
        });
     
        //todo 如果要删除的文件正在上传（包括暂停），则需要发送给后端一个请求用来清除服务器端的缓存文件
        $("#theList").on("click", ".itemDel", function(){
            $.post(del_video_cache_url,{uniqueFileName:uniqueFileName},function(res){
                if(res.status==1){
                    vuploader.removeFile($('.upload_li').attr("id"));   //从上传文件列表中删除     
                    $('.upload_li').remove();   //从上传列表dom中删除
                }
            });            
        });
    }
	//判断是否已上传视频
    $('#select_video').click(function(){
        if($('input[name='+video_name+']').val()!=''){
            alert(L_('请先删除之前的视频，在上传新的视频文件！'));
            return false;
        }
        $('.video_box').find('.error_msg').remove();
    });
    //删除视频
    $('.del_video').click(function(){
        var src=$(this).attr('href');
        var obj=$(this).parent();
        var video_url=$('input[name='+video_name+']').val();
        if(!video_url){
            obj.hide();
            obj.find('video').attr('href','');
            $('input[name='+video_name+']').val('');
            $('#theList').empty('');
            start_video_upload();
            return false;
        }
        $.post(src,{video_url:video_url},function(res){
            if(res.status==1){
                obj.hide();
                obj.find('video').attr('href','');
                $('input[name='+video_name+']').val('');
                $('#theList').empty('');
                start_video_upload();
            }else{
                layer.alert(res.info,{icon:2});
            }
        });
        return false;
    });

    function UploadComlate(file){
        if(file && file.name){
            $('#vedio').val(file.name);
            $('input[name='+video_name+']').val(file.path);
            $('.video_box .box').show().find(' video').attr('src',file.path);
            $('#theList .no_view').hide();
            $(".percentage").find('#pers').html("<span style='color:green;'>'"+L_('上传完毕')+"'</span>");
            $(".itemStop").hide();
            $(".itemUpload").hide();
            $(".itemDel").hide();
        }else{
            $(".percentage").find('#pers').html("<span style='color:red;'>上传失败，请您检查网络状况~</span>");
            $(".itemStop").hide();
            $(".itemUpload").hide();
        } 
    } 
});