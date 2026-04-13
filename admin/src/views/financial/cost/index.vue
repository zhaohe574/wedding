<template>
    <div class="financial-cost-offline">
        <el-card class="!border-none" shadow="never">
            <el-alert
                title="成本管理已在精简版后台下线"
                type="warning"
                :closable="false"
                show-icon
            />

            <el-result
                icon="warning"
                title="当前后台不再开放成本录入与成本统计页面"
                sub-title="请统一通过资金流水、结算记录和财务概览查看保留中的财务能力，避免继续进入已下线模块后再触发接口报错。"
            >
                <template #extra>
                    <div class="financial-cost-offline__actions">
                        <el-button type="primary" @click="goSettlement">查看结算管理</el-button>
                        <el-button @click="goFlow">查看资金流水</el-button>
                    </div>
                </template>
            </el-result>

            <div class="financial-cost-offline__tips">
                <div class="financial-cost-offline__tip-title">当前保留能力</div>
                <div class="financial-cost-offline__tip-text">1. 财务概览、收入统计、退款统计、资金流水仍可正常使用。</div>
                <div class="financial-cost-offline__tip-text">2. 结算记录、结算批次、结算配置仍作为正式后台能力保留。</div>
                <div class="financial-cost-offline__tip-text">3. 成本分析、利润分析与成本管理页面不再作为当前产品正式业务入口。</div>
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import { getRoutePath } from '@/router'
import { useRouter } from 'vue-router'

const router = useRouter()

const goSettlement = () => {
    router.push(getRoutePath('finance.settlement/lists') || '/financial/settlement')
}

const goFlow = () => {
    router.push(getRoutePath('finance.flow/lists') || '/financial/flow')
}
</script>

<style scoped>
.financial-cost-offline {
    padding: 0;
}

.financial-cost-offline__actions {
    display: inline-flex;
    gap: 12px;
    flex-wrap: wrap;
}

.financial-cost-offline__tips {
    margin-top: 20px;
    padding: 18px 20px;
    border-radius: 12px;
    background: #fff9eb;
    border: 1px solid #f3e1b4;
}

.financial-cost-offline__tip-title {
    font-size: 14px;
    font-weight: 600;
    color: #8a5a00;
    margin-bottom: 8px;
}

.financial-cost-offline__tip-text {
    font-size: 13px;
    line-height: 1.8;
    color: #7c5c1f;
}
</style>
