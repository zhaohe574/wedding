/**
 * 视频选择统一封装（P0 工作台写能力新增；与 useChooseImage 同一套失败口径）。
 *
 * 消费方：pkg-staff 的 dynamic-publish / work-edit（发布动态、编辑作品的视频上传）。
 * 复用度正好 2 处，达到抽 composable 的下限；只做「选 + 校验」，不负责上传
 * （上传走 src/api/app.ts 的 uploadVideo，保持选/传分离，两页错误提示口径各自可控）。
 *
 * 失败四类（对齐 useChooseImage 的三分支 + 视频特有的超限校验）：
 *   ① 用户取消（errMsg 含 cancel）→ 返回 null，静默（取消不是错误）；
 *   ② 隐私未授权 → 提示后返回 null（不做重放，理由见 useChooseImage 注释）；
 *   ③ 权限被拒 → 弹 modal 引导去设置后返回 null；
 *   ④ 时长/体积超限 → toast 具体限制后返回 null（用户能立刻理解为什么选不了）。
 *
 * 返回值：成功返回文件信息对象，任何失败都返回 null —— 调用方不用判 undefined，
 * 也不会因为 await 抛出而中断后续逻辑。
 */
import { toast } from '@/composables/useToast'
import { isPrivacyError } from '@/composables/usePrivacyGuard'
import { useAppStore } from '@/stores/app'

export interface ChooseVideoOptions {
    /** 最长时长（秒），超限直接拦。默认 60，与发布页给用户的提示文案一致 */
    maxDuration?: number
    /** 最大体积（MB），超限直接拦。默认 100 */
    maxSizeMB?: number
    /** 来源，默认相册 + 相机 */
    sourceType?: Array<'album' | 'camera'>
}

export interface PickedVideo {
    tempFilePath: string
    /** 秒 */
    duration: number
    /** 字节 */
    size: number
}

/** 权限被拒的特征串（与 useChooseImage 保持同源，iOS/Android/开发者工具 errMsg 都覆盖） */
const AUTH_DENY_RE = /auth\s*deny|auth\s*denied|authorize\s*no\s*response|permission|没有权限/i

export async function chooseVideo(options: ChooseVideoOptions = {}): Promise<PickedVideo | null> {
    const maxDuration = options.maxDuration ?? 60
    const maxSizeMB = options.maxSizeMB ?? 100

    return new Promise((resolve) => {
        uni.chooseVideo({
            sourceType: options.sourceType ?? ['album', 'camera'],
            // 压缩：选相册里的视频时微信会走压缩通道，体积更容易进 100MB 线
            compressed: true,
            maxDuration,
            success: (res: any) => {
                const tempFilePath = String(res?.tempFilePath || '')
                if (!tempFilePath) {
                    toast.error('视频选择失败，请重试')
                    resolve(null)
                    return
                }

                const duration = Number(res?.duration || 0)
                const size = Number(res?.size || 0)

                // 超限校验放在 success 里而不是 fail：超限不是系统错误，
                // 用明确的限制数字提示，用户才知道该删视频还是换短片
                if (duration > maxDuration) {
                    toast.warning(`视频不能超过 ${maxDuration} 秒，请剪辑后再上传`)
                    resolve(null)
                    return
                }
                if (size > maxSizeMB * 1024 * 1024) {
                    toast.warning(`视频不能超过 ${maxSizeMB}MB，请压缩后再上传`)
                    resolve(null)
                    return
                }

                resolve({ tempFilePath, duration, size })
            },
            fail: (err: any) => {
                const msg = String(err?.errMsg || err?.message || '')
                if (isPrivacyError(msg)) {
                    toast.info('请先同意隐私政策后再试')
                    resolve(null)
                    return
                }
                if (/cancel/i.test(msg)) {
                    resolve(null)
                    return
                }
                if (AUTH_DENY_RE.test(msg)) {
                    uni.showModal({
                        title: '需要访问相册',
                        // 应用名取自后台品牌配置，与用户看到的 App 名保持一致
                        content: `请在设置中允许「${useAppStore().brand.name}」访问相册，否则无法上传视频`,
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
                    resolve(null)
                    return
                }
                toast.error('视频选择失败，请重试')
                resolve(null)
            }
        })
    })
}
