/**
 * Created by tangjintao on 2018-10-30.
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
    (function(){
        var model='<div id="layermbox3" class="layermbox layermbox2 layermshow" index="3" style="display:none"><div class="laymshade"></div><div class="layermmain"><div class="section"><div class="layermchild layermanim"><div class="layermcont" style="padding: 20px 15px;"><i></i><i class="laymloadtwo"></i><i></i></div></div></div></div></div>'
        $('body').append(model)
        if($home){
            $("#layermbox3").show()
        }
    }());
    (function(){
        var wifi=function(options){
            this.options = $.extend({}, wifi.defaults, options);
            this.init();

        }
        wifi.defaults={
            callback:null
        }
        wifi.prototype.init=function(){
            var self=this;
            var wifi = true;
            var ua = navigator.userAgent;
            var networkStr = ua.match(/NetType\/\w+/) ? ua.match(/NetType\/\w+/)[0] : 'NetType/other';
            networkStr = networkStr.toLowerCase().replace('nettype/', '');
            var networkType;
            switch (networkStr) {
                case 'wifi':
                    networkType = 'wifi';
                    wifi=true;
                    break;
                case '4g':
                    networkType = '4g';
                    wifi=false;
                    break;
                case '3g':
                    networkType = '3g';
                    wifi=false;
                    break;
                case '3gnet':
                    networkType = '3g';
                    wifi=false;
                    break;
                case '2g':
                    networkType = '2g';
                    wifi=false;
                    break;
                default:
                    networkType = 'other';
                    wifi=false;
            }
            // self.options.callback && self.options.callback.call(self, wifi);
            self.options.callback && self.options.callback.call(self, true);

        }
        APP.wifi=function(options){
            return new wifi(options)
        }
    }());
    (function() {
        var timer = function(options) {
            this.options = $.extend({}, timer.defaults, options);
            // this.init();
            this.initChange()
        };
        timer.defaults = {
            time:0,
            setTimer:null,
            initTimer:0,
            onBackTime:null,
            parentClass:'',
            initTime:false,
            max_close_time:0
        };
        timer.prototype.init = function() {
            var self = this;
            if(self.options.time >= 0  ) {
                var days = parseInt(self.options.time / 1000 / 60 / 60 / 24, 10); //计算剩余的天数
                var hours = parseInt(self.options.time / 1000 / 60 / 60 % 24, 10); //计算剩余的小时
                var minutes = parseInt(self.options.time / 1000 / 60 % 60, 10); //计算剩余的分钟
                var seconds = parseInt(self.options.time / 1000 % 60, 10); //计算剩余的秒数
                days = checkTime(days);
                hours = checkTime(hours);
                minutes = checkTime(minutes);
                seconds = checkTime(seconds);
                var t=[days,hours,minutes,seconds]
                if(self.options.parentClass!=''){
                    t={
                        times:t,
                        className:self.options.parentClass
                    }
                }
                self.options.onBackTime && self.options.onBackTime.call(self, t);
            } else {
                clearInterval(self.options.setTimer);
                var t=["00","00","00","00"]
                if(self.options.parentClass!=''){
                    t={
                        times:t,
                        className:self.options.parentClass
                    }
                }
                self.options.onBackTime && self.options.onBackTime.call(self, t);
            }
            function checkTime(i) { //将0-9的数字前面加上0，例1变为01
                if(i < 10) {
                    i = "0" + i;
                }
                return i;
            }
        };
        timer.prototype.initChange=function(){
            var self = this;
            self.options.setTimer = setInterval(function() {
                self.options.initTimer++;
                self.options.time = self.options.time - 1000;
                self.init();
                if(self.options.initTime && self.options.initTimer % 3 ==0 ){
                    clearInterval(self.options.setTimer);
                     $.ajax({
                    url:'/wap.php?c=Mall&a=get_distance_time',
                    type:'post',
                    dataType:'json',
                    data:{"end_time":self.options.max_close_time},
                    async:false,
                    success:function(res){
                    	self.options.time =res*1*1000
                         self.initChange()
                    },
                    error:function(){
                    }
                });
                 }  
            }, 1000);
        }
        APP.timer = function(options) {
            return new timer(options);
        };
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
        //获取图片大小
        var imgPic = function(options) {
            this.options = $.extend({}, imgPic.defaults, options);
            this.init();
        };
        imgPic.defaults = {
            id:'',
            callback:null
        };
        imgPic.prototype.init=function(){
            var _self=this;
            var oImg = document.getElementById(this.options.id);
            var nImg = new Image();
            nImg.onload = function() {
                var nWidth = nImg.width,
                    nHeight = nImg.height;
                var data={
                    w: nWidth,
                    h:nHeight
                }
                _self.options.callback && _self.options.callback.call(_self, data);
            }
            nImg.src = oImg.src;


        };
        APP.imgPic = function(options) {
            return new imgPic(options);
        };
    }());
    (function(){
        //回到顶部
        $(window).on("scroll", function(){
            if($("#toTop").size()>0){
                if($("#refresh").size()==0){
                    if ($(window).scrollTop() > 100 ) {
                        $("#toTop").fadeIn()
                    }else{
                        $("#toTop").fadeOut()
                    }
                }
            }

        })
        $(document).on("click","#toTop",function(){
            $(this).addClass("disabled")
            if($("#refresh").size()==0){
                $("html,body").animate({"scrollTop":0},300);
            }else{
                $('#refresh #scrollers').css({
                    "transform": "translate(0px, -40px)",
                    "transition-duration": "300ms"
                })
                $("#toTop").fadeOut()
            }
        });
    }());
    APP.addMethod({
        fnUrlReplace:function(href){
            if (href && /^#|javasc/.test(href) === false) {
				location.replace(href);
            }
        },
        load:function(){
            $("#layermbox3").show()
        },
        hideLoad:function(){
            $("#layermbox3").hide()
        },
        ajax:function(url,data,type,success,error){
            $.ajax({
                url:url,
                type:type,
                dataType:'json',
                async:false,
                data:data,
                success:function(res){
                    success && success(res)
                },
                error:function(res){
                    error && error(res)
                }
            });
        },
        destruction:function(list){
            for(var i=0;i<list.length;i++){
                list[i]==null
            }


        },
        isMobile:function(){
            if (/(iPhone|iPad|iPod|iOS|Android)/i.test(navigator.userAgent.toLowerCase())) { //移动端
                return true
            }else{
                return false
            }

        }

    })
    window.APP = APP;
    return APP;
})();