export interface OrderConfirmLetterSnapshot {
    title?: string
    order_sn?: string
    customer_name?: string
    service_date?: string
    service_date_label?: string
    service_address?: string
    service_team_lines?: string[]
    service_staff_names?: string[]
    order_total_amount?: string
    paid_label?: string
    paid_amount?: string
    remain_amount?: string
    confirm_date?: string
    contact_mobile?: string
    remark_content?: string
    brand_name?: string
    brand_tagline?: string
    brand_logo?: string
    brand_logo_data_uri?: string
    payment_node?: string
    footer_note?: string
}

export interface OrderConfirmLetterRenderOptions {
    renderSpecVersion?: string
    small?: boolean
}

type RenderOptionsInput = boolean | OrderConfirmLetterRenderOptions | undefined

type TextBlockOptions = {
    x: number
    y: number
    lines: string[]
    fontSize: number
    lineHeight: number
    fill: string
    fontWeight?: number | string
    textAnchor?: 'start' | 'middle' | 'end'
    fontFamily?: string
    letterSpacing?: number | string
}

type InfoCardOptions = {
    x: number
    y: number
    width: number
    title: string
    lines: string[]
    fill: string
    stroke: string
    titleColor: string
    bodyColor: string
    titleSize: number
    bodySize: number
    lineHeight: number
    paddingX: number
    paddingY: number
    gap: number
    radius: number
}

export const ORDER_CONFIRM_LETTER_FONT_FAMILY_SANS =
    'Noto Sans SC, PingFang SC, Microsoft YaHei, sans-serif'
export const ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF =
    'Noto Serif SC, Georgia, Times New Roman, serif'

const DEFAULT_TITLE = '订单确认函'
const DEFAULT_HERO_EYEBROW = '订单确认函'
const DEFAULT_HERO_DESC =
    '为保证服务现场执行准确无误，系统已根据当前订单信息自动生成本次正式确认函。'
const DEFAULT_BRAND_NAME = '服务团队'
const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为服务安排与付款确认的纸本凭证。'
const V3_DEFAULT_HERO_EYEBROW = '订单确认'
const V3_DEFAULT_SUBTITLE = '服务确认函'
const V3_DEFAULT_HERO_DESC = '确认本次服务档期、服务内容与付款安排。'
const V3_FOOTER_KICKER = '感谢信任与确认。'
const V4_DEFAULT_SUBTITLE = 'Wedding Order Confirmation'

const escapeXml = (value: string) =>
    String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;')

const toText = (value: unknown) => String(value ?? '').trim()

const getTextFontAttr = (fontFamily = ORDER_CONFIRM_LETTER_FONT_FAMILY_SANS) =>
    ` font-family="${escapeXml(fontFamily)}"`

const toStringArray = (value: unknown) =>
    Array.isArray(value) ? value.map((item) => toText(item)).filter(Boolean) : []

const hasRenderableSnapshot = (snapshot?: OrderConfirmLetterSnapshot | null) => {
    if (!snapshot || typeof snapshot !== 'object') {
        return false
    }

    return Object.values(snapshot).some((value) => {
        if (Array.isArray(value)) {
            return value.some((item) => toText(item))
        }

        return toText(value)
    })
}

export const isOrderConfirmLetterBitmapAssetUrl = (value: unknown) => {
    const url = toText(value)
    if (!url) {
        return false
    }

    if (/^data:image\/(?:png|jpeg|jpg|webp|bmp)/i.test(url)) {
        return true
    }

    const normalized = url.split('#')[0].split('?')[0]
    return /\.(?:png|jpe?g|webp|bmp)$/i.test(normalized)
}

const normalizeVersion = (version?: string) => toText(version).toLowerCase()

const resolveRenderOptions = (
    input?: RenderOptionsInput
): Required<OrderConfirmLetterRenderOptions> => {
    if (typeof input === 'boolean') {
        return {
            renderSpecVersion: 'v1',
            small: input
        }
    }

    return {
        renderSpecVersion: toText(input?.renderSpecVersion) || 'v1',
        small: Boolean(input?.small)
    }
}

const isV3Spec = (version?: string) => normalizeVersion(version).startsWith('v3')
const isV4Spec = (version?: string) => normalizeVersion(version).startsWith('v4')
const isV2Spec = (version?: string) => normalizeVersion(version).startsWith('v2')

