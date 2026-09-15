const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs'), path = require('node:path'), vm = require('node:vm'), ts = require('typescript')
const vue = fs.readFileSync(path.join(__dirname, '../src/packages/pages/staff_order_create/staff_order_create.vue'), 'utf8')
const source = vue.match(/<script setup lang="ts">([\s\S]+?)<\/script>/)[1].replace(/^import .*$/gm, '')
const compiled = ts.transpileModule(source + '\nglobalThis.api = { next, form, quote, step, receipt, withReceipt, leave, changePayment, busy, openRegion, changeRegion, changeDate, regionOpen, regionTree, regionError };', { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText
const flush = () => new Promise(resolve => setImmediate(resolve))
function fixture() {
    const hooks = {}, calls = [], errors = [], user = { token: 'staff-token' }
    let proceed = true, submit, preview, regions = async () => [{ province_code: '330000', cities: [] }]
    const quote = { quote_hash: 'quote-1', pay_amount: 1000, deposit_amount: 300, balance_amount: 700 }
    const ctx = { Date, Math, Object, Number, String, getCurrentPages: () => [{}],
        ref: value => ({ value }), reactive: value => value, computed: fn => ({ get value() { return fn() } }),
        onLoad: fn => { hooks.load = fn }, onShow: fn => { hooks.show = fn }, onHide: fn => { hooks.hide = fn }, onUnload: fn => { hooks.unload = fn },
        loadServiceRegionSelection: () => ({ city_code: '330100', district_code: '330102' }), formatServiceRegionText: () => '浙江省杭州市上城区', hasServiceRegion: x => !!x.city_code,
        ensureStaffCenterAccess: async () => true, useUserStore: () => user, newStaffSubmitKey: () => 'staff-same-submit-key',
        showError: value => errors.push(value), wx: { enableAlertBeforeUnload() {}, disableAlertBeforeUnload() {} },
        staffOrderOptions: async () => ({ staff_name: '测试人员', packages: [{ id: 1, name: '本人套餐' }], addons: [] }),
        staffOrderCustomers: async () => [],
        getServiceRegionTree: () => regions(),
        staffOrderPreview: async () => preview ? preview() : { ...quote },
        remindBeforeOaAction: async () => { calls.push('reminder'); return proceed },
        staffOrderCreate: async payload => { calls.push(payload); return submit ? submit() : { order_id: 71 } },
        uni: { redirectTo: data => calls.push(data.url), navigateBack: data => calls.push(data), showModal: data => { hooks.modal = data } }
    }
    vm.runInNewContext(compiled, ctx)
    hooks.load()
    Object.assign(ctx.api.form, { contact_name: '联系人', contact_mobile: '13800138000', service_date: '2026-10-20', service_address: '测试酒店', main_package_id: 1 })
    ctx.api.step.value = 2; ctx.api.quote.value = { ...quote }
    return { api: ctx.api, hooks, calls, errors, user, quote, regions: fn => { regions = fn }, pause: () => { proceed = false }, submit: fn => { submit = fn }, preview: fn => { preview = fn } }
}
test('查看绑定方法暂停提交，保留原表单与录单标识', async () => {
    const f = fixture(); f.pause(); await f.api.next()
    assert.deepEqual(f.calls, ['reminder']); assert.equal(f.api.form.contact_name, '联系人'); assert.equal(f.api.step.value, 2)
})
test('提醒跳过后提交；重复点击不重复建单，成功进入本人订单详情', async () => {
    const f = fixture(); let resolve
    f.submit(() => new Promise(done => { resolve = done }))
    const first = f.api.next(); await flush(); await f.api.next()
    assert.equal(f.calls.filter(x => typeof x === 'object').length, 1)
    resolve({ order_id: 71 }); await first
    assert.ok(f.calls.includes('/packages/pages/staff_order_detail/staff_order_detail?id=71'))
})
test('报价变化只更新摘要，再次确认前不提交；无效表单不弹提醒', async () => {
    const f = fixture(); f.quote.quote_hash = 'quote-new'; f.quote.pay_amount = 1200
    await f.api.next(); assert.equal(f.calls.length, 0); assert.equal(f.api.quote.value.pay_amount, 1200)
    f.api.form.contact_mobile = ''; await f.api.next(); assert.equal(f.calls.length, 0)
})
test('查询过程中离页或换号，不继续旧页面提交', async () => {
    for (const reason of ['hide', 'account']) {
        const f = fixture(); let resolve; f.preview(() => new Promise(done => { resolve = done }))
        const pending = f.api.next(); await flush()
        if (reason === 'hide') f.hooks.hide(); else f.user.token = 'another-account'
        resolve({ ...f.quote }); await pending; assert.equal(f.calls.length, 0)
    }
})
test('网络失败重试使用同一提交标识；未提交离开须确认', async () => {
    const f = fixture(); f.submit(() => { throw new Error('网络中断') }); await f.api.next(); await f.api.next()
    const payloads = f.calls.filter(x => typeof x === 'object')
    assert.equal(payloads[0].submit_key, payloads[1].submit_key)
    f.api.leave(); assert.equal(f.hooks.modal.title, '离开录单页面？')
    const before = f.calls.length; f.hooks.modal.success({ confirm: false }); assert.equal(f.calls.length, before)
})

test('地区加载不修改表单，确认回填完整地区并清理旧报价，日期确认同步失效报价', async () => {
    const f = fixture(), before = { ...f.api.form }
    await f.api.openRegion()
    assert.equal(f.api.regionOpen.value, true)
    assert.equal(JSON.stringify(f.api.form), JSON.stringify(before))
    const region = { province_code: '330000', province_name: '浙江省', city_code: '330100', city_name: '杭州市', district_code: '330106', district_name: '西湖区' }
    f.api.changeRegion(region)
    for (const key of Object.keys(region)) assert.equal(f.api.form[key], region[key])
    assert.equal(f.api.regionOpen.value, false)
    assert.equal(f.api.quote.value, null)
    assert.equal(f.api.form.main_package_id, 0)
    f.api.quote.value = { ...f.quote }; f.api.changeDate('2026-11-20')
    assert.equal(f.api.form.service_date, '2026-11-20')
    assert.equal(f.api.quote.value, null)
})

test('地区失败及空列表允许重试，不打开空选择器', async () => {
    const f = fixture()
    f.regions(async () => { throw new Error('网络失败') }); await f.api.openRegion()
    assert.equal(f.api.regionOpen.value, false)
    assert.match(f.api.regionError.value, /重试/)
    f.regions(async () => []); await f.api.openRegion()
    assert.match(f.api.regionError.value, /暂无/)
    f.regions(async () => [{ province_code: '330000' }]); await f.api.openRegion()
    assert.equal(f.api.regionOpen.value, true)
    assert.equal(f.api.regionError.value, '')
})

test('地区请求期间离页，丢弃旧结果并允许返回后重试', async () => {
    const f = fixture(); let resolve
    f.regions(() => new Promise(done => { resolve = done }))
    const pending = f.api.openRegion(); f.hooks.hide()
    resolve([{ province_code: '330000' }]); await pending
    assert.equal(f.api.regionOpen.value, false)
    assert.equal(f.api.regionTree.value.length, 0)
    f.hooks.show(); f.regions(async () => [{ province_code: '330000' }]); await f.api.openRegion()
    assert.equal(f.api.regionOpen.value, true)
})
