const assert = require('assert');
const fs = require('fs');
const compiler = require('D:/projectView/platform_dev/node_modules/vue-template-compiler');
const file = 'D:/projectView/platform_dev/src/views/community/village/charge/cashier/printOrder.vue';
const source = compiler.parseComponent(fs.readFileSync(file, 'utf8'));
const result = compiler.compile(source.template.content);
assert.deepStrictEqual(result.errors, []);
const options = new Function(source.script.content.replace(/^import .*$/gm, '').replace('export default', 'return'))();
const format = options.methods.formatReceiptDate;
assert.strictEqual(format('2026-10-14'), '2026-10-14');
assert.strictEqual(format('2026-10-21 16:25:30'), '2026-10-21');
for (const invalid of ['', null, '无', '2026-10-14-2026-10-21', '2026-10-14，2026-10-21']) {
    assert.strictEqual(format(invalid), '');
}
const context = {data:[{pay_time:'2026-10-21'}, {pay_time:'2026-10-14'}, {pay_time:'无'}],formatReceiptDate:format};
assert.deepStrictEqual([0,1,2,3].map(i=>options.methods.receiptDate.call(context,i)), ['2026-10-21','2026-10-14','','']);
console.log('PASS: dates follow receipt order; datetime, unpaid/missing and aggregate values handled');