const wrapText = (text: string, maxCharsPerLine: number, maxLines: number) => {
    const source = toText(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n')
    if (!source) {
        return ['']
    }

    const lines: string[] = []
    let truncated = false

    for (const segment of source.split('\n')) {
        let remain = segment.trim()

        if (!remain) {
            lines.push('')
            if (lines.length >= maxLines) {
                truncated = true
                break
            }
            continue
        }

        while (remain.length > maxCharsPerLine) {
            lines.push(remain.slice(0, maxCharsPerLine))
            remain = remain.slice(maxCharsPerLine)
            if (lines.length >= maxLines) {
                truncated = true
                break
            }
        }

        if (truncated) {
            break
        }

        lines.push(remain)
        if (lines.length >= maxLines) {
            truncated = true
            break
        }
    }

    const visibleLines = lines.slice(0, maxLines)

    return visibleLines.length ? visibleLines : ['']
}

const fitTextLine = (
    text: string,
    maxCharsPerLine: number,
    baseFontSize: number,
    minFontSize: number
) => {
    const value = toText(text)
    const length = Math.max(value.length, 1)
    if (length <= maxCharsPerLine) {
        return {
            lines: [value],
            fontSize: baseFontSize,
            lineHeight: baseFontSize
        }
    }
    return {
        lines: [value],
        fontSize: Math.max(minFontSize, (baseFontSize * maxCharsPerLine) / length),
        lineHeight: baseFontSize
    }
}

const drawTextBlock = ({
    x,
    y,
    lines,
    fontSize,
    lineHeight,
    fill,
    fontWeight = 500,
    textAnchor = 'start',
    fontFamily = ORDER_CONFIRM_LETTER_FONT_FAMILY_SANS,
    letterSpacing
}: TextBlockOptions) => {
    const safeLines = lines.length ? lines : ['']
    const letterSpacingAttr =
        letterSpacing === undefined ? '' : ` letter-spacing="${escapeXml(String(letterSpacing))}"`
    const svg = safeLines
        .map(
            (line, index) =>
                `<text x="${x}" y="${
                    y + index * lineHeight
                }" text-anchor="${textAnchor}" font-size="${fontSize}" font-weight="${fontWeight}" fill="${fill}"${getTextFontAttr(
                    fontFamily
                )}${letterSpacingAttr}>${escapeXml(
                    line
                )}</text>`
        )
        .join('')

    return {
        svg,
        height: safeLines.length * lineHeight
    }
}

const shiftSvgY = (svg: string, offset: number) => {
    if (!svg || !offset) {
        return svg
    }

    return svg.replace(/( y=")(-?\d+(?:\.\d+)?)(")/g, (_match, prefix, value, suffix) => {
        return `${prefix}${Number(value) + offset}${suffix}`
    })
}

const drawInfoCard = ({
    x,
    y,
    width,
    title,
    lines,
    fill,
    stroke,
    titleColor,
    bodyColor,
    titleSize,
    bodySize,
    lineHeight,
    paddingX,
    paddingY,
    gap,
    radius
}: InfoCardOptions) => {
    const innerX = x + paddingX
    const innerY = paddingY + titleSize
    const titleBlock = drawTextBlock({
        x: innerX,
        y: innerY,
        lines: [title],
        fontSize: titleSize,
        lineHeight: titleSize,
        fill: titleColor,
        fontWeight: 700
    })
    const bodyY = innerY + titleBlock.height + gap + bodySize
    const bodyBlock = drawTextBlock({
        x: innerX,
        y: bodyY,
        lines,
        fontSize: bodySize,
        lineHeight,
        fill: bodyColor,
        fontWeight: 600
    })
    const height = paddingY * 2 + titleBlock.height + gap + bodyBlock.height

    return {
        svg: `<g transform="translate(0 ${y})"><rect x="${x}" y="0" width="${width}" height="${height}" rx="${radius}" fill="${fill}" stroke="${stroke}" />${titleBlock.svg}${bodyBlock.svg}</g>`,
        height
    }
}

const buildV1Rows = (snapshot: OrderConfirmLetterSnapshot) => {
    const staffNames = toStringArray(snapshot.service_staff_names)

    return [
        `客户名称：${toText(snapshot.customer_name)}`,
        `日期：${toText(snapshot.service_date)}`,
        `地点：${toText(snapshot.service_address)}`,
        `服务人员：${staffNames.join('、')}`,
        `订单总价：¥${toText(snapshot.order_total_amount) || '0.00'}`,
        `${toText(snapshot.paid_label) || '已付定金'}：¥${toText(snapshot.paid_amount) || '0.00'}`,
        `尾款剩余：¥${toText(snapshot.remain_amount) || '0.00'}`,
        `确认日期：${toText(snapshot.confirm_date)}`,
        `联系电话：${toText(snapshot.contact_mobile)}`
    ]
}

const renderV1OrderConfirmLetterSvg = (snapshot: OrderConfirmLetterSnapshot, small = false) => {
    const width = small ? 540 : 1080
    const height = small ? 960 : 1920
    const padding = small ? 40 : 80
    const titleSize = small ? 28 : 56
    const textSize = small ? 18 : 36
    const amountSize = small ? 20 : 40
    const lineHeight = small ? 32 : 64
    let y = small ? 88 : 150

    const rows = buildV1Rows(snapshot)
    const texts: string[] = []
    texts.push(
        `<text x="${
            width / 2
        }" y="${y}" text-anchor="middle" font-size="${titleSize}" font-weight="700" fill="#111111"${getTextFontAttr()}>${escapeXml(
            toText(snapshot.title) || DEFAULT_TITLE
        )}</text>`
    )
    y += small ? 56 : 100

    rows.forEach((row, index) => {
        const lines = wrapText(row, small ? 22 : 28, 2)
        const fontSize = index >= 4 && index <= 6 ? amountSize : textSize
        const fontWeight = index >= 4 && index <= 6 ? 700 : 400
        lines.forEach((line) => {
            texts.push(
                `<text x="${padding}" y="${y}" font-size="${fontSize}" font-weight="${fontWeight}" fill="#111111"${getTextFontAttr()}>${escapeXml(
                    line
                )}</text>`
            )
            y += lineHeight
        })
        y += small ? 12 : 20
    })

    texts.push(
        `<text x="${padding}" y="${y}" font-size="${textSize}" font-weight="600" fill="#111111"${getTextFontAttr()}>备注：</text>`
    )
    y += lineHeight
    wrapText(toText(snapshot.remark_content), small ? 22 : 28, 6).forEach((line) => {
        texts.push(
            `<text x="${padding}" y="${y}" font-size="${textSize}" fill="#5F5A50"${getTextFontAttr()}>${escapeXml(
                line
            )}</text>`
        )
        y += lineHeight
    })

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="100%" height="100%" fill="#ffffff" rx="24" />${texts.join(
        ''
    )}</svg>`
}

const renderV2OrderConfirmLetterSvg = (snapshot: OrderConfirmLetterSnapshot, small = false) => {
    const width = small ? 540 : 1080
    const height = small ? 960 : 1920
    const pagePadding = small ? 24 : 60
    const paperPaddingX = small ? 22 : 54
    const paperPaddingY = small ? 24 : 54
    const sectionGap = small ? 12 : 26
    const paperX = pagePadding
    const paperY = pagePadding
    const paperWidth = width - pagePadding * 2
    const paperHeight = height - pagePadding * 2
    const contentX = paperX + paperPaddingX
    const contentWidth = paperWidth - paperPaddingX * 2

    const heroPaddingX = small ? 22 : 38
    const heroPaddingY = small ? 22 : 38
    const heroEyebrowSize = small ? 10 : 18
    const heroTitleSize = small ? 28 : 54
    const heroDescSize = small ? 13 : 24
    const heroMetaSize = small ? 12 : 21
    const heroLineHeight = small ? 18 : 34
    const cardTitleSize = small ? 15 : 22
    const cardBodySize = small ? 16 : 24
    const cardLineHeight = small ? 24 : 41
    const cardPaddingX = small ? 20 : 28
    const cardPaddingY = small ? 18 : 24
    const cardGap = small ? 10 : 12
    const cardRadius = small ? 22 : 28
    const amountBigSize = small ? 26 : 48
    const amountBigLineHeight = small ? 30 : 52
    const footerBrandSize = small ? 11 : 20
    const footerNoteSize = small ? 11 : 20
    const footerNoteLineHeight = small ? 16 : 30

    const title = toText(snapshot.title) || DEFAULT_TITLE
    const heroEyebrow = toText(snapshot.brand_tagline) || DEFAULT_HERO_EYEBROW
    const heroMetaLines = [
        toText(snapshot.order_sn) ? `订单编号：${toText(snapshot.order_sn)}` : '',
        toText(snapshot.confirm_date) ? `确认日期：${toText(snapshot.confirm_date)}` : ''
    ].filter(Boolean)
    const weddingLines = [
        `客户姓名：${toText(snapshot.customer_name) || '-'}`,
        `婚礼日期：${toText(snapshot.service_date_label) || toText(snapshot.service_date) || '-'}`,
        `举办地点：${toText(snapshot.service_address) || '-'}`
    ].flatMap((line) => wrapText(line, small ? 18 : 24, small ? 2 : 2))

    const serviceTeamLines = toStringArray(snapshot.service_team_lines)
    const staffNames = toStringArray(snapshot.service_staff_names)
    const teamLines = (
        serviceTeamLines.length
            ? serviceTeamLines
            : staffNames.length
            ? [`服务人员：${staffNames.join('、')}`]
            : ['服务人员：待补充']
    )
        .flatMap((line) => wrapText(line, small ? 18 : 24, 2))
        .slice(0, small ? 4 : 5)

    const amountDetailLines = [
        `${toText(snapshot.paid_label) || '已付定金'}：¥${toText(snapshot.paid_amount) || '0.00'}`,
        `待付尾款：¥${toText(snapshot.remain_amount) || '0.00'}`
    ]

    const remarkLines = [
        `联系电话：${toText(snapshot.contact_mobile) || '-'}`,
        ...wrapText(
            toText(snapshot.remark_content) || DEFAULT_FOOTER_NOTE,
            small ? 18 : 24,
            small ? 4 : 5
        )
    ]

    const heroDescLines = wrapText(DEFAULT_HERO_DESC, small ? 22 : 30, 2)
    const heroMetaWrappedLines = (
        heroMetaLines.length ? heroMetaLines : ['确认信息待更新']
    ).flatMap((line) => wrapText(line, small ? 20 : 30, 1))
    const heroHeight =
        heroPaddingY * 2 +
        heroEyebrowSize +
        (small ? 10 : 18) +
        heroTitleSize +
        (small ? 12 : 18) +
        heroDescLines.length * heroLineHeight +
        (small ? 10 : 14) +
        heroMetaWrappedLines.length * heroLineHeight

    const infoCardHeight =
        cardPaddingY * 2 + cardTitleSize + cardGap + weddingLines.length * cardLineHeight
    const teamCardHeight =
        cardPaddingY * 2 + cardTitleSize + cardGap + teamLines.length * cardLineHeight
    const remarkCardBodySize = small ? 15 : 22
    const remarkCardLineHeight = small ? 22 : 36
    const remarkCardHeight =
        cardPaddingY * 2 + cardTitleSize + cardGap + remarkLines.length * remarkCardLineHeight

    const amountTitleBlock = drawTextBlock({
        x: contentX + cardPaddingX,
        y: cardPaddingY + cardTitleSize,
        lines: ['费用确认'],
        fontSize: cardTitleSize,
        lineHeight: cardTitleSize,
        fill: '#5F5A50',
        fontWeight: 700
    })
    const amountTotalY = cardPaddingY + amountTitleBlock.height + cardGap + amountBigSize
    const amountTotalBlock = drawTextBlock({
        x: contentX + cardPaddingX,
        y: amountTotalY,
        lines: [`合计 ¥${toText(snapshot.order_total_amount) || '0.00'}`],
        fontSize: amountBigSize,
        lineHeight: amountBigLineHeight,
        fill: '#0B0B0B',
        fontWeight: 700
    })
    const amountDetailsY = amountTotalY + amountTotalBlock.height + cardGap + cardBodySize
    const amountDetailsBlock = drawTextBlock({
        x: contentX + cardPaddingX,
        y: amountDetailsY,
        lines: amountDetailLines,
        fontSize: cardBodySize,
        lineHeight: cardLineHeight,
        fill: '#111111',
        fontWeight: 600
    })
    const amountCardHeight =
        cardPaddingY * 2 +
        amountTitleBlock.height +
        cardGap +
        amountTotalBlock.height +
        cardGap +
        amountDetailsBlock.height

    let currentY = paperY + paperPaddingY
    const sections: string[] = []

    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${heroHeight}" rx="${
            small ? 28 : 40
        }" fill="url(#heroGradient)" stroke="#D8C28A" /><circle cx="${
            contentX + contentWidth - (small ? 44 : 90)
        }" cy="${currentY + (small ? 42 : 78)}" r="${
            small ? 22 : 48
        }" fill="#FFFFFF" fill-opacity="0.36" /><circle cx="${
            contentX + contentWidth - (small ? 86 : 160)
        }" cy="${currentY + (small ? 86 : 146)}" r="${
            small ? 12 : 24
        }" fill="#FFFFFF" fill-opacity="0.24" />`
    )

    const heroTextX = contentX + heroPaddingX
    const heroEyebrowY = currentY + heroPaddingY + heroEyebrowSize
    sections.push(
        drawTextBlock({
            x: heroTextX,
            y: heroEyebrowY,
            lines: [heroEyebrow],
            fontSize: heroEyebrowSize,
            lineHeight: heroEyebrowSize,
            fill: '#C8A45D',
            fontWeight: 700
        }).svg
    )
    const heroTitleY = heroEyebrowY + (small ? 18 : 36) + heroTitleSize
    sections.push(
        drawTextBlock({
            x: heroTextX,
            y: heroTitleY,
            lines: [title],
            fontSize: heroTitleSize,
            lineHeight: heroTitleSize,
            fill: '#111111',
            fontWeight: 700
        }).svg
    )
    const heroDescY = heroTitleY + (small ? 16 : 28) + heroDescSize
    sections.push(
        drawTextBlock({
            x: heroTextX,
            y: heroDescY,
            lines: heroDescLines,
            fontSize: heroDescSize,
            lineHeight: heroLineHeight,
            fill: '#5F5A50',
            fontWeight: 500
        }).svg
    )
    const heroMetaY =
        heroDescY + heroDescLines.length * heroLineHeight + (small ? 8 : 12) + heroMetaSize
    sections.push(
        drawTextBlock({
            x: heroTextX,
            y: heroMetaY,
            lines: heroMetaWrappedLines,
            fontSize: heroMetaSize,
            lineHeight: heroLineHeight,
            fill: '#0B0B0B',
            fontWeight: 700
        }).svg
    )

    currentY += heroHeight + sectionGap

    sections.push(
        drawInfoCard({
            x: contentX,
            y: currentY,
            width: contentWidth,
            title: '婚礼信息',
            lines: weddingLines,
            fill: '#FFFFFF',
            stroke: '#E7E2D6',
            titleColor: '#5F5A50',
            bodyColor: '#111111',
            titleSize: cardTitleSize,
            bodySize: cardBodySize,
            lineHeight: cardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius
        }).svg
    )
    currentY += infoCardHeight + sectionGap

    sections.push(
        drawInfoCard({
            x: contentX,
            y: currentY,
            width: contentWidth,
            title: '服务团队',
            lines: teamLines,
            fill: '#FFFFFF',
            stroke: '#E7E2D6',
            titleColor: '#5F5A50',
            bodyColor: '#111111',
            titleSize: cardTitleSize,
            bodySize: cardBodySize,
            lineHeight: cardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius
        }).svg
    )
    currentY += teamCardHeight + sectionGap

    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${amountCardHeight}" rx="${
            small ? 24 : 32
        }" fill="#FFFFFF" stroke="#D8C28A" /><rect x="${
            contentX + contentWidth - (small ? 90 : 148)
        }" y="${currentY + (small ? 18 : 22)}" width="${small ? 62 : 104}" height="${
            small ? 24 : 36
        }" rx="999" fill="#F3F2EE" /><text x="${contentX + contentWidth - (small ? 59 : 96)}" y="${
            currentY + (small ? 34 : 47)
        }" text-anchor="middle" font-size="${
            small ? 11 : 18
        }" font-weight="700" fill="#0B0B0B"${getTextFontAttr()}>金额重点</text>${shiftSvgY(
            amountTitleBlock.svg,
            currentY
        )}${shiftSvgY(amountTotalBlock.svg, currentY)}${shiftSvgY(amountDetailsBlock.svg, currentY)}`
    )
    currentY += amountCardHeight + sectionGap

    sections.push(
        drawInfoCard({
            x: contentX,
            y: currentY,
            width: contentWidth,
            title: '备注与联系',
            lines: remarkLines,
            fill: '#FFFFFF',
            stroke: '#E7E2D6',
            titleColor: '#5F5A50',
            bodyColor: '#111111',
            titleSize: cardTitleSize,
            bodySize: remarkCardBodySize,
            lineHeight: remarkCardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius
        }).svg
    )
    currentY += remarkCardHeight + sectionGap

    const footerLineY = currentY
    const footerBrandY = footerLineY + (small ? 24 : 38)
    const footerNoteLines = wrapText(
        toText(snapshot.footer_note) || DEFAULT_FOOTER_NOTE,
        small ? 24 : 30,
        2
    )
    const footerNoteBlock = drawTextBlock({
        x: paperX + paperWidth / 2,
        y: footerBrandY + (small ? 22 : 36),
        lines: footerNoteLines,
        fontSize: footerNoteSize,
        lineHeight: footerNoteLineHeight,
        fill: '#5F5A50',
        fontWeight: 500,
        textAnchor: 'middle'
    })

    sections.push(
        `<rect x="${contentX}" y="${footerLineY}" width="${contentWidth}" height="1" fill="#E7E2D6" /><text x="${
            paperX + paperWidth / 2
        }" y="${footerBrandY}" text-anchor="middle" font-size="${footerBrandSize}" font-weight="700" letter-spacing="${
            small ? 0.8 : 1.2
        }" fill="#C8A45D"${getTextFontAttr()}>${escapeXml(
            toText(snapshot.brand_name) || DEFAULT_BRAND_NAME
        )}</text>${footerNoteBlock.svg}`
    )

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><defs><linearGradient id="pageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FFFFFF" /><stop offset="100%" stop-color="#F8F7F2" /></linearGradient><linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FFFFFF" /><stop offset="100%" stop-color="#F7F0DF" /></linearGradient><filter id="paperShadow" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="24" stdDeviation="18" flood-color="#C8A45D" flood-opacity="0.18" /></filter></defs><rect width="100%" height="100%" fill="url(#pageGradient)" /><rect x="${paperX}" y="${paperY}" width="${paperWidth}" height="${paperHeight}" rx="${
        small ? 28 : 48
    }" fill="#FFFFFF" stroke="#E7E2D6" stroke-width="2" filter="url(#paperShadow)" />${sections.join(
        ''
    )}</svg>`
}

