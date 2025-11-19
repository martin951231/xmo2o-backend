<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>绑定商家</title>
    <script src="/tpl/Wap/default/Qianzhangtong/tailwindcss.js"></script>
    <script src="{pigcms{:C('JQUERY_FILE_351')}"></script>
    <link href="/tpl/Wap/default/Qianzhangtong/all.min.css" rel="stylesheet">
    <style>
        /* 自定义样式，可根据需求调整 */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-4">绑定商家</h1>
            <form id="merchantForm">
                <!-- 商家基本信息 -->
                <div class="mb-4">
                    <label for="channel_merchant_no" class="block text-sm font-medium text-gray-700">渠道商户号</label>
                    <input type="text" id="channel_merchant_no" name="channel_merchant_no"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="channel_terminal_no" class="block text-sm font-medium text-gray-700">渠道终端号</label>
                    <input type="text" id="channel_terminal_no" name="channel_terminal_no"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <!-- 提交按钮 -->
                <div class="mt-6">
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="mr-2"></i> 提交绑定
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('merchantForm').addEventListener('submit', function (e) {
            e.preventDefault();
            // 这里可以添加表单提交逻辑，如发送数据到服务器
            var channel_merchant_no = $('#channel_merchant_no').val();
            var channel_terminal_no = $('#channel_terminal_no').val();
            if(channel_merchant_no == '' || channel_terminal_no == ''){
                alert('请填写渠道商户号和渠道终端号');
                return false;
            }
            $.post(
                "/wap.php?c=Qianzhangtong&a=bindMerchant",
                {
                    id:"{pigcms{$apply['id']}",
                    channel_merchant_no:channel_merchant_no,
                    channel_terminal_no:channel_terminal_no
                },
                function(rs){
                    if(rs.status == 1){
                        //绑定成功
                        window.location.href = rs.url;
                    }else{
                        alert(rs.info);
                    }
                    return;
                }
            );
        })

    </script>
</body>

</html>
    