import path from 'path'
import fsExtra from 'fs-extra'
import dotenv from 'dotenv'
const { existsSync, remove, copy } = fsExtra
const cwd = process.cwd()
dotenv.config()

// 部署形态由构建命令决定，而不是由 NUXT_SSR 决定：
// - `npm run build`      → nuxt generate，产物为 .output/public 下的静态文件（SSR 预渲染同样落在这里）
// - `npm run build:ssr`  → nuxt build，产物为 .output 下的 Node 服务端包
// 因此这里改为读取显式的 --ssr 参数 / BUILD_TARGET 环境变量，避免开启 NUXT_SSR 后
// 静态发布链路被误判成服务端发布。
const buildTarget = process.env.BUILD_TARGET || (process.argv.includes('--ssr') ? 'server' : 'static')
const isSSR = buildTarget === 'server'
//打包发布路径，可能会覆盖重要文件，请谨慎改动
const releaseRelativePath = '../server/public/pc'
// 静态发布：nuxt generate 的产物目录由 nuxt.config.ts 的 nitro.output.publicDir 指定为 dist
const distRelativePath = isSSR ? '' : './dist'
const distPath = path.resolve(cwd, distRelativePath)
const distSSRFilter = [
    '.output',
    'public',
    'static',
    'package.json',
    'package-lock.json'
]
const distNotSSRFilter = ['']
const distFilter = joinPath(isSSR ? distSSRFilter : distNotSSRFilter)

function joinPath(target, res = []) {
    target.forEach((src) => {
        res.push(path.join(distPath, src))
    })
    return res
}

const releasePath = path.resolve(cwd, releaseRelativePath)

async function build() {
    if (existsSync(releasePath)) {
        await remove(releasePath)
    }
    console.log(`文件正在复制 ==> ${releaseRelativePath}`)
    try {
        await copyFile(distPath, releasePath)
    } catch (error) {
        console.error(`\n ${error}`)
        process.exitCode = 1
        throw error
    }
    console.log(`文件已复制 ==> ${releaseRelativePath}`)
}

function copyFile(sourceDir, targetDir) {
    return new Promise((resolve, reject) => {
        copy(
            sourceDir,
            targetDir,
            {
                filter(src) {
                    if (src === distPath) return true
                    return distFilter.some((item) => src.includes(item))
                }
            },
            (err) => {
                if (err) {
                    reject(err)
                } else {
                    resolve()
                }
            }
        )
    })
}

build()
