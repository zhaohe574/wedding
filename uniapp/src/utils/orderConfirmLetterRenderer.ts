export interface OrderConfirmLetterSnapshot {
    title: string
    customer_name: string
    service_date: string
    service_address: string
    service_staff_names: string[]
    order_total_amount: string
    paid_label: string
    paid_amount: string
    remain_amount: string
    confirm_date: string
    contact_mobile: string
    remark_content: string
}

const escapeXml = (value: string) =>
    String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;')

const wrapText = (text: string, maxCharsPerLine: number, maxLines: number) => {
    const source = String(text || '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n')
    const lines: string[] = []
    for (const segment of source.split('\n')) {
        let remain = segment.trim()
        if (!remain) {
            lines.push('')
            continue
        }
        while (remain.length > maxCharsPerLine) {
            lines.push(remain.slice(0, maxCharsPerLine))
            remain = remain.slice(maxCharsPerLine)
            if (lines.length >= maxLines) break
        }
        if (lines.length >= maxLines) break
        lines.push(remain)
        if (lines.length >= maxLines) break
    }
    if (lines.length > maxLines) {
        return lines.slice(0, maxLines)
    }
    if (source.length > lines.join('').length) {
        const lastIndex = Math.max(lines.length - 1, 0)
        lines[lastIndex] = `${lines[lastIndex].slice(0, Math.max(maxCharsPerLine - 3, 0))}...`
    }
    return lines
}

const buildRows = (snapshot: OrderConfirmLetterSnapshot) => {
    return [
        `客户名称：${snapshot.customer_name || ''}`,
        `日期：${snapshot.service_date || ''}`,
        `地点：${snapshot.service_address || ''}`,
        `服务人员：${(snapshot.service_staff_names || []).join('、')}`,
        `订单总价：¥${snapshot.order_total_amount || '0.00'}`,
        `${snapshot.paid_label || '已付定金'}：¥${snapshot.paid_amount || '0.00'}`,
        `尾款剩余：¥${snapshot.remain_amount || '0.00'}`,
        `确认日期：${snapshot.confirm_date || ''}`,
        `联系电话：${snapshot.contact_mobile || ''}`
    ]
}

export function renderOrderConfirmLetterSvg(snapshot: OrderConfirmLetterSnapshot, small = false) {
    const width = small ? 540 : 1080
    const height = small ? 960 : 1920
    const padding = small ? 40 : 80
    const titleSize = small ? 28 : 56
    const textSize = small ? 18 : 36
    const amountSize = small ? 20 : 40
    const lineHeight = small ? 32 : 64
    let y = small ? 88 : 150

    const rows = buildRows(snapshot)
    const texts: string[] = []
    texts.push(
        `<text x="${
            width / 2
        }" y="${y}" text-anchor="middle" font-size="${titleSize}" font-weight="700" fill="#1e2432">${escapeXml(
            snapshot.title || '订单确认函'
        )}</text>`
    )
    y += small ? 56 : 100
    rows.forEach((row, index) => {
        const lines = wrapText(row, small ? 22 : 28, 2)
        const fontSize = index >= 4 && index <= 6 ? amountSize : textSize
        const fontWeight = index >= 4 && index <= 6 ? 700 : 400
        lines.forEach((line) => {
            texts.push(
                `<text x="${padding}" y="${y}" font-size="${fontSize}" font-weight="${fontWeight}" fill="#1e2432">${escapeXml(
                    line
                )}</text>`
            )
            y += lineHeight
        })
        y += small ? 12 : 20
    })

    texts.push(
        `<text x="${padding}" y="${y}" font-size="${textSize}" font-weight="600" fill="#1e2432">备注：</text>`
    )
    y += lineHeight
    wrapText(snapshot.remark_content || '', small ? 22 : 28, 6).forEach((line) => {
        texts.push(
            `<text x="${padding}" y="${y}" font-size="${textSize}" fill="#5b6475">${escapeXml(
                line
            )}</text>`
        )
        y += lineHeight
    })

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="100%" height="100%" fill="#ffffff" rx="24" />${texts.join(
        ''
    )}</svg>`
}

export function buildOrderConfirmLetterDataUrl(
    snapshot: OrderConfirmLetterSnapshot,
    small = false
) {
    const svg = renderOrderConfirmLetterSvg(snapshot, small)
    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`
}
