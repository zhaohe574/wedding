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

const DEFAULT_TITLE = '订单确认函'
const DEFAULT_HERO_EYEBROW = 'ORDER CONFIRMATION LETTER'
const DEFAULT_HERO_DESC =
    '为保证婚礼现场执行准确无误，系统已根据当前订单信息自动生成本次正式确认函。'
const DEFAULT_BRAND_NAME = 'LIKE WEDDING · 婚礼服务中心'
const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为婚礼服务安排与付款进度的核对凭证。'

const escapeXml = (value: string) =>
    String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;')

const toText = (value: unknown) => String(value ?? '').trim()

const toStringArray = (value: unknown) =>
    Array.isArray(value)
        ? value
              .map((item) => toText(item))
              .filter(Boolean)
        : []

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

const normalizeVersion = (version?: string) => toText(version).toLowerCase()

const resolveRenderOptions = (input?: RenderOptionsInput): Required<OrderConfirmLetterRenderOptions> => {
    if (typeof input === 'boolean') {
        return {
            renderSpecVersion: 'v1',
            small: input,
        }
    }

    return {
        renderSpecVersion: toText(input?.renderSpecVersion) || 'v1',
        small: Boolean(input?.small),
    }
}

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
    if (truncated && visibleLines.length) {
        const lastIndex = visibleLines.length - 1
        const lastLine = visibleLines[lastIndex]
        visibleLines[lastIndex] = `${lastLine.slice(0, Math.max(maxCharsPerLine - 3, 0))}...`
    }

    return visibleLines.length ? visibleLines : ['']
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
}: TextBlockOptions) => {
    const safeLines = lines.length ? lines : ['']
    const svg = safeLines
        .map(
            (line, index) =>
                `<text x="${x}" y="${y + index * lineHeight}" text-anchor="${textAnchor}" font-size="${fontSize}" font-weight="${fontWeight}" fill="${fill}">${escapeXml(line)}</text>`
        )
        .join('')

    return {
        svg,
        height: safeLines.length * lineHeight,
    }
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
    radius,
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
        fontWeight: 700,
    })
    const bodyY = innerY + titleBlock.height + gap + bodySize
    const bodyBlock = drawTextBlock({
        x: innerX,
        y: bodyY,
        lines,
        fontSize: bodySize,
        lineHeight,
        fill: bodyColor,
        fontWeight: 600,
    })
    const height = paddingY * 2 + titleBlock.height + gap + bodyBlock.height

    return {
        svg: `<g transform="translate(0 ${y})"><rect x="${x}" y="0" width="${width}" height="${height}" rx="${radius}" fill="${fill}" stroke="${stroke}" />${titleBlock.svg}${bodyBlock.svg}</g>`,
        height,
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
        `联系电话：${toText(snapshot.contact_mobile)}`,
    ]
}

const renderV1OrderConfirmLetterSvg = (
    snapshot: OrderConfirmLetterSnapshot,
    small = false
) => {
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
        `<text x="${width / 2}" y="${y}" text-anchor="middle" font-size="${titleSize}" font-weight="700" fill="#1e2432">${escapeXml(
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
                `<text x="${padding}" y="${y}" font-size="${fontSize}" font-weight="${fontWeight}" fill="#1e2432">${escapeXml(line)}</text>`
            )
            y += lineHeight
        })
        y += small ? 12 : 20
    })

    texts.push(
        `<text x="${padding}" y="${y}" font-size="${textSize}" font-weight="600" fill="#1e2432">备注：</text>`
    )
    y += lineHeight
    wrapText(toText(snapshot.remark_content), small ? 22 : 28, 6).forEach((line) => {
        texts.push(
            `<text x="${padding}" y="${y}" font-size="${textSize}" fill="#5b6475">${escapeXml(line)}</text>`
        )
        y += lineHeight
    })

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="100%" height="100%" fill="#ffffff" rx="24" />${texts.join(
        ''
    )}</svg>`
}

