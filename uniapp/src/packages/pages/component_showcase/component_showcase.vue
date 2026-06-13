<template>
    <PageShell scene="staff" tone="showcase" hasSafeBottom>
        <view class="showcase-hero">
            <text class="showcase-hero__kicker">Pencil Source · Component Library</text>
            <text class="showcase-hero__title">商业小程序高端黑金组件库</text>
            <text class="showcase-hero__desc">
                覆盖导航、操作、筛选、表单、业务卡片、指标、时间线、列表、反馈与弹层组件。
            </text>
            <BaseSegmentedControl v-model="activeGroup" :options="groupOptions" tone="dark" />
        </view>

        <view class="showcase-content">
            <PageSection
                v-if="shouldShow('basic')"
                variant="showcase"
                eyebrow="Group 01"
                title="基础导航与操作"
                description="顶部导航、主按钮、状态标签、筛选 Chip。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 顶部导航">
                        <view class="native-nav-sample">
                            <view class="native-nav-sample__icon">
                                <BaseIcon name="left" size="34" color="#FFFDF8" />
                            </view>
                            <text class="native-nav-sample__title">婚礼档期</text>
                            <view class="native-nav-sample__actions">
                                <BaseIcon name="search" size="30" color="#FFFDF8" />
                                <BaseIcon name="more-horizontal" size="34" color="#FFFDF8" />
                            </view>
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 主按钮">
                        <BaseButton
                            label="新建婚礼订单"
                            icon="add"
                            variant="dark"
                            size="lg"
                            block
                            :loading="buttonLoading"
                            loading-text="创建中"
                            @click="toggleButtonLoading"
                        />
                    </DemoBlock>

                    <DemoBlock title="组件 状态标签/默认">
                        <view class="showcase-row">
                            <StatusBadge tone="pending" dot>待确认</StatusBadge>
                            <StatusBadge tone="paid" dot>已收款</StatusBadge>
                            <StatusBadge tone="running" dot>执行中</StatusBadge>
                            <StatusBadge tone="risk" dot>有风险</StatusBadge>
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 筛选 Chip">
                        <view class="showcase-row">
                            <FilterChip label="本周档期" dropdown selected />
                            <FilterChip label="高定订单" icon="star" />
                            <FilterChip label="可关闭" closable />
                        </view>
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('business')"
                variant="showcase"
                eyebrow="Group 02"
                title="业务卡片与经营数据"
                description="订单信息、指标、时间线与底部胶囊导航。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 商业信息卡">
                        <BaseCard title="云水台婚礼订单" description="10 月 31 日 · 18:30 · 宴会厅 A">
                            <template #header>
                                <view class="business-card-head">
                                    <view>
                                        <text class="business-card-title">云水台婚礼订单</text>
                                        <text class="business-card-desc">10 月 31 日 · 18:30 · 宴会厅 A</text>
                                    </view>
                                    <StatusBadge tone="pending" size="sm" dot>待确认</StatusBadge>
                                </view>
                            </template>
                            <view class="business-lines">
                                <BaseMenuRow label="新人：陈宇 & 林沐" icon="user" />
                                <BaseMenuRow label="套餐：高定花艺 · 摄影 · 主持" icon="star" />
                                <BaseMenuRow label="尾款：¥ 42,800 待收" icon="wallet" />
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 数据指标卡">
                        <MetricCard label="本月成交" value="¥ 286K" trend="+18%" hint="较上月增长" icon="up" />
                    </DemoBlock>

                    <DemoBlock title="组件 时间线节点">
                        <BaseCard>
                            <view class="timeline-node">
                                <view class="timeline-node__dot"></view>
                                <view class="timeline-node__copy">
                                    <text class="timeline-node__title">确认婚礼方案</text>
                                    <text class="timeline-node__desc">策划师已同步最终流程，新人待确认。</text>
                                </view>
                                <StatusBadge tone="running" size="sm">执行中</StatusBadge>
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 底部胶囊导航">
                        <BaseCapsuleTabbar v-model="activeTab" :items="tabbarItems" />
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('form')"
                variant="showcase"
                eyebrow="Group 03"
                title="表单与录入"
                description="搜索、输入、选择器、步进器和状态样例。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 搜索框">
                        <BaseSearchBar v-model="searchKeyword" />
                    </DemoBlock>

                    <DemoBlock title="组件 输入框">
                        <BaseInput v-model="coupleName" label="新人姓名" icon="user" clearable />
                    </DemoBlock>

                    <DemoBlock title="组件 选择器">
                        <view class="showcase-stack">
                            <BasePickerField
                                label="日期选择器"
                                :model-value="datePickerValue"
                                icon="calendar"
                                hint="点击后打开底部日期选择器"
                                @click="datePickerOpen = true"
                            />
                            <BasePickerField
                                label="地区选择器"
                                :model-value="regionPickerText"
                                icon="location"
                                status-text="省市区"
                                @click="regionPickerOpen = true"
                            />
                            <BasePickerField
                                label="服务地区选择器"
                                :model-value="serviceRegionPickerText"
                                icon="location"
                                status-text="后台服务树"
                                hint="只展示后台已开通的服务地区"
                                @click="serviceRegionPickerOpen = true"
                            />
                            <BasePickerField
                                label="单选文字"
                                :model-value="singlePickerText"
                                icon="order"
                                @click="singlePickerOpen = true"
                            />
                            <BasePickerField
                                label="多列/级联"
                                :model-value="cascadePickerText"
                                icon="grid"
                                @click="cascadePickerOpen = true"
                            />
                            <BasePickerField
                                label="多选文字"
                                :model-value="multiPickerText"
                                icon="tag"
                                status-text="可多选"
                                @click="multiPickerOpen = true"
                            />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 数量步进器">
                        <BaseStepper v-model="guestTableCount" :min="1" :max="30" />
                    </DemoBlock>

                    <DemoBlock title="输入框状态样例">
                        <view class="showcase-stack">
                            <BaseInput model-value="正常输入" label="默认" icon="edit" />
                            <BaseInput model-value="已禁用" label="禁用" icon="lock" disabled />
                            <BaseInput model-value="手机号格式错误" label="错误" icon="phone" error-text="请输入正确手机号" />
                        </view>
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('content')"
                variant="showcase"
                eyebrow="Group 04"
                title="内容展示组件"
                description="案例图片、动态信息流、订单列表、进度与人员协作。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 图片/案例卡片">
                        <BaseCard variant="media">
                            <image class="case-image" src="/static/images/user/my_topbg.png" mode="aspectFill" />
                            <view class="case-copy">
                                <text class="case-title">湖畔香槟色婚礼</text>
                                <text class="case-desc">花艺 · 摄影 · 主持全案</text>
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="动态广场真实场景">
                        <view class="showcase-dynamic-scene">
                            <BaseCard variant="list" class="showcase-dynamic-filter">
                                <view class="showcase-dynamic-chip-row">
                                    <FilterChip label="全部" selected />
                                    <FilterChip label="图文" />
                                    <FilterChip label="#香槟色婚礼" closable />
                                    <FilterChip label="最新发布" icon="sort" dropdown />
                                </view>
                            </BaseCard>

                            <DynamicCard
                                class="showcase-dynamic-card"
                                :dynamic="showcaseDynamicItem"
                                variant="plaza-v2"
                                :show-share="false"
                            />

                            <BaseCard variant="list" class="showcase-list-panel">
                                <BaseMenuRow label="最新发布" value="当前" icon="sort" divided />
                                <BaseMenuRow label="最多点赞" value="128" icon="like" divided />
                                <BaseMenuRow label="最多浏览" value="1.2k" icon="eye" />
                            </BaseCard>
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 订单列表项">
                        <BaseCard variant="list">
                            <view class="order-line">
                                <view class="order-line__avatar">陈</view>
                                <view class="order-line__copy">
                                    <view class="order-line__title-row">
                                        <text class="order-line__title">陈宇 & 林沐</text>
                                        <StatusBadge tone="pending" size="sm">待确认</StatusBadge>
                                    </view>
                                    <text class="order-line__desc">10.31 · 宴会厅 A · 高定全案</text>
                                </view>
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 进度卡">
                        <BaseCard title="筹备进度" description="7/10 已完成">
                            <view class="progress-track">
                                <view class="progress-track__bar"></view>
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 人员卡">
                        <BaseCard variant="dark">
                            <view class="staff-mini">
                                <image class="staff-mini__avatar" src="/static/images/user/default_avatar.png" mode="aspectFill" />
                                <view class="staff-mini__copy">
                                    <text class="staff-mini__name">林屿 · 首席策划</text>
                                    <text class="staff-mini__desc">4.9 分 · 128 场服务</text>
                                </view>
                                <BaseIconButton icon="phone" size="sm" />
                            </view>
                        </BaseCard>
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('user')"
                variant="showcase"
                eyebrow="Group 05"
                title="用户中心组件"
                description="入口组、报价卡、操作栏、骨架、通知与空状态。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 我的页面入口组/v2">
                        <BaseCard variant="listDark" class="showcase-list-panel">
                            <BaseMenuRow
                                label="我的订单"
                                value="0 个进行中"
                                icon="order"
                                dark
                                divided
                                density="comfortable"
                            />
                            <BaseMenuRow
                                label="我的评价"
                                value="评价记录"
                                icon="comment"
                                dark
                                divided
                                density="comfortable"
                            />
                            <BaseMenuRow label="通知中心" icon="notice" dark density="comfortable" />
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 暗色列表面板">
                        <BaseCard variant="listDark" class="showcase-list-panel">
                            <BaseMenuRow label="合同与确认函" value="已生成" icon="file" dark divided />
                            <BaseMenuRow label="服务团队" value="3 人" icon="user" dark divided />
                            <BaseMenuRow label="尾款状态" value="待收款" icon="wallet" dark />
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 浅色列表面板">
                        <BaseCard variant="list" class="showcase-list-panel">
                            <BaseMenuRow label="订单编号" value="WM20260611001" icon="file" divided />
                            <BaseMenuRow label="婚礼日期" value="2026.10.31" icon="calendar" divided />
                            <BaseMenuRow label="服务团队" value="3 人协作" icon="team" />
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 套餐/报价卡">
                        <BaseCard variant="surface">
                            <view class="price-card">
                                <text class="price-card__title">高定婚礼全案</text>
                                <text class="price-card__desc">策划 · 花艺 · 摄影 · 主持</text>
                                <text class="price-card__price">¥ 42,800</text>
                                <BaseButton label="查看报价" variant="dark" size="sm" />
                            </view>
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 底部操作栏">
                        <ActionArea tone="solid" layout="split" :safe-bottom="false">
                            <BaseButton label="联系" icon="phone" variant="secondary" size="sm" block />
                            <BaseButton label="确认收款" variant="dark" size="sm" block />
                        </ActionArea>
                    </DemoBlock>

                    <DemoBlock title="组件 加载骨架">
                        <BaseSkeleton :rows="4" />
                    </DemoBlock>

                    <DemoBlock title="组件 通知条">
                        <view class="notice-bar">
                            <view class="notice-bar__icon">
                                <BaseIconButton
                                    icon="notice"
                                    variant="ghost"
                                    size="sm"
                                    width="56rpx"
                                    height="56rpx"
                                    icon-size="28"
                                />
                            </view>
                            <text class="notice-bar__text">今日有 3 个档期需要确认，请及时处理。</text>
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 空状态">
                        <EmptyState
                            title="暂无档期安排"
                            description="创建订单后，婚礼日期会自动同步到这里。"
                            action-text="新建订单"
                            compact
                        />
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('feedback')"
                variant="showcase"
                eyebrow="Group 06"
                title="反馈与浮层"
                description="Toast、确认弹层、分段控制和基础反馈状态。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 Toast/成功">
                        <view class="showcase-stack">
                            <BaseToast :show="toastVisible" message="保存成功，已同步经营数据" />
                            <BaseButton label="切换 Toast" variant="light" size="sm" @click="toastVisible = !toastVisible" />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 确认弹层">
                        <BaseButton label="打开确认弹层" variant="dark" size="sm" @click="dialogVisible = true" />
                    </DemoBlock>

                    <DemoBlock title="组件 分段控制">
                        <BaseSegmentedControl v-model="segmentValue" :options="segmentOptions" />
                    </DemoBlock>

                    <DemoBlock title="Toast 状态样例行">
                        <view class="showcase-stack">
                            <BaseToast message="已收款" tone="success" />
                            <BaseToast message="存在档期冲突" tone="warning" />
                        </view>
                    </DemoBlock>
                </view>
            </PageSection>

            <PageSection
                v-if="shouldShow('entry')"
                variant="showcase"
                eyebrow="Group 07"
                title="业务录入组件"
                description="档期日历、上传凭证、订单详情信息组与原子组件。"
            >
                <view class="showcase-grid">
                    <DemoBlock title="组件 日期档期日历">
                        <BaseScheduleCalendar
                            v-model="selectedCalendarDay"
                            :days="calendarDays"
                            @select="handleCalendarSelect"
                        />
                    </DemoBlock>

                    <DemoBlock title="组件 上传凭证/图片上传">
                        <BaseUploader :items="uploadItems" @add="addUploadMock" />
                    </DemoBlock>

                    <DemoBlock title="组件 订单详情信息组">
                        <view class="showcase-order-detail">
                            <BaseCard
                                variant="panel"
                                class="showcase-order-status"
                                background="linear-gradient(180deg, #F1E5C8 0%, #FFFDF8 100%)"
                                border="1rpx solid var(--wm-color-border-strong, #D9BE82)"
                            >
                                <StatusBadge tone="pending" size="sm" dot>待支付</StatusBadge>
                                <text class="showcase-order-status__title">高定花艺订单待支付</text>
                                <text class="showcase-order-status__desc">
                                    {{ '订单编号：WM20260611001\n请在 23:58:12 内完成尾款支付。' }}
                                </text>
                            </BaseCard>

                            <BaseCard variant="list" class="showcase-list-panel">
                                <BaseInfoRow label="当前阶段" value="服务已确认，等待尾款支付" multiline />
                                <BaseInfoRow label="等待对象" value="新人付款" />
                                <BaseInfoRow label="下一步" value="支付后进入婚礼执行阶段" multiline />
                            </BaseCard>

                            <BaseCard variant="panel" class="showcase-list-panel" title="费用明细">
                                <BaseInfoRow label="总价" value="¥ 68,800" tone="price" />
                                <BaseInfoRow label="已付" value="¥ 26,000" tone="success" />
                                <BaseInfoRow label="待付" value="¥ 42,800" tone="warning" />
                            </BaseCard>
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 分组标题">
                        <PageSection title="业务卡片" description="右侧可放操作入口" padding="0" gap="20rpx">
                            <template #action>
                                <BaseButton label="全部" variant="ghost" size="mini" />
                            </template>
                        </PageSection>
                    </DemoBlock>

                    <DemoBlock title="组件 信息字段行">
                        <BaseCard variant="list" class="showcase-list-panel">
                            <BaseInfoRow label="婚礼日期" value="2026.10.31 18:30" />
                            <BaseInfoRow label="服务团队" value="策划、摄影、主持" />
                            <BaseInfoRow
                                label="履约信息"
                                value="广东省广州市天河区 · 云水台 A 厅 · 仪式前 2 小时到场"
                                multiline
                            />
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 菜单入口行/v2">
                        <BaseCard variant="listDark" class="showcase-list-panel">
                            <BaseMenuRow label="我的订单" value="0 个进行中" icon="order" dark divided />
                            <BaseMenuRow label="服务合同" value="已生成" icon="file" dark />
                        </BaseCard>
                    </DemoBlock>

                    <DemoBlock title="组件 图标按钮">
                        <view class="showcase-row">
                            <BaseIconButton icon="set" />
                            <BaseIconButton icon="search" variant="light" />
                            <BaseIconButton icon="more" variant="ghost" />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 媒体缩略项">
                        <view class="showcase-row">
                            <BaseMediaThumb label="合同" icon="file" />
                            <BaseMediaThumb label="试妆图" src="/static/images/user/my_topbg.png" :dark="false" />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="组件 日期格">
                        <view class="date-cell-row">
                            <BaseDateCell day="31" label="选中" state="selected" />
                            <BaseDateCell day="01" label="今日" state="today" />
                            <BaseDateCell day="02" label="忙" state="busy" />
                            <BaseDateCell day="03" label="休" state="disabled" />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="日期格状态样例行">
                        <view class="date-cell-row">
                            <BaseDateCell v-for="item in dateStateSamples" :key="item.label" :day="item.day" :label="item.label" :state="item.state" />
                        </view>
                    </DemoBlock>

                    <DemoBlock title="上传缩略项状态样例行">
                        <BaseUploader :items="uploadItems" :max="4" />
                    </DemoBlock>

                    <DemoBlock title="菜单与字段行复用样例">
                        <BaseCard variant="listDark" class="showcase-list-panel">
                            <BaseMenuRow label="合同与确认函" icon="file" value="已生成" dark divided />
                            <BaseInfoRow label="尾款状态" value="待收款" dark />
                            <BaseMenuRow label="服务团队" icon="user" value="3 人" dark />
                        </BaseCard>
                    </DemoBlock>
                </view>
            </PageSection>
        </view>

        <BaseConfirmDialog
            :show="dialogVisible"
            title="确认收款？"
            description="确认后，该订单尾款状态将更新为已收款，并同步到经营数据。"
            confirm-text="确认收款"
            @confirm="handleDialogConfirm"
            @cancel="dialogVisible = false"
        />

        <BaseDateTimePicker
            v-model="datePickerValue"
            v-model:open="datePickerOpen"
            mode="date"
            format="YYYY-MM-DD"
        />

        <BaseRegionPicker
            v-model="regionPickerValue"
            v-model:open="regionPickerOpen"
            @confirm="handleRegionConfirm"
        />

        <BaseServiceRegionPicker
            v-model="serviceRegionPickerValue"
            v-model:open="serviceRegionPickerOpen"
            :data="serviceRegionPickerTree"
            @confirm="handleServiceRegionConfirm"
        />

        <BaseTextPicker
            v-model="singlePickerValue"
            v-model:open="singlePickerOpen"
            :data="singlePickerOptions"
            @confirm="handleSinglePickerConfirm"
        />

        <BaseTextPicker
            v-model="cascadePickerValue"
            v-model:open="cascadePickerOpen"
            :data="cascadePickerOptions"
            @confirm="handleCascadePickerConfirm"
        />

        <BaseMultiTextPicker
            v-model="multiPickerValue"
            v-model:open="multiPickerOpen"
            title="选择服务内容"
            description="用于演示多选文字选择器，最多选择 4 项。"
            :options="multiPickerOptions"
            :max="4"
            @confirm="handleMultiPickerConfirm"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCapsuleTabbar from '@/components/base/BaseCapsuleTabbar.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseConfirmDialog from '@/components/base/BaseConfirmDialog.vue'
