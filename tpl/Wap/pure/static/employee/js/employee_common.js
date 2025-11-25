/**
 * Created by 唐金涛 on 2018-09-15.
 */
(function() {
    var APP = {};
    APP.addMethod = function(methods) {
        for (var attr in methods) {
            if (methods.hasOwnProperty(attr)) {
                APP[attr] = methods[attr];
            }
        }
    };
    $.fn.extend({
        selfHtml: function(toArray) {
            if (toArray) {
                var arr = [];

                $(this).each(function() {
                    arr.push($('<div/>').html($(this).eq(0).clone()).html());
                });

                return arr;
            } else {
                return $('<div/>').html($(this).eq(0).clone()).html();
            }
        }
    });

    (function(){
        var model='<div id="layermbox3" class="layermbox layermbox2 layermshow" index="3"><div class="laymshade"></div><div class="layermmain"><div class="section"><div class="layermchild layermanim"><div class="layermcont" style="padding:0px"><i></i><i class="laymloadtwo"></i><i></i></div></div></div></div></div>'
         $(body).append(model)
    }());
    (function(){
        var $flie = function(options){
            this.options = $.extend({}, $flie.defaults, options);
            this.$fileInput = $(this.options.element);
            this.$avatarWrapper=$(this.options.avatarWrapper);
            this.$comfirm_btn=$(this.options.com_btn);
            this.$btn_back=$(this.options.btn_back);
            this.init();
            this.submit()
        }
        var $width=0;
        $flie.defaults = {
            element: '',
            aspectRatio: 200/200,
            strict: true,
            zoomOnTouch:false,
            modal:true,
            guides:true,
            background:true,
            autoCrop:true,
            scalable:false,
            zoomable:false,
            wheelZoomRatio:false,
            cropBoxMovable:true,
            cropBoxResizable:true,
            minCropBoxWidth:200,
            minCropBoxHeight:200,
            maxCropBoxWidth:200,
            maxCropBoxHeight:200,
            View:window,
            avatarWrapper:'#clipArea',
            com_btn:'',
            btn_back:'',
            btn_success_back:null,
            back:null,
            element1:'',
            crop:function(data){
                $width=data.width;
                console.log(data)
            }
        };
        $flie.prototype.showJump=function(blobURL){
            var self=this;
            self.options.View.setting.sing_up='#sign_up=1';
            var wh=$(window).width();
            $("#page__bd").animate({'left':-wh+'px'},200);
            self.options.View.setting.chackchange=false
            location.hash=self.options.View.setting.sing_up
            $('#file_pic').animate({'left':'0px'},200,function() {
                $("#page__bd").hide()
                $('#file_pic').show();
                self.options.View.setting.hashchange = 5
                self.options.View.setting.chackchange = true
                self.showImg(blobURL)
            })
        };
        $flie.prototype.init =function(){
            var self = this,
                URL = window.URL || window.webkitURL,
                blobURL;

            if (URL) {
                self.$fileInput.on('change.$flie',function () {
                    $.showLoading('加载中')
                    var files = this.files,
                        file;
                    if (files && files.length) {
                        file = files[0];
                        if (/^image\/\w+$/.test(file.type)) {
                            blobURL = URL.createObjectURL(file);
                            var fileObj = document.getElementById(self.options.element1).files[0];
                            var form = new FormData(); // FormData 对象
                            if(fileObj.size/1024 > 1025) {
                                //大于1M，进行压缩上传
                                self.photoCompress(fileObj, {
                                    quality: 0.7
                                }, function(base64Codes){
                                    blobURL=base64Codes
                                   self.showJump(blobURL)
                                });
                            }else{
                                self.showJump(blobURL)
                            }

                        }
                    }
                })
            }
        };
        $flie.prototype.photoCompress=function(file,w,objDiv){
            var self=this;
            var ready=new FileReader();
            /*开始读取指定的Blob对象或File对象中的内容. 当读取操作完成时,readyState属性的值会成为DONE,如果设置了onloadend事件处理程序,则调用之.同时,result属性中将包含一个data: URL格式的字符串以表示所读取文件的内容.*/
            ready.readAsDataURL(file);
            ready.onload=function(){
                var re=this.result;
                self.canvasDataURL(re,w,objDiv)
            }
        };
        $flie.prototype.canvasDataURL=function(path, obj, callback){
            var img = new Image();
            img.src = path;
            img.onload = function(){
                var that = this;
                // 默认按比例压缩
                var w = that.width,
                    h = that.height,
                    imageWidth= 0,
                    imageHeight= 0,
                    square=700,
                    offsetX = 0,
                    offsetY = 0,
                    scale = w / h;
                w = obj.width || w;
                h = obj.height || (w / scale);

                var quality = 0.7;  // 默认图片质量为0.7
                //生成canvas
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                // 创建属性节点
                var anw = document.createAttribute("width");
                anw.nodeValue = square;
                var anh = document.createAttribute("height");
                anh.nodeValue = square;
                canvas.setAttributeNode(anw);
                canvas.setAttributeNode(anh);
                if (that.width > that.height) {
                    imageWidth = Math.round(square * this.width / this.height);
                    imageHeight = square;
                    offsetX = - Math.round((imageWidth - square) / 2);
                } else {
                    imageHeight = Math.round(square * this.height / this.width);
                    imageWidth = square;
                    offsetY = - Math.round((imageHeight - square) / 2);
                }
                ctx.drawImage(that, offsetX, offsetY, imageWidth, imageHeight);
                //ctx.drawImage(that, 0, 0, w, h);
                // 图像质量
                if(obj.quality && obj.quality <= 1 && obj.quality > 0){
                    quality = obj.quality;
                }
                // quality值越小，所绘制出的图像越模糊
                var base64 = canvas.toDataURL('image/jpeg', quality);
                // 回调函数返回base64的值
                callback(base64);
            }
        };
        $flie.prototype.showImg=function(blobURL){
            var self=this;
            this.$img = $('<img src="' + blobURL + '">');
            this.$avatarWrapper.empty().html(this.$img);
            this.$img.cropper(this.options);
            $.hideLoading()
        };
        $flie.prototype.submit=function(){
            var self=this;
            this.$comfirm_btn.on("click.$flie",function(){
                $.showLoading('图片生成中')
                var datas={
                    'width':$width,
                    'height':$width/self.options.aspectRatio
                }
                var cas = self.$img.cropper('getCroppedCanvas');
                var base64url=cas.toDataURL('image/jpeg');
                self.options.btn_success_back && self.options.btn_success_back.call(self, base64url);
                self.$fileInput.val('')
                setTimeout(function(){
                    self.$img.cropper('destroy').cropper(self.options);
                    self.$img.remove();
                },1000)

            })

            this.$btn_back.on("click.$flie",function(){
                self.options.back && self.options.back.call(this);
                self.$img.cropper('destroy').cropper(self.options);
                self.$img.remove();

            })
        };
        APP.$flie = function(options) {
            return new $flie(options);
        };
    }());
    (function() {
        var Uploader = function(options) {
            this.options = $.extend({}, Uploader.defaults, options);
            this.$fileInput = $(this.options.element);
            this.fileNode = this.$fileInput.selfHtml();
            this.$parent = this.$fileInput.parent();
            this.selectedFiles = [];
            this.init();
        };

        Uploader.defaults = {
            element: '',
            onValidate: null,
            onSelectValid: null,
            onDetail:null,
            removeContainer: '',
            removeBtn: '.item',
            removeItem: '.weui-uploader__file',
            removeConfirm: true
        };
        Uploader.prototype.init = function() {
            var self = this;
            var siblingsLength = this.$fileInput.siblings().length;
            if (siblingsLength === 0) {
                this.restoreMethod = 'html';
            } else {
                this.restoreMethod = 'append';
            }
            this.setDefaultEvents();
            this.initBindChange();
        };
        Uploader.prototype.setDefaultEvents = function() {
            var self = this;
            if (self.options.removeContainer) {
                $(self.options.removeContainer).on('click.Uploader', self.options.removeBtn, function(event) {
                    var $this = $(this);
                    event.stopPropagation()
                    if (self.options.removeConfirm) {
                        $.confirm("确定要删除吗？", function() {
                            self.removeFile(getIndex());
                        });
                    } else {
                        self.removeFile(getIndex());
                    }
                    function getIndex() {
                        var index = $this.closest(self.options.removeItem).index();
                        $this.closest(self.options.removeItem).remove()
                        return index;
                    }
                });
            }
        };
        Uploader.prototype.initBindChange = function() {
            var self = this;
            self.$fileInput.on('change.Uploader', function(event, dataUrl) {
                var $this = $(this);
                $this.removeAttr('id').hide();
                var getObjectURL = function (file) {
                    if (window.createObjectURL != undefined) {
                        dataUrl = window.createObjectURL(file)
                    } else if (window.URL != undefined) {
                        dataUrl = window.URL.createObjectURL(file)
                    } else if (window.webkitURL != undefined) {
                        dataUrl = window.webkitURL.createObjectURL(file)
                    }
                };
                getObjectURL($(this)[0].files[0])
                var item = {
                    file: $this,
                    value: $this.val(),
                    src: dataUrl,
                    index: self.selectedFiles.length
                };

                self.selectedFiles.push(item);
                self.options.onSelectValid && self.options.onSelectValid.call(self, item, self.selectedFiles);

                self.$fileInput = $(self.fileNode);

                self.$parent[self.restoreMethod](self.$fileInput);

                self.initBindChange();
                self.initBindValid();
            });
        };

        Uploader.prototype.initBindValid = function(){
            var self = this;

            var validator = self.options.onValidate();

            self.$fileInput.off('click.Uploader');

            if(validator.invalid){
                self.$fileInput.on('click.Uploader', function(event) {
                    $.toptip(validator.message, 1000, 'error');
                    event.preventDefault();
                });
            }
        };

        Uploader.prototype.removeFile = function(index, length) {
            var self = this;
            self.selectedFiles.splice(index, length || 1);
            self.options.onDetail && self.options.onDetail.call(self, self.selectedFiles);
            self.initBindValid();
        };

        APP.Uploader = function(options) {
            return new Uploader(options);
        };
    }());
    APP.addMethod({
        render: function(options) {
            var tpl = Simplite.getTemplate(options.templateId);

            if (!options.data) {
                return tpl;
            }

            var compiledTpl = Simplite.compile(tpl)(options.data);

            if (!options.element) {
                return compiledTpl;
            }

            if (options.replaceAll) {
                return $(options.element).html(compiledTpl);
            } else {
                return $(options.element).append(compiledTpl);
            }
        },
        http:function(url,data,success,error,type){
            if(is_sign==''){
                $.alert({
                    title: '提示',
                    text: '请先登录',
                    onOK: function () {
                        window.location.href='/wap.php?g=Wap&c=Login&a=index'
                    }
                });
            }else{
                $.ajax({
                    type : type,
                    url : url,
                    dataType : "json",
                    data:data,
                    async:false,
                    success : function(data){
                        success(data)

                    },
                    error:function(res){
                        error(res)
                    }
                })
            }
        }
    });

    window.APP = APP;
    return APP;
})();