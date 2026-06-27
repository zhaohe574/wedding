<template>
    <div class="decoration-pc min-w-[1100px]">
        <section class="decoration-pc__hero">
            <div>
                <span class="decoration-pc__eyebrow">PC 企业展示首页</span>
                <h1>用一张可装修的 PC 官网承接品牌、案例和咨询转化</h1>
                <p>当前 PC 端以黑金仪式感为视觉基线，展示首屏、品牌介绍、服务能力、案例现场、数据背书和联系信息。</p>
            </div>
            <router-link
                :to="{
                    path: '/decoration/pc_details',
                    query: {
                        url: state.pc_url
                    }
                }"
            >
                <el-button type="primary" size="large">进入装修</el-button>
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
                    <span>PC 端链接</span>
                    <strong>前台访问地址</strong>
                </div>
                <div class="decoration-pc__link-row">
                    <el-input v-model="state.pc_url" disabled />
                    <el-button type="primary" v-copy="state.pc_url">复制</el-button>
                    <el-button :disabled="!state.pc_url" @click="openPcPage">打开</el-button>
                </div>
            </el-card>
        </section>

        <section class="decoration-pc__modules">
            <article v-for="item in modules" :key="item.title">
                <span>{{ item.index }}</span>
                <strong>{{ item.title }}</strong>
                <p>{{ item.description }}</p>
            </article>
        </section>
    </div>
</template>
<script lang="ts" setup name="decorationPc">
import { getDecoratePc } from '@/api/decoration'

const state = ref({
    update_time: '',
    pc_url: ''
})

const modules = [
    { index: '01', title: '企业首屏', description: '承接品牌名、主视觉、核心 CTA 和服务标签。' },
    { index: '02', title: '品牌介绍', description: '展示团队定位、服务理念和品牌现场感。' },
    { index: '03', title: '服务能力', description: '突出仪式文本、节奏管理和审美协同。' },
    { index: '04', title: '案例图集', description: '用真实现场图承接客户对品质的判断。' },
    { index: '05', title: '数据背书', description: '呈现服务沉淀、好评和覆盖能力。' },
    { index: '06', title: '联系信息', description: '提供电话、服务时间、地址和二维码。' }
]

const getData = async () => {
    try {
        state.value = await getDecoratePc()
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
    background:
        linear-gradient(90deg, rgba(23, 19, 15, 0.04) 1px, transparent 1px),
        var(--el-bg-color-page);
    background-size: 56px 56px;

    &__hero {
        min-height: 240px;
        padding: 38px 42px;
        border-radius: 8px;
        color: #fffaf1;
        background:
            linear-gradient(120deg, rgba(216, 177, 106, 0.2), rgba(216, 177, 106, 0) 44%),
            #17130f;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 48px;
        box-shadow: 0 20px 60px rgba(23, 19, 15, 0.16);

        h1 {
            max-width: 760px;
            margin: 12px 0 0;
            font-size: 34px;
            line-height: 1.24;
            font-weight: 900;
        }

        p {
            max-width: 700px;
            margin: 18px 0 0;
            color: rgba(255, 250, 241, 0.68);
            font-size: 15px;
            line-height: 1.8;
        }
    }

    &__eyebrow {
        color: #d8b16a;
        font-size: 13px;
        font-weight: 900;
    }

    &__grid {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 14px;
        margin-top: 14px;
    }

    &__card {
        border-radius: 8px;

        :deep(.el-card__body) {
            min-height: 132px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        p {
            margin: 18px 0 0;
            color: var(--el-text-color-secondary);
            font-size: 13px;
            line-height: 1.7;
        }
    }

    &__card-head {
        display: grid;
        gap: 8px;

        span {
            color: var(--el-text-color-secondary);
            font-size: 13px;
        }

        strong {
            color: var(--el-text-color-primary);
            font-size: 18px;
            font-weight: 800;
        }
    }

    &__link-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 10px;
        margin-top: 18px;
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
            border: 1px solid var(--el-border-color-extra-light);
            background: #fffaf1;
            box-sizing: border-box;
        }

        span {
            color: #a77a34;
            font-size: 12px;
            font-weight: 900;
        }

        strong {
            display: block;
            margin-top: 18px;
            color: #17130f;
            font-size: 18px;
            font-weight: 900;
        }

        p {
            margin: 10px 0 0;
            color: #71685c;
            font-size: 13px;
            line-height: 1.7;
        }
    }
}
</style>
