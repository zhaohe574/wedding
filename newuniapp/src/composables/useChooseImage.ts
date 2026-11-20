/**
 * 图片选择统一封装（合规 S1）。
 *
 * 背景：全站 6 处 `uni.chooseImage` 原来**都没有 `fail` 分支** —— 用户一键拒绝相册权限后，
 * 点「上传图片」没有任何 toast、没有引导，按钮像坏的，审核判「按钮点击无响应」。
 *
 * 与隐私授权的关系：**不做重放**。隐私接口被拦时是「挂起等 resolve」，不是失败 ——
 * 用户同意后微信会让原调用继续，这里再包一层重放就会变成"选两次图"。
 *
 * 失败分三类（除隐私外）：
 *   ① 用户取消（errMsg 含 cancel）→ 静默返回，不打扰（取消不是错误）；
 *   ② 权限被拒（auth deny / authorize no response / auth denied）→ 弹 modal 引导去设置；
 *      **这是唯一能把用户救回来的路径**，不引导他永远不会再点第二次；
 *   ③ 其它 → 错误提示，可重试。
 *
 * 返回值统一 `string[]`（tempFilePaths），任何失败都返回空数组 —— 调用方不用判 undefined，
 * 也不会因为 `await` 抛出而中断后续逻辑。
 */
import { toast } from '@/composables/useToast'
import { isPrivacyError } from '@/composables/usePrivacyGuard'
import { useAppStore } from '@/stores/app'

export interface ChooseImageOptions {
    /** 最多可选张数，默认 1。**各页保留自己的上限**，不要统一 */
    count?: number
    /** 压缩策略，默认 compressed */
    sizeType?: Array<'original' | 'compressed'>
    /** 来源，默认相册 + 相机 */
    sourceType?: Array<'album' | 'camera'>
}

/** 权限被拒的特征串（iOS / Android / 开发者工具的 errMsg 不完全一致，都覆盖） */
const AUTH_DENY_RE = /auth\s*deny|auth\s*denied|authorize\s*no\s*response|permission|没有权限/i

function pick(options: ChooseImageOptions): Promise<string[]> {
    return new Promise((resolve) => {
        uni.chooseImage({
            count: Math.max(1, options.count ?? 1),
            sizeType: options.sizeType ?? ['compressed'],
            sourceType: options.sourceType ?? ['album', 'camera'],
            success: (res: any) => {
                resolve((res?.tempFilePaths as string[]) || [])
            },
            fail: (err: any) => {
                const msg = String(err?.errMsg || err?.message || '')
                // 隐私未授权导致失败时**不做重放**（曾评估 withPrivacy 包装器，已否决，别再"补"回来）：
                //   ① 正常路径：用户点「同意」后微信会让被挂起的那次调用自动继续，
                //      重放等于让用户选两次图；
                //   ② 异常路径（errno 112：后台未声明隐私类型）是配置问题，重放一百次还是 112；
                //   ③ 重放意味着在数据路径里同步等用户操作，必须再配可见性守卫与超时，
                //      多一层守卫就多一处可能写错的地方。
                // → 这里的处理就是「提示 + 返回空数组」，有界收尾，不会悬挂。
                if (isPrivacyError(msg)) {
                    toast.info('请先同意隐私政策后再试')
                    resolve([])
                    return
                }
                if (/cancel/i.test(msg)) {
                    // 用户主动取消，静默（不弹 toast 打扰，也不当错误）
                    resolve([])
                    return
                }
                if (AUTH_DENY_RE.test(msg)) {
                    uni.showModal({
                        title: '需要访问相册',
                        // 应用名取自后台品牌配置：设置页里显示的名字必须和用户看到的 App 名一致
                        content: `请在设置中允许「${useAppStore().brand.name}」访问相册，否则无法上传图片`,
                        confirmText: '去设置',
                        cancelText: '暂不',
                        success: (r) => {
                            if (r.confirm) {
                                uni.openSetting({
                                    fail: () => toast.info('请在系统设置中开启相册权限')
                                })
                            }
                        }
                    })
                    resolve([])
                    return
                }
                toast.error('图片选择失败，请重试')
                resolve([])
            }
        })
    })
}

/**
 * 选图。任何失败都返回空数组，调用方不用判 undefined、也不会因 await 抛出而中断。
 */
export async function chooseImages(options: ChooseImageOptions = {}): Promise<string[]> {
    return pick(options)
}
