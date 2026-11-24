<template>
    <view class="card-canvas-wrap" aria-hidden="true">
        <canvas
            id="staffCardCanvas"
            canvas-id="staffCardCanvas"
            type="2d"
            class="card-canvas"
        />
    </view>
</template>

<script setup lang="ts">
/**
 * 服务人员电子名片离屏 Canvas 2D 绘制组件。
 *
 * 仅在用户点击「生成并保存名片图」时按需绘制 750x1180 高清名片，
 * 全程使用 rgb/rgba 字面量以符合新端静态门禁。
 */
import { getCurrentInstance } from 'vue'
import type { StaffBusinessCard } from '@/api/staff'
import { useBrand } from '@/composables/useBrand'

const props = defineProps<{
    card: StaffBusinessCard | null
}>()

const instance = getCurrentInstance()
const { brand } = useBrand()

const CARD_W = 750
const CARD_H = 1180

const PALETTE = {
    inkDark: 'rgb(24, 22, 20)',
    inkPanel: 'rgb(33, 29, 25)',
    ivory: 'rgb(250, 246, 238)',
    surface: 'rgb(255, 253, 248)',
    surfaceMuted: 'rgb(243, 237, 225)',
    inkText: 'rgb(24, 22, 20)',
    inkSub: 'rgb(90, 85, 78)',
    inkMuted: 'rgb(107, 101, 92)',
    white: 'rgb(255, 255, 255)',
    whiteSoft: 'rgba(255, 255, 255, 0.72)',
    gold: 'rgb(198, 161, 91)',
    goldText: 'rgb(138, 100, 36)',
    goldSoft: 'rgb(217, 190, 130)',
    goldBorder: 'rgba(198, 161, 91, 0.45)',
    line: 'rgb(231, 224, 211)',
    success: 'rgb(72, 100, 75)',
    successBg: 'rgb(234, 240, 234)'
}

function drawRoundedRect(
    ctx: any,
    x: number,
    y: number,
    w: number,
    h: number,
    r: number
): void {
    const radius = Math.min(r, w / 2, h / 2)
    ctx.beginPath()
    ctx.moveTo(x + radius, y)
    ctx.arcTo(x + w, y, x + w, y + h, radius)
    ctx.arcTo(x + w, y + h, x, y + h, radius)
    ctx.arcTo(x, y + h, x, y, radius)
    ctx.arcTo(x, y, x + w, y, radius)
    ctx.closePath()
}

function drawWrappedText(
    ctx: any,
    text: string,
    x: number,
    y: number,
    maxWidth: number,
    lineHeight: number,
    maxLines: number
): number {
    const clean = String(text || '').replace(/\s+/g, ' ').trim()
    if (!clean) return y
    const chars = [...clean]
    let currentLine = ''
    let lineIndex = 0

    for (let i = 0; i < chars.length; i += 1) {
        const nextLine = currentLine + chars[i]
        const width = Number(ctx.measureText(nextLine)?.width || 0)
        if (width > maxWidth && currentLine) {
            lineIndex += 1
            if (lineIndex >= maxLines) {
                const truncated = currentLine.slice(0, Math.max(1, currentLine.length - 1)) + '…'
                ctx.fillText(truncated, x, y + (lineIndex - 1) * lineHeight)
                return y + lineIndex * lineHeight
            }
            ctx.fillText(currentLine, x, y + (lineIndex - 1) * lineHeight)
            currentLine = chars[i]
        } else {
            currentLine = nextLine
        }
    }

    if (currentLine && lineIndex < maxLines) {
        ctx.fillText(currentLine, x, y + lineIndex * lineHeight)
        return y + (lineIndex + 1) * lineHeight
    }
    return y + lineIndex * lineHeight
}

function resolveLocalImagePath(src: string): Promise<string> {
    return new Promise((resolve) => {
        const clean = String(src || '').trim()
        if (!clean) {
            resolve('')
            return
        }
        uni.getImageInfo({
            src: clean,
            success: (res) => resolve(res.path || ''),
            fail: () => resolve('')
        })
    })
}

async function loadCanvasImage(node: any, src: string): Promise<any | null> {
    const localPath = await resolveLocalImagePath(src)
    if (!localPath || typeof node?.createImage !== 'function') return null

    return new Promise((resolve) => {
        const timer = setTimeout(() => resolve(null), 5000)
        const img = node.createImage()
        img.onload = () => {
            clearTimeout(timer)
            resolve(img)
        }
        img.onerror = () => {
            clearTimeout(timer)
            resolve(null)
        }
        img.src = localPath
    })
}

function queryCanvasNode(): Promise<any | null> {
    return new Promise((resolve) => {
        const proxy = instance?.proxy
        if (!proxy) {
            resolve(null)
            return
        }
        uni.createSelectorQuery()
            .in(proxy)
            .select('#staffCardCanvas')
            .fields({ node: true, size: true } as any, (res: any) => {
                resolve(res?.node || null)
            })
            .exec()
    })
}

