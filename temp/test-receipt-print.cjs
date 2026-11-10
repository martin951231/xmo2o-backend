const fs = require('fs');
const { chromium } = require('C:/Users/GIGABYTE/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async () => {
    const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe', headless:true});
    const page = await browser.newPage({ viewport: {width:1300,height:900} });
    const helper = fs.readFileSync('D:/projectView/platform_dev/src/utils/receiptPrintLayout.js','utf8').replace(/export /g, '');
    await page.setContent('<html><body><div id="receipts" style="width:1200px"></div></body></html>');
    await page.evaluate(async source => {
        const root = document.getElementById('receipts');
        for(let i=1;i<=9;i++) {
            const bill = document.createElement('div');
            bill.innerHTML = `<div style="border:1px solid black;padding:12px;position:relative"><h2>START-BILL-${i}</h2><p>Receipt ${i} / charge details</p><div style="height:${i === 5 ? 950 : 100}px">Amount: 100.00</div><p>END-BILL-${i}</p><span style="position:absolute;bottom:-12px;right:20px">STAMP-${i}</span></div>`;
            root.appendChild(bill);
        }
        const build = new Function(source + ';return {createReceiptPrintDocument,createReceiptPrintStyle}');
        const api = build();
        const output = await api.createReceiptPrintDocument(root);
        root.remove();
        document.body.appendChild(output);
        const style = document.createElement('style');
        style.textContent = api.createReceiptPrintStyle('a4');
        document.head.appendChild(style);
    },helper);
    await page.addScriptTag({path:'D:/projectView/platform_dev/node_modules/print-js/dist/print.js'});
    await page.evaluate(() => new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Print iframe did not render')), 10000);
        const observer = new MutationObserver(() => {
            const frame = document.getElementById('printJS');
            if (!frame) return;
            observer.disconnect();
            frame.addEventListener('load', () => {
                frame.contentWindow.print = () => {
                    window.printedReceiptHtml = frame.contentDocument.documentElement.outerHTML;
                    clearTimeout(timer);
                    resolve();
                };
            }, {capture:true,once:true});
        });
        observer.observe(document.body,{childList:true});
        printJS({printable:document.querySelector('.receipt-print-document'),type:'html',scanStyles:false,
            style:document.querySelector('style').textContent});
    }));
    const printedHtml = await page.evaluate(() => window.printedReceiptHtml);
    await page.setContent(printedHtml);
    fs.mkdirSync('temp/receipt-print-tests',{recursive:true});
    for(const format of ['A4','A5','Letter']) {
        await page.evaluate(({helper,paper}) => {
            const style = new Function(helper + ';return createReceiptPrintStyle')();
            document.querySelector('style').textContent = style(paper);
        }, {helper,paper: format === 'A4' ? 'a4' : 'small'});
        for(const scale of [0.8,1,1.1,1.5]) {
            await page.pdf({path:`temp/receipt-print-tests/${format}-${scale}.pdf`,format,scale,printBackground:true});
        }
    }
    await page.evaluate(helper => {
        document.querySelector('style').textContent = new Function(helper + ';return createReceiptPrintStyle')()('a4');
    },helper);
    await page.pdf({path:'temp/receipt-print-tests/A4-landscape.pdf',format:'A4',landscape:true,printBackground:true});
    await browser.close();
    console.log('Generated A4, A5, Letter at 80%, 100%, 110%, 150%, plus A4 landscape, including an oversized bill');
})().catch(error => {console.error(error);process.exit(1)});
