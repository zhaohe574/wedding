/**
 * 目录类元数据缓存（风格标签 / 地区树）。
 *
 * 为什么需要它：
 *   1. 请求层只有 in-flight 去重，没有响应缓存（request/index.ts 的 dedupe 按 method|url|data 做 key，
 *      请求结束即失效）。要做「切类型零等待」必须自己包一层。
 *   2. 地区树 100 城约 155–167KB、365 城约 555–597KB（两次独立实测，UTF-8 紧凑 JSON）。
 *      实测 365/365 城都有区县，所以 districts 必须保留；但每个区县对象带六个字段，
 *      同城 10 个区县会把省市名重复存 10 遍 —— 入缓存前必须瘦身（−59%）。
 *
 * 读序：内存 ref → uni cache（30 分钟）→ 网络。
 * 懒加载：两个都只在用户首次打开对应弹窗时才拉，主页面冷启动只拉 categoryTree。
 */
import { getRegionTree, getServiceTags } from '@/api/catalog'
import { SERVICE_TAG_TYPE, type ServiceTagVO, type RegionProvinceVO } from '@/api/types/catalog'
import cache from '@/utils/cache'

/** 缓存时效：30 分钟，与 categoryTree 口径一致 */
const TTL = 30 * 60

const KEY_TAGS = 'query_tags_flat'
const KEY_REGION = 'query_region_tree'

/** 瘦身后的区县：只留渲染第二屏需要的两个字段 */
export interface SlimDistrict {
    district_code: string
    district_name: string
}

/** 瘦身后的城市：保留省市名（卡值与摘要条直接用），区县只留 code/name */
export interface SlimCity {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    districts: SlimDistrict[]
}

export interface SlimProvince {
    province_code: string
    province_name: string
    cities: SlimCity[]
}

/** 已选地区。null = 全国；两个 code 同生同死，让「只选了市」在类型层写不出来 */
export interface PickedRegion {
    cityCode: string
    cityName: string
    districtCode: string
    districtName: string
    provinceCode: string
    provinceName: string
}

const memory: { tags: ServiceTagVO[] | null; region: SlimProvince[] | null } = {
    tags: null,
    region: null
}

let tagsInflight: Promise<ServiceTagVO[]> | null = null
let regionInflight: Promise<SlimProvince[]> | null = null

function slimRegion(tree: RegionProvinceVO[]): SlimProvince[] {
    if (!Array.isArray(tree)) return []
    return tree
        .map((p) => ({
            province_code: String(p?.province_code || ''),
            province_name: String(p?.province_name || ''),
            cities: (Array.isArray(p?.cities) ? p.cities : [])
                .map((c) => ({
                    province_code: String(c?.province_code || p?.province_code || ''),
                    province_name: String(c?.province_name || p?.province_name || ''),
                    city_code: String(c?.city_code || ''),
                    city_name: String(c?.city_name || ''),
                    districts: (Array.isArray(c?.districts) ? c.districts : [])
                        .map((d) => ({
                            district_code: String(d?.district_code || ''),
                            district_name: String(d?.district_name || '')
                        }))
                        // 城市本身没有区县时该行不展示（实测 365/365 全覆盖，属防御分支）
                        .filter((d) => d.district_code && d.district_name)
                }))
                .filter((c) => c.city_code && c.city_name && c.districts.length > 0)
        }))
        .filter((p) => p.province_code && p.cities.length > 0)
}

/** 同步读（内存 → 本地缓存），拿不到返回 null；不触发网络 */
function readTagsSync(): ServiceTagVO[] | null {
    if (memory.tags) return memory.tags
    const cached = cache.get(KEY_TAGS) as ServiceTagVO[] | null
    if (Array.isArray(cached)) {
        memory.tags = cached
        return cached
    }
    return null
}

function readRegionSync(): SlimProvince[] | null {
    if (memory.region) return memory.region
    const cached = cache.get(KEY_REGION) as SlimProvince[] | null
    if (Array.isArray(cached)) {
        memory.region = cached
        return cached
    }
    return null
}

/** 拉全量标签（不传 type、不传 grouped、不传 category_id） */
function loadTags(): Promise<ServiceTagVO[]> {
    const hit = readTagsSync()
    if (hit) return Promise.resolve(hit)
    if (tagsInflight) return tagsInflight

    tagsInflight = getServiceTags()
        .then((rows) => {
            const list = (Array.isArray(rows) ? rows : [])
                .map((t: any) => ({
                    id: Number(t?.id) || 0,
                    name: String(t?.name || ''),
                    type: Number(t?.type) || SERVICE_TAG_TYPE.Style,
                    category_id: Number(t?.category_id) || 0
                }))
                .filter((t) => t.id > 0 && t.name)
            memory.tags = list
            cache.set(KEY_TAGS, list, TTL)
            return list
        })
        .finally(() => {
            tagsInflight = null
        })

    return tagsInflight
}

/** 拉地区树并瘦身 */
function loadRegionTree(): Promise<SlimProvince[]> {
    const hit = readRegionSync()
    if (hit) return Promise.resolve(hit)
    if (regionInflight) return regionInflight

    regionInflight = getRegionTree()
        .then((tree) => {
            const slim = slimRegion(tree as RegionProvinceVO[])
            memory.region = slim
            cache.set(KEY_REGION, slim, TTL)
            return slim
        })
        .finally(() => {
            regionInflight = null
        })

    return regionInflight
}

export function useCatalogCache() {
    return {
        loadTags,
        loadRegionTree,
        readTagsSync,
        readRegionSync,
        /** 仅供测试/调试：清空内存（不动本地缓存） */
        resetMemory() {
            memory.tags = null
            memory.region = null
        }
    }
}
