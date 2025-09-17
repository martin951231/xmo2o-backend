<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>店员中心</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}css/app.css" rel="stylesheet"/>
        <link href="{pigcms{$static_path}css/whole.css" rel="stylesheet"/>
    </head>
    <body>
        <view class="container">
            <div class="scrollList fl">
                <view class="leftNav">
                    <view>推荐收藏</view>
                    <view>享生活</view>
                    <view>爱美食</view>
                </view>
            </div>

            <div class="contentList">
                <view class="rightnav">
                    <view class="level link-url" data-url="{pigcms{:U('Fav/index_review')}">
                        <view class="img fl">
                            <img src="{pigcms{:file_domain()}/upload/adver/000/000/030/585c88be076d9.png"/>
                        </view>
                        <view class="p123">
                            <view class="h2">热点合肥1</view>
                            <view class="star">124</view>
                        </view>
                    </view>
                </view>
                <view class="rightnav">
                    <view class="level link-url" data-url="index_review.html">
                        <view class="img fl">
                            <img src="{pigcms{:file_domain()}/upload/adver/000/000/030/585c88be076d9.png"/>
                        </view>
                        <view class="p123">
                            <view class="h2">热点合肥2</view>
                            <view class="star">124</view>
                        </view>
                    </view>
                </view>
                <view class="rightnav">
                    <view class="level link-url" data-url="index_review.html">
                        <view class="img fl">
                            <img src="{pigcms{:file_domain()}/upload/adver/000/000/030/585c88be076d9.png"/>
                        </view>
                        <view class="p123">
                            <view class="h2">热点合肥3</view>
                            <view class="star">124</view>
                        </view>
                    </view>
                </view>
            </div>
        
        </view>

        <script src="{pigcms{$static_path}scripts/jquery-1.8.3.min.js"></script>
        <script src="{pigcms{$static_path}scripts/common.js"></script>
        <script src="{pigcms{$static_path}scripts/choice_whole.js"></script>
    </body>
</html>