import BaseDateCell from '@/components/base/BaseDateCell.vue'
import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseIconButton from '@/components/base/BaseIconButton.vue'
import BaseInfoRow from '@/components/base/BaseInfoRow.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseMediaThumb from '@/components/base/BaseMediaThumb.vue'
import BaseMenuRow from '@/components/base/BaseMenuRow.vue'
import BaseMultiTextPicker from '@/components/base/BaseMultiTextPicker.vue'
import BasePickerField from '@/components/base/BasePickerField.vue'
import BaseRegionPicker from '@/components/base/BaseRegionPicker.vue'
import BaseScheduleCalendar from '@/components/base/BaseScheduleCalendar.vue'
import BaseSearchBar from '@/components/base/BaseSearchBar.vue'
import BaseSegmentedControl from '@/components/base/BaseSegmentedControl.vue'
import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'
import BaseSkeleton from '@/components/base/BaseSkeleton.vue'
import BaseStepper from '@/components/base/BaseStepper.vue'
import BaseTextPicker from '@/components/base/BaseTextPicker.vue'
import BaseToast from '@/components/base/BaseToast.vue'
import BaseUploader from '@/components/base/BaseUploader.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import MetricCard from '@/components/base/MetricCard.vue'
import PageSection from '@/components/base/PageSection.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import DynamicCard from '@/components/business/DynamicCard.vue'
import type { DynamicCardData } from '@/utils/dynamic'
import DemoBlock from './components/DemoBlock.vue'

