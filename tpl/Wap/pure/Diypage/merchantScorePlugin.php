<!-- 判断是否在小程序打开 -->
<if condition="$is_wxapp_browser && $user_session && $showScoreModal">
    <div>
        <style>
            .auth-mask {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.4);
                padding-top: 50%;
                display: none;
                z-index: 99999;
            }

            .auth-mask .auth-content {
                background: #fff;
                margin: 0 30px;
                text-align: center;
                border-radius: 6px;
                line-height: 1.5;
                color: #333;
                font-size: 14px;
                padding: 50px 0 30px;
                position: relative;
            }

            .auth-mask .auth-content .btn {
                background: #23AC3A;
                color: #fff;
                width: 80%;
                margin-top: 30px;
                height: 40px;
                border: none;
                font-size: 14px;
            }

            .auth-mask .auth-content .tip {
                color: #999;
                font-size: 12px;
                margin-top: 5px;
            }

            .auth-mask .auth-content .close {
                position: absolute;
                right: 0;
                top: 0;
                padding: 0 5px;
                font-size: 32px;
                line-height: 1;
                color: #999;
            }

            .auth-mask .auth-content .circle {
                width: 14px;
                height: 14px;
                text-align: center;
                line-height: 14px;
                border-radius: 50%;
                border: 1px solid #ccc;
                display: inline-block;
                vertical-align: middle;
                font-size: 12px;
                box-sizing: border-box;
            }

            .auth-mask .auth-content .circle.active {
                color: #FFFFFF;
                background-color: #23AC3A;
                border: none;
            }
        </style>
        <div class="auth-mask">
            <div class="auth-content">
                <div>授权微信支付，享积分自动到账</div>
                <div>更有机会获得多倍积分</div>
                <div>
                    <button class="btn">现在去授权</button>
                </div>
                <div class="tip">
                    <span class="circle"></span>
                    <!-- <span class="circle active">✓</span> -->
                    <span>今日不再提示</span>
                </div>
                <div class="close">×</div>
            </div>
        </div>
        <script type="text/javascript">
            var isSelected = false
            var name = ''
            $.ajax({
                url: '/wap.php?g=Wap&c=WxCircleIntegral&a=getWinStatus',
                async: false, //这一步是非常重要的，作用是设置为同步执行
                dataType: "json",
                data: {},
                type: "post",
                success: function(data) {
                    console.log('请求成功', data)


                    if (data.wxapp_openid == 'oFdjS4pT9fkBkjenv4r4YvFku7zw' && data.win_open == 1) {
                        name = data.wxapp_openid.slice(0, 10) + '_notShow'
                        console.log('4444', $.cookie(name))
                        if (!$.cookie(name)) {
                            $('.auth-mask').show()
                        }
                    }

                    $('.auth-mask .close').on('click', function() {
                        $('.auth-mask').hide()

                    })
                    $('.auth-mask .btn').on('click', function() {
                        $('.auth-mask').hide()


                        wx.miniProgram.navigateTo({
                            url: 'plugin://merchantScorePlugin/index?openid=' + data.wxapp_openid + '&mch_id=' + data.mch_id,
                            fail: function(res) {
                                layer.open({
                                    content: '打开分享失败，请重试',
                                    btn: '好的'
                                });
                            }
                        });
                    });

                    $('.circle').on('click', function() {
                        console.log('点击选择', $(this), $(this).attr('class'))
                        var className = $(this).attr('class')
                        if (className.indexOf('active') == -1) {
                            $(this).text('✓').addClass('active')
                            isSelected = true
                        } else {
                            $(this).text('').removeClass('active')
                            isSelected = false
                        }

                        showMaskOpt()
                    })

                },
                error: function(data) {
                    console.log('请求失败', data)
                }
            });

            function showMaskOpt() {
                if (!isSelected) {
                    $.cookie(name, null, {
                        expires: -1
                    });
                    return
                }

                // var now = <?php echo time(); ?>;//当前时间
                var torrow = <?php echo strtotime(date('Y-m-d', strtotime('+1 day'))); ?>; //明日凌晨时间
                // expire =  torrow - now;//今日剩余时间(单位:s)
                document.cookie = name + "=true;expires=" + new Date(torrow * 1000);
            }
        </script>
    </div>
</if>
<script type="text/javascript">
    <if condition="$user_session">
        var is_login = true;
    <else/>
        var is_login = false;
    </if>
</script>
<script type = "text/javascript" >
    $('.merchantScorePluginLink').click(function() {
        if(!is_login){
            location.href = '/wap.php?c=Login&a=index';
            return;
        }
        $.ajax({
            url: '/wap.php?g=Wap&c=WxCircleIntegral&a=getWinStatus',
            async: false, //这一步是非常重要的，作用是设置为同步执行
            dataType: "json",
            data: {},
            type: "post",
            success: function(data) {
                console.log('请求成功', data)

                wx.miniProgram.navigateTo({
                    url: 'plugin://merchantScorePlugin/index?openid=' + data.wxapp_openid + '&mch_id=' + data.mch_id,
                    fail: function(res) {
                        layer.open({
                            content: '打开分享失败，请重试',
                            btn: '好的'
                        });
                    }
                });
            },
            error: function(data) {
                console.log('请求失败', data)
            }
        });
    });
</script>