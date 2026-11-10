from pathlib import Path
root = Path('D:/wechatApp/uniapp_community')
page = root / 'pages/Community_menu/index.vue'
text = page.read_text(encoding='utf-8-sig')
marker = '\n\t\t\t</view>\n\t\t</view>\n\t\t<tabbar'
card = '''
                <view class="mini-box" @tap="goVisitorRegister">
                    <view class="mini-left">
                        <view class="mini-title">{{visitorRegisterCount}}</view>
                        <view class="mini-text">访客登记</view>
                    </view>
                    <view class="approval-icon-bg">
                        <u-icon name="account-fill" color="#2681f3" size="46"></u-icon>
                    </view>
                </view>'''
assert marker in text
text = text.replace(marker, card + marker, 1)
text = text.replace('_this.getApprovalTodoCount();', '_this.getApprovalTodoCount();\n            _this.getVisitorRegisterCount();', 1)
text = text.replace('approvalTodoCount: 0,', "approvalTodoCount: 0,\n                visitorRegisterCount: '-',", 1)
text = text.replace('methods: {', '''methods: {
            getVisitorRegisterCount() {
                const ticket = uni.getStorageSync('community_ticket')
                this.visitorRegisterCount = '-'
                common.post('manage_api.v1.VisitorRegister/records', { page: 1, page_size: 1 }, data => {
                    if (ticket === uni.getStorageSync('community_ticket')) this.visitorRegisterCount = Number(data.total || 0)
                }, this, () => { this.visitorRegisterCount = '-' })
            },
            goVisitorRegister() {
                uni.navigateTo({ url: '/pages/Community/visitorRegisterNew/list' })
            },''', 1)
page.write_text(text, encoding='utf-8')
pages = root / 'pages.json'
text = pages.read_text(encoding='utf-8-sig')
text = text.replace('"pages": [', '''"pages": [
        {
            "path": "pages/Community/visitorRegisterNew/list",
            "style": {
                "navigationBarTitleText": "访客登记",
                "enablePullDownRefresh": true,
                "onReachBottomDistance": 80
            }
        },''', 1)
# Preserve original login launch page; insert new route immediately before the community homepage.
start = text.index('        {\n            "path": "pages/Community/visitorRegisterNew/list"')
end = text.index('        },', start) + len('        },\n')
route = text[start:end]
text = text[:start] + text[end:]
marker = '\t\t/*社区*/'
assert marker in text
text = text.replace(marker, route + marker, 1)
pages.write_text(text, encoding='utf-8')
target = root / 'pages/Community/visitorRegisterNew/list.vue'
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text('''<template>
  <view class="visitor-page">
    <view class="record-card" v-for="item in records" :key="item.registration_no">
      <view class="record-row"><text class="label">昵称</text><text class="value nickname">{{ item.nickname }}</text></view>
      <view class="record-row"><text class="label">手机号</text><text class="value">{{ item.phone }}</text></view>
      <view class="record-row"><text class="label">登记时间</text><text class="value">{{ item.registration_time }}</text></view>
    </view>
    <view v-if="error" class="state">
      <view>{{ error }}</view>
      <button class="retry" size="mini" @tap="fetchRecords(records.length === 0)">重新加载</button>
    </view>
    <view v-else-if="!loading && records.length === 0" class="state">
      <image class="empty-image" src="/static/images/no_data.png" mode="widthFix"></image>
      <view>暂无访客登记记录</view>
    </view>
    <view v-if="loading" class="state">加载中…</view>
    <view v-else-if="!error && records.length && !hasMore" class="state">没有更多了</view>
  </view>
</template>
<script>
const common = require('../../../utils/common.js')
export default {
  data() {
    return { records: [], page: 0, hasMore: true, loading: false, error: '', requestVersion: 0 }
  },
  onShow() { this.fetchRecords(true) },
  onPullDownRefresh() { this.fetchRecords(true) },
  onReachBottom() { this.fetchRecords(false) },
  onUnload() { this.requestVersion++ },
  methods: {
    fetchRecords(reset) {
      if (!reset && (this.loading || !this.hasMore)) return
      if (reset) { this.records = []; this.page = 0; this.hasMore = true }
      const version = ++this.requestVersion
      const ticket = uni.getStorageSync('community_ticket')
      const page = reset ? 1 : this.page + 1
      this.loading = true
      this.error = ''
      common.post('manage_api.v1.VisitorRegister/records', { page, page_size: 20 }, data => {
        if (version !== this.requestVersion || ticket !== uni.getStorageSync('community_ticket')) return
        this.records = reset ? (data.list || []) : this.records.concat(data.list || [])
        this.page = page
        this.hasMore = !!data.has_more
        this.loading = false
        uni.stopPullDownRefresh()
      }, this, err => {
        if (version !== this.requestVersion || ticket !== uni.getStorageSync('community_ticket')) return
        this.loading = false
        this.error = (err && err.data && err.data.msg) || '加载失败，请检查网络后重试'
        uni.stopPullDownRefresh()
      })
    }
  }
}
</script>
<style scoped>
.visitor-page { min-height: 100vh; background: #f5f5f5; padding: 24rpx 30rpx; box-sizing: border-box; }
.record-card { padding: 20rpx 28rpx; margin-bottom: 24rpx; background: #fff; border-radius: 15rpx; }
.record-row { display: flex; align-items: flex-start; padding: 14rpx 0; font-size: 28rpx; line-height: 42rpx; }
.label { color: #999; width: 140rpx; flex-shrink: 0; }
.value { color: #333; flex: 1; text-align: right; word-break: break-all; }
.nickname { font-weight: 600; }
.state { padding: 40rpx 10rpx; text-align: center; color: #999; font-size: 26rpx; }
.empty-image { width: 260rpx; margin: 90rpx auto 24rpx; display: block; }
.retry { margin-top: 24rpx; color: #2681f3; background: #eaf3ff; }
</style>
''', encoding='utf-8')