type GroupKey = 'all' | 'basic' | 'business' | 'form' | 'content' | 'user' | 'feedback' | 'entry'

const activeGroup = ref<GroupKey>('all')
const activeTab = ref('home')
const buttonLoading = ref(false)
const searchKeyword = ref('')
const coupleName = ref('陈宇 & 林沐')
const guestTableCount = ref(18)
const toastVisible = ref(true)
const dialogVisible = ref(false)
const segmentValue = ref('all')
const selectedCalendarDay = ref<number | string>(31)
const datePickerOpen = ref(false)
const regionPickerOpen = ref(false)
const serviceRegionPickerOpen = ref(false)
const singlePickerOpen = ref(false)
const cascadePickerOpen = ref(false)
const multiPickerOpen = ref(false)
const datePickerValue = ref('2026-11-08')
const regionPickerValue = ref<string[]>(['广东省', '广州市', '天河区'])
const regionPickerText = ref('广东省 / 广州市 / 天河区')
const serviceRegionPickerValue = ref({
    province_code: '440000',
    province_name: '广东省',
    city_code: '440100',
    city_name: '广州市',
    district_code: '440106',
    district_name: '天河区'
})
const serviceRegionPickerText = ref('广东省 / 广州市 / 天河区')
const singlePickerValue = ref<string | number>('banquet')
const singlePickerText = ref('宴会厅婚礼')
const cascadePickerValue = ref<Array<string | number>>(['luxury', 'photo'])
const cascadePickerText = ref('高定服务 / 摄影摄像')
const multiPickerValue = ref<string[]>(['photo', 'host'])
const multiPickerText = ref('摄影、主持')

