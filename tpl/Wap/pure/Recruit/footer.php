<style type="text/css">
.footerMenu ul li a{
    position: relative;
}
.footerMenu ul li a .number{
    position: absolute;
    right: 25px;
    top:5px;
    width: 6px;
    height: 6px; 
    border-radius: 100%;
    background: red;
    color:#fff;
    font-size: 10px;
    font-style: normal
}
</style>
<footer class="footerMenu ">
   <ul style="border-top: unset;">
        <li>
           <a class="active" href="{pigcms{:U('index')}"><em class="home"></em><p>首页</p></a>
        </li>
        <li>
           <!-- <a href="/wap.php?g=Wap&c=ActivityOffline"><em class="activity"></em><p>活动</p></a> -->
           <a href="{pigcms{:U('message')}"><em class="message"></em><p>消息</p><i class="number"></i></a>
          
        </li>
        <?php if ($now_company['status'] == 1): ?>
        <li> 
           <a class='openUrl newWin' data-url="{pigcms{:U('position_select',array('type'=>'position'))}" ><em class="add"></em><p>发布</p></a>
        </li>
        <?php endif ?>
        <li>
           <a href="{pigcms{:U('course_index')}"><em class="store"></em><p>商学院</p></a> 
        </li>
        <li>
           <a class="my_recruit" href="{pigcms{:U('my')}"><em class="my"></em><p>我的</p></a>
        </li>
   </ul>
</footer>

<script type="text/javascript">
    var action = "{pigcms{$_GET['a']}";
    if (window.location.href.indexOf("a=index")!=-1) {
        $('a').removeClass('active');
        $('.home').parent('a').addClass('active');
    }else if(window.location.href.indexOf("a=my")!=-1){
        $('a').removeClass('active');
        $('.my').parent('a').addClass('active');
    }else if(window.location.href.indexOf("a=course_index")!=-1){
        $('a').removeClass('active');
        $('.store').parent('a').addClass('active');
    }else if(window.location.href.indexOf("ActivityOffline")!=-1){
        $('a').removeClass('active');
        $('.activity').parent('a').addClass('active');
    }else if(window.location.href.indexOf("message")!=-1){
        $('a').removeClass('active');
        $('.message').parent('a').addClass('active');
    }else{
        $('a').removeClass('active');
        $('.home').parent('a').addClass('active');
    }

    if ($('.footerMenu').find('li').length == 4) {
        $('.footerMenu').find('li').css('width','25%')
    }

    setInterval(function(){
        message_count();
    },3000)

    function message_count(){
        $.ajax({
            type: 'GET',
            url: "{pigcms{:U('ajax_message_count')}",
            data:{},
            dataType: 'json',
            success: function(data){   
                console.log(data)
                // $('.number').html(data.result);
                if(data.result > 0){
                    $('.number').show();
                }else{
                    $('.number').hide();
                }
            }
        });
    }
    message_count();
</script>
<script>
	var currency_symbol = "{pigcms{$config.Currency_symbol}";
	</script>
 <script src="{pigcms{$static_path}recruit/js/base.js?133" ></script>
<div class="share_html">{pigcms{$shareScript}</div>

</body>
</html>