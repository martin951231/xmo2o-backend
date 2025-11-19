<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>拉卡拉钱账通入网</title>
    <script src="/tpl/Wap/default/Qianzhangtong/tailwindcss.js"></script>
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
            <h1 class="text-2xl font-bold mb-4">拉卡拉钱账通入网</h1>
            <form id="merchantForm">
                <!-- 商家基本信息 -->
                <div class="mb-4">
                    <label for="out_member_no" class="block text-sm font-medium text-gray-700">外部会员标识</label>
                    <input type="text" id="out_member_no" name="out_member_no"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="merchantAddress" class="block text-sm font-medium text-gray-700">商家地址</label>
                    <input type="text" id="merchantAddress" name="merchantAddress"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="contactPerson" class="block text-sm font-medium text-gray-700">联系人姓名</label>
                    <input type="text" id="contactPerson" name="contactPerson"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="contactPhone" class="block text-sm font-medium text-gray-700">联系人电话</label>
                    <input type="tel" id="contactPhone" name="contactPhone"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <!-- 营业执照信息 -->
                <div class="mb-4">
                    <label for="businessLicenseNumber" class="block text-sm font-medium text-gray-700">营业执照号码</label>
                    <input type="text" id="businessLicenseNumber" name="businessLicenseNumber"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="businessLicenseExpiry" class="block text-sm font-medium text-gray-700">营业执照有效期</label>
                    <input type="date" id="businessLicenseExpiry" name="businessLicenseExpiry"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <!-- 银行账户信息 -->
                <div class="mb-4">
                    <label for="bankAccountNumber" class="block text-sm font-medium text-gray-700">银行账号</label>
                    <input type="text" id="bankAccountNumber" name="bankAccountNumber"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="bankName" class="block text-sm font-medium text-gray-700">开户银行名称</label>
                    <input type="text" id="bankName" name="bankName"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <!-- 提交按钮 -->
                <div class="mt-6">
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="mr-2"></i> 提交申请
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('merchantForm').addEventListener('submit', function (e) {
            e.preventDefault();
            // 这里可以添加表单提交逻辑，如发送数据到服务器
            alert('表单已提交！');
        });
    </script>
</body>

</html>
    