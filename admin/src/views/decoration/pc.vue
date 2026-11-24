<template>
    <div class="decoration-pc min-w-[1100px]">
        <!-- 顶部主卡：暖墨黑渐变 + 细金边 -->
        <section class="decoration-pc__hero">
            <div>
                <span class="decoration-pc__eyebrow">PC 官网展示入口</span>
                <h1>暖墨黑高定影院风 · 仅展示品牌与引导微信扫码进入小程序</h1>
                <p>
                    收敛为电影感首屏、精选现场与小程序引导 3 个固定模块，自动复用系统站点 Logo、备案信息与渠道小程序码，
                    不承载重型交易与表单。
                </p>
            </div>
            <router-link :to="{ path: '/decoration/pc_details' }">
                <el-button class="decoration-pc__cta" size="large">进入装修</el-button>
            </router-link>
        </section>

        <section class="decoration-pc__grid">
            <el-card shadow="never" class="decoration-pc__card !border-none">
                <div class="decoration-pc__card-head">
                    <span>最近更新</span>
                    <strong>{{ state.update_time || '暂无更新记录' }}</strong>
                </div>
                <p>保存 PC 装修后，前台 `/pc` 会按最新模块配置展示。</p>
            </el-card>

            <el-card shadow="never" class="decoration-pc__card !border-none">
                <div class="decoration-pc__card-head">
                    <span>PC 端访问地址</span>
                    <strong>前台访问地址</strong>
                </div>
                <div class="decoration-pc__link-row">
                    <el-input v-model="state.pc_url" disabled />
                    <el-button class="decoration-pc__btn-gold" v-copy="state.pc_url">复制</el-button>
                    <el-button
                        class="decoration-pc__btn-ghost"
                        :disabled="!state.pc_url"
                        @click="openPcPage"
                    >
                        打开
                    </el-button>
                </div>
            </el-card>

            <el-card shadow="never" class="decoration-pc__card !border-none">
                <div class="decoration-pc__card-head">
                    <span>系统复用状态</span>
                </div>
                <div class="decoration-pc__reuse">
                    <div class="decoration-pc__reuse-row">
                        <span>渠道小程序码</span>
                        <strong :class="{ 'is-muted': !state.mnp_qr_code }">
                            {{ state.mnp_qr_code ? '已配置' : '待在渠道设置配置' }}
                        </strong>
                    </div>
                    <div class="decoration-pc__reuse-row">
                        <span>站点 Logo</span>
                        <strong :class="{ 'is-muted': !state.pc_logo }">
                            {{ state.pc_logo ? '已配置' : '使用默认香槟金方印' }}
                        </strong>
                        <span v-if="!state.pc_logo && brandSealChar" class="decoration-pc__seal">
                            {{ brandSealChar }}
                        </span>
                    </div>
                </div>
                <p>装修字段留空时自动复用系统配置：站点 Logo 回退默认香槟金方印，二维码回退渠道设置小程序码。</p>
            </el-card>
        </section>

        <section class="decoration-pc__modules">
            <article v-for="item in modules" :key="item.index">
                <span>{{ item.index }}</span>
                <strong>{{ item.title }}</strong>
                <p>{{ item.description }}</p>
            </article>
        </section>
    </div>
</template>
<script lang="ts" setup name="decorationPc">
import { PC_WIDGET_META, PC_WIDGET_ORDER } from '@shared/contracts/pc-decorate'

import { getDecoratePc } from '@/api/decoration'

const state = ref({
    update_time: '',
    pc_url: '',
    mnp_qr_code: '',
    pc_logo: '',
    brand_name: ''
})

/** 站点 Logo 未配置时的品牌方印兜底字，与前台 `website.pc_logo || shop_logo` 缺省回退一致 */
const brandSealChar = computed(() => state.value.brand_name.trim().charAt(0))

/** 模块一览：顺序与文案唯一来源 shared/contracts/pc-decorate.ts */
const modules = PC_WIDGET_ORDER.map((name, index) => ({
    index: String(index + 1).padStart(2, '0'),
    ...PC_WIDGET_META[name]
}))

const getData = async () => {
    try {
        const data = await getDecoratePc()
        state.value = {
            update_time: String(data?.update_time ?? ''),
            pc_url: String(data?.pc_url ?? ''),
            mnp_qr_code: String(data?.mnp_qr_code ?? ''),
            pc_logo: String(data?.pc_logo ?? ''),
            brand_name: String(data?.brand_name ?? '')
        }
    } catch (error) {
    }
}

const openPcPage = () => {
    if (!state.value.pc_url) return
    window.open(state.value.pc_url, '_blank')
}

getData()
</script>