const renderV2OrderConfirmLetterSvg = (
    snapshot: OrderConfirmLetterSnapshot,
    small = false
) => {
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
        toText(snapshot.confirm_date) ? `确认日期：${toText(snapshot.confirm_date)}` : '',
    ].filter(Boolean)
    const weddingLines = [
        `客户姓名：${toText(snapshot.customer_name) || '-'}`,
        `婚礼日期：${toText(snapshot.service_date_label) || toText(snapshot.service_date) || '-'}`,
        `举办地点：${toText(snapshot.service_address) || '-'}`,
    ].flatMap((line) => wrapText(line, small ? 18 : 24, small ? 2 : 2))

    const serviceTeamLines = toStringArray(snapshot.service_team_lines)
    const staffNames = toStringArray(snapshot.service_staff_names)
    const teamLines = (serviceTeamLines.length
        ? serviceTeamLines
        : staffNames.length
          ? [`服务人员：${staffNames.join('、')}`]
          : ['服务人员：待补充'])
        .flatMap((line) => wrapText(line, small ? 18 : 24, 2))
        .slice(0, small ? 4 : 5)

    const amountDetailLines = [
        `${toText(snapshot.paid_label) || '已付定金'}：¥${toText(snapshot.paid_amount) || '0.00'}`,
        `待付尾款：¥${toText(snapshot.remain_amount) || '0.00'}`,
    ]

    const remarkLines = [
        `联系电话：${toText(snapshot.contact_mobile) || '-'}`,
        ...wrapText(toText(snapshot.remark_content) || DEFAULT_FOOTER_NOTE, small ? 18 : 24, small ? 4 : 5),
    ]

    const heroDescLines = wrapText(DEFAULT_HERO_DESC, small ? 22 : 30, 2)
    const heroMetaWrappedLines = (heroMetaLines.length ? heroMetaLines : ['确认信息待更新']).flatMap(
        (line) => wrapText(line, small ? 20 : 30, 1)
    )
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
        fill: '#7F7B78',
        fontWeight: 700,
    })
    const amountTotalY = cardPaddingY + amountTitleBlock.height + cardGap + amountBigSize
    const amountTotalBlock = drawTextBlock({
        x: contentX + cardPaddingX,
        y: amountTotalY,
        lines: [`合计 ¥${toText(snapshot.order_total_amount) || '0.00'}`],
        fontSize: amountBigSize,
        lineHeight: amountBigLineHeight,
        fill: '#E85A4F',
        fontWeight: 700,
    })
    const amountDetailsY = amountTotalY + amountTotalBlock.height + cardGap + cardBodySize
    const amountDetailsBlock = drawTextBlock({
        x: contentX + cardPaddingX,
        y: amountDetailsY,
        lines: amountDetailLines,
        fontSize: cardBodySize,
        lineHeight: cardLineHeight,
        fill: '#1E2432',
        fontWeight: 600,
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
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${heroHeight}" rx="${small ? 28 : 40}" fill="url(#heroGradient)" stroke="#F4C7BF" /><circle cx="${contentX + contentWidth - (small ? 44 : 90)}" cy="${currentY + (small ? 42 : 78)}" r="${small ? 22 : 48}" fill="#FFFFFF" fill-opacity="0.36" /><circle cx="${contentX + contentWidth - (small ? 86 : 160)}" cy="${currentY + (small ? 86 : 146)}" r="${small ? 12 : 24}" fill="#FFFFFF" fill-opacity="0.24" />`
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
            fill: '#C99B73',
            fontWeight: 700,
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
            fill: '#1E2432',
            fontWeight: 700,
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
            fill: '#7F7B78',
            fontWeight: 500,
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
            fill: '#E85A4F',
            fontWeight: 700,
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
            fill: '#FFF8F5',
            stroke: '#EFE6E1',
            titleColor: '#7F7B78',
            bodyColor: '#1E2432',
            titleSize: cardTitleSize,
            bodySize: cardBodySize,
            lineHeight: cardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius,
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
            stroke: '#EFE6E1',
            titleColor: '#7F7B78',
            bodyColor: '#1E2432',
            titleSize: cardTitleSize,
            bodySize: cardBodySize,
            lineHeight: cardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius,
        }).svg
    )
    currentY += teamCardHeight + sectionGap

    sections.push(
        `<rect x="${contentX}" y="${currentY}" width="${contentWidth}" height="${amountCardHeight}" rx="${small ? 24 : 32}" fill="#FFFFFF" stroke="#F4C7BF" /><rect x="${contentX + contentWidth - (small ? 90 : 148)}" y="${currentY + (small ? 18 : 22)}" width="${small ? 62 : 104}" height="${small ? 24 : 36}" rx="999" fill="#FFF1EE" /><text x="${contentX + contentWidth - (small ? 59 : 96)}" y="${currentY + (small ? 34 : 47)}" text-anchor="middle" font-size="${small ? 11 : 18}" font-weight="700" fill="#E85A4F">金额重点</text>${amountTitleBlock.svg.replace(
            new RegExp(`x="${contentX + cardPaddingX}"`, 'g'),
            `x="${contentX + cardPaddingX}"`
        ).replace(/y="([^\"]+)"/g, (_match, value) => `y="${Number(value) + currentY}"`)}${amountTotalBlock.svg.replace(/y="([^\"]+)"/g, (_match, value) => `y="${Number(value) + currentY}"`)}${amountDetailsBlock.svg.replace(/y="([^\"]+)"/g, (_match, value) => `y="${Number(value) + currentY}"`)}`
    )
    currentY += amountCardHeight + sectionGap

    sections.push(
        drawInfoCard({
            x: contentX,
            y: currentY,
            width: contentWidth,
            title: '备注与联系',
            lines: remarkLines,
            fill: '#FFF8F5',
            stroke: '#EFE6E1',
            titleColor: '#7F7B78',
            bodyColor: '#1E2432',
            titleSize: cardTitleSize,
            bodySize: remarkCardBodySize,
            lineHeight: remarkCardLineHeight,
            paddingX: cardPaddingX,
            paddingY: cardPaddingY,
            gap: cardGap,
            radius: cardRadius,
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
        fill: '#7F7B78',
        fontWeight: 500,
        textAnchor: 'middle',
    })

    sections.push(
        `<rect x="${contentX}" y="${footerLineY}" width="${contentWidth}" height="1" fill="#F1E4DD" /><text x="${paperX + paperWidth / 2}" y="${footerBrandY}" text-anchor="middle" font-size="${footerBrandSize}" font-weight="700" letter-spacing="${small ? 0.8 : 1.2}" fill="#C99B73">${escapeXml(
            toText(snapshot.brand_name) || DEFAULT_BRAND_NAME
        )}</text>${footerNoteBlock.svg}`
    )

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><defs><linearGradient id="pageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#FCFBF9" /><stop offset="100%" stop-color="#FFF4EF" /></linearGradient><linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FFF7F4" /><stop offset="100%" stop-color="#FDE9E2" /></linearGradient><filter id="paperShadow" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="24" stdDeviation="18" flood-color="#DAB5A6" flood-opacity="0.18" /></filter></defs><rect width="100%" height="100%" fill="url(#pageGradient)" /><rect x="${paperX}" y="${paperY}" width="${paperWidth}" height="${paperHeight}" rx="${small ? 28 : 48}" fill="#FFFDFB" stroke="#EFE6E1" stroke-width="2" filter="url(#paperShadow)" />${sections.join(
        ''
    )}</svg>`
}

export function renderOrderConfirmLetterSvg(
    snapshot: OrderConfirmLetterSnapshot,
    options?: RenderOptionsInput
) {
    const resolved = resolveRenderOptions(options)
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
