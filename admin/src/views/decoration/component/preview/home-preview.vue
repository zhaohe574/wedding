<template>
    <div class="pv-home">
        <!-- ============ 首屏（banner） ============ -->
        <div
            class="pv-block"
            :class="{ 'is-active': selected === indexOf('banner') }"
            @click="select('banner')"
        >
            <span class="pv-block__tag">首屏主图</span>
            <div class="hero">
                <img v-if="heroImage" class="hero__media" :src="heroImage" alt="" />
                <div v-else class="hero__media hero__media--empty">
                    <el-icon :size="28"><Picture /></el-icon>
                    <span>未上传首屏图，真机回落到内置主图</span>
                </div>
                <div class="hero__scrim" />

                <!-- 左上角不设品牌位：与真机一致，主图铺满到状态栏下，只留系统胶囊 -->
                <div class="hero__copy">
                    <div v-if="heroText.showSlogan" class="hero__eyebrow" :style="sloganStyle">
                        {{ heroText.slogan }}
                    </div>
                    <div v-if="heroText.showTitle" class="hero__title">
                        <span v-for="(line, i) in heroText.titleLines" :key="i" :style="heroTitleStyle">
                            {{ line }}
                        </span>
                    </div>                    <div
                        v-if="heroText.showSubtitle"
                        class="hero__scope"
                        :style="heroSubtitleStyle"
                    >
                        {{ heroText.subtitle }}
                    </div>
                    <div
                        v-if="heroText.showPrimary || heroText.showSecondary"
                        class="hero__cta"
                    >
                        <div v-if="heroText.showPrimary" class="hero__primary">
                            <span>{{ heroText.primaryText }}</span>
                            <el-icon :size="12"><ArrowRight /></el-icon>
                        </div>
                        <div v-if="heroText.showSecondary" class="hero__ghost">
                            {{ heroText.secondaryText }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="isHidden('banner')" class="pv-hidden-mask">
                已关闭自定义首屏，真机显示内置主图与默认文案
            </div>
        </div>

        <!-- ============ 数据条（home-brand） ============ -->
        <div
            class="pv-block"
            :class="{ 'is-active': selected === indexOf('home-brand') }"
            @click="select('home-brand')"
        >
            <span class="pv-block__tag">数据条</span>
            <div v-if="stats.length" class="stats">
                <div v-for="(s, i) in stats" :key="i" class="stats__item">
                    <span class="stats__value">{{ s.value }}</span>
                    <span class="stats__label">{{ s.label }}</span>
                </div>
            </div>
            <div v-else class="pv-empty">未配置统计数据，真机整块不渲染</div>
            <div v-if="isHidden('home-brand')" class="pv-hidden-mask">该组件已隐藏</div>
        </div>

        <!-- ============ 四宫格（home-service-categories） ============ -->
        <div
            class="pv-block"
            :class="{ 'is-active': selected === indexOf('home-service-categories') }"
            @click="select('home-service-categories')"
        >
            <span class="pv-block__tag">四宫格入口</span>
            <div v-if="quickEntries.length" class="quick">
                <div v-for="(entry, i) in quickEntries" :key="i" class="quick__item">
                    <div class="quick__ic">
                        <img v-if="entry.image" class="quick__img" :src="entry.image" alt="" />
                        <el-icon v-else :size="19"><Calendar /></el-icon>
                    </div>
                    <span class="quick__label">{{ entry.label }}</span>
                </div>
            </div>
            <div v-else class="pv-empty">没有可显示的入口，真机不显示这一排</div>
            <div v-if="isHidden('home-service-categories')" class="pv-hidden-mask">
                该组件已隐藏
            </div>
        </div>

        <!-- ============ 以下两块为接口内容，不在装修范围 ============ -->
        <div class="pv-section">本期推荐</div>
        <div class="pv-placeholder pv-placeholder--cards">
            <div v-for="n in 2" :key="n" class="pv-card">
                <div class="pv-card__ph" />
                <div class="pv-card__line" />
                <div class="pv-card__line pv-card__line--short" />
            </div>
            <span class="pv-placeholder__note">推荐人员来自接口；无数据时整块不渲染（含区块标题）</span>
        </div>

        <div class="pv-section">近期吉日 · 可约情况</div>
        <div class="pv-placeholder">
            <div class="pv-darkcard">
                <span class="pv-placeholder__note">档期数据来自接口；无数据时整块不渲染（含区块标题）</span>
            </div>
        </div>

        <!-- ============ 服务承诺（home-flow） ============ -->
        <div
            class="pv-block"
            :class="{ 'is-active': selected === indexOf('home-flow') }"
            @click="select('home-flow')"
        >
            <span class="pv-block__tag">服务承诺 · 时间轴</span>
            <template v-if="flowVisible">
                <div class="pv-section">{{ flowTitle }}</div>
                <div class="promise">
                    <div v-for="(it, i) in flowItems" :key="i" class="promise__row">
                        <span class="promise__tag">{{ it.tag }}</span>
                        <div class="promise__body">
                            <span class="promise__title">{{ it.title }}</span>
                            <span v-if="it.desc" class="promise__desc">{{ it.desc }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <!-- 无内容时仍保留可点选区块，否则运营在预览里找不到这个组件 -->
            <div v-else class="pv-placeholder">
                <span class="pv-placeholder__note">未配置步骤或已隐藏，真机整块不渲染</span>
            </div>
            <div v-if="isHidden('home-flow')" class="pv-hidden-mask">该组件已隐藏</div>
        </div>

        <!-- ============ 页脚 ============ -->
        <div class="foot">
            <div class="foot__rule" />
            <span class="foot__text">{{ brand.slogan || brand.name }}</span>
        </div>

        <!-- ============ 弹窗广告（home-popup-ad） ============ -->
        <div
            class="pv-block pv-block--popup"
            :class="{ 'is-active': selected === indexOf('home-popup-ad') }"
            @click="select('home-popup-ad')"
        >
            <span class="pv-block__tag">首页弹窗广告</span>
            <div class="popup-card">
                <div class="popup-card__head">
                    <span class="popup-card__title">弹窗广告</span>
                    <el-tag v-if="popupEnabled" size="small" type="success">已启用</el-tag>
                    <el-tag v-else size="small" type="info">未启用</el-tag>
                </div>
                <p class="popup-card__desc">
                    真机在首页加载后按频次弹出，会盖住整屏；这里以卡片形式展示内容，
                    避免遮挡其他组件的编辑。
                </p>
                <el-button
                    v-if="popupEnabled"
                    size="small"
                    @click.stop="emit('preview-popup')"
                    >预览弹窗效果</el-button
                >
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
/**
 * 首页装修预览（后台自绘版）。
 *
 * 与小程序 `pages/index/index.vue` 的关系：
 *   - **几何参数同源**：所有尺寸直接抄小程序的 rpx 数字，用 `rpx()` 换算成 px；
 *   - **装驱动的部分**：banner(首屏含上浮文字) / home-brand(数据条) /
 *     home-service-categories(四宫格) / home-flow(服务承诺) / home-popup-ad(弹窗)；
 *   - **兜底文案同源**：首屏文案与断行规则来自 `shared/contracts/decorate.ts`，
 *     不再在这里维护第二份 `FIXED_COPY`；
 *   - **接口内容用占位块表示**：本期推荐 / 近期吉日来自接口，不是装修配置，
 *     且两者无数据时真机整块不渲染（含标题），预览沿用同一口径。
 *   - **首屏左上角无品牌位**：与真机一致（2026-11-19 真机已移除 logo 与牌面底衬），
 *     这里也不画；品牌仍出现在首屏文案与页脚。
 *
 * 已知差异（可接受，见 docs §11）：
 *   1. 字体回落到后台所在系统的字体栈，不做 webfont；
 *   2. 不做真实图片裁切（object-fit 与小程序 aspectFill 的裁剪基准略有差异）；
 *   3. 弹窗以卡片展示，不真的盖屏；
 *   4. 所有点击不跳转（预览不产生业务行为）。
 */
import { ArrowRight, Calendar, Picture } from '@element-plus/icons-vue'
import { HOME_FLOW_DEFAULTS, resolveHomeHeroText } from '@shared/contracts/decorate'
import { computed } from 'vue'

const props = defineProps<{
    /** 当前页的组件列表（编辑态草稿） */
    widgets: any[]
    /** 当前选中的组件下标 */
    selected: number
    /** 站点品牌（来自后台「网站信息」，用于首屏文案与页脚；首屏品牌位已移除） */
    brand: { name: string; logo: string; slogan?: string }
    /** 文件域名，用于把相对路径补成可访问地址 */
    domain: string
}>()

const emit = defineEmits<{
    (event: 'select', index: number): void
    (event: 'preview-popup'): void
}>()

function indexOf(name: string) {
    return props.widgets.findIndex((w) => w?.name === name)
}

function contentOf(name: string): any {
    const index = indexOf(name)
    return index >= 0 ? props.widgets[index]?.content || {} : null
}

function isHidden(name: string) {
    const content = contentOf(name)
    return !!content && Number(content.enabled) === 0
}

function select(name: string) {
    const index = indexOf(name)
    if (index >= 0) emit('select', index)
}

/** 相对路径补域名；绝对地址原样返回 */
function toUrl(url?: string): string {
    const value = String(url || '').trim()
    if (!value) return ''
    if (/^https?:/i.test(value)) return value
    return props.domain ? `${props.domain.replace(/\/+$/, '')}/${value.replace(/^\/+/, '')}` : value
}

/* ---------------- banner ---------------- */
const bannerItem = computed<any>(() => {
    const content = contentOf('banner')
    if (!content || Number(content.enabled) !== 1) return null
    const list = Array.isArray(content.data) ? content.data : []
    // 首屏只有一张图；旧的 is_show 字段已删除
    return list[0] || null
})

const heroImage = computed(() => toUrl(bannerItem.value?.image))
/** 开关 + 兜底文案 + 样式一次性解析，与小程序调用同一个纯函数 */
const heroText = computed(() =>
    resolveHomeHeroText(bannerItem.value, {
        brandName: props.brand?.name,
        brandSlogan: props.brand?.slogan
    })
)
/** 样式只开放颜色；字号与版式由设计稿固定 */
const heroTitleStyle = computed(() =>
    heroText.value.style.title_color ? { color: heroText.value.style.title_color } : undefined
)
const heroSubtitleStyle = computed(() =>
    heroText.value.style.subtitle_color ? { color: heroText.value.style.subtitle_color } : undefined
)
const sloganStyle = computed(() => {
    const color = String(bannerItem.value?.slogan_color || '').trim()
    return color ? { color } : undefined
})

/* ---------------- home-brand ---------------- */
const stats = computed<Array<{ value: string; label: string }>>(() => {
    const content = contentOf('home-brand')
    if (!content || Number(content.enabled) !== 1) return []
    const list = Array.isArray(content.stats) ? content.stats : []
    return list
        .filter((s: any) => s && s.value !== undefined && s.value !== null && s.value !== '')
        .slice(0, 3)
})

/* ---------------- home-service-categories ---------------- */
const quickEntries = computed<Array<{ label: string; image: string }>>(() => {
    const content = contentOf('home-service-categories')
    const fallback = [
        { label: '查档期', image: '' },
        { label: '选主持', image: '' },
        { label: '真实案例', image: '' },
        { label: '在线咨询', image: '' }
    ]
    // 组件缺失 → 真机用内置四条；显式关闭 → 整块不渲染，不能再回落
    if (!content) return fallback
    if (Number(content.enabled) !== 1) return []

    const list = Array.isArray(content.data) ? content.data : []
    return list
        .filter(
            (item: any) =>
                item &&
                String(item.is_show ?? '1') === '1' &&
                String(item.title || '').trim() &&
                String(item.link?.path || '').trim()
        )
        .map((item: any) => ({
            label: String(item.title || '').trim(),
            image: toUrl(item.image)
        }))
})

/* ---------------- home-flow ---------------- */
const flowContent = computed<any>(() => contentOf('home-flow') || {})
const flowTitle = computed(
    () => String(flowContent.value.title || '').trim() || HOME_FLOW_DEFAULTS.title
)
const flowItems = computed<Array<{ tag: string; title: string; desc: string }>>(() => {
    if (Number(flowContent.value.enabled) === 0) return []
    const list = Array.isArray(flowContent.value.data) ? flowContent.value.data : []
    return list
        .filter(
            (item: any) =>
                item && String(item.is_show ?? '1') === '1' && String(item.title || '').trim()
        )
        .slice(0, 6)
        .map((item: any) => ({
            tag: String(item.tag || '').trim(),
            title: String(item.title || '').trim(),
            desc: String(item.desc || '').trim()
        }))
})
const flowVisible = computed(() => flowItems.value.length > 0)

/* ---------------- home-popup-ad ---------------- */
const popupEnabled = computed(() => Number(contentOf('home-popup-ad')?.enabled) === 1)
</script>

<style lang="scss" scoped>
@use './preview-tokens.scss' as *;

.pv-home {
    --pv-rpx: 0.5px;
    position: absolute;
    inset: 0;
    overflow-y: auto;
    background: $bg;
    font-family: $font-sans;
    -webkit-font-smoothing: antialiased;
}

/* ---------------- 选中态（不参与排版，只是覆盖一层描边） ---------------- */
.pv-block {
    position: relative;

    &::after {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px dashed transparent;
        pointer-events: none;
        z-index: 8;
    }

    &:hover::after {
        border-color: rgba(64, 158, 255, 0.65);
    }

    &.is-active::after {
        border: 1px solid #409eff;
    }

    &__tag {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 9;
        padding: 1px 6px;
        font-size: 11px;
        line-height: 18px;
        color: #fff;
        background: #409eff;
        border-bottom-right-radius: 6px;
        opacity: 0;
        transition: opacity 0.15s;
        pointer-events: none;
    }

    &:hover &__tag,
    &.is-active &__tag {
        opacity: 1;
    }
}

.pv-hidden-mask {
    position: absolute;
    inset: 0;
    z-index: 7;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    font-size: 12px;
    pointer-events: none;
}

/* ---------------- 首屏 ---------------- */
.hero {
    position: relative;
    height: rpx(1054);
    padding: 0 rpx(42);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
    background: $ink;

    &__media {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;

        &--empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #2a2622;
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
        }
    }

    &__scrim {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba(24, 22, 20, 0.58) 0%,
            rgba(24, 22, 20, 0.16) 26%,
            rgba(24, 22, 20, 0.48) 58%,
            rgba(24, 22, 20, 0.94) 100%
        );
    }

    &__copy {
        position: relative;
        z-index: 2;
        padding-bottom: rpx(100);
    }

    &__eyebrow {
        display: block;
        font-family: $font-serif;
        font-size: $fs-xs;
        letter-spacing: 0.24em;
        color: $brand-soft;
    }

    &__title {
        margin-top: rpx(27);

        span {
            display: block;
            font-size: $fs-52;
            line-height: 1.52;
            font-weight: 500;
            letter-spacing: 0.01em;
            color: $on-dark;
        }
    }

    &__scope {
        margin-top: rpx(23);
        font-size: $fs-xs;
        letter-spacing: 0.08em;
        color: $on-dark-2;
    }

    &__cta {
        display: flex;
        align-items: center;
        gap: rpx(34);
        margin-top: rpx(50);
    }

    &__primary {
        height: rpx(85);
        padding: 0 rpx(46);
        display: inline-flex;
        align-items: center;
        gap: rpx(12);
        border-radius: $r-pill;
        background: $surface;
        color: $ink;
        font-size: $fs-md;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    &__ghost {
        height: rpx(85);
        display: inline-flex;
        align-items: center;
        padding: 0 rpx(8);
        font-size: $fs-xs;
        letter-spacing: 0.06em;
        color: $on-dark;
        border-bottom: 1px solid $on-dark-2;
    }
}

/* ---------------- 数据条 ---------------- */
.stats {
    position: relative;
    z-index: 6;
    margin: rpx(-65) rpx(38) 0;
    display: flex;
    align-items: center;
    padding: rpx(35) rpx(12);
    background: $surface;
    border: 1px solid $line;
    border-radius: $r-md;

    &__item {
        position: relative;
        flex: 1;
        text-align: center;

        & + &::before {
            content: '';
            position: absolute;
            left: 0;
            top: rpx(12);
            bottom: rpx(12);
            width: 1px;
            background: $line;
        }
    }

    &__value {
        display: block;
        font-family: $font-serif;
        font-size: $fs-40;
        font-weight: 500;
        letter-spacing: 0.01em;
        color: $ink;
    }

    &__label {
        display: block;
        margin-top: rpx(10);
        font-size: $fs-xs;
        color: $ink-2;
    }
}

/* ---------------- 四宫格 ---------------- */
.quick {
    display: flex;
    padding: rpx(50) rpx(27) rpx(8);

    &__item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: rpx(17);
    }

    &__ic {
        width: rpx(88);
        height: rpx(88);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid $line;
        border-radius: $r-29;
        background: $brand-bg;
        color: $brand-text;
    }

    &__img {
        width: rpx(48);
        height: rpx(48);
        object-fit: contain;
    }

    &__label {
        font-size: $fs-xs;
        letter-spacing: 0.04em;
        color: $ink-2;
        text-align: center;
    }
}

