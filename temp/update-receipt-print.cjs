const fs = require('fs');
const path = 'D:/projectView/platform_dev/src/views/community/village/charge/cashier/printOrder.vue';
let source = fs.readFileSync(path, 'utf8');
const newline = source.includes('\r\n') ? '\r\n' : '\n';
source = source.replace(/\r\n/g, '\n');
for (const fragment of [
    '                <div v-if="index_list < data.length-1" class="page-break"></div>\n',
    '                <div v-if="index_p != tableList.length - 1" class="page-break"></div>\n'
]) {
    if (!source.includes(fragment)) throw new Error('Missing expected page break');
    source = source.replace(fragment, '');
}
source = source.replace("import print from 'print-js'", "import printJS from 'print-js'\nimport { createReceiptPrintDocument, receiptPrintStyle } from '@/utils/receiptPrintLayout'");
const start = source.indexOf('        print() {');
const end = source.indexOf('                onPrintDialogClose:', start);
if (start < 0 || end < 0) throw new Error('Print function not found');
source = source.slice(0, start) + `        async print() {
            if (this.confirmLoading || this.printPreparing) return
            this.printPreparing = true
            let printable
            try {
                await this.$nextTick()
                const root = document.getElementById(this.table_id)
                if (!root || !root.children.length) return
                printable = await createReceiptPrintDocument(root)
            } catch (error) {
                this.$message.error('打印内容准备失败，请重试')
                return
            } finally {
                this.printPreparing = false
            }
            printJS({
                printable,
                type: 'html',
                maxWidth: '100%',
                style: receiptPrintStyle,
                scanStyles: false,
` + source.slice(end);
fs.mkdirSync('temp/receipt-print-backup', { recursive: true });
fs.copyFileSync(path, 'temp/receipt-print-backup/printOrder.vue');
fs.writeFileSync(path, source.replace(/\n/g, newline));
fs.copyFileSync('temp/receipt-print-layout.js', 'D:/projectView/platform_dev/src/utils/receiptPrintLayout.js');
console.log('Updated receipt print component and layout helper');
