<link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/photo.css"/>
<style>
    .pics img{cursor:pointer}
</style>
<div class="weui-photo-browser-modal weui-photo-browser-modal-visible" style="display: none;">
    <div class="swiper-container swiper-container-visible" style="touch-action: none; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
        <div class="swiper-wrapper" >

        </div>
        <div class="swiper-pagination"></div>
    </div>
</div>
<script src="{pigcms{$static_path}employee/js/common/simplite.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/swiper.min.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/employee_common.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/cate_complite.js" type="text/javascript" charset="UTF-8"></script>
<script type="text/html" id="showBigPhoto">
    <& for(var i=0;i< _this.length;i++) {&>
    <div class="swiper-slide">
        <div class="photo-container" style="height:100%">
            <img src="<&= _this[i] &>" >
        </div>
    </div>
    <&}&>
</script>
<script type="text/javascript">
    var $weui_photo_browser_modal=false,is_auto_back=false;
    var $activeIndex=0;
        window.addEventListener("popstate", function(e) {
            if(!$weui_photo_browser_modal && !is_auto_back){
                window.history.go(-1)
            }else{
                $(".weui-photo-browser-modal").hide();
                $(".weui-photo-browser-modal .swiper-wrapper").html("")
                $weui_photo_browser_modal=false;
            }

        }, false);
        function pushHistory() {
            var state = {
                title: "title",
                url: window.location.href
            };
            window.history.pushState(state, "title", "#");
        }
    $(document).on("click",".pics img",function(){
        var album_array = [];
        var  index=parseInt($(this).attr('data-index'));
        $(this).closest(".pics").find('img').each(function(n,v){
            var pics=$(this).attr('data-img');
            if(pics){
                album_array[n]=pics;
            }
        });
        console.log(album_array)
        var $photo = Simplite.getTemplate('showBigPhoto');
        var $photo_item = Simplite.compile($photo)(album_array);
       /* APP.bigPhotos({
            items:$photo_item,
            initIndex:index,
            parent:".weui-photo-browser-modal",
            onSlideChange: function() {
                console.log(this.activeIndex)
            },
            onOpen: function() {
                $weui_photo_browser_modal=true;
                is_auto_back=false;
                pushHistory();
              console.log(this.activeIndex)
            },
            onClose: function() {
                $weui_photo_browser_modal=false;
                is_auto_back=true;
                window.history.go(-1)

            }
        });*/
      var pb1 = APP.Photos({
            items:album_array,
            onSlideChange: function(index) {
                console.log(this, index);
                $activeIndex=index
            },
            onOpen: function() {
                $weui_photo_browser_modal=true;
                is_auto_back=false;
                $activeIndex=this.activeIndex;
                console.log("onOpen", this);
                pushHistory();
            },
            onClose: function() {
                $(".weui-photo-browser-modal").remove();
                $weui_photo_browser_modal=false;
                is_auto_back=true;
                $activeIndex=this.activeIndex;
                window.history.go(-1)
                console.log("onClose", this);
            }
        });
        pb1.open(index);
});
</script>