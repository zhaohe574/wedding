const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const ts = require('typescript')

// 使用已安装路由库的真实守卫调度实现，防止同步守卫未放行导致全局跳转悬挂。
const library = fs.readFileSync(path.join(__dirname, '../node_modules/uniapp-router-next/js_sdk/index.mjs'), 'utf8')
const dispatch = library.slice(library.indexOf('function runGuardQueue('), library.indexOf('function getRouteByVueVm('))
const router = fs.readFileSync(path.join(__dirname, '../src/router/index.ts'), 'utf8')
const invitationGuard = router.match(/router\.beforeEach\(\(to\) => \{[\s\S]*?\}\)/)[0]

for (const route of ['/pages/user/user', '/pages/order/order', '/pages/oa_subscribe/oa_subscribe']) {
    test(`邀请守卫必须放行 ${route} 并继续执行后续守卫`, async () => {
        const guards = [], captured = [], visited = []
        const context = {
            router: { beforeEach: guard => guards.push(guard) },
            captureOaInvitation: (path, query) => captured.push({ path, query }),
            isRouteLocation: value => typeof value === 'string' || (value && typeof value === 'object'),
            createRouterError: () => new Error('跳转中止')
        }
        vm.runInNewContext(dispatch + ts.transpileModule(invitationGuard, { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText +
            '\nglobalThis.execute = (guards, to) => runGuardQueue(guards.map(guard => guardToPromiseFn(guard, to, {})));', context)
        guards.push(async () => { visited.push(route) })
        const query = route.includes('oa_subscribe') ? { invitation: 'd'.repeat(64) } : {}
        let timer
        try {
            await Promise.race([
                context.execute(guards, { path: route, query }),
                new Promise((_, reject) => { timer = setTimeout(() => reject(new Error('守卫没有放行，页面跳转被挂起')), 300) })
            ])
        } finally { clearTimeout(timer) }
        assert.equal(captured[0].path, route)
        assert.deepEqual(captured[0].query, query)
        assert.deepEqual(visited, [route])
    })
}