const groupOptions = [
    { label: '全部', value: 'all' },
    { label: '基础', value: 'basic' },
    { label: '业务', value: 'business' },
    { label: '表单', value: 'form' },
    { label: '展示', value: 'content' },
    { label: '反馈', value: 'feedback' },
    { label: '录入', value: 'entry' }
]

const segmentOptions = [
    { label: '全部', value: 'all' },
    { label: '待确认', value: 'pending' },
    { label: '已收款', value: 'paid' }
]

const tabbarItems = [
    { key: 'home', label: '首页', icon: 'home' },
    { key: 'dynamic', label: '动态', icon: 'activity' },
    { key: 'mine', label: '我的', icon: 'user' }
]

const singlePickerOptions = [
    { label: '宴会厅婚礼', value: 'banquet' },
    { label: '户外草坪', value: 'garden' },
    { label: '小型家宴', value: 'family' }
]

const cascadePickerOptions = [
    {
        label: '高定服务',
        value: 'luxury',
        children: [
            { label: '摄影摄像', value: 'photo' },
            { label: '花艺设计', value: 'flower' },
            { label: '婚礼主持', value: 'host' }
        ]
    },
    {
        label: '基础服务',
        value: 'basic',
        children: [
            { label: '场地布置', value: 'layout' },
            { label: '灯光音响', value: 'light' },
            { label: '跟妆服务', value: 'makeup' }
        ]
    }
]

