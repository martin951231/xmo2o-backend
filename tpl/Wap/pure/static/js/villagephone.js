var myScroll;
var isApp = true;
$(function(){
    $('#scroller').css({'min-height':($(window).height()-131-50+1)+'px'});
    if(isApp){
        $('#container').css({'top':'0px'});
        $('#container,#scroller').css({'position':'static'});
        $('body').append('<style>::-webkit-scrollbar{width:0px;}</style>');
    }else{
        if (typeof no_change =='undefined' || !no_change) {
            $('#container').css({'bottom':'57px'});
        }
        myScroll = new IScroll('#container', { probeType: 3,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick(),scrollbars:false,useTransform:false,useTransition:false});
    }
});