/* ---------------- 接口内容占位 ---------------- */
.pv-section {
    padding: rpx(40) rpx(42) rpx(20);
    font-size: $fs-lg;
    font-weight: 500;
    color: $ink;
}

.pv-placeholder {
    position: relative;
    margin: 0 rpx(38) rpx(20);
    padding: rpx(24);
    border: 1px dashed $line-strong;
    border-radius: $r-md;
    background: rgba(255, 253, 248, 0.6);

    &__note {
        display: block;
        margin-top: rpx(12);
        font-size: 11px;
        color: $ink-3;
        text-align: center;
    }

    &--cards {
        display: flex;
        gap: rpx(16);
        flex-wrap: wrap;
    }
}

.pv-card {
    flex: 1;
    min-width: 0;

    &__ph {
        height: rpx(160);
        border-radius: $r-sm;
        background: $surface-2;
    }

    &__line {
        height: rpx(14);
        margin-top: rpx(12);
        border-radius: 4px;
        background: $surface-2;

        &--short {
            width: 60%;
        }
    }
}

.pv-darkcard {
    padding: rpx(28);
    border-radius: $r-md;
    background: linear-gradient(180deg, #211d19 0%, #14110f 100%);

    .pv-placeholder__note {
        margin-top: 0;
        color: rgba(255, 255, 255, 0.5);
    }
}

/* ---------------- 服务承诺（home-flow） ---------------- */
.promise {
    margin: 0 rpx(38);
    padding: 0 rpx(4);

    &__row {
        display: flex;
        gap: rpx(18);
        padding: rpx(24) 0;
        border-bottom: 1px solid $line;

        &:last-child {
            border-bottom: 0;
        }
    }

    &__tag {
        width: rpx(88);
        flex: none;
        padding-top: 2px;
        font-family: $font-serif;
        font-size: $fs-xs;
        letter-spacing: 0.08em;
        color: $brand-text;
    }

    &__body {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    &__title {
        font-size: $fs-md;
        font-weight: 500;
        color: $ink;
    }

    &__desc {
        margin-top: rpx(8);
        font-size: $fs-xs;
        line-height: 1.7;
        color: $ink-2;
    }
}

/* ---------------- 页脚 ---------------- */
.foot {
    padding: rpx(50) rpx(42) rpx(16);
    text-align: center;

    &__rule {
        width: rpx(42);
        height: 1px;
        margin: 0 auto rpx(23);
        background: $brand;
    }

    &__text {
        font-family: $font-serif;
        font-size: $fs-xs;
        letter-spacing: 0.28em;
        color: $ink-3;
    }
}

/* ---------------- 弹窗卡片 ---------------- */
.pv-block--popup {
    margin: 0 rpx(38) rpx(40);
}

.popup-card {
    padding: rpx(24);
    border: 1px solid $line;
    border-radius: $r-md;
    background: $surface;

    &__head {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &__title {
        font-size: $fs-md;
        font-weight: 600;
        color: $ink;
    }

    &__desc {
        margin: 8px 0 10px;
        font-size: 11px;
        line-height: 1.7;
        color: $ink-3;
    }
}

.pv-empty {
    margin: 0 rpx(38);
    padding: rpx(24);
    border: 1px dashed $line-strong;
    border-radius: $r-md;
    font-size: 11px;
    color: $ink-3;
    text-align: center;
}
</style>