const renderV3OrderConfirmLetterSvg = (
    snapshot: OrderConfirmLetterSnapshot,
    small = false
) => {
    const width = small ? 540 : 1080
    const height = small ? 960 : 1920
    const pagePadding = small ? 24 : 60
    const paperX = pagePadding
    const paperY = pagePadding
    const paperWidth = width - pagePadding * 2
    const paperHeight = height - pagePadding * 2
    const paperPaddingX = small ? 34 : 74
    const paperPaddingY = small ? 34 : 72
    const contentX = paperX + paperPaddingX
    const contentWidth = paperWidth - paperPaddingX * 2
    const sectionGap = small ? 16 : 34

    const title = toText(snapshot.title) || DEFAULT_TITLE
    const heroEyebrow = toText(snapshot.brand_tagline) || V3_DEFAULT_HERO_EYEBROW
    const heroSubtitle = V3_DEFAULT_SUBTITLE
    const heroDescLines = wrapText(
        toText((snapshot as any).hero_desc) || V3_DEFAULT_HERO_DESC,
        small ? 24 : 38,
        2
    )

    const heroMetaPrimaryParts = [
        toText(snapshot.customer_name),
        toText(snapshot.service_date_label) || toText(snapshot.service_date),
        toText(snapshot.service_address),
    ].filter(Boolean)
    const heroMetaSecondaryParts = [
        toText(snapshot.order_sn) ? `订单编号：${toText(snapshot.order_sn)}` : '',
        toText(snapshot.confirm_date) ? `确认日期：${toText(snapshot.confirm_date)}` : '',
    ].filter(Boolean)
    let heroMetaLines: string[] = []
    if (heroMetaPrimaryParts.length) {
        heroMetaLines = heroMetaLines.concat(
            wrapText(heroMetaPrimaryParts.join(' · '), small ? 28 : 42, 1)
        )
    }
    if (heroMetaSecondaryParts.length) {
        heroMetaLines = heroMetaLines.concat(
            wrapText(heroMetaSecondaryParts.join(' · '), small ? 26 : 38, 1)
        )
    }
    if (!heroMetaLines.length) {
        heroMetaLines = ['确认信息待更新']
    }

    const leftInfoLines = [
        `新人：${toText(snapshot.customer_name) || '-'}`,
        `婚礼日期：${toText(snapshot.service_date_label) || toText(snapshot.service_date) || '-'}`,
        `举办地点：${toText(snapshot.service_address) || '-'}`,
    ].flatMap((line) => wrapText(line, small ? 16 : 23, 2))

    const serviceTeamLines = toStringArray(snapshot.service_team_lines)
    const staffNames = toStringArray(snapshot.service_staff_names)
    const rightInfoSource = serviceTeamLines.length
        ? serviceTeamLines.slice(0, small ? 3 : 4)
        : staffNames.length
          ? [`服务团队：${staffNames.join('、')}`]
          : ['服务团队：待补充']
    const rightInfoLines = rightInfoSource
        .flatMap((line) => wrapText(line, small ? 16 : 23, 2))
        .slice(0, small ? 5 : 6)

    const amountDetailLines = [
        `${toText(snapshot.paid_label) || '已付定金'}：¥${toText(snapshot.paid_amount) || '0.00'}`,
        `待付尾款：¥${toText(snapshot.remain_amount) || '0.00'}`,
        '支付节点：婚礼前 3 日',
    ]

    const acknowledgementLines = wrapText(
        toText(snapshot.remark_content) || DEFAULT_FOOTER_NOTE,
        small ? 18 : 28,
        4
    )
    const contactLines = [
        `联系电话：${toText(snapshot.contact_mobile) || '-'}`,
        `确认日期：${toText(snapshot.confirm_date) || '-'}`,
        '当前版本：婚礼确认函',
    ]

    const heroPaddingTop = small ? 18 : 24
    const heroPaddingBottom = small ? 20 : 26
    const sealSize = small ? 44 : 84
    const sealInnerSize = small ? 34 : 64
    const heroEyebrowSize = small ? 9 : 16
    const heroTitleSize = small ? 30 : 54
    const heroSubtitleSize = small ? 16 : 28
    const heroDescSize = small ? 13 : 22
    const heroMetaSize = small ? 11 : 20
    const heroDescLineHeight = small ? 18 : 32
    const heroMetaLineHeight = small ? 16 : 26
    const heroHeight =
        heroPaddingTop +
        heroEyebrowSize +
        (small ? 12 : 16) +
        sealSize +
        (small ? 12 : 16) +
        heroTitleSize +
        (small ? 8 : 10) +
        heroSubtitleSize +
        (small ? 10 : 14) +
        heroDescLines.length * heroDescLineHeight +
        (small ? 10 : 14) +
        heroMetaLines.length * heroMetaLineHeight +
        heroPaddingBottom

    const sectionLabelSize = small ? 9 : 16
    const sectionLabelGap = small ? 10 : 14
    const sectionBodySize = small ? 14 : 24
    const sectionBodyLineHeight = small ? 20 : 36
    const infoSectionPaddingY = small ? 16 : 26
    const infoSectionHeight =
        infoSectionPaddingY * 2 +
        sectionLabelSize +
        sectionLabelGap +
        Math.max(leftInfoLines.length, rightInfoLines.length) * sectionBodyLineHeight

    const amountPaddingY = small ? 18 : 28
    const amountLabelSize = small ? 9 : 16
    const amountValueSize = small ? 32 : 58
    const amountValueLineHeight = small ? 34 : 60
    const amountCaptionSize = small ? 14 : 22
    const amountBodySize = small ? 13 : 22
    const amountBodyLineHeight = small ? 20 : 33
    const amountSectionHeight =
        amountPaddingY * 2 +
        amountLabelSize +
        (small ? 10 : 12) +
        amountValueLineHeight +
        (small ? 8 : 10) +
        amountCaptionSize +
        Math.max(small ? 16 : 24, amountDetailLines.length * amountBodyLineHeight)

    const ackBodySize = small ? 13 : 22
    const ackBodyLineHeight = small ? 20 : 34
    const ackSectionPaddingY = small ? 16 : 24
    const ackSectionHeight =
        ackSectionPaddingY * 2 +
        sectionLabelSize +
        sectionLabelGap +
        Math.max(acknowledgementLines.length, contactLines.length) * ackBodyLineHeight

    const signLineGap = small ? 10 : 12
    const signLabelSize = small ? 12 : 19
    const signSectionHeight = (small ? 22 : 28) + signLineGap + signLabelSize

    const footerKickerSize = small ? 14 : 22
    const footerBrandSize = small ? 16 : 24
    const footerNoteSize = small ? 11 : 18
    const footerNoteLineHeight = small ? 16 : 28
    const footerNoteLines = wrapText(
        toText(snapshot.footer_note) || DEFAULT_FOOTER_NOTE,
        small ? 24 : 34,
        2
    )

    let currentY = paperY + paperPaddingY
    const sections: string[] = []
    const centerX = paperX + paperWidth / 2

    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${heroHeight}" fill="url(#v3HeroGradient)" stroke="#D8C28A" />`
    )
    sections.push(
        `<ellipse cx="${contentX + contentWidth - (small ? 58 : 108)}" cy="${currentY + (small ? 24 : 38)}" rx="${small ? 34 : 66}" ry="${small ? 28 : 54}" fill="#F7F0DF" fill-opacity="0.4" /><ellipse cx="${contentX + contentWidth - (small ? 42 : 72)}" cy="${currentY + (small ? 36 : 54)}" rx="${small ? 22 : 46}" ry="${small ? 18 : 36}" fill="#F7F0DF" fill-opacity="0.8" />`
    )

    const heroEyebrowY = currentY + heroPaddingTop + heroEyebrowSize
    sections.push(
        drawTextBlock({
            x: centerX,
            y: heroEyebrowY,
            lines: [heroEyebrow],
            fontSize: heroEyebrowSize,
            lineHeight: heroEyebrowSize,
            fill: '#C8A45D',
            fontWeight: 500,
            textAnchor: 'middle',
            letterSpacing: small ? 1.4 : 2.2,
        }).svg
    )

    const sealY = heroEyebrowY + (small ? 12 : 16)
    sections.push(
        `<circle cx="${centerX}" cy="${sealY + sealSize / 2}" r="${sealSize / 2}" fill="none" stroke="#C8A45D" stroke-width="1" /><circle cx="${centerX}" cy="${sealY + sealSize / 2}" r="${sealInnerSize / 2}" fill="none" stroke="#D8C28A" stroke-width="1" />`
    )
    sections.push(
        drawTextBlock({
            x: centerX,
            y: sealY + sealSize / 2 + (small ? 5 : 9),
            lines: ['服务'],
            fontSize: small ? 16 : 28,
            lineHeight: small ? 16 : 28,
            fill: '#9F7A2E',
            fontWeight: 600,
            textAnchor: 'middle',
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF,
        }).svg
    )

    const heroTitleY = sealY + sealSize + (small ? 12 : 16) + heroTitleSize
    sections.push(
        drawTextBlock({
            x: centerX,
            y: heroTitleY,
            lines: [title],
            fontSize: heroTitleSize,
            lineHeight: heroTitleSize,
            fill: '#111111',
            fontWeight: 700,
            textAnchor: 'middle',
        }).svg
    )

    const heroSubtitleY = heroTitleY + (small ? 8 : 10) + heroSubtitleSize
    sections.push(
        drawTextBlock({
            x: centerX,
            y: heroSubtitleY,
            lines: [heroSubtitle],
            fontSize: heroSubtitleSize,
            lineHeight: heroSubtitleSize,
            fill: '#9A9388',
            fontWeight: 500,
            textAnchor: 'middle',
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF,
        }).svg
    )

    const heroDescY = heroSubtitleY + (small ? 10 : 14) + heroDescSize
    sections.push(
        drawTextBlock({
            x: centerX,
            y: heroDescY,
            lines: heroDescLines,
            fontSize: heroDescSize,
            lineHeight: heroDescLineHeight,
            fill: '#5F5A50',
            fontWeight: 500,
            textAnchor: 'middle',
        }).svg
    )

    const heroMetaY =
        heroDescY + heroDescLines.length * heroDescLineHeight + (small ? 10 : 14) + heroMetaSize
    sections.push(
        drawTextBlock({
            x: centerX,
            y: heroMetaY,
            lines: heroMetaLines,
            fontSize: heroMetaSize,
            lineHeight: heroMetaLineHeight,
            fill: '#9F7A2E',
            fontWeight: 500,
            textAnchor: 'middle',
        }).svg
    )

    currentY += heroHeight + sectionGap

    const columnGap = small ? 18 : 40
    const columnWidth = (contentWidth - columnGap) / 2
    const sectionBodyY =
        currentY + infoSectionPaddingY + sectionLabelSize + sectionLabelGap + sectionBodySize
    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="1" fill="#E7E2D6" /><rect x="${contentX}" y="${
            currentY + infoSectionHeight
        }" width="${contentWidth}" height="1" fill="#E7E2D6" />`
    )
    sections.push(
        drawTextBlock({
            x: contentX,
            y: currentY + infoSectionPaddingY + sectionLabelSize,
            lines: ['服务信息'],
            fontSize: sectionLabelSize,
            lineHeight: sectionLabelSize,
            fill: '#C8A45D',
            fontWeight: 500,
            letterSpacing: small ? 1.4 : 2,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX,
            y: sectionBodyY,
            lines: leftInfoLines,
            fontSize: sectionBodySize,
            lineHeight: sectionBodyLineHeight,
            fill: '#111111',
            fontWeight: 500,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + columnWidth + columnGap,
            y: currentY + infoSectionPaddingY + sectionLabelSize,
            lines: ['服务团队'],
            fontSize: sectionLabelSize,
            lineHeight: sectionLabelSize,
            fill: '#C8A45D',
            fontWeight: 500,
            letterSpacing: small ? 1.4 : 2,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + columnWidth + columnGap,
            y: sectionBodyY,
            lines: rightInfoLines,
            fontSize: sectionBodySize,
            lineHeight: sectionBodyLineHeight,
            fill: '#111111',
            fontWeight: 500,
        }).svg
    )

    currentY += infoSectionHeight + sectionGap

    const amountLabelY = currentY + amountPaddingY + amountLabelSize
    const amountValueY = amountLabelY + (small ? 10 : 12) + amountValueSize
    const amountCaptionY = amountValueY + (small ? 8 : 10) + amountCaptionSize
    const amountRightX = contentX + contentWidth - (small ? 150 : 300)
    const amountRightY = currentY + amountPaddingY + (small ? 4 : 8) + amountBodySize
    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${amountSectionHeight}" fill="url(#v3AmountGradient)" stroke="#D8C28A" stroke-width="1" />`
    )
    sections.push(
        drawTextBlock({
            x: contentX + (small ? 20 : 30),
            y: amountLabelY,
            lines: ['费用确认'],
            fontSize: amountLabelSize,
            lineHeight: amountLabelSize,
            fill: '#C8A45D',
            fontWeight: 500,
            letterSpacing: small ? 1.4 : 2,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + (small ? 20 : 30),
            y: amountValueY,
            lines: [`¥${toText(snapshot.order_total_amount) || '0.00'}`],
            fontSize: amountValueSize,
            lineHeight: amountValueLineHeight,
            fill: '#5F5A50',
            fontWeight: 600,
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + (small ? 20 : 30),
            y: amountCaptionY,
            lines: ['合同合计金额'],
            fontSize: amountCaptionSize,
            lineHeight: amountCaptionSize,
            fill: '#5F5A50',
            fontWeight: 500,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: amountRightX,
            y: amountRightY,
            lines: amountDetailLines,
            fontSize: amountBodySize,
            lineHeight: amountBodyLineHeight,
            fill: '#5A4433',
            fontWeight: 500,
        }).svg
    )

    currentY += amountSectionHeight + sectionGap

    const ackBodyY =
        currentY + ackSectionPaddingY + sectionLabelSize + sectionLabelGap + ackBodySize
    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="1" fill="#E7E2D6" /><rect x="${contentX}" y="${
            currentY + ackSectionHeight
        }" width="${contentWidth}" height="1" fill="#E7E2D6" />`
    )
    sections.push(
        drawTextBlock({
            x: contentX,
            y: currentY + ackSectionPaddingY + sectionLabelSize,
            lines: ['确认说明'],
            fontSize: sectionLabelSize,
            lineHeight: sectionLabelSize,
            fill: '#C8A45D',
            fontWeight: 500,
            letterSpacing: small ? 1.4 : 2,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX,
            y: ackBodyY,
            lines: acknowledgementLines,
            fontSize: ackBodySize,
            lineHeight: ackBodyLineHeight,
            fill: '#5A4433',
            fontWeight: 500,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + columnWidth + columnGap,
            y: currentY + ackSectionPaddingY + sectionLabelSize,
            lines: ['联系信息'],
            fontSize: sectionLabelSize,
            lineHeight: sectionLabelSize,
            fill: '#C8A45D',
            fontWeight: 500,
            letterSpacing: small ? 1.4 : 2,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: contentX + columnWidth + columnGap,
            y: ackBodyY,
            lines: contactLines,
            fontSize: small ? 12 : 21,
            lineHeight: ackBodyLineHeight,
            fill: '#5A4433',
            fontWeight: 500,
        }).svg
    )

    currentY += ackSectionHeight + sectionGap

    const signLineY = currentY + (small ? 10 : 14)
    const signLabelY = signLineY + signLineGap + signLabelSize
    const signRightX = contentX + columnWidth + columnGap
    sections.push(
        `<rect x="${contentX}" y="${signLineY}" width="${columnWidth}" height="1" fill="#D8C28A" /><rect x="${signRightX}" y="${signLineY}" width="${columnWidth}" height="1" fill="#D8C28A" />`
    )
    sections.push(
        drawTextBlock({
            x: contentX,
            y: signLabelY,
            lines: ['客户签名'],
            fontSize: signLabelSize,
            lineHeight: signLabelSize,
            fill: '#9A9388',
            fontWeight: 500,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: signRightX,
            y: signLabelY,
            lines: ['婚礼顾问签署'],
            fontSize: signLabelSize,
            lineHeight: signLabelSize,
            fill: '#9A9388',
            fontWeight: 500,
        }).svg
    )

    currentY += signSectionHeight + sectionGap

    const footerLineY = currentY
    const footerBrandY = footerLineY + (small ? 22 : 24) + footerBrandSize
    const footerKickerY = footerBrandY + (small ? 6 : 8) + footerKickerSize
    const footerNoteY = footerKickerY + (small ? 8 : 10) + footerNoteSize
    sections.push(
        `<rect x="${contentX}" y="${footerLineY}" width="${contentWidth}" height="1" fill="#E7E2D6" />`
    )
    sections.push(
        drawTextBlock({
            x: centerX,
            y: footerBrandY,
            lines: [toText(snapshot.brand_name) || DEFAULT_BRAND_NAME],
            fontSize: footerBrandSize,
            lineHeight: footerBrandSize,
            fill: '#9F7A2E',
            fontWeight: 600,
            textAnchor: 'middle',
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: centerX,
            y: footerKickerY,
            lines: [V3_FOOTER_KICKER],
            fontSize: footerKickerSize,
            lineHeight: footerKickerSize,
            fill: '#9A9388',
            fontWeight: 500,
            textAnchor: 'middle',
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF,
        }).svg
    )
    sections.push(
        drawTextBlock({
            x: centerX,
            y: footerNoteY,
            lines: footerNoteLines,
            fontSize: footerNoteSize,
            lineHeight: footerNoteLineHeight,
            fill: '#9A9388',
            fontWeight: 500,
            textAnchor: 'middle',
        }).svg
    )

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><defs><linearGradient id="v3PageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#F8F7F2" /><stop offset="100%" stop-color="#F7F0DF" /></linearGradient><linearGradient id="v3PaperGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FFFFFF" /><stop offset="100%" stop-color="#F8F7F2" /></linearGradient><linearGradient id="v3HeroGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#F8F7F2" /><stop offset="100%" stop-color="#F7F0DF" /></linearGradient><linearGradient id="v3AmountGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#F7F0DF" /><stop offset="100%" stop-color="#F7F0DF" /></linearGradient><filter id="v3PaperShadow" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="20" stdDeviation="14" flood-color="#C8A45D" flood-opacity="0.12" /></filter></defs><rect width="100%" height="100%" fill="url(#v3PageGradient)" /><rect x="${paperX}" y="${paperY}" width="${paperWidth}" height="${paperHeight}" rx="${small ? 8 : 14}" fill="url(#v3PaperGradient)" stroke="#D8C28A" stroke-width="1" filter="url(#v3PaperShadow)" />${sections.join(
        ''
    )}</svg>`
}

const drawV4OrnamentLine = (centerX: number, y: number, width: number, color: string, scale: number) =>
    `<path d="M${centerX - width / 2} ${y} H${centerX - 34 * scale}" stroke="${color}" stroke-width="${Math.max(
        0.8,
        scale
    )}" /><path d="M${centerX + 34 * scale} ${y} C${centerX + 52 * scale} ${y - 16 * scale} ${
        centerX + 74 * scale
    } ${y - 12 * scale} ${centerX + 86 * scale} ${y} C${centerX + 74 * scale} ${
        y + 12 * scale
    } ${centerX + 52 * scale} ${y + 16 * scale} ${centerX + 34 * scale} ${y}" fill="none" stroke="${color}" stroke-width="${Math.max(
        0.8,
        scale
    )}" /><circle cx="${centerX}" cy="${y}" r="${4 * scale}" fill="${color}" /><path d="M${
        centerX + 34 * scale
    } ${y} H${centerX + width / 2}" stroke="${color}" stroke-width="${Math.max(0.8, scale)}" />`