const multiPickerOptions = [
    { label: '摄影', value: 'photo' },
    { label: '摄像', value: 'video' },
    { label: '主持', value: 'host' },
    { label: '化妆', value: 'makeup' },
    { label: '花艺', value: 'flower' },
    { label: '灯光', value: 'light' },
    { label: '甜品台', value: 'dessert' },
    { label: '婚车', value: 'car', disabled: true }
]

const serviceRegionPickerTree = [
    {
        province_code: '440000',
        province_name: '广东省',
        cities: [
            {
                province_code: '440000',
                province_name: '广东省',
                city_code: '440100',
                city_name: '广州市',
                districts: [
                    {
                        province_code: '440000',
                        province_name: '广东省',
                        city_code: '440100',
                        city_name: '广州市',
                        district_code: '440106',
                        district_name: '天河区'
                    },
                    {
                        province_code: '440000',
                        province_name: '广东省',
                        city_code: '440100',
                        city_name: '广州市',
                        district_code: '440111',
                        district_name: '白云区'
                    }
                ]
            },
            {
                province_code: '440000',
                province_name: '广东省',
                city_code: '440300',
                city_name: '深圳市',
                districts: [
                    {
                        province_code: '440000',
                        province_name: '广东省',
                        city_code: '440300',
                        city_name: '深圳市',
                        district_code: '440304',
                        district_name: '福田区'
                    }
                ]
            }
        ]
    },
    {
        province_code: '330000',
        province_name: '浙江省',
        cities: [
            {
                province_code: '330000',
                province_name: '浙江省',
                city_code: '330100',
                city_name: '杭州市',
                districts: [
                    {
                        province_code: '330000',
                        province_name: '浙江省',
                        city_code: '330100',
                        city_name: '杭州市',
                        district_code: '330106',
                        district_name: '西湖区'
                    }
                ]
            }
        ]
    }
]

