const WechatCodeProtectionCompatPlugin = require('./build/WechatCodeProtectionCompatPlugin')

module.exports = {
  configureWebpack: {
    plugins: process.env.UNI_PLATFORM === 'mp-weixin'
      ? [new WechatCodeProtectionCompatPlugin()]
      : []
  }
}
