<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title> </title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name='apple-touch-fullscreen' content='yes'>
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/pay.css?2151"/> 
        <style>
            .rechargeBox{
                padding: 30px 20px;
            }
            .rechargeBox h2{
                padding: 10px 0;
                color: #6aa782;
                font-weight: 600;
            }
            .rechargeBox ul{
                /* border:1px solid #eee; */
                /* box-shadow: 1px 1px 4px #f4f6fb; */
                padding: 20px 10px;
                color: #707c8d;
                border-radius: 6px;
                margin: 20px auto;
            }
            .rechargeBox ul li{ 
                line-height: 50px;
                padding: 0 10px;
                border-bottom:1px solid #eee
            }
            
            .clear{
                clear: both; 
            }
            .rechargeBox ul li a{
                 padding: 4px 10px;
                 height: 20px;
                 line-height: 20px;
                 margin: 14px 0;
                 color: #fff;
                 background: -webkit-linear-gradient(#30d89c,#19cf6b);  
                 background: -o-linear-gradient(#30d89c,#19cf6b);  
                 background: -moz-linear-gradient(#30d89c,#19cf6b); 
                 background: linear-gradient(#30d89c,#19cf6b);  
                 border-radius:20px; 
                 border-bottom: 1px solid #19cf6b;
            }
            .rechargeBox ul li span{
                font-weight: bold;
            }
        </style>
    </head>
    <body style="background: #fff;">
        <div class="rechargeBox">
            <h2>充值享折上折</h2>
            <ul>
                <volist name="recharge_discount_list" id="vo">
                    <li>
                        <span class="fl">充{pigcms{$vo.full_money},送{pigcms{$vo.money}</span>
                        <a class="fr" href="{pigcms{:U('recharge')}">立即充值</a>
                        <div class="clear"></div>
                   </li> 
                </volist>
            </ul> 
        </div>
	 
      	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js?11" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112" charset="utf-8"></script> 
    </body>
</html>