const calendarDays = ref([
    { day: 28, label: '', state: 'disabled' as const },
    { day: 29, label: '', state: 'disabled' as const },
    { day: 30, label: '', state: 'today' as const },
    { day: 31, label: '婚礼', state: 'booked' as const },
    { day: 1, label: '忙', state: 'busy' as const },
    { day: 2, label: '', state: 'default' as const },
    { day: 3, label: '', state: 'default' as const },
    { day: 4, label: '', state: 'default' as const },
    { day: 5, label: '空', state: 'default' as const },
    { day: 6, label: '忙', state: 'busy' as const },
    { day: 7, label: '', state: 'default' as const },
    { day: 8, label: '', state: 'default' as const },
    { day: 9, label: '', state: 'default' as const },
    { day: 10, label: '', state: 'default' as const }
])

const dateStateSamples = [
    { day: 31, label: '选中', state: 'selected' as const },
    { day: 1, label: '今日', state: 'today' as const },
    { day: 2, label: '忙', state: 'busy' as const },
    { day: 3, label: '禁用', state: 'disabled' as const }
]

const uploadItems = ref([
    { id: 1, url: '/static/images/user/my_topbg.png', label: '收款凭证' },
    { id: 2, label: '合同附件' }
])

const showcaseDynamicItem: DynamicCardData = {
    id: 1,
    user: {
        id: 1,
        nickname: '陈宇 & 林沐',
        avatar: '/static/images/user/default_avatar.png',
        staffId: 0,
        isFavorite: false,
        roleLabel: '新人',
        canFavorite: false
    },
    content: '妆造团队已上传试妆确认图，香槟色花艺和湖畔仪式区的细节正在同步给新人确认。',
    images: ['/static/images/user/my_topbg.png'],
    topics: [
        { id: 1, name: '香槟色婚礼' },
        { id: 2, name: '试妆确认' }
    ],
    location: {
        name: '云水台宴会厅',
        lat: 0,
        lng: 0
    },
    viewCount: 1280,
    likeCount: 128,
    commentCount: 26,
    isLiked: true,
    createTime: '2026-06-11 14:30:00',
    dynamicType: 1,
    dynamicTypeLabel: '图文'
}