function exportTempFile(node: any): Promise<string> {
    return new Promise((resolve, reject) => {
        uni.canvasToTempFilePath(
            {
                canvasId: 'staffCardCanvas',
                canvas: node,
                x: 0,
                y: 0,
                width: CARD_W,
                height: CARD_H,
                destWidth: CARD_W,
                destHeight: CARD_H,
                fileType: 'png',
                quality: 1,
                success: (res: any) => resolve(res.tempFilePath),
                fail: (err: any) => reject(new Error(err?.errMsg || '生成名片图片失败'))
            } as any,
            instance?.proxy as any
        )
    })
}

async function exportImage(): Promise<string> {
    const data = props.card
    if (!data?.staff) {
        throw new Error('名片信息尚未加载完成')
    }

    const node = await queryCanvasNode()
    if (!node || typeof node.getContext !== 'function') {
        throw new Error('当前运行环境暂不支持离线生成名片海报，请在微信小程序中使用')
    }

    node.width = CARD_W
    node.height = CARD_H
    const ctx = node.getContext('2d')
    if (!ctx) {
        throw new Error('画布初始化失败，请稍后重试')
    }

    const [avatarImg, qrImg] = await Promise.all([
        loadCanvasImage(node, data.staff.avatar),
        loadCanvasImage(node, data.qrcode_image_url)
    ])

    // 1. 整体暗墨底
    ctx.fillStyle = PALETTE.inkDark
    ctx.fillRect(0, 0, CARD_W, CARD_H)

    // 2. 顶部品牌抬头区
    ctx.fillStyle = PALETTE.inkPanel
    drawRoundedRect(ctx, 32, 32, CARD_W - 64, 240, 28)
    ctx.fill()
    ctx.strokeStyle = PALETTE.goldBorder
    ctx.lineWidth = 2
    ctx.stroke()

    const brandName = String(brand.value?.name || '婚礼服务工作台').trim()
    ctx.fillStyle = PALETTE.goldSoft
    ctx.font = '500 22px sans-serif'
    ctx.fillText(brandName, 64, 82)

    ctx.fillStyle = PALETTE.whiteSoft
    ctx.font = '400 20px sans-serif'
    ctx.fillText('专属电子名片 · 档期直约', 64, 114)

    // 头像
    const avatarX = 64
    const avatarY = 144
    const avatarSize = 96
    ctx.save()
    drawRoundedRect(ctx, avatarX, avatarY, avatarSize, avatarSize, 48)
    ctx.clip()
    if (avatarImg) {
        ctx.drawImage(avatarImg, avatarX, avatarY, avatarSize, avatarSize)
    } else {
        ctx.fillStyle = PALETTE.gold
        ctx.fillRect(avatarX, avatarY, avatarSize, avatarSize)
        ctx.fillStyle = PALETTE.inkDark
        ctx.font = '600 40px sans-serif'
        ctx.fillText(data.staff.name.slice(0, 1) || '师', avatarX + 28, avatarY + 62)
    }
    ctx.restore()

    // 姓名与身份
    ctx.fillStyle = PALETTE.white
    ctx.font = '600 38px sans-serif'
    ctx.fillText(data.staff.name || '服务老师', 180, 186)

    const metaParts = [
        data.staff.category_name,
        data.staff.experience_years > 0 ? `${data.staff.experience_years}年经验` : ''
    ].filter(Boolean)
    ctx.fillStyle = PALETTE.goldSoft
    ctx.font = '500 24px sans-serif'
    ctx.fillText(metaParts.join(' · ') || '认证服务老师', 180, 224)

    // 3. 中部暖象牙信息主卡
    ctx.fillStyle = PALETTE.ivory
    drawRoundedRect(ctx, 32, 296, CARD_W - 64, 610, 28)
    ctx.fill()

    // 核心指标行（参考价 / 评分 / 累计场次 / 14日可约）
    const statsBoxY = 328
    const stats = [
        {
            label: '参考报价',
            val: data.staff.price_text || '详询报价'
        },
        {
            label: '综合评分',
            val: `${Number(data.staff.rating || 5).toFixed(1)} 分`
        },
        {
            label: '服务场次',
            val: `${Number(data.staff.order_count || 0)} 场`
        },
        {
            label: '近14日可约',
            val: `${Number(data.available_days_14d || 0)} 天`
        }
    ]
    const statW = (CARD_W - 128) / 2
    stats.forEach((item, idx) => {
        const col = idx % 2
        const row = Math.floor(idx / 2)
        const bx = 56 + col * (statW + 16)
        const by = statsBoxY + row * 98
        ctx.fillStyle = PALETTE.surface
        drawRoundedRect(ctx, bx, by, statW, 84, 16)
        ctx.fill()
        ctx.strokeStyle = PALETTE.line
        ctx.lineWidth = 1
        ctx.stroke()

        ctx.fillStyle = PALETTE.inkMuted
        ctx.font = '400 20px sans-serif'
        ctx.fillText(item.label, bx + 20, by + 32)

        ctx.fillStyle = idx === 0 ? PALETTE.goldText : PALETTE.inkText
        ctx.font = '600 26px sans-serif'
        ctx.fillText(String(item.val), bx + 20, by + 66)
    })

    // 个人标签
    let nextY = 548
    const tags = Array.isArray(data.staff.tags) ? data.staff.tags.slice(0, 4) : []
    if (tags.length > 0) {
        let tagX = 56
        for (const tag of tags) {
            const label = String(tag || '').slice(0, 8)
            if (!label) continue
            const tw = Math.min(180, Math.max(88, label.length * 22 + 32))
            if (tagX + tw > CARD_W - 56) break
            ctx.fillStyle = PALETTE.surfaceMuted
            drawRoundedRect(ctx, tagX, nextY, tw, 40, 20)
            ctx.fill()
            ctx.fillStyle = PALETTE.goldText
            ctx.font = '500 20px sans-serif'
            ctx.fillText(label, tagX + 16, nextY + 27)
            tagX += tw + 12
        }
        nextY += 66
    }

    // 个人简介
    ctx.fillStyle = PALETTE.inkText
    ctx.font = '600 24px sans-serif'
    ctx.fillText('服务理念与简介', 56, nextY + 10)
    nextY += 44

    ctx.fillStyle = PALETTE.inkSub
    ctx.font = '400 22px sans-serif'
    nextY = drawWrappedText(
        ctx,
        data.staff.intro || data.staff.service_desc || '专注每一场婚礼的现场呈现与细节把控。',
        56,
        nextY,
        CARD_W - 112,
        34,
        4
    )

    // 近 14 天可约概览条
    const availY = Math.max(nextY + 24, 758)
    ctx.fillStyle = PALETTE.surface
    drawRoundedRect(ctx, 56, availY, CARD_W - 112, 120, 16)
    ctx.fill()
    ctx.strokeStyle = PALETTE.line
    ctx.lineWidth = 1
    ctx.stroke()

    ctx.fillStyle = PALETTE.inkText
    ctx.font = '600 22px sans-serif'
    ctx.fillText('近期可约档期速览', 76, availY + 34)

    ctx.fillStyle = PALETTE.success
    ctx.font = '500 20px sans-serif'
    ctx.fillText(`可约 ${data.available_days_14d} / 14 天`, CARD_W - 216, availY + 34)

    const days = Array.isArray(data.upcoming_availability)
        ? data.upcoming_availability.slice(0, 7)
        : []
    const dayW = (CARD_W - 152) / Math.max(1, days.length)
    days.forEach((d, idx) => {
        const dx = 76 + idx * dayW
        const dy = availY + 52
        ctx.fillStyle = d.available ? PALETTE.successBg : PALETTE.surfaceMuted
        drawRoundedRect(ctx, dx + 4, dy, dayW - 8, 52, 10)
        ctx.fill()

        ctx.fillStyle = d.available ? PALETTE.success : PALETTE.inkMuted
        ctx.font = '500 18px sans-serif'
        ctx.fillText(String(d.md_label || ''), dx + 10, dy + 23)
        ctx.fillText(d.available ? '可约' : '已满', dx + 14, dy + 44)
    })

    // 4. 底部小程序码与引导区
    ctx.fillStyle = PALETTE.inkPanel
    drawRoundedRect(ctx, 32, 930, CARD_W - 64, 218, 28)
    ctx.fill()
    ctx.strokeStyle = PALETTE.goldBorder
    ctx.lineWidth = 2
    ctx.stroke()

    const qrSize = 152
    const qrX = CARD_W - 64 - qrSize
    const qrY = 963
    ctx.fillStyle = PALETTE.white
    drawRoundedRect(ctx, qrX, qrY, qrSize, qrSize, 16)
    ctx.fill()
    if (qrImg) {
        ctx.drawImage(qrImg, qrX + 8, qrY + 8, qrSize - 16, qrSize - 16)
    } else {
        ctx.fillStyle = PALETTE.inkMuted
        ctx.font = '400 20px sans-serif'
        ctx.fillText('微信扫码预约', qrX + 16, qrY + 82)
    }

    ctx.fillStyle = PALETTE.goldSoft
    ctx.font = '600 28px sans-serif'
    ctx.fillText('微信长按识别预约档期', 64, 1010)

    ctx.fillStyle = PALETTE.whiteSoft
    ctx.font = '400 22px sans-serif'
    ctx.fillText('查看精选作品、服务套餐与实时可约日历', 64, 1054)

    if (data.staff.mobile_masked) {
        ctx.fillStyle = PALETTE.goldSoft
        ctx.font = '400 20px sans-serif'
        ctx.fillText(`工作联系：${data.staff.mobile_masked}`, 64, 1098)
    }

    return await exportTempFile(node)
}

defineExpose({ exportImage })
</script>

<style lang="scss" scoped>
.card-canvas-wrap {
    position: fixed;
    left: -9999px;
    top: 0;
    width: 750px;
    height: 1180px;
    overflow: hidden;
    pointer-events: none;
    opacity: 0;
    background: var(--c-dark-1);
    border-radius: var(--r-lg);
}

.card-canvas {
    width: 750px;
    height: 1180px;
    display: block;
    box-sizing: border-box;
}
</style>
