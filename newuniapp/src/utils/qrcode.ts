/**
 * 轻量零依赖离线 QR Code 生成器（Byte 模式 / ECC-M / Version 1-4，支持 <= 62 字节内容）。
 * 输出布尔矩阵与无压缩 PNG Data URI（兼容微信小程序 <image> 与 H5，无 canvas 弹层生命周期问题）。
 */

const GF_EXP = new Uint8Array(512)
const GF_LOG = new Uint8Array(256)

;(function initGaloisTables() {
    let x = 1
    for (let i = 0; i < 255; i++) {
        GF_EXP[i] = x
        GF_LOG[x] = i
        x <<= 1
        if (x & 0x100) x ^= 0x11d
    }
    for (let i = 255; i < 512; i++) {
        GF_EXP[i] = GF_EXP[i - 255]
    }
})()

function gfMul(a: number, b: number): number {
    if (a === 0 || b === 0) return 0
    return GF_EXP[GF_LOG[a] + GF_LOG[b]]
}

function buildRsGenerator(degree: number): number[] {
    let poly = [1]
    for (let i = 0; i < degree; i++) {
        const next = new Array(poly.length + 1).fill(0)
        const factor = GF_EXP[i]
        for (let j = 0; j < poly.length; j++) {
            next[j] ^= poly[j]
            next[j + 1] ^= gfMul(poly[j], factor)
        }
        poly = next
    }
    return poly
}

function computeEcc(data: number[], eccCount: number): number[] {
    const gen = buildRsGenerator(eccCount)
    const msg = data.concat(new Array(eccCount).fill(0))
    for (let i = 0; i < data.length; i++) {
        const coef = msg[i]
        if (coef !== 0) {
            for (let j = 0; j < gen.length; j++) {
                msg[i + j] ^= gfMul(gen[j], coef)
            }
        }
    }
    return msg.slice(data.length)
}

interface VersionSpec {
    version: number
    size: number
    dataCodewords: number
    eccPerBlock: number
    blocks: number
    align: number[]
}

const VERSIONS: VersionSpec[] = [
    { version: 1, size: 21, dataCodewords: 16, eccPerBlock: 10, blocks: 1, align: [] },
    { version: 2, size: 25, dataCodewords: 28, eccPerBlock: 16, blocks: 1, align: [6, 18] },
    { version: 3, size: 29, dataCodewords: 44, eccPerBlock: 26, blocks: 1, align: [6, 22] },
    { version: 4, size: 33, dataCodewords: 64, eccPerBlock: 18, blocks: 2, align: [6, 26] }
]

function encodeUtf8Bytes(text: string): number[] {
    const out: number[] = []
    for (let i = 0; i < text.length; i++) {
        let code = text.charCodeAt(i)
        if (code < 0x80) {
            out.push(code)
        } else if (code < 0x800) {
            out.push(0xc0 | (code >> 6), 0x80 | (code & 0x3f))
        } else if (code >= 0xd800 && code <= 0xdbff && i + 1 < text.length) {
            const next = text.charCodeAt(++i)
            code = ((code - 0xd800) << 10) + (next - 0xdc00) + 0x10000
            out.push(
                0xf0 | (code >> 18),
                0x80 | ((code >> 12) & 0x3f),
                0x80 | ((code >> 6) & 0x3f),
                0x80 | (code & 0x3f)
            )
        } else {
            out.push(0xe0 | (code >> 12), 0x80 | ((code >> 6) & 0x3f), 0x80 | (code & 0x3f))
        }
    }
    return out
}

function buildCodewords(bytes: number[], spec: VersionSpec): number[] {
    const bits: number[] = []
    const pushBits = (val: number, len: number) => {
        for (let i = len - 1; i >= 0; i--) {
            bits.push((val >> i) & 1)
        }
    }

    // Byte mode indicator (0100) + 8-bit character count
    pushBits(0b0100, 4)
    pushBits(bytes.length, 8)
    for (const b of bytes) pushBits(b, 8)

    const totalDataBits = spec.dataCodewords * 8
    const terminator = Math.min(4, totalDataBits - bits.length)
    for (let i = 0; i < terminator; i++) bits.push(0)
    while (bits.length % 8 !== 0) bits.push(0)

    const data: number[] = []
    for (let i = 0; i < bits.length; i += 8) {
        let v = 0
        for (let j = 0; j < 8; j++) v = (v << 1) | bits[i + j]
        data.push(v)
    }
    const pads = [0xec, 0x11]
    let p = 0
    while (data.length < spec.dataCodewords) {
        data.push(pads[p++ & 1])
    }

    if (spec.blocks === 1) {
        return data.concat(computeEcc(data, spec.eccPerBlock))
    }

    // Version 4 ECC-M: 2 equal blocks of 32 data + 18 ecc
    const blockLen = spec.dataCodewords / spec.blocks
    const dataBlocks: number[][] = []
    const eccBlocks: number[][] = []
    for (let b = 0; b < spec.blocks; b++) {
        const slice = data.slice(b * blockLen, (b + 1) * blockLen)
        dataBlocks.push(slice)
        eccBlocks.push(computeEcc(slice, spec.eccPerBlock))
    }
    const interleaved: number[] = []
    for (let i = 0; i < blockLen; i++) {
        for (let b = 0; b < spec.blocks; b++) interleaved.push(dataBlocks[b][i])
    }
    for (let i = 0; i < spec.eccPerBlock; i++) {
        for (let b = 0; b < spec.blocks; b++) interleaved.push(eccBlocks[b][i])
    }
    return interleaved
}

