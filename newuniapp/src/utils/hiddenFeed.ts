/**
 * 「不感兴趣」的本地隐藏记录（feed 页写、设置页读并恢复）。
 *
 * 为什么抽成公共模块：feed.vue 与 settings.vue 都要读写同一份缓存，
 * 不能让设置页依赖页面内部函数，也不能两边各自定义 key/格式。
 *
 * ⚠️ key 必须保持 'feed_hidden_ids' —— 线上已有用户用旧格式（number[]）写过，
 *    换 key 会让这些人已隐藏的内容全部失效。本模块负责就地迁移，见 getHidden()。
 *
 * 纯前端本地功能：不请求后端、不写接口、key 不带 expire（cache.set 第三参省略即永久）。
 */
import cache from '@/utils/cache'

const HIDDEN_KEY = 'feed_hidden_ids'

const TITLE_MAX = 24
const EXCERPT_MAX = 40

export interface HiddenRecord {
    id: number
    title: string
    excerpt: string
    author: string
    time: string
}

/** hide() 需要的最小行形状，避免 utils 反向依赖页面类型 */
export interface FeedRowLike {
    id: number | string
    title?: string
    content?: string
    user_nickname?: string
    create_time?: string
}

function asText(v: unknown): string {
    return String(v ?? '')
        .replace(/\s+/g, ' ')
        .trim()
}

function cut(v: string, max: number): string {
    return v.length > max ? `${v.slice(0, max)}…` : v
}

function isRecord(v: unknown): v is HiddenRecord {
    return (
        !!v &&
        typeof v === 'object' &&
        !Array.isArray(v) &&
        Number.isFinite(Number((v as HiddenRecord).id))
    )
}

/**
 * 读隐藏记录，并就地兼容旧格式：
 *  - 旧版写的是 number[] → 逐项转成 { id, title:'动态 #id', 其余空串 }；
 *  - 新版是 HiddenRecord[] → 补齐可能缺失的字段；
 *  - 混合数组（老数据 + 新记录）也能逐项处理；
 *  - 非数组、脏值、JSON 解析失败一律返回 []，**不抛错、不把老数据当垃圾丢掉**。
 * 只要发生过迁移就立刻用新格式写回，避免每次读都转换。
 */
export function getHidden(): HiddenRecord[] {
    const raw = cache.get(HIDDEN_KEY)
    if (!Array.isArray(raw)) return []

    let migrated = false
    const seen = new Set<number>()
    const records: HiddenRecord[] = []

    for (const item of raw) {
        let rec: HiddenRecord
        if (isRecord(item)) {
            rec = {
                id: Number(item.id),
                title: asText(item.title),
                excerpt: asText(item.excerpt),
                author: asText(item.author),
                time: asText(item.time)
            }
        } else {
            const id = Number(item)
            if (!Number.isFinite(id) || id <= 0) continue
            rec = { id, title: `动态 #${id}`, excerpt: '', author: '', time: '' }
            migrated = true
        }
        if (seen.has(rec.id)) continue
        seen.add(rec.id)
        records.push(rec)
    }

    if (migrated) cache.set(HIDDEN_KEY, records)
    return records
}

/** 列表过滤用的 id 集合（每次读都经过 getHidden，因此自动触发旧格式迁移） */
export function hiddenIdSet(): Set<number> {
    return new Set(getHidden().map((r) => r.id))
}

/**
 * 由一条动态（列表行或详情接口返回）生成展示快照。
 * hide() 与「补全旧占位记录」共用这一份规则，避免两处口径漂移：
 * 无标题时用内容当标题；内容摘要单独存 excerpt；作者/时间取扁平字段。
 */
export function buildHiddenRecord(id: number, post: FeedRowLike): HiddenRecord {
    const title = asText(post?.title)
    const content = asText(post?.content)
    return {
        id,
        title: cut(title || content, TITLE_MAX) || `动态 #${id}`,
        excerpt: cut(content, EXCERPT_MAX),
        author: asText(post?.user_nickname),
        time: asText(post?.create_time)
    }
}

/** 隐藏一条并存展示快照；重复隐藏同一 id 会覆盖旧快照、不产生重复项 */
export function hide(post: FeedRowLike): void {
    const id = Number(post?.id)
    if (!Number.isFinite(id) || id <= 0) return

    const next = getHidden().filter((r) => r.id !== id)
    next.push(buildHiddenRecord(id, post))
    cache.set(HIDDEN_KEY, next)
}

/**
 * 按 id 覆盖一条已有记录（用于把详情接口补齐的内容写回缓存）。
 * 语义与 hide() 不同：不新增、不改隐藏状态，只更新展示字段；id 不存在时不动。
 */
export function updateHiddenRecord(rec: HiddenRecord): void {
    const next = getHidden()
    const i = next.findIndex((r) => r.id === Number(rec?.id))
    if (i < 0) return
    next[i] = {
        id: Number(rec.id),
        title: asText(rec.title),
        excerpt: asText(rec.excerpt),
        author: asText(rec.author),
        time: asText(rec.time)
    }
    cache.set(HIDDEN_KEY, next)
}

/** 是否仍是需要补全的占位记录：标题是「动态 #id」或没有内容摘要 */
export function needsEnrichment(rec: HiddenRecord): boolean {
    return !rec.excerpt || rec.title === `动态 #${rec.id}`
}

/**
 * 后台补全占位记录（旧格式 number[] 迁移出来的只有 id）。
 * fetchDetail 由调用方注入（本模块不依赖 api 层）：返回详情数据，失败请抛错。
 * 并发用 Promise.allSettled，单条失败/404/下架都静默跳过，不影响其它记录、不抛给页面。
 *
 * 只**返回**补全到的记录，不写缓存：并发回调里各自读改写同一份缓存容易让人担心互相覆盖，
 * 统一交给调用方顺序 updateHiddenRecord() 写回，语义更清楚。
 */
export async function fetchHiddenDetails(
    fetchDetail: (id: number) => Promise<any>
): Promise<HiddenRecord[]> {
    const targets = getHidden().filter(needsEnrichment)
    if (!targets.length) return []

    const found: HiddenRecord[] = []
    await Promise.allSettled(
        targets.map(async (rec) => {
            try {
                const d = await fetchDetail(rec.id)
                if (!d || typeof d !== 'object') return
                found.push(buildHiddenRecord(rec.id, d))
            } catch {
                // 单条失败静默保留占位：动态可能已下架，不能因此让整个面板打不开
            }
        })
    )
    return found
}

/** 恢复单条 */
export function restore(id: number): void {
    cache.set(
        HIDDEN_KEY,
        getHidden().filter((r) => r.id !== Number(id))
    )
}

/** 一键全部恢复 */
export function clear(): void {
    cache.set(HIDDEN_KEY, [])
}
