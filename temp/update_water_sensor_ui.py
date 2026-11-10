from pathlib import Path
p=Path('D:/projectView/platform_dev/src/views/community/village/waterAlarm/index.vue')
s=p.read_text(encoding='utf-8')
s=s.replace('<a-button type="primary" icon="plus" :disabled="!canAdd || busy || loading"', '<a-button v-if="activeTab === \'devices\'" type="primary" icon="plus" :disabled="!canAdd || busy || loading"',1)
s=s.replace('<a-alert message="报警持续循环播放', '<a-alert v-if="activeTab === \'devices\'" message="报警持续循环播放',1)
s=s.replace('<a-tabs default-active-key="devices"><a-tab-pane key="devices" tab="设备管理" /></a-tabs>', '''<a-tabs v-model="activeTab" @change="changeTab"><a-tab-pane key="sensors" tab="传感器管理" /><a-tab-pane key="devices" tab="喇叭管理" /></a-tabs>
      <div v-if="activeTab === 'sensors'">
        <div class="toolbar"><span>共 {{ sensorRows.length }} 个传感器</span><div><a-button :loading="sensorLoading" @click="loadSensors">刷新</a-button> <a-button type="primary" icon="plus" @click="openSensor()">添加传感器</a-button></div></div>
        <a-table row-key="sensor_id" :columns="sensorColumns" :data-source="sensorRows" :loading="sensorLoading" :pagination="{ pageSize: 10 }">
          <template slot="sensorState" slot-scope="text, row"><a-tag :color="Number(row.last_state) === 1 ? 'red' : 'default'">{{ Number(row.last_state) === 1 ? '漏水' : (Number(row.last_state) === 0 ? '正常' : '未上报') }}</a-tag></template>
          <template slot="sensorTime" slot-scope="text">{{ time(text) }}</template>
          <template slot="sensorEdit" slot-scope="text, row"><a-button type="link" @click="openSensor(row)">编辑报警词</a-button></template>
        </a-table>
        <p class="secondary">状态为最近一次上报结果。请将生成的ID配置到传感器；漏水后循环播报报警词并闪光，全部传感器恢复正常后停止。</p>
      </div>
      <div v-show="activeTab === 'devices'">''')
s=s.replace('    </a-card>', '      </div>\n    </a-card>',1)
s=s.replace('    <a-modal v-model="editorVisible"', '''    <a-modal v-model="sensorVisible" :title="sensorEditing ? '编辑报警词' : '添加传感器'" :confirm-loading="sensorSaving" :mask-closable="false" @ok="saveSensor">
      <a-form layout="vertical">
        <a-form-item label="传感器ID" required><a-input :value="sensorId" read-only placeholder="点击右侧按钮生成"><a-button slot="addonAfter" type="link" :disabled="sensorEditing || sensorSaving" :loading="sensorGenerating" @click="generateSensor">生成随机ID</a-button></a-input></a-form-item>
        <a-form-item label="报警词" required><a-textarea v-model="sensorText" :rows="3" :max-length="199" placeholder="例如：地下车库一号水浸传感器检测到漏水，请及时处理。" /><span :class="{ 'error-text': sensorBytes > 199 }">{{ sensorBytes }} / 199 字节（中文通常每字3字节）</span></a-form-item>
      </a-form>
    </a-modal>
    <a-modal v-model="editorVisible"''',1)
s=s.replace('      rows: [],', '''      activeTab: 'sensors',
      sensorRows: [], sensorLoading: false, sensorVisible: false, sensorSaving: false, sensorGenerating: false, sensorEditing: false, sensorId: '', sensorText: '',
      sensorColumns: [
        { title: '传感器ID', dataIndex: 'sensor_id' },
        { title: '报警词', dataIndex: 'alarm_text' },
        { title: '最近状态', dataIndex: 'last_state', scopedSlots: { customRender: 'sensorState' } },
        { title: '最近上报', dataIndex: 'last_seen', scopedSlots: { customRender: 'sensorTime' } },
        { title: '操作', key: 'edit', scopedSlots: { customRender: 'sensorEdit' } }
      ],
      rows: [],''',1)
s=s.replace('  computed: {', "  computed: {\n    sensorBytes () { return new Blob([this.sensorText.trim()]).size },",1)
s=s.replace('  methods: {', '''  methods: {
    changeTab (tab) { if (tab === 'sensors') this.loadSensors() },
    async loadSensors () {
      if (this.sensorLoading) return
      this.sensorLoading = true
      try { this.sensorRows = (await this.call('sensors')).list } catch (e) { this.$message.error('传感器列表加载失败') } finally { this.sensorLoading = false }
    },
    openSensor (row) { this.sensorEditing = !!row; this.sensorId = row ? row.sensor_id : ''; this.sensorText = row ? row.alarm_text : ''; this.sensorVisible = true },
    async generateSensor () {
      if (this.sensorGenerating || this.sensorEditing) return
      this.sensorGenerating = true
      try { this.sensorId = (await this.call('generateSensor')).sensor_id } catch (e) { this.$message.error('ID生成失败，请重试') } finally { this.sensorGenerating = false }
    },
    async saveSensor () {
      if (this.sensorSaving || this.sensorGenerating) return
      if (!this.sensorId || !this.sensorText.trim() || this.sensorBytes > 199) { this.$message.warning('请生成ID并填写199字节以内的报警词'); return }
      this.sensorSaving = true
      try { this.sensorRows = (await this.call('saveSensor', { sensor_id: this.sensorId, alarm_text: this.sensorText.trim() })).list; this.sensorVisible = false; this.$message.success('传感器已保存') } catch (e) { this.$message.error('保存失败，请检查报警词或重新生成ID') } finally { this.sensorSaving = false }
    },''',1)
s=s.replace('      this.active = true\n', '      this.active = true\n      this.loadSensors()\n',1)
s=s.replace('        this.now = Date.now()\n', "        this.now = Date.now()\n        if (this.activeTab === 'sensors' && !this.sensorVisible && !document.hidden) this.loadSensors()\n",1)
p.write_text(s,encoding='utf-8')
p=Path('D:/projectView/platform_dev/src/api/community/waterAlarm.js')
s=p.read_text(encoding='utf-8').replace('export default {', "export default {\n  sensors: root + 'sensors',\n  generateSensor: root + 'generateSensor',\n  saveSensor: root + 'saveSensor',",1)
p.write_text(s,encoding='utf-8')