const shouldShow = (key: GroupKey) => activeGroup.value === 'all' || activeGroup.value === key

const toggleButtonLoading = () => {
    buttonLoading.value = true
    setTimeout(() => {
        buttonLoading.value = false
    }, 900)
}

const handleCalendarSelect = (day: string | number) => {
    selectedCalendarDay.value = day
}

const resolvePickerLabel = (value: string | number, options: Array<Record<string, any>>) => {
    const option = options.find((item) => item.value === value)
    return String(option?.label || value || '')
}

const resolveCascadeText = (value: Array<string | number>, options: Array<Record<string, any>>) => {
    const [parentValue, childValue] = value
    const parent = options.find((item) => item.value === parentValue)
    const child = (parent?.children || []).find((item: Record<string, any>) => item.value === childValue)
    return [parent?.label, child?.label].filter(Boolean).join(' / ')
}

const handleRegionConfirm = (value: string[], item?: any) => {
    const itemText = Array.isArray(item)
        ? item.map((region) => region?.name || region?.label || region?.value).filter(Boolean).join(' / ')
        : ''
    regionPickerText.value = itemText || value.filter(Boolean).join(' / ')
}

const handleServiceRegionConfirm = (value: typeof serviceRegionPickerValue.value) => {
    serviceRegionPickerText.value = [
        value.province_name,
        value.city_name,
        value.district_name
    ]
        .filter(Boolean)
        .join(' / ')
}

const handleSinglePickerConfirm = (value: string | number | Array<string | number>) => {
    const nextValue = Array.isArray(value) ? value[0] : value
    singlePickerValue.value = nextValue
    singlePickerText.value = resolvePickerLabel(nextValue, singlePickerOptions)
}

const handleCascadePickerConfirm = (value: string | number | Array<string | number>) => {
    const nextValue = Array.isArray(value) ? value : [value]
    cascadePickerValue.value = nextValue
    cascadePickerText.value = resolveCascadeText(nextValue, cascadePickerOptions)
}

const handleMultiPickerConfirm = (value: string[]) => {
    multiPickerText.value =
        value
            .map((item) => multiPickerOptions.find((option) => option.value === item)?.label)
            .filter(Boolean)
            .join('、') || '请选择服务'
}

const addUploadMock = () => {
    uploadItems.value = [
        ...uploadItems.value,
        {
            id: Date.now(),
            label: `凭证 ${uploadItems.value.length + 1}`
        }
    ]
}

const handleDialogConfirm = () => {
    dialogVisible.value = false
    toastVisible.value = true
}

</script>

