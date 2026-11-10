from pathlib import Path

root = Path('D:/wechatApp/village/pages/visitorRegister')
path = root / 'registration.js'
text = path.read_text(encoding='utf-8-sig')
text = text.replace('// or forcing WeChat login for these two public endpoints.', '// Identity is resolved by the server using wx.login, never a client-supplied user ID.')
text = text.replace('registrationTheme: {}, villageId:', "authToken: '', nickname: '', phoneAuthorizing: false, prefilled: false, registrationTheme: {}, villageId:")
text = text.replace("this.registrationTheme = result.theme || {}; this.available = result.status === 'published'; this.statusMessage = result.message", """this.registrationTheme = result.theme || {}; this.statusMessage = result.message
        if (result.status === 'published') {
          const code = await new Promise((resolve, reject) => uni.login({
            provider: 'weixin',
            success: result => result.code ? resolve(result.code) : reject(new Error('微信登录失败，请重试')),
            fail: () => reject(new Error('微信登录失败，请重试'))
          }))
          const identity = await request('login', { village_id: this.villageId, form_type: this.formType, code })
          this.authToken = identity.auth_token
          this.nickname = identity.nickname || ''
          // Only fill known non-time fields, without replacing unsaved user input on retry.
          const fields = this.formType === 'visitor'
            ? ['visitor_name', 'organization_or_id', 'visit_reason', 'host_name_room', 'remark']
            : ['plate_number', 'destination']
          fields.forEach(key => { if (!this.form[key] && identity.prefill && identity.prefill[key]) this.form[key] = identity.prefill[key] })
          this.form.phone = identity.phone || ''
          this.prefilled = !!(identity.prefill && Object.keys(identity.prefill).length)
          this.available = true
        }""")
text = text.replace("consentChanged (event)", """nicknameChanged (event) {
      const previous = this.nickname
      this.nickname = (event.detail.value || '').trim()
      if (this.formType === 'visitor' && (!this.form.visitor_name || this.form.visitor_name === previous)) this.form.visitor_name = this.nickname
    },
    async authorizePhone (event) {
      if (this.phoneAuthorizing) return
      if (!this.consent) { uni.showToast({ title: '请先阅读并同意个人信息收集告知', icon: 'none' }); return }
      if (!event.detail || !event.detail.code) {
        uni.showToast({ title: '未获取手机号，请点击按钮重新授权', icon: 'none' }); return
      }
      this.phoneAuthorizing = true
      try {
        const result = await request('authorizePhone', { village_id: this.villageId, form_type: this.formType,
          auth_token: this.authToken, code: event.detail.code, privacy_consent: 1 })
        this.authToken = result.auth_token
        this.form.phone = result.phone
      } catch (e) { uni.showModal({ title: '授权失败', content: e.message, showCancel: false }) }
      finally { this.phoneAuthorizing = false }
    },
    consentChanged (event)""")
text = text.replace("const error = this.validate()", """if (!this.authToken || !this.nickname.trim() || !this.form.phone) {
        uni.showToast({ title: !this.authToken ? '请重新进入页面完成微信登录' : !this.nickname.trim() ? '请填写微信昵称' : '请授权获取微信手机号', icon: 'none' }); return
      }
      const error = this.validate()""")
text = text.replace('const payload = JSON.stringify(data)', 'const payload = JSON.stringify({ data, nickname: this.nickname.trim() })')
text = text.replace("data, request_id: this.requestId, privacy_consent: 1", "data, nickname: this.nickname.trim(), auth_token: this.authToken, request_id: this.requestId, privacy_consent: 1")
path.write_text(text, encoding='utf-8')
for name in ['person', 'vehicle']:
    path = root / (name + '.vue')
    text = path.read_text(encoding='utf-8-sig')
    text = text.replace('<view v-else>', '''<view v-else>
      <view v-if="prefilled" class="hint">已填入您今天最近一次登记的信息，请确认本次来访内容。</view>
      <view class="field"><view class="label"><text class="required">*</text>微信昵称</view><input type="nickname" :value="nickname" @input="nicknameChanged" @blur="nicknameChanged" maxlength="60" placeholder="点击选择微信昵称或填写昵称"></view>''', 1)
    text = text.replace('<input v-model="form.phone" type="number" maxlength="11" placeholder="请输入手机号">', '<input :value="form.phone" disabled placeholder="同意下方告知后，点击授权手机号">')
    text = text.replace('我们收集您填写的', '我们使用微信身份标识关联本人当天登记记录，并收集您确认的昵称、授权的手机号及您填写的')
    marker = '<button class="submit"'
    text = text.replace(marker, '''<button open-type="getPhoneNumber" @getphonenumber="authorizePhone" :disabled="!consent || phoneAuthorizing || submitting" :loading="phoneAuthorizing">{{ form.phone ? '重新授权手机号' : '授权微信手机号并填入' }}</button>
      <view class="hint">昵称将填入访客姓名（人员登记），请核对并按实际来访人修改。每次扫码的来访时间重新生成。</view>
      <button class="submit"''')
    text = text.replace(':disabled="submitting" @click="submitRegistration"', ':disabled="submitting || phoneAuthorizing" @click="submitRegistration"')
    if name == 'vehicle':
        text = text.replace('昵称将填入访客姓名（人员登记），请核对并按实际来访人修改。每次扫码的来访时间重新生成。', '每次扫码重新生成进入时间，离开时间请按本次实际情况填写。')
    path.write_text(text, encoding='utf-8')