const drawV4Seal = (
    centerX: number,
    topY: number,
    size: number,
    brandName: string,
    brandTagline: string,
    logoDataUri: string,
    brandInitial: string,
    small: boolean
) => {
    const centerY = topY + size / 2
    const scale = size / 122
    const logoSize = 52 * scale
    const logoSvg = logoDataUri
        ? `<defs><clipPath id="v4LogoClip"><circle cx="${centerX}" cy="${centerY - 8 * scale}" r="${
              logoSize / 2
          }" /></clipPath></defs><image href="${escapeXml(logoDataUri)}" x="${
              centerX - logoSize / 2
          }" y="${centerY - 8 * scale - logoSize / 2}" width="${logoSize}" height="${logoSize}" preserveAspectRatio="xMidYMid meet" clip-path="url(#v4LogoClip)" />`
        : drawTextBlock({
              x: centerX,
              y: centerY - 8 * scale + 9 * scale,
              lines: [brandInitial],
              fontSize: 26 * scale,
              lineHeight: 26 * scale,
              fill: '#A87023',
              fontWeight: 700,
              textAnchor: 'middle',
              fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF
          }).svg

    return `<circle cx="${centerX}" cy="${centerY}" r="${size / 2}" fill="#FFF9EC" stroke="url(#v4Gold)" stroke-width="${Math.max(
        1,
        3 * scale
    )}" /><circle cx="${centerX}" cy="${centerY}" r="${size / 2 - 10 * scale}" fill="none" stroke="#E8CD82" stroke-width="${Math.max(
        0.8,
        1.4 * scale
    )}" /><path d="M${centerX - 45 * scale} ${centerY + 26 * scale} C${centerX - 20 * scale} ${
        centerY + 50 * scale
    } ${centerX + 20 * scale} ${centerY + 50 * scale} ${centerX + 45 * scale} ${
        centerY + 26 * scale
    }" fill="none" stroke="#D2A34B" stroke-width="${Math.max(0.8, 1.4 * scale)}" />${logoSvg}${drawTextBlock({
        x: centerX,
        y: centerY + 31 * scale,
        lines: wrapText(brandName, small ? 8 : 8, 1),
        fontSize: 15 * scale,
        lineHeight: 15 * scale,
        fill: '#B17A24',
        fontWeight: 700,
        textAnchor: 'middle',
        letterSpacing: 1.2 * scale
    }).svg}${drawTextBlock({
        x: centerX,
        y: centerY + 48 * scale,
        lines: wrapText(brandTagline.toUpperCase(), small ? 16 : 18, 1),
        fontSize: 8.5 * scale,
        lineHeight: 8.5 * scale,
        fill: '#C0923D',
        fontWeight: 500,
        textAnchor: 'middle',
        fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF
    }).svg}`
}