<style lang="scss" scoped>
.showcase-hero {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 22rpx;
    padding: 42rpx var(--wm-space-page-x, 32rpx) 32rpx;
    color: var(--wm-text-inverse, #FFFDF8);

    &__kicker {
        align-self: flex-start;
        min-height: 40rpx;
        padding: 0 18rpx;
        display: inline-flex;
        align-items: center;
        border-radius: 999rpx;
        background: rgba(217, 190, 130, 0.14);
        border: 1rpx solid rgba(217, 190, 130, 0.42);
        font-size: 20rpx;
        font-weight: 900;
        color: var(--wm-color-champagne, #D9BE82);
    }

    &__title {
        font-family: var(--wm-font-family-display, Georgia, serif);
        font-size: 56rpx;
        font-weight: 900;
        line-height: 1.08;
    }

    &__desc {
        max-width: 640rpx;
        font-size: 24rpx;
        line-height: 1.65;
        color: rgba(255, 253, 248, 0.72);
    }

    .base-segmented-control {
        margin-top: 8rpx;
    }
}

.showcase-content {
    position: relative;
    z-index: 1;
    padding-bottom: 56rpx;
}

.showcase-grid,
.showcase-stack {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.showcase-row,
.date-cell-row {
    display: flex;
    align-items: center;
    gap: 16rpx;
    flex-wrap: wrap;
}

.native-nav-sample {
    height: 96rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 26rpx;
    background: var(--wm-nav-bg, #000000);
    border-bottom: 1rpx solid var(--wm-nav-border, var(--wm-color-champagne, #D9BE82));
    color: var(--wm-nav-text, #FFFDF8);
    box-shadow: var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18));
}

.native-nav-sample__icon,
.native-nav-sample__actions {
    width: 136rpx;
    display: flex;
    align-items: center;
}

.native-nav-sample__icon {
    justify-content: flex-start;
}

.native-nav-sample__actions {
    justify-content: flex-end;
    gap: 24rpx;
}

.native-nav-sample__title {
    flex: 1;
    min-width: 0;
    text-align: center;
    font-size: 30rpx;
    font-weight: 900;
    line-height: 1.2;
    color: var(--wm-nav-text, #FFFDF8);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.business-card-head,
.timeline-node,
.feed-card,
.order-line,
.staff-mini,
.price-card {
    display: flex;
    align-items: center;
    gap: 18rpx;
}

.business-card-head {
    justify-content: space-between;
}

.business-card-title,
.case-title,
.feed-card__title,
.order-line__title,
.timeline-node__title,
.staff-mini__name,
.price-card__title {
    display: block;
    font-size: 30rpx;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.business-card-desc,
.case-desc,
.feed-card__desc,
.order-line__desc,
.timeline-node__desc,
.staff-mini__desc,
.price-card__desc {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #665E52);
}

.business-lines {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.showcase-list-panel {
    --wm-space-list-panel-y: 12rpx;
    --wm-space-list-panel-x: 28rpx;
}

.showcase-order-detail {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.showcase-order-status {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.showcase-order-status__title {
    font-size: 34rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-text-primary, #191713);
}

.showcase-order-status__desc {
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #665E52);
    white-space: pre-line;
}

.showcase-list-panel :deep(.base-info-row + .base-info-row) {
    border-top: 1rpx solid var(--wm-list-divider, rgba(216, 201, 173, 0.72));
}

.showcase-dynamic-scene {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.showcase-dynamic-filter {
    --wm-space-list-panel-y: 12rpx;
    --wm-space-list-panel-x: 16rpx;
}

.showcase-dynamic-chip-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.showcase-dynamic-card {
    --dynamic-plaza-card-radius: 32rpx;
}

.showcase-dynamic-scene .showcase-list-panel :deep(.base-menu-row + .base-menu-row) {
    border-top: 1rpx solid var(--wm-list-divider, rgba(216, 201, 173, 0.72));
}

.timeline-node__dot {
    width: 24rpx;
    height: 24rpx;
    border-radius: 999rpx;
    background: var(--wm-color-gold, #B8954A);
    box-shadow: 0 0 0 12rpx rgba(184, 149, 74, 0.14);
}

.timeline-node__copy,
.feed-card__copy,
.order-line__copy,
.staff-mini__copy {
    flex: 1;
    min-width: 0;
}

.case-image {
    width: 100%;
    height: 260rpx;
}

.case-copy {
    padding: 26rpx;
}

.feed-card__avatar,
.order-line__avatar {
    width: 88rpx;
    height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: var(--wm-color-primary, #191713);
    color: var(--wm-color-champagne, #D9BE82);
    font-family: var(--wm-font-family-display, Georgia, serif);
    font-size: 36rpx;
    font-weight: 900;
}

.order-line__title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.progress-track {
    height: 18rpx;
    border-radius: 999rpx;
    background: var(--wm-color-mist, #ECE4D6);
    overflow: hidden;
}

.progress-track__bar {
    width: 70%;
    height: 100%;
    border-radius: inherit;
    background: var(--wm-color-primary, #191713);
}

.staff-mini__avatar {
    width: 88rpx;
    height: 88rpx;
    border-radius: 32rpx;
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
}

.staff-mini__name,
.staff-mini__desc {
    color: var(--wm-text-inverse, #FFFDF8);
}

.staff-mini__desc {
    color: rgba(255, 253, 248, 0.68);
}

.price-card {
    align-items: flex-start;
    flex-direction: column;
}

.price-card__price {
    font-family: var(--wm-font-family-display, Georgia, serif);
    font-size: 54rpx;
    line-height: 1;
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.notice-bar {
    min-height: 96rpx;
    padding: 0 28rpx;
    display: flex;
    align-items: center;
    gap: 16rpx;
    border-radius: 32rpx;
    background: var(--wm-color-gold-soft, #F1E5C8);
    border: 1rpx solid var(--wm-color-champagne, #D9BE82);
}

.notice-bar__text {
    flex: 1;
    min-width: 0;
    font-size: 24rpx;
    font-weight: 900;
    color: var(--wm-color-clay, #9A6B35);
}

.notice-bar__icon {
    flex-shrink: 0;
}
</style>
