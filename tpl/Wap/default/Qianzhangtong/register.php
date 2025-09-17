<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员开户结果</title>
    <script src="/tpl/Wap/default/Qianzhangtong/tailwindcss.js"></script>
    <script src="{pigcms{:C('JQUERY_FILE_351')}"></script>
    <link href="/tpl/Wap/default/Qianzhangtong/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .label_font {
            font-size: 0.8rem;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex justify-center">
    <div class="container mx-auto p-4 w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
            <div id="success">
                <div class="flex space-x-4">
                    <div>
                        <h2 class="text-xl font-bold text-green-500">会员开户结果</h2>
                        <p class="text-gray-700">以下是您的会员开户档案信息。</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-6 label_font">
                    <div class="font-medium text-gray-700">外部会员标识：</div>
                    <div class="text-gray-700 break-words">{pigcms{$apply.out_member_no}</div>
                    <div class="font-medium text-gray-700">会员编号：</div>
                    <div class="text-gray-700 break-words">{pigcms{$apply.member_no}</div>
                    <div class="font-medium text-gray-700">手机号：</div>
                    <div class="text-gray-700 break-words">{pigcms{$apply.mobile}</div>
                    <!-- <div class="font-medium text-gray-700">银行卡编号</div>
                    <div class="text-gray-700 break-words">{pigcms{$apply.card_no}</div> -->
                    <div class="font-medium text-gray-700">会员状态：</div>
                    <div class="text-gray-700 break-words">
                        <php>
                            $desc = [
                            '1' => '有效',
                            '10' => '锁定',
                            '20' => '关闭',
                            '99' => '待审核',
                            ];
                            if($apply['member_state'] == 20){
                            echo '<font color="red">'.$desc[$apply['member_state']].'</font>';
                            }else{
                            echo $desc[$apply['member_state']]??'已提交申请';
                            }

                        </php>
                    </div>
                    <notempty name='apply.message'>
                        <div class="font-medium text-gray-700">错误信息：</div>
                        <div class="text-gray-700 break-words">{pigcms{$apply.message}</div>
                    </notempty>

                    <notempty name='apply.channel_merchant_no'>
                        <div class="font-medium text-gray-700">渠道商户号：</div>
                        <div class="text-gray-700 break-words">{pigcms{$apply.channel_merchant_no}</div>
                        <div class="font-medium text-gray-700">渠道终端号：</div>
                        <div class="text-gray-700 break-words">{pigcms{$apply.channel_terminal_no}</div>
                        <div class="font-medium text-gray-700">钱账通商户号：</div>
                        <div class="text-gray-700 break-words">{pigcms{$apply.merchant_no}</div>

                        <div class="font-medium text-gray-700">商户状态：</div>
                        <div class="text-gray-700 break-words">
                            <php>
                                $desc = [
                                '1' => '审核中',
                                '2' => '进件中',
                                '3' => '有效',
                                '4' => '无效',
                                ];
                                if($apply['merchant_status'] == 4){
                                echo '<font color="red">'.$desc[$apply['merchant_status']].'</font>';
                                }else{
                                echo $desc[$apply['merchant_status']]??'未知';
                                }

                            </php>
                        </div>
                    </notempty>
                </div>
                <br />

                <if condition="$apply['member_state'] == 1">
                    <empty name="apply.channel_merchant_no">
                        <a href="#" id="bindMerchant" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">绑定商家</a>
                        <else />
                        <a href="#" id="unbindMerchant" class="mt-4 inline-block bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">解绑商家</a>
                    </empty>
                    <a href="#" id="reBindCard" style="margin-left:0.2rem" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">查看绑卡</a>
                </if>

                <if condition="$apply['member_state'] == 20">
                    <!-- 会员状态是关闭，增加重新注册 -->
                    <a href="#" id="reRegister" style="margin-left:0.2rem" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">重新注册</a>
                </if>
            </div>
        </div>
    </div>
</body>
<script type="text/javascript">
    var id = "{pigcms{$apply.id}";
    var token = "{pigcms{$_GET.token}";

    $('#bindMerchant').on('click', function() {
        window.location.href = '/wap.php?c=Qianzhangtong&a=bindMerchant&id=' + id + '&token=' + token;
    });

    $('#unbindMerchant').on('click', function() {
        if (confirm('您确定要解绑商家吗，解绑后将无法使用钱账通支付功能？')) {
            $.post("/wap.php?c=Qianzhangtong&a=unbindMerchant", {
                id: id,
                token: token
            }, function(res) {
                if (res.status == 1) {
                    alert('解绑成功');
                    history.go(0);
                } else {
                    alert(res.info);
                }
            })
        }
    });

    $('#reRegister').on('click', function() {
        if (confirm('重新注册会员将清空当前会员信息，操作不可逆，请谨慎操作。是否继续？')) {
            window.location.href = '/wap.php?c=Qianzhangtong&a=reRegister&id=' + id + '&token=' + token;
        }
    })

    $('#reBindCard').on('click', function() {
        if (confirm('正常不需要调用，默认使用注册会员时提供的银行账户作为结算账户。确定重新绑定结算银行卡吗？')) {
            window.location.href = '/wap.php?c=Qianzhangtong&a=reBindCard&id=' + id + '&token=' + token;
        }
    })
</script>

</html>