function drawFinder(grid: number[][], reserved: boolean[][], row: number, col: number) {
    const size = grid.length
    for (let r = -1; r <= 7; r++) {
        for (let c = -1; c <= 7; c++) {
            const rr = row + r
            const cc = col + c
            if (rr < 0 || rr >= size || cc < 0 || cc >= size) continue
            const inOuter = r >= 0 && r <= 6 && (c === 0 || c === 6)
            const inOuterH = c >= 0 && c <= 6 && (r === 0 || r === 6)
            const inInner = r >= 2 && r <= 4 && c >= 2 && c <= 4
            grid[rr][cc] = inOuter || inOuterH || inInner ? 1 : 0
            reserved[rr][cc] = true
        }
    }
}

function drawAlignment(grid: number[][], reserved: boolean[][], row: number, col: number) {
    for (let r = -2; r <= 2; r++) {
        for (let c = -2; c <= 2; c++) {
            const rr = row + r
            const cc = col + c
            if (reserved[rr][cc]) return
        }
    }
    for (let r = -2; r <= 2; r++) {
        for (let c = -2; c <= 2; c++) {
            const dark = Math.abs(r) === 2 || Math.abs(c) === 2 || (r === 0 && c === 0)
            grid[row + r][col + c] = dark ? 1 : 0
            reserved[row + r][col + c] = true
        }
    }
}

// Precomputed BCH(15,5) format bits for ECC-M (00) + mask 0..7, XORed with 101010000010010
const FORMAT_INFO_M = [
    0x5412, 0x5125, 0x5e7c, 0x5b4b, 0x45f9, 0x40ce, 0x4f97, 0x4aa0
]

function writeFormatInfo(grid: number[][], mask: number) {
    const size = grid.length
    const fmt = FORMAT_INFO_M[mask]
    for (let i = 0; i < 15; i++) {
        const bit = (fmt >> i) & 1
        // Horizontal + vertical top-left
        if (i < 6) grid[8][i] = bit
        else if (i < 8) grid[8][i + 1] = bit
        else grid[8][size - 15 + i] = bit

        if (i < 8) grid[size - 1 - i][8] = bit
        else if (i < 9) grid[15 - i][8] = bit
        else grid[14 - i][8] = bit
    }
}

function scorePenalty(grid: number[][]): number {
    const n = grid.length
    let penalty = 0
    for (let r = 0; r < n; r++) {
        let run = 1
        for (let c = 1; c < n; c++) {
            if (grid[r][c] === grid[r][c - 1]) {
                run++
                if (run === 5) penalty += 3
                else if (run > 5) penalty += 1
            } else {
                run = 1
            }
        }
    }
    for (let c = 0; c < n; c++) {
        let run = 1
        for (let r = 1; r < n; r++) {
            if (grid[r][c] === grid[r - 1][c]) {
                run++
                if (run === 5) penalty += 3
                else if (run > 5) penalty += 1
            } else {
                run = 1
            }
        }
    }
    for (let r = 0; r < n - 1; r++) {
        for (let c = 0; c < n - 1; c++) {
            const v = grid[r][c]
            if (v === grid[r][c + 1] && v === grid[r + 1][c] && v === grid[r + 1][c + 1]) {
                penalty += 3
            }
        }
    }
    return penalty
}

