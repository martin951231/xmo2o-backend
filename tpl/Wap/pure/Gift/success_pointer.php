<!DOCTYPE html>
<html lang="en">
<head>
    <title>成功获得积分</title>
<include file="Public:gift_header" />
    <style>
        .add_more{position:relative}
        .add_more:before {
            transform: rotate(135deg);
            position: absolute;
            width: 8px;
            height: 8px;
            top: 15px;
            right: 10px;
            content: '';
            border: 2px solid #ddd;
            border-right: 2px solid transparent;
            border-bottom: 2px solid transparent;
        }
    </style>
</head>
<body>
<section class="orderSucced">
    <div class="wrap">
        <span class="dib vm icon"><i class="fa fa-check-circle"></i></span>
        <div class="dib vm desc">
            <h2>恭喜您，获得{pigcms{$_GET['score']|floatval}个平台积分!</h2>

        </div>
    </div>
</section>
<section id="banner_hei">
    <ul>
		<volist name="adver" id="vo">
        <li>
            <img src="{pigcms{$vo.pic}"/>
        </li>
		</volist>

    </ul>
</section>


<section class="ftHeight"></section>

<section class="fullBg"></section>
<script type="text/javascript" language="javascript">
    var currency_symbol = "{pigcms{$config.Currency_symbol}";
</script>
<include file="Public:gift_footer" />
<script>

$(function(){
    var banner_height	=	$(window).width()/320;
    banner_height	=	 Math.ceil(banner_height*119);
    $("#banner_hei img").css({
        'height':banner_height,
        'width':'100%'
    })
    $("#share").tap(function(){
       $(".fullBg").show();
        $(".JSshareMod").animate({
          height:'auto'
        }, 500, 'ease');
    });

    $(".JSbtn,.fullBg").tap(function(){
        $(".fullBg").hide();
        $(".JSshareMod").animate({
            height:'0'
        }, 500, 'ease');
    });
});
</script>
</body>
</html>