const drawV4Icon = (type: string, x: number, y: number, size: number) => {
    const stroke = '#B9812B'
    const sw = Math.max(1, size / 10)
    if (type === 'user') {
        return `<circle cx="${x}" cy="${y - size * 0.22}" r="${size * 0.23}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><path d="M${x - size * 0.48} ${y + size * 0.52} C${x - size * 0.32} ${
            y + size * 0.12
        } ${x + size * 0.32} ${y + size * 0.12} ${x + size * 0.48} ${
            y + size * 0.52
        }" fill="none" stroke="${stroke}" stroke-width="${sw}" />`
    }
    if (type === 'calendar') {
        return `<rect x="${x - size * 0.45}" y="${y - size * 0.38}" width="${size * 0.9}" height="${
            size * 0.82
        }" rx="${size * 0.08}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><path d="M${
            x - size * 0.45
        } ${y - size * 0.14} H${x + size * 0.45} M${x - size * 0.24} ${
            y - size * 0.52
        } V${y - size * 0.25} M${x + size * 0.24} ${y - size * 0.52} V${
            y - size * 0.25
        }" stroke="${stroke}" stroke-width="${sw}" />`
    }
    if (type === 'pin') {
        return `<path d="M${x} ${y + size * 0.55} C${x - size * 0.44} ${y + size * 0.05} ${
            x - size * 0.36
        } ${y - size * 0.48} ${x} ${y - size * 0.48} C${x + size * 0.36} ${
            y - size * 0.48
        } ${x + size * 0.44} ${y + size * 0.05} ${x} ${y + size * 0.55} Z" fill="none" stroke="${stroke}" stroke-width="${sw}" /><circle cx="${x}" cy="${
            y - size * 0.16
        }" r="${size * 0.13}" fill="none" stroke="${stroke}" stroke-width="${sw}" />`
    }
    if (type === 'phone') {
        return `<path d="M${x - size * 0.36} ${y - size * 0.48} C${x - size * 0.52} ${
            y - size * 0.28
        } ${x - size * 0.34} ${y + size * 0.34} ${x + size * 0.18} ${y + size * 0.52}" fill="none" stroke="${stroke}" stroke-width="${sw}" stroke-linecap="round" />`
    }
    if (type === 'clock') {
        return `<circle cx="${x}" cy="${y}" r="${size * 0.45}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><path d="M${x} ${
            y - size * 0.24
        } V${y} H${x + size * 0.22}" fill="none" stroke="${stroke}" stroke-width="${sw}" stroke-linecap="round" />`
    }
    if (type === 'team') {
        return `<circle cx="${x}" cy="${y - size * 0.18}" r="${size * 0.18}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><circle cx="${
            x - size * 0.28
        }" cy="${y - size * 0.04}" r="${size * 0.14}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><circle cx="${
            x + size * 0.28
        }" cy="${y - size * 0.04}" r="${size * 0.14}" fill="none" stroke="${stroke}" stroke-width="${sw}" />`
    }
    return `<rect x="${x - size * 0.36}" y="${y - size * 0.46}" width="${size * 0.72}" height="${
        size * 0.9
    }" rx="${size * 0.06}" fill="none" stroke="${stroke}" stroke-width="${sw}" /><path d="M${
        x - size * 0.18
    } ${y - size * 0.18} H${x + size * 0.18} M${x - size * 0.18} ${y + size * 0.04} H${
        x + size * 0.18
    } M${x - size * 0.18} ${y + size * 0.26} H${x + size * 0.18}" stroke="${stroke}" stroke-width="${sw}" />`
}

