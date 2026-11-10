const fs = require('fs');
const phpFile = 'v20/app/community/model/service/HouseNewCashierService.php';
let php = fs.readFileSync(phpFile, 'utf8');
const anchor = '                if (isset($order_body_merge[$merge_project_key])) {';
if (php.split(anchor).length !== 2) throw new Error('Unexpected merge anchor');
const newline = php.includes('\r\n') ? '\r\n' : '\n';
const addition = [
    '                // 独立收据按实际收款日期分组，跨日账单不能合并成同一张收据。',
    "                if ((int)$templateInfo['type'] === 4) {",
    "                    $merge_project_key .= '_receipt_date_' . $body_item['pay_time'];",
    '                }',
].join(newline) + newline;
php = php.replace(anchor, addition + anchor);
fs.writeFileSync(phpFile, php);
const vueFile = 'D:/projectView/platform_dev/src/views/community/village/charge/cashier/printOrder.vue';
let vue = fs.readFileSync(vueFile, 'utf8');
const expression = "formatReceiptDate(printList3.find(v=>v.title == '收款日期').value)";
if (!vue.includes(expression)) throw new Error('Receipt date binding missing');
vue = vue.replace(expression, 'receiptDate(index_list)');
const start = vue.indexOf('        formatReceiptDate(value) {');
const end = vue.indexOf('        getReduceRes(list)', start);
if (start < 0 || end < 0) throw new Error('Date formatter missing');
const methods = `        receiptDate(index) {
            // data_order 和 tab_list 均由同一个 order_body 顺序生成。
            // 必须使用当前收据的收款日期，不能使用批次汇总的最早/最晚日期。
            const order = this.data[index]
            return this.formatReceiptDate(order ? order.pay_time : '')
        },

        formatReceiptDate(value) {
            const match = String(value || '').trim().match(/^(\\d{4}-\\d{2}-\\d{2})(?:[ T]\\d{2}:\\d{2}(?::\\d{2})?)?$/)
            return match ? match[1] : ''
        },

`;
vue = vue.slice(0, start) + methods.replace(/\n/g, vue.includes('\r\n') ? '\r\n' : '\n') + vue.slice(end);
fs.writeFileSync(vueFile, vue);
console.log('Use per-receipt paid date; keep different paid dates in separate type-4 receipt groups');
