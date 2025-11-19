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
        var model='<div id="layermbox3" class="layermbox layermbox2 layermshow" index="3" style="display:none"><div class="laymshade"></div><div class="layermmain"><div class="section"><div class="layermchild layermanim"><div class="layermcont" style="padding: 20px 15px;"><i></i><i class="laymloadtwo"></i><i></i></div></div></div></div></div>'
        $('body').append(model)
    }());
    (function(){
        var Photos = function(config) {
            this.initConfig(config);
            this.index = 0;
        }
        Photos.defaults={
            items: [],
            autoOpen: false, //初始化完成之后立刻打开
            onOpen: undefined,
            onClose: undefined,
            initIndex: 0, //打开时默认显示第几张
            maxScale: 3,
            onSlideChange: undefined,
            duration: 100, // 默认动画时间，如果没有在调用函数的时候指定，则使用这个值
            tpl: '<div class="weui-photo-browser-modal">\
            <div class="swiper-container">\
              <div class="swiper-wrapper">\
                {{#items}}\
                <div class="swiper-slide">\
                  <div class="photo-container">\
                    <img src="{{image}}" />\
                  </div>\
                </div>\
                {{/items}}\
              </div>\
              <div class="caption">\
                {{#items}}\
                <div class="caption-item caption-item-{{@index}}">{{caption}}</div>\
                {{/items}}\
              </div>\
              <div class="swiper-pagination swiper-pagination-bullets">\
                {{#items}}\
                <span class="swiper-pagination-bullet"></span>\
                {{/items}}\
              </div>\
            </div>\
          </div>'
        }
        Photos.prototype.initConfig=function(config){
            this.config = $.extend({}, Photos.defaults, config);
            this.activeIndex = this.lastActiveIndex = this.config.initIndex;

            this.config.items = this.config.items.map(function(d, i) {
                if(typeof d === typeof 'a') {
                    return {
                        image: d,
                        caption: ''
                    }
                }
                return d;
            });

            this.tpl = $.Template7.compile(this.config.tpl);
            if(this.config.autoOpen) this.open();
        }
        Photos.prototype.open=function (index) {
            if (this._open) return false;
            if (!this.modal) {
                this.modal = $(this.tpl(this.config)).appendTo(document.body);
                this.container = this.modal.find('.swiper-container');
                this.wrapper = this.modal.find('.swiper-wrapper');

                var hammer = new Hammer(this.container[0]);
                hammer.get('pinch').set({ enable: true });
                hammer.on('pinchstart', $.proxy(this.onGestureStart, this));
                hammer.on('pinchmove', $.proxy(this.onGestureChange, this));
                hammer.on('pinchend', $.proxy(this.onGestureEnd, this));
                this.modal.on($.touchEvents.start, $.proxy(this.onTouchStart, this));
                this.modal.on($.touchEvents.move, $.proxy(this.onTouchMove, this));
                this.modal.on($.touchEvents.end, $.proxy(this.onTouchEnd, this));

                //init index
                this.wrapper.transition(0);
                this.wrapper.transform('translate3d(-' + $(window).width()*this.config.initIndex + 'px,0,0)');
                this.container.find('.caption-item').eq(this.config.initIndex).addClass('active');
                this.container.find('.swiper-pagination-bullet').eq(this.config.initIndex).addClass('swiper-pagination-bullet-active');
            }

            var self = this;
            this.modal.show().height();
            if(index==0 || isNaN(parseInt(index))){
                index=0;
                this.modal.addClass('weui-photo-browser-modal-visible');
                this.container.addClass('swiper-container-visible').transitionEnd(function() {
                    self.initParams();
                    if(index !== undefined) {
                        self.slideTo(index);
                    }
                    if(self.config.onOpen) {
                        self.config.onOpen.call(self);
                    }
                });
            }else{
                self.container.find(".swiper-slide img").css("display","none")
                self.container.find(".swiper-slide").eq(index).find("img").css("display","block")
                self.initParams();
                if(index !== undefined) {
                    self.slideTo(index,100,true,function(){
                        self.modal.addClass('weui-photo-browser-modal-visible');
                        self.container.addClass('swiper-container-visible');
                        setTimeout(function(){
                            self.container.find(".swiper-slide img").css("display","block")
                        },500)
                    });
                }
                if(self.config.onOpen) {
                    self.config.onOpen.call(self);
                }

            }





            this._open = true;
        }

        Photos.prototype.close= function() {
            this.container.transitionEnd($.proxy(function() {
                this.modal.hide();
                this._open = false;
                if(this.config.onClose) this.config.onClose.call(this);
            }, this));
            this.container.removeClass('swiper-container-visible');
            this.modal.removeClass('weui-photo-browser-modal-visible');
        }

        Photos.prototype.initParams=  function () {
            if(this.containerHeight) return false;

            this.windowWidth = $(window).width();
            this.containerHeight = this.container.height();
            this.containerWidth = this.container.width();

            this.touchStart = {};

            this.wrapperTransform = 0;
            this.wrapperLastTransform = - $(window).width()*this.config.initIndex;
            this.wrapperDiff = 0;

            this.lastScale = 1;
            this.currentScale = 1;

            this.imageLastTransform = { x: 0, y: 0 };
            this.imageTransform = { x: 0, y: 0 };
            this.imageDiff = { x: 0, y: 0 };
            this.imageLastDiff = { x: 0, y: 0 };
        }

        Photos.prototype.onTouchStart=  function (e) {
            if(this.scaling) return false;
            this.touching = true;
            this.touchStart = $.getTouchPosition(e);
            this.touchMove = null;
            this.touchStartTime = + new Date;
            this.wrapperDiff = 0;
            this.breakpointPosition = null;
        }

        Photos.prototype.onTouchMove= function (e) {
            if(!this.touching || this.scaling) return false;

            e.preventDefault();

            if(this.gestureImage) {
                var rect = this.gestureImage[0].getBoundingClientRect();
                if (rect.left >= 0 || rect.right <= this.windowWidth) {
                    this.overflow = true;
                } else {
                    this.overflow = false;
                }
            } else {
                this.oveflow = false;
            }
            var p = this.touchMove = $.getTouchPosition(e);
            if(this.currentScale === 1 || this.overflow) {
                if(this.breakpointPosition) {
                    this.wrapperDiff = p.x - this.breakpointPosition.x;
                } else {
                    this.wrapperDiff = p.x - this.touchStart.x;
                }
                if(this.activeIndex === 0 && this.wrapperDiff > 0) this.wrapperDiff = Math.pow(this.wrapperDiff, .8);
                if(this.activeIndex === this.config.items.length - 1 && this.wrapperDiff < 0) this.wrapperDiff = - Math.pow(-this.wrapperDiff, .8);
                this.wrapperTransform = this.wrapperLastTransform + this.wrapperDiff;
                this.doWrapperTransform();
            } else {
                var img = this.gestureImage;
                this.imageDiff = {
                    x: p.x - this.touchStart.x,
                    y: p.y - this.touchStart.y
                }

                this.imageTransform = {
                    x: this.imageDiff.x + this.imageLastTransform.x,
                    y: this.imageDiff.y + this.imageLastTransform.y
                };
                this.doImageTransform();

                this.breakpointPosition = p;

                this.imageLastDiff = this.imageDiff;
            }
        }

        Photos.prototype.onTouchEnd= function (e) {
            if(!this.touching) return false;
            this.touching = false;
            if(this.scaling) return false;
            var duration = (+ new Date) - this.touchStartTime;

            if(duration < 200 && (!this.touchMove || Math.abs(this.touchStart.x - this.touchMove.x) <= 2 && Math.abs(this.touchStart.y - this.touchMove.y) <= 2)) {
                this.onClick();
                return;
            }
            if(this.wrapperDiff > 0) {
                if(this.wrapperDiff > this.containerWidth/2 || (this.wrapperDiff > 20 && duration < 300)) {
                    this.slidePrev();
                } else {
                    this.slideTo(this.activeIndex, 200);
                }
            } else {
                if(- this.wrapperDiff > this.containerWidth/2 || (-this.wrapperDiff > 20 && duration < 300)) {
                    this.slideNext();
                } else {
                    this.slideTo(this.activeIndex, 200);
                }
            }

            this.imageLastTransform = this.imageTransform;

            this.adjust();
        }

        Photos.prototype.onClick= function () {
            var self = this;
            if (this._lastClickTime && ( + new Date - this._lastClickTime < 300)) {
                this.onDoubleClick();
                clearTimeout(this._clickTimeout);
            } else {
                this._clickTimeout = setTimeout(function () {
                    self.close();
                }, 300);
            }
            this._lastClickTime = + new Date;
        },

            Photos.prototype.onDoubleClick= function () {
                this.gestureImage = this.container.find('.swiper-slide').eq(this.activeIndex).find('img');
                this.currentScale = this.currentScale > 1 ? 1 : 2;
                this.doImageTransform(200);
                this.adjust();
            },

            Photos.prototype.onGestureStart= function (e) {
                this.scaling = true;
                this.gestureImage = this.container.find('.swiper-slide').eq(this.activeIndex).find('img');
            },

            Photos.prototype.onGestureChange= function (e) {
                var s = this.lastScale * e.scale;
                if (s > this.config.maxScale) {
                    s = this.config.maxScale + Math.pow((s - this.config.maxScale), 0.5);
                } else if (s < 1) {
                    s = Math.pow(s, .5);
                }
                this.currentScale = s;
                this.doImageTransform();
            },

            Photos.prototype.onGestureEnd=function (e) {
                if (this.currentScale > this.config.maxScale) {
                    this.currentScale = this.config.maxScale;
                    this.doImageTransform(200);
                } else if (this.currentScale < 1) {
                    this.currentScale = 1;
                    this.doImageTransform(200);
                }
                this.lastScale = this.currentScale;
                this.scaling = false;
                this.adjust();
            },

            Photos.prototype.doWrapperTransform= function(duration, callback) {
                if (duration === 0) {
                    var origin = this.wrapper.css('transition-property')
                    this.wrapper.css('transition-property', 'none').transform('translate3d(' + this.wrapperTransform + 'px, 0, 0)');
                    this.wrapper.css('transition-property', origin);
                    callback()
                } else {
                    this.wrapper.transitionEnd(function() {
                        callback && callback();
                    });
                    this.wrapper.transition(duration || Photos.defaults.duration).transform('translate3d(' + this.wrapperTransform + 'px, 0, 0)');
                }
            },

            Photos.prototype.doImageTransform=function(duration, callback) {
                if(!this.gestureImage) return;
                this.gestureImage.transition(duration || 0).transform('translate3d(' + this.imageTransform.x + 'px,' + this.imageTransform.y + 'px, 0) scale(' + this.currentScale + ')');
                this._needAdjust = true;
            },

            Photos.prototype.adjust=function() {
                if(!this._needAdjust) return false;
                var img = this.gestureImage;
                if(!img) return false;
                if(this.currentScale === 1) {
                    this.imageTransform = this.imageLastDiff =  {x:0,y:0};
                    this.doImageTransform(200);
                    return;
                }

                var rect = img[0].getBoundingClientRect();

                //调整上下
                if(rect.height < this.containerHeight) {  // 如果高度没容器高，则自动居中
                    this.imageTransform.y = this.imageLastTransform.y = 0;
                } else {  //如果比容器高，那么要保证上下不能有空隙
                    if(rect.top > 0) this.imageTransform.y = this.imageTransform.y - rect.top;
                    else if(rect.bottom < this.containerHeight) this.imageTransform.y = this.imageTransform.y + this.containerHeight - rect.bottom;
                }

                this.doImageTransform(200);
                this._needAdjust = false; // must at last line, because doImageTransform will set this._needAdjust true
            },

            Photos.prototype.slideTo= function(index, duration,type,success) {
                if(index < 0) index = 0;
                if(index > this.config.items.length-1) index = this.config.items.length - 1;
                this.lastActiveIndex = this.activeIndex;
                this.activeIndex = index;
                this.wrapperTransform = - (index * this.containerWidth);
                this.wrapperLastTransform = this.wrapperTransform;
                this.doWrapperTransform(duration, $.proxy(function() {
                    if(this.lastActiveIndex === this.activeIndex) return false; // active index not change
                    this.container.find('.caption-item.active').removeClass('active');
                    this.container.find('.swiper-slide-active').removeClass('swiper-slide-active');
                    this.container.find('.swiper-pagination-bullet-active').removeClass('swiper-pagination-bullet-active');
                    this.container.find('.caption-item').eq(this.activeIndex).addClass('active');
                    this.container.find('.swiper-slide').eq(this.activeIndex).addClass('swiper-slide-active');
                    this.container.find('.swiper-pagination-bullet').eq(this.activeIndex).addClass('swiper-pagination-bullet-active');

                    //reset image transform
                    this.container.find('.swiper-slide img[style]').transition(0).transform('translate3d(0,0,0) scale(1)');

                    this.lastScale = 1;
                    this.currentScale = 1;

                    this.imageLastTransform = { x: 0, y: 0 };
                    this.imageTransform = { x: 0, y: 0 };
                    this.imageDiff = { x: 0, y: 0 };
                    this.imageLastDiff = { x: 0, y: 0 };

                    if(this.config.onSlideChange) {
                        this.config.onSlideChange.call(this, this.activeIndex,type,success);
                    }
                    if(type){
                        success && success()
                    }

                }, this));
            },
            Photos.prototype.slideNext= function() {
                return this.slideTo(this.activeIndex+1, 200);
            },
            Photos.prototype.slidePrev= function() {
                return this.slideTo(this.activeIndex-1, 200);
            }
        APP.Photos = function(options) {
            return new Photos(options);
        };
    }());
    (function(){
        var bigPhotos = function(config) {
            this.initConfig(config);
            this.index = 0;
        }
        bigPhotos.defaults={
            items: '',
            autoOpen: false, //初始化完成之后立刻打开
            onOpen: undefined,
            onClose: undefined,
            initIndex: 0, //打开时默认显示第几张
            maxScale: 3,
            parent:".weui-photo-browser-modal",
            onSlideChange: undefined,
            duration: 200

        }
        bigPhotos.prototype.initConfig=function(config){
            var _self=this;
            this.config = $.extend({}, bigPhotos.defaults, config);
            this.activeIndex  = this.config.initIndex;
            $(_self.config.parent+' '+'.swiper-wrapper').html(_self.config.items);
            $(_self.config.parent).show();
           this.init();
        }
        bigPhotos.prototype.init=function(config){
            var _self=this;
            var img_active=this.config.initIndex
            var mybigPhotos = new Swiper(_self.config.parent +' '+'.swiper-container', {
                autoplay: false,
                initialSlide :_self.config.initIndex,
                pagination: {
                    el: _self.config.parent +' ' +'.swiper-pagination'
                },
                on:{
                    transitionEnd: function(){
                        //alert(this.activeIndex);
                        img_active= this.activeIndex
                        if(_self.config.onSlideChange) {
                            _self.config.onSlideChange.call(_self);
                        }
                    },
                    init: function(){
                        //alert(this.activeIndex);
                        if(_self.config.onOpen) {
                            _self.config.onOpen.call(_self);
                        }
                    },
                    tap: function(){
                        if(_self.config.onClose) {
                            _self.config.onClose.call();
                            mybigPhotos.destroy(false);
                        }
                    }
                }
            })
            setTimeout(function(){
            },_self.config.duration)
        }
        APP.bigPhotos = function(options) {
            return new bigPhotos(options);
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
    (function(){
        var textAuto=function(options){
            this.options = $.extend({}, textAuto.defaults, options);
            this.$el = $(this.options.element);
            this.init(this.options);
        }
        textAuto.defaults={
            element:''
        }
        textAuto.prototype.addEvent=function(type, callback){

        }
        textAuto.prototype.init = function (elem, extra, maxHeight) {
            extra = extra || 0;
            var isFirefox = !!document.getBoxObjectFor || 'mozInnerScreenX' in window,
                isOpera = !!window.opera && !!window.opera.toString().indexOf('Opera'),
                  addEvent = function (type, callback) {
                    elem.addEventListener ?
                        elem.addEventListener(type, callback, false) :
                        elem.attachEvent('on' + type, callback);
                },
                getStyle = elem.currentStyle ? function (name) {
                    var val = elem.currentStyle[name];

                    if (name === 'height' && val.search(/px/i) !== 1) {
                        var rect = elem.getBoundingClientRect();
                        return rect.bottom - rect.top -
                            parseFloat(getStyle('paddingTop')) -
                            parseFloat(getStyle('paddingBottom')) + 'px';
                    };

                    return val;
                } : function (name) {
                    return getComputedStyle(elem, null)[name];
                },
                minHeight = parseFloat(getStyle('height'));

            elem.style.resize = 'none';

            var change = function () {
                var scrollTop, height,
                    padding = 0,
                    style = elem.style;

                if (elem._length === elem.value.length) return;
                elem._length = elem.value.length;

                if (!isFirefox && !isOpera) {
                    padding = parseInt(getStyle('paddingTop')) + parseInt(getStyle('paddingBottom'));
                };
                scrollTop = document.body.scrollTop || document.documentElement.scrollTop;

                elem.style.height = minHeight + 'px';
                if (elem.scrollHeight > minHeight) {
                    if (maxHeight && elem.scrollHeight > maxHeight) {
                        height = maxHeight - padding;
                        style.overflowY = 'auto';
                    } else {
                        height = elem.scrollHeight - padding;
                        style.overflowY = 'hidden';
                    };
                    style.height = height + extra + 'px';
                    scrollTop += parseInt(style.height) - elem.currHeight;
                    document.body.scrollTop = scrollTop;
                    document.documentElement.scrollTop = scrollTop;
                    elem.currHeight = parseInt(style.height);
                };
            };

            addEvent('propertychange', change);
            addEvent('input', change);
            addEvent('focus', change);
            change();
        };
        APP.textAuto=function(options){
             return new textAuto(options)
        }
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
        load:function(){
            $("#layermbox3").show()
        },
        hideLoad:function(){
            $("#layermbox3").hide()
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