const drawV4InfoPanel = (
    x: number,
    y: number,
    width: number,
    height: number,
    rows: Array<{ icon: string; label: string; value: string }>,
    scale: number
) => {
    const rowHeight = height / rows.length
    let svg = `<path d="M${x} ${y} H${x + width - 30 * scale} Q${x + width - 12 * scale} ${y} ${
        x + width - 12 * scale
    } ${y + 30 * scale} V${y + height} H${x + 24 * scale} Q${x} ${y + height} ${x} ${
        y + height - 24 * scale
    } V${y} Z" fill="#FFFDF8" fill-opacity="0.78" stroke="#D6A04B" stroke-width="${Math.max(
        1,
        1.4 * scale
    )}" />`
    rows.forEach((row, index) => {
        const rowY = y + index * rowHeight
        const cy = rowY + rowHeight / 2
        if (index > 0) {
            svg += `<path d="M${x + 86 * scale} ${rowY} H${x + width - 52 * scale}" stroke="#D9BD87" stroke-width="${Math.max(
                0.6,
                0.8 * scale
            )}" stroke-dasharray="${5 * scale} ${5 * scale}" />`
        }
        const valueFit = fitTextLine(row.value, scale < 1 ? 16 : 24, 24 * scale, 14 * scale)
        svg += drawV4Icon(row.icon, x + 58 * scale, cy, 18 * scale)
        svg += drawTextBlock({
            x: x + 105 * scale,
            y: cy + 9 * scale,
            lines: [row.label],
            fontSize: 25 * scale,
            lineHeight: 25 * scale,
            fill: '#49372C',
            fontWeight: 500
        }).svg
        svg += drawTextBlock({
            x: x + 238 * scale,
            y: cy + 9 * scale,
            lines: valueFit.lines,
            fontSize: valueFit.fontSize,
            lineHeight: valueFit.lineHeight,
            fill: '#2E2A27',
            fontWeight: 500
        }).svg
    })
    return svg
}