<style lang="scss" scoped>
.decoration-pc {
    min-height: calc(100vh - var(--navbar-height));
    padding: 18px;
    box-sizing: border-box;
    background-color: #110e0c;
    background-image:
        radial-gradient(1100px 480px at 50% -8%, rgba(201, 169, 110, 0.08), transparent 62%),
        linear-gradient(90deg, rgba(201, 169, 110, 0.03) 1px, transparent 1px);
    background-size: auto, 56px 56px;

    &__hero {
        padding: 38px 42px;
        border: 1px solid rgba(201, 169, 110, 0.35);
        border-radius: 8px;
        color: #efe5d3;
        background: linear-gradient(120deg, #211d19 0%, #14110f 100%);
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 48px;

        h1 {
            max-width: 720px;
            margin: 12px 0 0;
            color: #efe5d3;
            font-size: 30px;
            line-height: 1.3;
            font-weight: 800;
        }

        p {
            max-width: 680px;
            margin: 16px 0 0;
            color: #b9aea0;
            font-size: 14px;
            line-height: 1.85;
        }
    }

    &__eyebrow {
        color: #c9a96e;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 13px;
        letter-spacing: 0.14em;
        font-weight: 600;
    }

    &__cta {
        --el-button-bg-color: #c9a96e;
        --el-button-border-color: #c9a96e;
        --el-button-text-color: #181614;
        --el-button-hover-bg-color: #d8b980;
        --el-button-hover-border-color: #d8b980;
        --el-button-hover-text-color: #181614;
        --el-button-active-bg-color: #b8965e;
        --el-button-active-border-color: #b8965e;
        --el-button-active-text-color: #181614;
        font-weight: 700;
    }

    &__grid {
        display: grid;
        grid-template-columns: 300px minmax(0, 1fr) 300px;
        gap: 14px;
        margin-top: 14px;
    }

    &__card {
        border-radius: 8px;
        background: #1b1714;
        box-shadow: inset 0 0 0 1px rgba(201, 169, 110, 0.22);

        :deep(.el-card__body) {
            min-height: 132px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        p {
            margin: 16px 0 0;
            color: #8b8073;
            font-size: 13px;
            line-height: 1.7;
        }
    }

    &__card-head {
        display: grid;
        gap: 8px;

        span {
            color: #8b8073;
            font-size: 13px;
        }

        strong {
            color: #efe5d3;
            font-size: 18px;
            font-weight: 800;

            &.is-muted {
                color: #c9a96e;
            }
        }
    }

    &__link-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 10px;
        margin-top: 18px;

        :deep(.el-input__wrapper),
        :deep(.el-input.is-disabled .el-input__wrapper) {
            background-color: rgba(239, 229, 211, 0.06);
            box-shadow: 0 0 0 1px rgba(201, 169, 110, 0.3) inset;
        }

        :deep(.el-input__inner),
        :deep(.el-input.is-disabled .el-input__inner) {
            color: #efe5d3;
            -webkit-text-fill-color: #efe5d3;
        }
    }

    &__btn-gold {
        --el-button-bg-color: #c9a96e;
        --el-button-border-color: #c9a96e;
        --el-button-text-color: #181614;
        --el-button-hover-bg-color: #d8b980;
        --el-button-hover-border-color: #d8b980;
        --el-button-hover-text-color: #181614;
        font-weight: 700;
    }

    &__btn-ghost {
        --el-button-bg-color: transparent;
        --el-button-border-color: rgba(201, 169, 110, 0.45);
        --el-button-text-color: #dfd0b0;
        --el-button-hover-bg-color: rgba(201, 169, 110, 0.12);
        --el-button-hover-border-color: rgba(201, 169, 110, 0.7);
        --el-button-hover-text-color: #efe5d3;
        --el-button-active-bg-color: rgba(201, 169, 110, 0.2);
        --el-button-disabled-bg-color: transparent;
        --el-button-disabled-border-color: rgba(201, 169, 110, 0.25);
        --el-button-disabled-text-color: rgba(223, 208, 176, 0.4);
    }

    &__reuse {
        display: grid;
        gap: 12px;
        margin-top: 4px;
    }

    &__reuse-row {
        display: flex;
        align-items: center;
        gap: 10px;

        > span {
            color: #8b8073;
            font-size: 13px;
        }

        strong {
            color: #efe5d3;
            font-size: 14px;
            font-weight: 700;

            &.is-muted {
                color: #c9a96e;
            }
        }
    }

    &__seal {
        margin-left: auto;
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: none;
        border-radius: 4px;
        background: linear-gradient(150deg, #d8b980 0%, #c9a96e 100%);
        color: #181614;
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 13px;
        font-weight: 700;
    }

    &__modules {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;

        article {
            min-height: 136px;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid rgba(201, 169, 110, 0.22);
            background: #1b1714;
            box-sizing: border-box;
        }

        span {
            color: #c9a96e;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 12px;
            font-weight: 600;
        }

        strong {
            display: block;
            margin-top: 18px;
            color: #efe5d3;
            font-size: 18px;
            font-weight: 800;
        }

        p {
            margin: 10px 0 0;
            color: #8b8073;
            font-size: 13px;
            line-height: 1.7;
        }
    }
}
</style>
