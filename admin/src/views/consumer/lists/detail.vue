<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header content="用户详情" @back="$router.back()" />
        </el-card>
        <el-card class="mt-4 !border-none" header="基本资料" shadow="never">
            <el-form ref="formRef" class="ls-form" :model="formData" label-width="120px">
                <div class="bg-page flex py-5 mb-10 items-center">
                    <div class="basis-40 flex flex-col justify-center items-center">
                        <div class="mb-2 text-tx-regular">用户头像</div>
                        <el-avatar :src="formData.avatar" :size="58" />
                    </div>
                    <div class="basis-40 flex flex-col justify-center items-center">
                        <div class="text-tx-regular">账户余额</div>
                        <div class="mt-2 flex items-center">
                            ¥{{ formData.user_money }}
                            <el-button
                                v-perms="['content.user/adjustMoney']"
                                type="primary"
                                link
                                @click="handleAdjust(formData.user_money)"
                            >
                                调整
                            </el-button>
                        </div>
                    </div>
                </div>
                <el-form-item label="用户昵称：">
                    {{ formData.nickname }}
                </el-form-item>
                <el-form-item label="账号：">
                    {{ formData.account }}
                    <popover-input
                        class="ml-[10px]"
                        @confirm="handleEdit($event, 'account')"
                        :limit="32"
                        v-perms="['content.user/edit']"
                    >
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="真实姓名：">
                    {{ formData.real_name || '-' }}
                    <popover-input
                        class="ml-[10px]"
                        @confirm="handleEdit($event, 'real_name')"
                        :limit="32"
                        v-perms="['content.user/edit']"
                    >
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="性别：">
                    {{ formData.sex }}
                    <popover-input
                        class="ml-[10px]"
                        type="select"
                        :options="[
                            {
                                label: '未知',
                                value: 0
                            },
                            {
                                label: '男',
                                value: 1
                            },
                            {
                                label: '女',
                                value: 2
                            }
                        ]"
                        @confirm="handleEdit($event, 'sex')"
                        v-perms="['content.user/edit']"
                    >
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="联系电话：">
                    {{ formData.mobile || '-' }}
                    <popover-input
                        class="ml-[10px]"
                        type="number"
                        @confirm="handleEdit($event, 'mobile')"
                        v-perms="['content.user/edit']"
                    >
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="风险等级：">
                    <div class="risk-rank">
                        <div class="risk-rank__line">
                            <el-tag :type="riskTagType(formData.effective_risk_rank)">
                                {{ formData.effective_risk_rank_desc || '正常' }}
                            </el-tag>
                            <span class="risk-rank__source">{{ formData.risk_rank_source_desc || '跟随微信检测' }}</span>
                            <popover-input
                                class="ml-[10px]"
                                type="select"
                                :value="formData.manual_risk_rank"
                                :options="riskRankOptions"
                                width="260px"
                                @confirm="handleEdit($event, 'manual_risk_rank')"
                                v-perms="['content.user/edit']"
                            >
                                <el-button type="primary" link>
                                    <icon name="el-icon-EditPen" />
                                </el-button>
                            </popover-input>
                        </div>
                        <div class="risk-rank__meta">
                            微信检测：{{ formData.wechat_risk_rank_desc || '正常' }}；人工设置：{{ formData.manual_risk_rank_desc || '跟随微信检测' }}
                            <span v-if="formData.risk_rank_update_time">；更新时间：{{ formData.risk_rank_update_time }}</span>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="注册来源："> {{ formData.channel }} </el-form-item>
                <el-form-item label="注册时间："> {{ formData.create_time }} </el-form-item>
                <el-form-item label="最近登录时间："> {{ formData.login_time }} </el-form-item>
            </el-form>
        </el-card>

        <account-adjust
            v-model:show="adjustState.show"
            :value="adjustState.value"
            @confirm="handleConfirmAdjust"
        />
    </div>
</template>

<script lang="ts" setup name="consumerDetail">
import type { FormInstance } from 'element-plus'

import { adjustMoney, getUserDetail, userEdit } from '@/api/consumer'
import { isEmpty } from '@/utils/util'

import AccountAdjust from '../components/account-adjust.vue'

const route = useRoute()
const formData = reactive({
    avatar: '',
    channel: '',
    create_time: '',
    login_time: '',
    mobile: '',
    nickname: '',
    real_name: '',
    sex: 0,
    sn: '',
    account: '',
    user_money: '',
    wechat_risk_rank: 0,
    wechat_risk_rank_desc: '',
    manual_risk_rank: -1,
    manual_risk_rank_desc: '',
    effective_risk_rank: 0,
    effective_risk_rank_desc: '',
    risk_rank_source_desc: '',
    risk_rank_update_time: ''
})

const adjustState = reactive({
    show: false,
    value: ''
})
const formRef = shallowRef<FormInstance>()

const riskRankOptions = [
    { label: '跟随微信检测', value: -1 },
    { label: '0 正常', value: 0 },
    { label: '1 低风险', value: 1 },
    { label: '2 中风险', value: 2 },
    { label: '3 高风险', value: 3 },
    { label: '4 极高风险', value: 4 }
]

const riskTagType = (rank: any) => {
    const value = Number(rank)
    if (value >= 3) return 'danger'
    if (value === 2) return 'warning'
    if (value === 1) return 'info'
    return 'success'
}

const getDetails = async () => {
    const data = await getUserDetail({
        id: route.query.id
    })
    Object.keys(formData).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key]
    })
}

const handleEdit = async (value: string, field: string) => {
    if (isEmpty(value)) return
    await userEdit({
        id: route.query.id,
        field,
        value
    })
    getDetails()
}

const handleAdjust = (value: string) => {
    adjustState.show = true
    adjustState.value = value
}
const handleConfirmAdjust = async (value: any) => {
    await adjustMoney({ user_id: route.query.id, ...value })
    adjustState.show = false
    getDetails()
}
getDetails()
</script>

<style lang="scss" scoped>
.risk-rank {
    display: flex;
    flex-direction: column;
    gap: 6px;

    &__line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    &__source,
    &__meta {
        color: var(--el-text-color-secondary);
    }

    &__meta {
        font-size: 12px;
        line-height: 1.6;
    }
}
</style>