const renderV4OrderConfirmLetterSvg = (
    snapshot: OrderConfirmLetterSnapshot,
    small = false
) => {
    const width = small ? 540 : 1080
    const height = small ? 960 : 1920
    const scale = small ? 0.5 : 1
    const paperX = 62 * scale
    const paperY = 56 * scale
    const paperWidth = width - paperX * 2
    const paperHeight = height - paperY * 2
    const contentX = paperX + 70 * scale
    const contentWidth = paperWidth - 140 * scale
    const centerX = width / 2
    const brandName = toText(snapshot.brand_name) || DEFAULT_BRAND_NAME
    const brandTagline = toText(snapshot.brand_tagline) || V3_DEFAULT_HERO_EYEBROW
    const brandInitial = brandName.slice(0, Math.min(Math.max(brandName.length, 1), 4))
    const title = toText(snapshot.title) || DEFAULT_TITLE
    const paymentNode = toText(snapshot.payment_node) || '婚礼前 3 日'
    const serviceTeamLines = toStringArray(snapshot.service_team_lines)
    const staffNames = toStringArray(snapshot.service_staff_names)
    const teamText = serviceTeamLines.length
        ? serviceTeamLines.slice(0, 3).join('、')
        : staffNames.length
          ? staffNames.join('、')
          : '待确认'
    const rows = [
        { icon: 'clipboard', label: '订单编号：', value: toText(snapshot.order_sn) || '-' },
        { icon: 'user', label: '新人姓名：', value: toText(snapshot.customer_name) || '-' },
        {
            icon: 'calendar',
            label: '婚礼日期：',
            value: toText(snapshot.service_date_label) || toText(snapshot.service_date) || '-'
        },
        { icon: 'pin', label: '举办地点：', value: toText(snapshot.service_address) || '-' },
        { icon: 'team', label: '服务团队：', value: teamText },
        { icon: 'phone', label: '联系电话：', value: toText(snapshot.contact_mobile) || '-' },
        { icon: 'clock', label: '确认日期：', value: toText(snapshot.confirm_date) || '-' }
    ]
    const tipLines = wrapText(toText(snapshot.footer_note) || DEFAULT_FOOTER_NOTE, small ? 24 : 34, 2)

    const amountY = 1192 * scale
    const amountHeight = 296 * scale
    const third = contentWidth / 3
    const amountDetails = [
        [toText(snapshot.paid_label) || '已付定金', `¥ ${toText(snapshot.paid_amount) || '0.00'}`],
        ['待付尾款', `¥ ${toText(snapshot.remain_amount) || '0.00'}`],
        ['支付节点：', paymentNode]
    ]
    let amountSvg = `<rect x="${contentX}" y="${amountY}" width="${contentWidth}" height="${amountHeight}" rx="${
        10 * scale
    }" fill="url(#v4Amount)" stroke="#D6A04B" stroke-width="${Math.max(
        1,
        1.4 * scale
    )}" /><path d="M${contentX + 46 * scale} ${amountY + 168 * scale} H${
        contentX + contentWidth - 46 * scale
    }" stroke="#B88937" stroke-width="${Math.max(0.8, scale)}" />${drawV4OrnamentLine(
        contentX + contentWidth / 2,
        amountY + 30 * scale,
        170 * scale,
        '#D1A24B',
        scale
    )}`
    amountSvg += drawTextBlock({
        x: contentX + contentWidth / 2,
        y: amountY + 62 * scale,
        lines: ['合同合计金额'],
        fontSize: 28 * scale,
        lineHeight: 28 * scale,
        fill: '#2E241D',
        fontWeight: 600,
        textAnchor: 'middle'
    }).svg
    amountSvg += drawTextBlock({
        x: contentX + contentWidth / 2,
        y: amountY + 142 * scale,
        lines: [`¥ ${toText(snapshot.order_total_amount) || '0.00'}`],
        fontSize: 72 * scale,
        lineHeight: 72 * scale,
        fill: '#B98226',
        fontWeight: 700,
        textAnchor: 'middle',
        fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF
    }).svg
    amountDetails.forEach((item, index) => {
        const cx = contentX + third * index + third / 2
        const valueFit = fitTextLine(item[1], index === 2 ? 8 : 12, 23 * scale, 15 * scale)
        if (index > 0) {
            amountSvg += `<path d="M${contentX + third * index} ${amountY + 204 * scale} V${
                amountY + amountHeight - 40 * scale
            }" stroke="#B88937" stroke-width="${Math.max(0.8, scale)}" stroke-opacity="0.65" />`
        }
        amountSvg += drawTextBlock({
            x: cx,
            y: amountY + 230 * scale,
            lines: [item[0]],
            fontSize: 23 * scale,
            lineHeight: 23 * scale,
            fill: '#322820',
            fontWeight: 600,
            textAnchor: 'middle'
        }).svg
        amountSvg += drawTextBlock({
            x: cx,
            y: amountY + 266 * scale,
            lines: valueFit.lines,
            fontSize: valueFit.fontSize,
            lineHeight: valueFit.lineHeight,
            fill: '#322820',
            fontWeight: 500,
            textAnchor: 'middle'
        }).svg
    })

    const sections = [
        drawV4Seal(
            centerX,
            112 * scale,
            122 * scale,
            brandName,
            brandTagline,
            toText(snapshot.brand_logo_data_uri),
            brandInitial,
            small
        ),
        drawV4OrnamentLine(centerX, 328 * scale, 260 * scale, '#C99842', scale),
        drawTextBlock({
            x: centerX,
            y: 436 * scale,
            lines: [title],
            fontSize: 72 * scale,
            lineHeight: 72 * scale,
            fill: '#3B2116',
            fontWeight: 700,
            textAnchor: 'middle',
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF,
            letterSpacing: 10 * scale
        }).svg,
        drawTextBlock({
            x: centerX,
            y: 532 * scale,
            lines: [V4_DEFAULT_SUBTITLE],
            fontSize: 27 * scale,
            lineHeight: 27 * scale,
            fill: '#8D6F50',
            fontWeight: 500,
            textAnchor: 'middle',
            fontFamily: ORDER_CONFIRM_LETTER_FONT_FAMILY_SERIF
        }).svg,
        drawV4InfoPanel(contentX, 604 * scale, contentWidth, 538 * scale, rows, scale),
        amountSvg,
        `<rect x="${contentX}" y="${1532 * scale}" width="${contentWidth}" height="${
            104 * scale
        }" rx="${10 * scale}" fill="#FFFDF8" fill-opacity="0.72" stroke="#D9B96E" stroke-width="${Math.max(
            0.8,
            scale
        )}" stroke-dasharray="${5 * scale} ${5 * scale}" />`,
        drawTextBlock({
            x: centerX,
            y: 1554 * scale,
            lines: ['温馨提示'],
            fontSize: 24 * scale,
            lineHeight: 24 * scale,
            fill: '#7C5C2B',
            fontWeight: 600,
            textAnchor: 'middle'
        }).svg,
        drawTextBlock({
            x: centerX,
            y: 1600 * scale,
            lines: tipLines,
            fontSize: 22 * scale,
            lineHeight: 28 * scale,
            fill: '#4C4035',
            fontWeight: 500,
            textAnchor: 'middle'
        }).svg,
        drawTextBlock({
            x: contentX + contentWidth * 0.25,
            y: 1696 * scale,
            lines: ['客户签名'],
            fontSize: 24 * scale,
            lineHeight: 24 * scale,
            fill: '#342A23',
            fontWeight: 500,
            textAnchor: 'middle'
        }).svg,
        drawTextBlock({
            x: contentX + contentWidth * 0.75,
            y: 1696 * scale,
            lines: ['婚礼顾问签署'],
            fontSize: 24 * scale,
            lineHeight: 24 * scale,
            fill: '#342A23',
            fontWeight: 500,
            textAnchor: 'middle'
        }).svg,
        `<path d="M${contentX + 32 * scale} ${1760 * scale} H${
            contentX + contentWidth / 2 - 70 * scale
        } M${contentX + contentWidth / 2 + 70 * scale} ${1760 * scale} H${
            contentX + contentWidth - 32 * scale
        }" stroke="#927863" stroke-width="${Math.max(0.8, scale)}" />`
    ]

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><defs><linearGradient id="v4Bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#F3E1C9" /><stop offset="48%" stop-color="#FFF6EA" /><stop offset="100%" stop-color="#D7BE9E" /></linearGradient><linearGradient id="v4Paper" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FFFDF7" /><stop offset="100%" stop-color="#FFF7E9" /></linearGradient><linearGradient id="v4Gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#E9C66A" /><stop offset="42%" stop-color="#B57A23" /><stop offset="100%" stop-color="#F0D27D" /></linearGradient><linearGradient id="v4Amount" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FFF8E9" /><stop offset="100%" stop-color="#F9E8C4" /></linearGradient><filter id="v4Shadow" x="-20%" y="-20%" width="150%" height="150%"><feDropShadow dx="${10 * scale}" dy="${24 * scale}" stdDeviation="${14 * scale}" flood-color="#7D5434" flood-opacity="0.22" /></filter></defs><rect width="100%" height="100%" fill="url(#v4Bg)" /><rect x="${paperX}" y="${paperY}" width="${paperWidth}" height="${paperHeight}" fill="url(#v4Paper)" filter="url(#v4Shadow)" />${sections.join(
        ''
    )}</svg>`
}

export function renderOrderConfirmLetterSvg(
    snapshot: OrderConfirmLetterSnapshot,
    options?: RenderOptionsInput
) {
    const resolved = resolveRenderOptions(options)
    if (isV4Spec(resolved.renderSpecVersion)) {
        return renderV4OrderConfirmLetterSvg(snapshot, resolved.small)
    }
    if (isV3Spec(resolved.renderSpecVersion)) {
        return renderV3OrderConfirmLetterSvg(snapshot, resolved.small)
    }
    return isV2Spec(resolved.renderSpecVersion)
        ? renderV2OrderConfirmLetterSvg(snapshot, resolved.small)
        : renderV1OrderConfirmLetterSvg(snapshot, resolved.small)
}

export function buildOrderConfirmLetterDataUrl(
    snapshot: OrderConfirmLetterSnapshot,
    options?: RenderOptionsInput
) {
    if (!hasRenderableSnapshot(snapshot)) {
        return ''
    }

    const svg = renderOrderConfirmLetterSvg(snapshot, options)
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`
}