/** 生成标准 QR 二维矩阵（true = 黑格，false = 白格） */
export function buildQrMatrix(text: string): boolean[][] {
    const bytes = encodeUtf8Bytes(text)
    const spec =
        VERSIONS.find((v) => v.dataCodewords * 8 - 12 >= bytes.length * 8) ||
        VERSIONS[VERSIONS.length - 1]
    const safeBytes = bytes.slice(0, Math.floor((spec.dataCodewords * 8 - 12) / 8))
    const codewords = buildCodewords(safeBytes, spec)

    const size = spec.size
    const baseGrid = Array.from({ length: size }, () => new Array(size).fill(0))
    const reserved = Array.from({ length: size }, () => new Array(size).fill(false))

    drawFinder(baseGrid, reserved, 0, 0)
    drawFinder(baseGrid, reserved, 0, size - 7)
    drawFinder(baseGrid, reserved, size - 7, 0)

    for (let i = 8; i < size - 8; i++) {
        const dark = i % 2 === 0 ? 1 : 0
        baseGrid[6][i] = dark
        reserved[6][i] = true
        baseGrid[i][6] = dark
        reserved[i][6] = true
    }

    for (const ar of spec.align) {
        for (const ac of spec.align) {
            drawAlignment(baseGrid, reserved, ar, ac)
        }
    }

    // Dark module + reserve format info cells
    baseGrid[4 * spec.version + 9][8] = 1
    reserved[4 * spec.version + 9][8] = true
    for (let i = 0; i < 9; i++) {
        reserved[8][i] = true
        reserved[i][8] = true
    }
    for (let i = 0; i < 8; i++) {
        reserved[8][size - 1 - i] = true
        reserved[size - 1 - i][8] = true
    }

    // Place data bits in zig-zag order
    const bitStream: number[] = []
    for (const cw of codewords) {
        for (let b = 7; b >= 0; b--) bitStream.push((cw >> b) & 1)
    }

    let bitIdx = 0
    let dirUp = true
    for (let col = size - 1; col > 0; col -= 2) {
        if (col === 6) col--
        for (let step = 0; step < size; step++) {
            const row = dirUp ? size - 1 - step : step
            for (let dx = 0; dx < 2; dx++) {
                const c = col - dx
                if (!reserved[row][c]) {
                    baseGrid[row][c] = bitIdx < bitStream.length ? bitStream[bitIdx++] : 0
                }
            }
        }
        dirUp = !dirUp
    }

    const maskFns = [
        (r: number, c: number) => (r + c) % 2 === 0,
        (r: number, _c: number) => r % 2 === 0,
        (_r: number, c: number) => c % 3 === 0,
        (r: number, c: number) => (r + c) % 3 === 0,
        (r: number, c: number) => (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0,
        (r: number, c: number) => ((r * c) % 2) + ((r * c) % 3) === 0,
        (r: number, c: number) => (((r * c) % 2) + ((r * c) % 3)) % 2 === 0,
        (r: number, c: number) => (((r + c) % 2) + ((r * c) % 3)) % 2 === 0
    ]

    let bestGrid = baseGrid
    let bestScore = Infinity
    for (let m = 0; m < 8; m++) {
        const candidate = baseGrid.map((row) => row.slice())
        const fn = maskFns[m]
        for (let r = 0; r < size; r++) {
            for (let c = 0; c < size; c++) {
                if (!reserved[r][c] && fn(r, c)) {
                    candidate[r][c] ^= 1
                }
            }
        }
        writeFormatInfo(candidate, m)
        const s = scorePenalty(candidate)
        if (s < bestScore) {
            bestScore = s
            bestGrid = candidate
        }
    }

    return bestGrid.map((row) => row.map((v) => v === 1))
}

const CRC_TABLE = (() => {
    const t = new Uint32Array(256)
    for (let n = 0; n < 256; n++) {
        let c = n
        for (let k = 0; k < 8; k++) {
            c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
        }
        t[n] = c >>> 0
    }
    return t
})()

function crc32(bytes: Uint8Array): number {
    let c = 0xffffffff
    for (let i = 0; i < bytes.length; i++) {
        c = CRC_TABLE[(c ^ bytes[i]) & 0xff] ^ (c >>> 8)
    }
    return (c ^ 0xffffffff) >>> 0
}

function adler32(bytes: Uint8Array): number {
    let a = 1
    let b = 0
    for (let i = 0; i < bytes.length; i++) {
        a = (a + bytes[i]) % 65521
        b = (b + a) % 65521
    }
    return ((b << 16) | a) >>> 0
}

function writeU32BE(buf: Uint8Array, offset: number, val: number) {
    buf[offset] = (val >>> 24) & 0xff
    buf[offset + 1] = (val >>> 16) & 0xff
    buf[offset + 2] = (val >>> 8) & 0xff
    buf[offset + 3] = val & 0xff
}

function makeChunk(type: string, data: Uint8Array): Uint8Array {
    const chunk = new Uint8Array(12 + data.length)
    writeU32BE(chunk, 0, data.length)
    for (let i = 0; i < 4; i++) chunk[4 + i] = type.charCodeAt(i)
    chunk.set(data, 8)
    writeU32BE(chunk, 8 + data.length, crc32(chunk.subarray(4, 8 + data.length)))
    return chunk
}

function bytesToBase64(bytes: Uint8Array): string {
    if (typeof uni !== 'undefined' && typeof uni.arrayBufferToBase64 === 'function') {
        return uni.arrayBufferToBase64(bytes.buffer as ArrayBuffer)
    }
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
    let out = ''
    for (let i = 0; i < bytes.length; i += 3) {
        const b0 = bytes[i]
        const b1 = i + 1 < bytes.length ? bytes[i + 1] : 0
        const b2 = i + 2 < bytes.length ? bytes[i + 2] : 0
        out += chars[b0 >> 2]
        out += chars[((b0 & 3) << 4) | (b1 >> 4)]
        out += i + 1 < bytes.length ? chars[((b1 & 15) << 2) | (b2 >> 6)] : '='
        out += i + 2 < bytes.length ? chars[b2 & 63] : '='
    }
    return out
}

/**
 * 将文本编码为离线高清 PNG Data URI（可直接绑定给 <image :src="..." />）。
 * 前景色为品牌墨黑 rgb(24,22,20)，背景色为暖白 rgb(255,253,248)。
 */
export function buildQrPngDataUrl(text: string, scale = 6, quietZone = 2): string {
    if (!text) return ''
    const matrix = buildQrMatrix(text)
    const n = matrix.length
    const imgSize = (n + quietZone * 2) * scale
    const rowStride = 1 + imgSize * 3
    const raw = new Uint8Array(rowStride * imgSize)

    for (let y = 0; y < imgSize; y++) {
        const rowStart = y * rowStride
        raw[rowStart] = 0 // PNG filter type 0
        const my = Math.floor(y / scale) - quietZone
        for (let x = 0; x < imgSize; x++) {
            const mx = Math.floor(x / scale) - quietZone
            const dark = my >= 0 && my < n && mx >= 0 && mx < n && matrix[my][mx]
            const px = rowStart + 1 + x * 3
            if (dark) {
                raw[px] = 24
                raw[px + 1] = 22
                raw[px + 2] = 20
            } else {
                raw[px] = 255
                raw[px + 1] = 253
                raw[px + 2] = 248
            }
        }
    }

    // Wrap raw scanlines in uncompressed zlib stream (65535-byte stored blocks)
    const maxBlock = 65535
    const blockCount = Math.ceil(raw.length / maxBlock)
    const zlib = new Uint8Array(2 + raw.length + blockCount * 5 + 4)
    zlib[0] = 0x78
    zlib[1] = 0x01
    let zPos = 2
    let rPos = 0
    while (rPos < raw.length) {
        const len = Math.min(maxBlock, raw.length - rPos)
        const isFinal = rPos + len >= raw.length ? 1 : 0
        zlib[zPos++] = isFinal
        zlib[zPos++] = len & 0xff
        zlib[zPos++] = (len >>> 8) & 0xff
        const nlen = ~len & 0xffff
        zlib[zPos++] = nlen & 0xff
        zlib[zPos++] = (nlen >>> 8) & 0xff
        zlib.set(raw.subarray(rPos, rPos + len), zPos)
        zPos += len
        rPos += len
    }
    writeU32BE(zlib, zPos, adler32(raw))

    const ihdr = new Uint8Array(13)
    writeU32BE(ihdr, 0, imgSize)
    writeU32BE(ihdr, 4, imgSize)
    ihdr[8] = 8 // bit depth
    ihdr[9] = 2 // RGB color
    ihdr[10] = 0
    ihdr[11] = 0
    ihdr[12] = 0

    const sig = new Uint8Array([137, 80, 78, 71, 13, 10, 26, 10])
    const c1 = makeChunk('IHDR', ihdr)
    const c2 = makeChunk('IDAT', zlib)
    const c3 = makeChunk('IEND', new Uint8Array(0))
    const png = new Uint8Array(sig.length + c1.length + c2.length + c3.length)
    png.set(sig, 0)
    png.set(c1, sig.length)
    png.set(c2, sig.length + c1.length)
    png.set(c3, sig.length + c1.length + c2.length)

    return 'data:image/png;base64,' + bytesToBase64(png)
}
