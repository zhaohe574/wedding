<template>
    <!-- 透明导航只在「顶部真有 hero 承接」时成立：
         loading 有 .sk__hero、正常态 step<3 有 .bhero；但 error 态是整页空态、没有 hero，
         此时若仍透明+light，白色返回键/标题压在浅色空态上完全看不见，且没有占位块。
         故把 error 一并纳入实底条件；导航底色沿用全站默认 --c-surface，与其它 solid 页一致。 -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        :title="navTitle"
        :nav-variant="step < 3 && !error ? 'transparent' : 'solid'"
        :tone="step < 3 && !error ? 'light' : 'dark'"
        :nav-capsule-avoid="true"
        nav-back-mode="none"
        @nav-back="onBack"
    >
        <!-- 首屏骨架：hero 米色块 + 卡片骨架 + 底栏金额占位（§4.6/4.7 L） -->
        <view v-if="loading" class="sk">
            <view class="sk__hero">
                <base-skeleton :rows="2" />
            </view>
            <view class="sk__body">
                <base-skeleton :rows="3" />
            </view>
        </view>

        <!-- 缺参数（business，不可重试）/ 接口失败（可重试） -->
        <base-empty
            v-else-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
            @retry="load"
        />

        <template v-else>
            <!-- 大图 hero：06 用套餐图，07 用附加项图；无图则米色块（不拿无关图顶替） -->
            <view v-if="step < 3" class="bhero">
                <image
                    v-if="heroImage"
                    class="bhero__img"
                    :src="imageUrl(heroImage)"
                    mode="aspectFill"
                />
                <view v-else class="bhero__blank" />
                <view class="bhero__scrim" aria-hidden="true" />

                <!-- 日期胶囊（只显示，点开改日期；未下单前允许改） -->
                <view
                    v-if="step === 1"
                    class="bhero__date"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="更改服务日期"
                    @tap="openDatePicker"
                >
                    <tn-icon name="calendar" :size="24" />
                    <text class="bhero__date-text">{{ dateLabel }}</text>
                    <tn-icon name="down" :size="20" />
                </view>

                <view class="bhero__info">
                    <text class="bhero__eyebrow">{{ heroEyebrow }}</text>
                    <text class="bhero__name ellipsis">{{ heroTitle }}</text>
                    <text v-if="heroDesc" class="bhero__desc ellipsis-2">{{ heroDesc }}</text>
                    <view v-if="heroPrice != null" class="bhero__price">
                        <price size="lg" :content="heroPrice" color="var(--c-brand-soft)" />
                        <text class="bhero__unit">{{ step === 1 ? '/ 场' : '/ 项' }}</text>
                    </view>
                </view>
            </view>

            <!-- 两步步骤条：收编到 components/steps（组件此前零引用，本批首次进包）。
                 current 是 0-based：step 1 → 0（第 1 步当前）、step 2 → 1（第 2 步当前，第 1 步完成）。
                 标签沿用原文案「选套餐 / 附加项」，不改文案。 -->
            <view class="steps-wrap">
                <steps v-if="step < 3" :steps="['选套餐', '附加项']" :current="step - 1" />
            </view>

            <!-- ============ 06 选择套餐 ============ -->
            <view v-if="step === 1" class="panel">
                <view v-if="packages.length" class="optrow">
                    <view
                        v-for="p in packages"
                        :key="pkgId(p)"
                        class="optcard"
                        :class="{ 'is-on': Number(packageId) === Number(pkgId(p)) }"
                        hover-class="is-pressed"
                        aria-role="radio"
                        :aria-checked="Number(packageId) === Number(pkgId(p))"
                        @tap="selectPackage(p)"
                    >
                        <view class="optcard__tick" aria-hidden="true">
                            <tn-icon name="check" :size="18" />
                        </view>
                        <image
                            v-if="pkgImage(p)"
                            class="optcard__img"
                            :src="imageUrl(pkgImage(p))"
                            mode="aspectFill"
                        />
                        <view v-else class="optcard__blank" />
                        <view class="optcard__body">
                            <text class="optcard__name ellipsis">{{ pkgName(p) }}</text>
                            <view class="optcard__price">
                                <price size="sm" :content="pkgPrice(p)" />
                                <text class="optcard__unit">/ 场</text>
                            </view>
                        </view>
                    </view>
                </view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="这档服务暂时不可选"
                    desc="换一位主持人，或过两天再来"
                    action-text="返回详情"
                    show-action
                    @action="onBack"
                />

                <text v-if="packageRules" class="bkhint">{{ packageRules }}</text>
            </view>

            <!-- ============ 07 选择附加项 ============ -->
            <view v-else-if="step === 2" class="panel">
                <template v-if="addons.length">
                    <view class="optrow">
                        <view
                            v-for="a in addons"
                            :key="addonId(a)"
                            class="optcard"
                            :class="{ 'is-on': selectedAddons.includes(addonId(a)) }"
                            hover-class="is-pressed"
                            aria-role="checkbox"
                            :aria-checked="selectedAddons.includes(addonId(a))"
                            @tap="toggleAddon(addonId(a))"
                        >
                            <view class="optcard__tick" aria-hidden="true">
                                <tn-icon name="check" :size="18" />
                            </view>
                            <image
                                v-if="addonImage(a)"
                                class="optcard__img"
                                :src="imageUrl(addonImage(a))"
                                mode="aspectFill"
                            />
                            <view v-else class="optcard__blank" />
                            <view class="optcard__body">
                                <text class="optcard__name ellipsis">{{ a.name }}</text>
                                <view class="optcard__price">
                                    <price
                                        size="sm"
                                        :content="addonPrice(a)"
                                        prefix="+¥"
                                        :trim-zeros="true"
                                    />
                                </view>
                            </view>
                        </view>
                    </view>

                    <view class="bkline">
                        <text class="bkline__sum">
                            已选附加
                            <text class="bkline__strong">{{ selectedAddons.length }}</text> 项
                            <text class="bkline__strong"> {{ addonSumText }}</text>
                        </text>
                        <view
                            class="bkline__skip"
                            hover-class="is-pressed"
                            aria-role="button"
                            @tap="skipAddons"
                        >
                            <text class="bkline__skip-text">不需要，直接下一步</text>
                            <tn-icon name="right" :size="20" />
                        </view>
                    </view>
                </template>

                <!-- 无附加项：整块不出现空标题，直接给结论与出口（§4.7 E） -->
                <base-empty
                    v-else
                    status="empty"
                    empty-text="这档套餐已含全部附加服务"
                    desc="直接进入确认即可"
                    action-text="下一步 · 确认订单"
                    show-action
                    @action="nextStep"
                />
            </view>

            <!-- ============ 08 订单确认 ============ -->
            <view v-else class="panel">
                <!-- 报价失败：整页错误 + 重试，绝不沿用旧价 -->
                <view v-if="quoteState === 'failed'" class="qfail">
                    <base-empty
                        status="error"
                        :error-kind="view?.errorKind || 'unknown'"
                        error-text="报价没有算出来"
                        desc="价格必须以服务端报价为准，请重新获取"
                        action-text="重新获取报价"
                        show-action
                        @action="runPreview"
                    />
                    <view class="qfail__back" hover-class="is-pressed" @tap="prevStep">
                        <text class="qfail__back-text">返回上一步</text>
                    </view>
                </view>

                <template v-else>
                    <!-- 订单卡 -->
                    <view class="ord">
                        <view class="ord__top">
                            <view class="ord__date">
                                <text class="ord__date-d">{{ dateDay }}</text>
                                <text class="ord__date-w">{{ dateYearWeek }}</text>
                            </view>
                            <view class="ord__meta">
                                <text class="ord__meta-b ellipsis">{{ cityVenueText }}</text>
                                <text class="ord__meta-p ellipsis">{{ heroTitle }} · 14:00</text>
                            </view>
                            <!-- 无 pay_deadline_time 时不能说「已锁档」，只能说「待支付」（§4.8 ⚠️） -->
                            <text class="ord__badge">{{ orderBadge }}</text>
                        </view>

                        <!-- 锁档提示：只有真的拿到锁才说「已保留」，接口异常时说真话 -->
                        <view v-if="lockHint" class="ord__lock">
                            <tn-icon
                                name="clock"
                                :size="22"
                                class="ic-lock"
                                :class="{ 'is-on': lockState === 'locked' }"
                            />
                            <text class="ord__lock-text">{{ lockHint }}</text>
                        </view>

                        <view v-if="staffName" class="ord__host">
                            <image
                                v-if="staffAvatar"
                                class="ord__host-avatar"
                                :src="imageUrl(staffAvatar)"
                                mode="aspectFill"
                            />
                            <text class="ord__host-name ellipsis">{{ staffName }}</text>
                            <text class="ord__pkg ellipsis">{{ currentPackageName }}</text>
                        </view>

                        <!-- 服务人员（可选角色）：接口给了 related_role_configs 才出现 -->
                        <view v-if="roleConfigs.length" class="ord__roles">
                            <view v-for="r in roleConfigs" :key="r.role_key" class="ord__role">
                                <text class="ord__role-k">{{ r.role_label }}</text>
                                <text class="ord__role-v ellipsis">{{
                                    roleSummary(r.role_key)
                                }}</text>
                                <text class="ord__role-link" @tap="openRolePicker(r)">
                                    {{ roleSelected[r.role_key] ? '更换' : '去选择' }}
                                </text>
                            </view>
                        </view>

                        <view class="ord__addon">
                            <view class="ord__addon-head">
                                <text class="ord__addon-label"
                                    >附加项 {{ selectedAddons.length }} 项</text
                                >
                                <text class="ord__addon-amount">{{ addonSumText }}</text>
                            </view>
                            <view
                                v-for="a in selectedAddonList"
                                :key="addonId(a)"
                                class="ord__addon-row"
                            >
                                <text class="ord__addon-name ellipsis">{{ a.name }}</text>
                                <text class="ord__addon-price">+¥{{ money(addonPrice(a)) }}</text>
                            </view>
                            <!-- 加购行：无百分比数据，只给「可加什么 + 多少钱」，不编社会认同 -->
                            <view v-if="upsellAddon" class="upsell">
                                <text class="upsell__text ellipsis"
                                    >还可以加「{{ upsellAddon.name }}」</text
                                >
                                <view class="upsell__btn" hover-class="is-pressed" @tap="addUpsell">
                                    <text class="upsell__btn-text"
                                        >+¥{{ money(addonPrice(upsellAddon)) }} 加上</text
                                    >
                                </view>
                            </view>
                        </view>

                        <view class="ord__contact">
                            <text class="ord__contact-title">联系与地址</text>
                            <view class="ord__row">
                                <text class="ord__row-k">联系人</text>
                                <text class="ord__row-v ellipsis">
                                    {{ form.contact_name || '未填写' }}
                                    {{
                                        form.contact_mobile
                                            ? `· ${maskMobile(form.contact_mobile)}`
                                            : ''
                                    }}
                                </text>
                                <text class="ord__row-link" @tap="openContactSheet">更换</text>
                            </view>
                            <view class="ord__row">
                                <text class="ord__row-k">服务地址</text>
                                <text class="ord__row-v ellipsis">{{
                                    form.service_address || '未填写'
                                }}</text>
                                <text class="ord__row-link" @tap="openContactSheet">更换</text>
                            </view>
                        </view>
                    </view>

                    <!-- 支付安排：定金 / 尾款两段（金额只取服务端字段） -->
                    <view class="card2">
                        <text class="card2__title">支付安排</text>
                        <view class="sched">
                            <view class="sched__step is-now">
                                <text class="sched__b">定金</text>
                                <text class="sched__s">{{ depositStepText }}</text>
                                <text class="sched__e">{{ depositText }}</text>
                            </view>
                            <view class="sched__step">
                                <text class="sched__b">尾款</text>
                                <text class="sched__s">{{ balanceStepText }}</text>
                                <text class="sched__e">{{ balanceText }}</text>
                            </view>
                        </view>

                        <view class="amounts">
                            <view class="amounts__row">
                                <text class="amounts__k">套餐金额</text>
                                <text class="amounts__v">{{ quoteText('service_amount') }}</text>
                            </view>
                            <view class="amounts__row">
                                <text class="amounts__k">附加项</text>
                                <text class="amounts__v">{{ quoteText('addon_amount') }}</text>
                            </view>
                            <view class="amounts__row amounts__row--total">
                                <text class="amounts__k amounts__k--total">合计</text>
                                <text class="amounts__total">{{ quoteText('total_amount') }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 金额一致性断言失败：定金+尾款 ≠ 总额，阻断支付 -->
                    <view v-if="amountMismatch" class="block">
                        <tn-icon name="warning" :size="28" class="block__icon" />
                        <text class="block__text">
                            报价金额不一致（定金 + 尾款 ≠ 总额），已为你阻断支付，请重新获取报价
                        </text>
                        <view class="block__btn" hover-class="is-pressed" @tap="runPreview">
                            <text class="block__btn-text">重新获取报价</text>
                        </view>
                    </view>

                    <!-- 支付失败：不换页，就地插错误块（§4.8 D） -->
                    <view v-if="payError" class="block block--warn">
                        <tn-icon name="warning" :size="28" class="block__icon" />
                        <text class="block__text">{{ payError }}</text>
                        <view
                            v-if="orderId"
                            class="block__btn"
                            hover-class="is-pressed"
                            @tap="goOrderDetail"
                        >
                            <text class="block__btn-text">查看订单</text>
                        </view>
                    </view>

                    <!-- 服务端结构化校验（档期冲突等），直接上墙 -->
                    <view v-if="validations.length" class="block block--err">
                        <view v-for="(v, i) in validations" :key="i" class="block__row">
                            <tn-icon name="warning" :size="24" class="block__icon" />
                            <text class="block__text">{{ v.message }}</text>
                        </view>
                    </view>

                    <!-- 协议：默认未勾；未勾时按钮不置灰，点按在下方出红字 -->
                    <view class="agree-wrap">
                        <agreement-check v-model="agreed" :error="agreeError">
                            <text>
                                我已阅读并同意《预约服务协议》与《售后服务规则》，定金支付后档期即锁定。
                            </text>
                        </agreement-check>
                    </view>
                </template>
            </view>
        </template>

        <!-- 吸底操作栏：左侧金额 + 右侧主操作，随 step 变化 -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abp">
                    <text class="abp__label">{{ barLabel }}</text>
                    <view v-if="barAmount != null" class="abp__amount">
                        <price size="lg" :content="barAmount" :trim-zeros="false" />
                    </view>
                    <text v-else class="abp__na">—</text>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            v-if="step > 1"
                            text="上一步"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="prevStep"
                        />
                        <base-button
                            v-if="step < 3"
                            :text="step === 1 ? '下一步 · 附加项' : '下一步 · 确认订单'"
                            variant="ink"
                            size="md"
                            :block="false"
                            @click="nextStep"
                        />
                        <base-button
                            v-else
                            :text="payButtonText"
                            variant="ink"
                            size="md"
                            :block="false"
                            :loading="submitting || paying"
                            :disabled="payDisabled"
                            @click="handleSubmit"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <!-- 日期选择（只改日期，未下单前允许） -->
    <tn-popup
        v-model="datePickerVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="pop">
            <view class="pop__head">
                <text class="pop__title">选择服务日期</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="datePickerVisible = false"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>
            <tn-calendar v-model="pickerDate" @change="onDateChange" />
        </view>
    </tn-popup>

    <!-- 联系人 / 地址编辑（底部弹层，不跳页） -->
    <tn-popup
        v-model="contactSheetVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="pop">
            <view class="pop__head">
                <text class="pop__title">联系与地址</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="contactSheetVisible = false"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>
            <view class="pop__body">
                <base-input
                    v-model="form.contact_name"
                    label="联系人"
                    placeholder="请输入姓名"
                    maxlength="20"
                    required
                    :error="errors.contact_name"
                    @blur="validateField('contact_name')"
                />
                <base-input
                    v-model="form.contact_mobile"
                    label="手机号"
                    type="number"
                    placeholder="请输入手机号"
                    maxlength="11"
                    required
                    :error="errors.contact_mobile"
                    @blur="validateField('contact_mobile')"
                />
                <base-input
                    v-model="form.service_address"
                    label="服务地址"
                    placeholder="请输入详细地址"
                    required
                    :error="errors.service_address"
                    @blur="validateField('service_address')"
                />
                <base-input v-model="form.remark" label="备注" placeholder="选填" />
            </view>
            <view class="pop__foot">
                <base-button text="保存" variant="ink" size="md" @click="saveContact" />
            </view>
        </view>
    </tn-popup>

    <!-- 角色候选人弹层（接口给 related_role_configs 时才可能打开） -->
    <tn-popup
        v-model="pickerVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="pop">
            <view class="pop__head">
                <text class="pop__title">{{ pickerTitle }}</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="closePicker"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>

            <view v-if="!pickerStage.staffId" class="pop__body">
                <view v-if="!pickerCandidates.length" class="pop__empty">
                    <text class="pop__empty-text">暂无可选人员</text>
                </view>
                <view
                    v-for="c in pickerCandidates"
                    :key="c.id"
                    class="cand"
                    hover-class="is-pressed"
                    @tap="chooseCandidate(c)"
                >
                    <image
                        v-if="c.avatar"
                        class="cand__avatar"
                        :src="imageUrl(c.avatar)"
                        mode="aspectFill"
                    />
                    <view class="cand__body">
                        <text class="cand__name">{{ c.name }}</text>
                        <text v-if="c.rating || c.order_count" class="cand__meta">
                            {{ c.rating ? `${c.rating} 分` : ''
                            }}{{ c.rating && c.order_count ? ' · ' : ''
                            }}{{ c.order_count ? `${c.order_count} 单` : '' }}
                        </text>
                    </view>
                    <tn-icon name="right" :size="24" />
                </view>
            </view>

            <view v-else class="pop__body">
                <view class="pop__back" hover-class="is-pressed" @tap="pickerStage.staffId = 0">
                    <tn-icon name="left" :size="24" />
                    <text class="pop__back-text">返回人员列表</text>
                </view>
                <view
                    v-for="p in pickerPackages"
                    :key="pkgId(p)"
                    class="cand"
                    hover-class="is-pressed"
                    @tap="confirmRole(p)"
                >
                    <view class="cand__body">
                        <text class="cand__name">{{ pkgName(p) }}</text>
                    </view>
                    <price size="sm" :content="pkgPrice(p)" />
                </view>
            </view>
        </view>
    </tn-popup>

    <app-toast />
</template>

<script setup lang="ts">
/**
 * 06 选择套餐 / 07 选择附加项 / 08 订单确认（设计稿 2585–2855）。
 *
 * 单页三步（step=1/2/3 分别对应三屏），**步骤数与既有下单逻辑不改**：
 *  - 「套餐 + 附加项」在 06/07 各占一屏（设计稿把两者拆开，避免附加项被藏在页签后面）；
 *  - 角色选人（related_role_configs）在设计稿里没有，收进 08 订单卡的一行，接口没给就不出现；
 *  - 提交顺序固定：校验 → createOrder（幂等）→ 读服务端 need_pay / current_pay_stage 判断能否立即支付
 *    → prepay → wx.requestPayment → redirectTo 09。**不做「下单成功就支付」**，后端待确认订单不能 prepay。
 *
 * 铁律：
 *  - 金额只消费服务端字段；支付前断言 定金 + 尾款 === 总额，不等就阻断（本页 amountMismatch）；
 *  - 报价失败整页错误 + 重试，不沿用旧价；
 *  - 协议默认未勾，未勾时按钮不置灰，点按出红字。
 */
import { computed, nextTick, reactive, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import {
    getStaffDetail,
    getStaffPackages,
    getBookingRoleCandidates,
    lockSchedule,
    releaseLock,
    checkAvailable
} from '@/api/catalog'
import { previewOrder, previewBooking, createOrder } from '@/api/order'
import { prepay } from '@/api/pay'
import { requestPayment } from '@/platform/pay'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'
import { formatDate, formatMoney, parseDateValue } from '@shared/utils/format'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'

const appStore = useAppStore()

const step = ref(1)
const loading = ref(true)
const error = ref(false)
const submitting = ref(false)
const paying = ref(false)

const rawError = ref<unknown>(null)
const view = computed(() =>
    resolveErrorView(staffId.value ? (rawError.value as any) : new Error(FB.ERR_PARAM_MISSING), {
        hasForm: true,
        reason: staffId.value ? undefined : 'param-missing'
    })
)

const staffId = ref(0)
const date = ref('')
const packages = ref<any[]>([])
const addons = ref<any[]>([])
const roleConfigs = ref<any[]>([])

const packageId = ref(0)
const selectedAddons = ref<number[]>([])
const roleSelected = reactive<
    Record<string, { staff_id: number; package_id: number; name: string; price: number }>
>({})

const staffName = ref('')
const staffAvatar = ref('')
const staffCover = ref('')
const city = ref('')

const quote = ref<any>({})
const validations = ref<Array<{ message: string; level?: string }>>([])
const quoteHash = ref('')
const quoteState = ref<'idle' | 'loading' | 'ready' | 'failed'>('idle')

const agreed = ref(false)
const agreeError = ref('')
const payError = ref('')
const orderId = ref(0)

const form = reactive({
    contact_name: '',
    contact_mobile: '',
    service_address: '',
    remark: ''
})
const errors = reactive<Record<string, string>>({})

const datePickerVisible = ref(false)
const pickerDate = ref('')
const contactSheetVisible = ref(false)

const pickerVisible = ref(false)
const pickerRole = ref<any>(null)
const pickerCandidates = ref<any[]>([])
const pickerPackages = ref<any[]>([])
const pickerStage = reactive({ staffId: 0 })
const pickerTitle = computed(() => pickerRole.value?.role_label || '选择人员')

/* ---------------- 导航 / 标题 ---------------- */

const navTitle = computed(() =>
    step.value === 1 ? '预约档期' : step.value === 2 ? '附加项' : '订单确认'
)

function onBack() {
    if (step.value > 1) {
        prevStep()
        return
    }
    const pages = getCurrentPages()
    if (pages.length > 1) uni.navigateBack()
    else uni.switchTab({ url: '/pages/index/index' })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        onBack()
        return
    }
    if (kind === 'login') navigate({ path: '/pages/login/login' })
}

/* ---------------- 卡片 / 展示工具 ---------------- */

function pkgId(p: any) {
    return Number(p?.package_id || p?.id || 0)
}
function pkgName(p: any) {
    return p?.package?.name || p?.name || '服务套餐'
}
function pkgPrice(p: any) {
    return p?.price ?? p?.package?.price ?? 0
}
function pkgImage(p: any) {
    return p?.image || p?.cover || p?.package?.image || p?.package?.cover || ''
}
function addonId(a: any) {
    return Number(a?.id || a?.addon_id || 0)
}
function addonPrice(a: any) {
    return a?.price ?? 0
}
function addonImage(a: any) {
    return a?.image || a?.cover || ''
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function money(v: any) {
    return formatMoney(v as any, { thousand: true })
}

function maskMobile(v: string) {
    const s = String(v || '')
    return /^\d{11}$/.test(s) ? `${s.slice(0, 3)}****${s.slice(7)}` : s
}

const currentPackage = computed(() =>
    packages.value.find((p) => pkgId(p) === Number(packageId.value))
)
const currentPackageName = computed(() => {
    const p = currentPackage.value
    return p ? pkgName(p) : ''
})

const dateLabel = computed(() => {
    const d = parseDateValue(date.value)
    if (!d) return '选择日期'
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${formatDate(d, 'MM.DD')} ${w}${city.value ? ` · ${city.value}` : ''}`
})

const dateDay = computed(() => (date.value ? formatDate(date.value, 'MM.DD', '待定') : '待定'))
const dateYearWeek = computed(() => {
    const d = parseDateValue(date.value)
    if (!d) return '日期待定'
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${d.getFullYear()} ${w}`
})

/** 最后点选的附加项：仅用于 hero 大图联动，不代表「已选」 */
const lastPickedAddon = ref<any>(null)

/* hero 文案：06 = 当前套餐，07 = 最后勾选的附加项（没勾则显示套餐） */
const heroImage = computed(() => {
    if (step.value === 2 && lastPickedAddon.value)
        return addonImage(lastPickedAddon.value) || staffCover.value
    const p = currentPackage.value
    return (p && pkgImage(p)) || staffCover.value
})
const heroEyebrow = computed(() => (step.value === 1 ? '服务套餐 · PACKAGE' : '附加项 · ADD-ON'))
const heroTitle = computed(() => {
    if (step.value === 1) return currentPackageName.value || '服务套餐'
    if (lastPickedAddon.value) return lastPickedAddon.value.name
    return '附加项'
})
const heroDesc = computed(() => {
    if (step.value === 1) {
        const p = currentPackage.value
        return String(p?.desc || p?.package?.desc || p?.package?.description || '')
    }
    if (lastPickedAddon.value)
        return String(lastPickedAddon.value.desc || lastPickedAddon.value.description || '')
    return ''
})
const heroPrice = computed(() => {
    if (step.value === 1) return currentPackage.value ? pkgPrice(currentPackage.value) : null
    if (lastPickedAddon.value) return addonPrice(lastPickedAddon.value)
    return null
})

const selectedAddonList = computed(() =>
    addons.value.filter((a) => selectedAddons.value.includes(addonId(a)))
)

/** 加购推荐：只推荐「还没选的第一项」，不出现百分比 */
const upsellAddon = computed(() => {
    const first = addons.value.find((a) => !selectedAddons.value.includes(addonId(a)))
    return first || null
})

const addonSumText = computed(() => {
    const sum = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
    return sum > 0 ? `+¥${money(sum)}` : '+¥0'
})

const packageRules = computed(() => {
    const p = currentPackage.value
    const rules = p?.rules || p?.package?.rules || p?.duration_desc || ''
    return rules ? String(rules) : ''
})

const cityVenueText = computed(() => {
    const addr = String(form.service_address || '').trim()
    if (city.value && addr) return `${city.value} · ${addr}`
    return addr || city.value || '场地待确认'
})

/* ---------------- 报价金额 ---------------- */

function toCents(v: any): number | null {
    if (v === null || v === undefined || v === '') return null
    const n = Number(v)
    return Number.isFinite(n) ? Math.round(n * 100) : null
}

const depositCents = computed(() => toCents(quote.value?.deposit_amount))
const balanceCents = computed(() => toCents(quote.value?.balance_amount))
const totalCents = computed(() => toCents(quote.value?.total_amount))

/**
 * 支付前断言：定金 + 尾款 === 总额。
 * 三个字段都拿到才断言；任一缺失返回 false 但不阻断（服务端可能只做一段收款）。
 */
const amountMismatch = computed(() => {
    const d = depositCents.value
    const b = balanceCents.value
    const t = totalCents.value
    if (d === null || b === null || t === null) return false
    if (d <= 0 || b <= 0) return false
    return Math.abs(d + b - t) > 1
})

const depositText = computed(() => quoteText('deposit_amount'))
const balanceText = computed(() => quoteText('balance_amount'))
const depositStepText = computed(() => quote.value?.payment_mode_desc || '今天支付')
const balanceStepText = computed(() => {
    const d = quote.value?.balance_pay_desc || quote.value?.balance_desc
    return d ? String(d) : '婚礼前 7 天开放支付'
})

function quoteText(key: string) {
    const v = quote.value?.[key]
    if (v === null || v === undefined || v === '') return '—'
    return `¥${money(v)}`
}

/** 应付金额：有定金付定金，没有定金付全额；都缺失返回 null（禁用支付） */
const barAmount = computed<number | string | null>(() => {
    if (step.value === 1) {
        return currentPackage.value ? pkgPrice(currentPackage.value) : null
    }
    if (step.value === 2) {
        if (quoteState.value === 'ready' && quote.value?.total_amount != null)
            return quote.value.total_amount
        // 报价未回时用本地套餐价 + 附加项价（仅用于展示，提交仍以服务端报价为准）
        const base = Number(currentPackage.value ? pkgPrice(currentPackage.value) : 0)
        const add = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
        return base + add
    }
    const dep = quote.value?.deposit_amount
    if (dep !== undefined && dep !== null && Number(dep) > 0) return dep
    const t = quote.value?.total_amount
    return t === undefined || t === null || t === '' ? null : t
})

const barLabel = computed(() => {
    if (step.value === 1) return '当前套餐'
    if (step.value === 2) return '合计（含套餐）'
    return quote.value?.deposit_amount && Number(quote.value.deposit_amount) > 0
        ? '应付定金'
        : '应付金额'
})

const payableNow = computed(() => barAmount.value !== null && !amountMismatch.value)

/** 无 pay_deadline_time 时说真话：支付成功前档期不为你预留，角标只写「待支付 / 待确认」 */
/**
 * 锁档状态（08 屏进入即锁，15 分钟 TTL）。
 * - locked：已锁住，显示真实剩余时间
 * - failed：业务失败（别人先锁）→ 必须拦，回退换档期
 * - error：接口异常 → 不拦，隐藏角标，改真话文案
 */
const lockState = ref<'idle' | 'locking' | 'locked' | 'failed' | 'error'>('idle')
const lockLeft = ref(0)
let lockTimer: ReturnType<typeof setInterval> | null = null

const orderBadge = computed(() => {
    if (lockState.value === 'locked') return '已锁档'
    if (lockState.value === 'locking') return '锁定中'
    return payableNow.value ? '待支付' : '待确认'
})

const lockHint = computed(() => {
    if (lockState.value === 'locked') {
        const mm = String(Math.floor(lockLeft.value / 60)).padStart(2, '0')
        const ss = String(lockLeft.value % 60).padStart(2, '0')
        return `档期已为你保留 ${mm}:${ss}，超时需重新选择`
    }
    if (lockState.value === 'error') return '档期在支付成功前不为你预留，建议尽快完成支付'
    if (lockState.value === 'failed') return '该档期刚被锁定，请换一个日期或主持人'
    return ''
})

function stopLockTimer() {
    if (lockTimer) clearInterval(lockTimer)
    lockTimer = null
}

async function doLock() {
    if (!staffId.value || !date.value) return
    if (lockState.value === 'locked' && lockLeft.value > 0) return
    lockState.value = 'locking'
    try {
        await lockSchedule({ staff_id: staffId.value, date: date.value, lock_duration: 900 })
        lockState.value = 'locked'
        lockLeft.value = 900
        stopLockTimer()
        lockTimer = setInterval(() => {
            lockLeft.value -= 1
            if (lockLeft.value <= 0) {
                stopLockTimer()
                lockState.value = 'failed'
            }
        }, 1000)
    } catch (e: any) {
        // 区分「业务失败（别人已锁）」与「接口异常」：前者必须拦，后者不能拦
        const msg = String(e?.message || '')
        const isBusiness = /锁定|已被|不可约|占用|已订/.test(msg) || Number(e?.code) === 0
        if (isBusiness) {
            lockState.value = 'failed'
            toast.info('该档期刚被锁定，请换一个日期或主持人')
            step.value = 2
            // 锁档失败回滚不是用户切步，但同样是 step 变更。doLock 是异步的，
            // 请求慢时用户可能已在 step 3 页面上滚动过，不能靠「来不及滚动」保证布局；
            // 与其它 3 处赋值保持一致，本文件所有 step 赋值点无例外地归零。
            resetScrollAfterStepChange()
        } else {
            lockState.value = 'error'
            report('api_error', {
                endpoint: 'schedule.lockSchedule',
                message: '档期锁定失败（降级放行）'
            })
        }
    }
}

const payDisabled = computed(
    () => submitting.value || paying.value || quoteState.value !== 'ready' || !payableNow.value
)

const payButtonText = computed(() => {
    if (submitting.value) return '提交中…'
    if (paying.value) return '支付中…'
    if (quoteState.value === 'loading') return '报价计算中…'
    if (barAmount.value === null) return '金额待确认'
    return '微信支付'
})

/* ---------------- 选择与校验 ---------------- */

/** 报价已失效：套餐/附加项/人员/日期任一变更后，旧 quote_hash 不能再用于下单 */
function invalidateQuote() {
    quoteHash.value = ''
    quote.value = {}
    validations.value = []
    quoteState.value = 'idle'
    payError.value = ''
}

function selectPackage(p: any) {
    const next = pkgId(p)
    if (Number(next) === Number(packageId.value)) return
    packageId.value = next
    invalidateQuote()
}

function toggleAddon(id: number) {
    const i = selectedAddons.value.indexOf(id)
    if (i >= 0) selectedAddons.value.splice(i, 1)
    else selectedAddons.value.push(id)
    lastPickedAddon.value = addons.value.find((a) => addonId(a) === id) || null
    invalidateQuote()
}

function addUpsell() {
    const a = upsellAddon.value
    if (!a) return
    if (!selectedAddons.value.includes(addonId(a))) selectedAddons.value.push(addonId(a))
    lastPickedAddon.value = a
    invalidateQuote()
}

function validateField(key: 'contact_name' | 'contact_mobile' | 'service_address') {
    if (key === 'contact_name') {
        const v = form.contact_name.trim()
        if (!v) errors.contact_name = '请输入联系人姓名'
        else if (v.length < 2) errors.contact_name = '姓名至少 2 个字'
        else delete errors.contact_name
    }
    if (key === 'contact_mobile') {
        const v = form.contact_mobile.trim()
        if (!v) errors.contact_mobile = '请输入手机号'
        else if (!/^1\d{10}$/.test(v)) errors.contact_mobile = '手机号格式不正确'
        else delete errors.contact_mobile
    }
    if (key === 'service_address') {
        const v = form.service_address.trim()
        if (!v) errors.service_address = '请输入服务地址'
        else delete errors.service_address
    }
}

function validate() {
    validateField('contact_name')
    validateField('contact_mobile')
    validateField('service_address')
    return Object.keys(errors).length === 0
}

function saveContact() {
    const ok = validate()
    if (!ok) {
        contactSheetVisible.value = true
        return
    }
    contactSheetVisible.value = false
}

function openContactSheet() {
    contactSheetVisible.value = true
}

/* ---------------- 步骤流转 ---------------- */

/**
 * 切步后把页面滚回顶部。step 一改，.bhero（v-if="step < 3"）会消失/出现、面板整块替换、
 * 导航占位块也会出现/消失，页面高度在同一帧内变化；等 DOM 更新完再滚比直接调用稳。
 * 这是顺带修的既有定位问题：之前在 step 1/2 滚到中段点「下一步」，新一屏会从中间开始显示。
 */
function resetScrollAfterStepChange() {
    nextTick(() => uni.pageScrollTo({ scrollTop: 0, duration: 0 }))
}

function nextStep() {
    if (step.value === 1) {
        if (!packageId.value) {
            toast.info('请选择套餐')
            return
        }
        step.value = 2
        resetScrollAfterStepChange()
        return
    }
    if (step.value === 2) {
        step.value = 3
        resetScrollAfterStepChange()
        if (quoteState.value !== 'ready') runPreview()
        doLock()
    }
}

function skipAddons() {
    selectedAddons.value = []
    invalidateQuote()
    nextStep()
}

function prevStep() {
    if (step.value > 1) {
        step.value -= 1
        resetScrollAfterStepChange()
    }
}

/* ---------------- 日期 ---------------- */

function openDatePicker() {
    pickerDate.value = date.value ? formatDate(date.value, 'YYYY-MM-DD') : ''
    datePickerVisible.value = true
}

function onDateChange(v: any) {
    const next = String(v || '')
    if (!next) return
    date.value = next
    invalidateQuote()
    datePickerVisible.value = false
    // 日期变了报价必须重算：已经到第 3 步就立刻重取，别让用户带着旧价提交
    if (step.value === 3) runPreview()
}

/* ---------------- 角色选人 ---------------- */

function roleSummary(roleKey: string) {
    const sel = roleSelected[roleKey]
    if (!sel) return '未选择，由平台安排'
    return `${sel.name || '已选人员'} · ¥${money(sel.price)}`
}

async function openRolePicker(role: any) {
    pickerRole.value = role
    pickerStage.staffId = 0
    pickerPackages.value = []
    pickerCandidates.value = []
    pickerVisible.value = true
    try {
        const list: any = await getBookingRoleCandidates({
            staff_id: staffId.value,
            role_key: role.role_key,
            date: date.value || undefined
        })
        pickerCandidates.value = Array.isArray(list) ? list : list?.lists || []
    } catch {
        pickerCandidates.value = []
    }
}

async function chooseCandidate(c: any) {
    pickerStage.staffId = Number(c.id || 0)
    pickerPackages.value = []
    try {
        const list: any = await getStaffPackages({ staff_id: pickerStage.staffId })
        pickerPackages.value = Array.isArray(list) ? list : list?.lists || []
    } catch {
        pickerPackages.value = []
    }
}

function confirmRole(p: any) {
    const role = pickerRole.value
    if (!role) return
    roleSelected[role.role_key] = {
        staff_id: pickerStage.staffId,
        package_id: pkgId(p),
        name:
            (pickerCandidates.value.find((c) => Number(c.id) === pickerStage.staffId) || {}).name ||
            '',
        price: Number(pkgPrice(p) || 0)
    }
    invalidateQuote()
    closePicker()
}

function closePicker() {
    pickerVisible.value = false
}

/* ---------------- 数据 ---------------- */

/** 组装后端要求的参数（字段名来自 BookingFlowService，与既有下单链路一致） */
function buildParams() {
    const params: Record<string, any> = {
        staff_id: staffId.value,
        package_id: packageId.value
    }
    if (date.value) params.date = date.value
    if (selectedAddons.value.length) params.addon_ids = [...selectedAddons.value]
    for (const key of Object.keys(roleSelected)) {
        const sel = roleSelected[key]
        if (!sel) continue
        params[`${key}_staff_id`] = sel.staff_id
        params[`${key}_package_id`] = sel.package_id
    }
    return params
}

async function load() {
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    loading.value = true
    error.value = false
    try {
        const detail: any = await getStaffDetail({
            id: staffId.value,
            date: date.value || undefined
        })
        if (!detail || !detail.id) throw new Error('empty')

        staffName.value = detail.name || ''
        staffAvatar.value = detail.avatar || ''
        staffCover.value = detail.cover || detail.avatar || ''
        city.value = detail.city || detail.city_name || ''
        packages.value = Array.isArray(detail.packages) ? detail.packages : []
        addons.value = Array.isArray(detail.addons) ? detail.addons : []
        roleConfigs.value = Array.isArray(detail.related_role_configs)
            ? detail.related_role_configs
            : []

        if (packages.value.length) {
            const preferred = packages.value.find((p) => pkgId(p) === Number(packageId.value))
            packageId.value = preferred ? packageId.value : pkgId(packages.value[0])
        }
        // 附加项默认不选（避免用户在不知情的情况下多付钱；设计稿「默认勾前二」不采用，见交付说明）
        selectedAddons.value = []
        lastPickedAddon.value = null
    } catch (e) {
        rawError.value = e
        error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '预约配置加载失败' })
    } finally {
        loading.value = false
    }
}

async function runPreview() {
    quoteState.value = 'loading'
    payError.value = ''
    try {
        // 优先 v2：一次拿到报价 + 结构化校验 + 报价指纹（路径已修正为 /v2/bookingPreview）
        try {
            const v2: any = await previewBooking(buildParams())
            applyPreview(v2)
            return
        } catch {
            // v2 未上线：降级 v1
        }
        const legacy: any = await previewOrder(buildParams())
        applyPreview(legacy)
    } catch (e: any) {
        quoteState.value = 'failed'
        quote.value = {}
        quoteHash.value = ''
        validations.value = []
        report('api_error', { endpoint: 'order.preview', message: e?.message || '报价获取失败' })
    }
}

function applyPreview(res: any) {
    quote.value = res?.quote || res || {}
    quoteHash.value = String(res?.quote_hash || '')
    validations.value = Array.isArray(res?.validations) ? res.validations : []
    const contact = res?.contact_default
    if (contact) {
        if (!form.contact_name) form.contact_name = contact.name || ''
        if (!form.contact_mobile) form.contact_mobile = contact.mobile || ''
    }
    // 没有指纹等于报价没算出来，不能当成功
    quoteState.value = quoteHash.value ? 'ready' : 'failed'
}

/* ---------------- 提交与支付 ---------------- */

async function handleSubmit() {
    if (submitting.value || paying.value) return

    // ① 协议：未勾不置灰按钮，点按就地出红字
    if (!agreed.value) {
        agreeError.value = '请先勾选《预约服务协议》再支付'
        return
    }
    agreeError.value = ''

    // ② 表单
    if (!validate()) {
        contactSheetVisible.value = true
        toast.info('请完善联系人信息')
        return
    }

    // ②' 锁档拦截：锁失败（别人先锁）必须拦，锁接口异常放行（网络问题拦了就是白丢单）
    if (lockState.value === 'failed') {
        payError.value = '该档期刚被锁定，请返回上一步重新选择日期'
        return
    }

    // ③ 报价与金额断言
    if (quoteState.value !== 'ready' || !quoteHash.value) {
        runPreview()
        toast.info('正在重新获取报价，请稍候')
        return
    }
    if (amountMismatch.value) {
        payError.value = '报价金额不一致，已阻断支付，请重新获取报价'
        return
    }
    if (barAmount.value === null) {
        payError.value = '应付金额缺失，暂不能支付'
        return
    }

    submitting.value = true
    try {
        const params: Record<string, any> = {
            ...buildParams(),
            contact_name: form.contact_name.trim(),
            contact_mobile: form.contact_mobile.trim(),
            service_address: form.service_address.trim(),
            quote_hash: quoteHash.value
        }
        if (form.remark.trim()) params.remark = form.remark.trim()

        // 锁是 Redis TTL，停留超过 15 分钟会自然失效 → 下单前再校验一次（降低概率，不消除）
        try {
            const av: any = await checkAvailable({ staff_id: staffId.value, date: date.value })
            if (av && av.is_available === false) {
                payError.value = av.message || '该档期刚被订走，请返回上一步换一个日期'
                lockState.value = 'failed'
                return
            }
        } catch {
            // 校验失败不阻断主流程
        }

        const res: any = await createOrder(params as any)
        const id = Number(res?.order_id || res?.id || 0)
        if (!id) throw new Error('下单失败：未返回订单号')
        orderId.value = id

        // 后端时序：create → status=0 待确认 → 确认后才可 prepay。能不能现在付只看服务端字段。
        const stage = String(res?.current_pay_stage || '')
        const needPay = String(res?.need_pay || '')
        const payable = stage ? ['deposit', 'full', 'balance'].includes(stage) : needPay !== 'none'
        if (!payable) {
            toast.info('订单已提交，待确认后可支付')
            uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${id}` })
            return
        }
        await payOrder(id)
    } catch (e: any) {
        payError.value = e?.message || '下单失败，请稍后重试'
        report('api_error', { endpoint: 'order.create', message: payError.value })
    } finally {
        submitting.value = false
    }
}

async function payOrder(id: number) {
    paying.value = true
    payError.value = ''
    try {
        // 支付参数按契约修正后的签名：{ from: 'order', order_id, pay_way }
        const pre = await prepay({ from: 'order', order_id: id, pay_way: 2 })
        if (!pre || !pre.config) throw new Error('预支付参数缺失')

        const result = await requestPayment(pre.config)
        if (result === 'cancel') {
            payError.value = '支付未完成，可重新支付'
            return
        }
        if (result === 'fail') {
            payError.value = '支付未完成，请重新支付'
            return
        }

        // 结果判定交给 09 统一轮询，避免两处各写一套
        const amount = Number(barAmount.value ?? 0) || ''
        uni.redirectTo({
            url:
                `/packages/pkg-order/pages/payment-result/payment-result?order_id=${id}` +
                `&payment_sn=${encodeURIComponent(pre.payment_sn || '')}&amount=${amount}`
        })
    } catch (e: any) {
        payError.value = e?.message || '支付发起失败，请稍后重试'
        report('api_error', { endpoint: 'pay.prepay', message: payError.value })
    } finally {
        paying.value = false
    }
}

function goOrderDetail() {
    if (!orderId.value) {
        uni.redirectTo({ url: '/pages/order/order' })
        return
    }
    uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${orderId.value}` })
}

onUnload(() => {
    stopLockTimer()
    // 未支付就离开：主动释放，否则这 15 分钟该档期对别人不可约
    if (lockState.value === 'locked' && !orderId.value && staffId.value && date.value) {
        releaseLock({ staff_id: staffId.value, date: date.value }).catch(() => undefined)
    }
})

onLoad((options: any) => {
    staffId.value = Number(options?.staff_id || 0)
    packageId.value = Number(options?.package_id || 0)
    date.value = options?.date ? String(options.date) : ''
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.sk {
    &__hero {
        height: 954rpx;
        padding: var(--sp-4);
        background: var(--c-hero-bg);
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    &__body {
        padding: var(--sp-4);
    }
}

/* ========== Bk Hero 496px → 954rpx ========== */
.bhero {
    position: relative;
    height: 954rpx;
    overflow: hidden;
    background: var(--c-ink);

    &__img {
        width: 100%;
        height: 100%;
    }

    &__blank {
        width: 100%;
        height: 100%;
        background: var(--c-hero-bg);
    }

    &__scrim {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(14, 12, 10, 0.56) 0%,
            rgba(14, 12, 10, 0.04) 30%,
            rgba(14, 12, 10, 0.34) 60%,
            rgba(14, 12, 10, 0.94) 100%
        );
    }

    &__date {
        position: absolute;
        left: 42rpx;
        top: 184rpx;
        z-index: 3;
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        height: 58rpx;
        padding: 0 21rpx;
        border-radius: var(--r-pill);
        background: rgba(255, 255, 255, 0.16);
        border: 1rpx solid rgba(255, 255, 255, 0.26);
        color: var(--c-on-dark);
    }

    &__date-text {
        font-size: 20rpx;
        letter-spacing: 1rpx;
    }

    &__info {
        position: absolute;
        left: 42rpx;
        right: 42rpx;
        bottom: 42rpx;
        z-index: 2;
        color: var(--c-on-dark);
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        text-transform: uppercase;
        color: var(--c-brand-soft);
    }

    &__name {
        display: block;
        margin-top: 17rpx;
        font-size: var(--fs-44);
        font-weight: var(--fw-semibold);
        letter-spacing: 2rpx;
        line-height: var(--lh-tight);
    }

    &__desc {
        display: block;
        margin-top: 16rpx;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
        max-width: 540rpx;
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 10rpx;
        margin-top: 21rpx;
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-on-dark-2);
    }
}

/* 步骤条外层：组件自身不带外边距（只管自己那块），上边距由页面给。
   用 --sp-4(32rpx) 而不是旧内联写法那个取整值 —— 差 1rpx 肉眼不可见，且裸间距会被 R8 拦。 */
.steps-wrap {
    margin-top: var(--sp-4);
}

/* ========== 面板 ========== */
.panel {
    padding: 27rpx 42rpx 0;
}

.optrow {
    display: flex;
    gap: 16rpx;
}

.optcard {
    flex: 1;
    min-width: 0;
    position: relative;
    border-radius: var(--r-sm);
    overflow: hidden;
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);

    &.is-on {
        /* 批 1.5 ⑤：选中描边 --c-brand(#c6a15b) 对 --c-surface 仅 2.39:1（<3:1），改深金 5.26:1 */
        border-color: var(--c-brand-text);

        .optcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__tick {
        position: absolute;
        right: 12rpx;
        top: 12rpx;
        z-index: 2;
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        color: var(--c-on-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.7);
        transition: opacity var(--dur-fast) var(--ease-out),
            transform var(--dur-fast) var(--ease-out);
    }

    &__img {
        width: 100%;
        height: 127rpx;
    }

    &__blank {
        width: 100%;
        height: 127rpx;
        background: var(--c-hero-bg);
    }

    &__body {
        padding: 15rpx 17rpx 17rpx;
    }

    &__name {
        display: block;
        font-size: 20rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 1rpx;
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: 4rpx;
        margin-top: var(--sp-1);
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.bkhint {
    display: block;
    margin-top: 23rpx;
    font-size: var(--fs-18);
    line-height: var(--lh-loose);
    color: var(--c-ink-3);
}

.bkline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 25rpx;
    font-size: 20rpx;
    color: var(--c-ink-3);

    &__sum {
        flex: 1;
        min-width: 0;
    }

    &__strong {
        color: var(--c-ink-2);
    }

    &__skip {
        display: inline-flex;
        align-items: center;
        gap: 2rpx;
        flex: none;
        /* 视觉小字 + 88rpx 热区 */
        padding: 22rpx 0;
        margin: -22rpx 0;
    }

    &__skip-text {
        font-size: 20rpx;
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ========== 08 订单卡 ========== */
.ord {
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-md);
    padding: 25rpx 29rpx;

    &__top {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
        padding-bottom: 25rpx;
        border-bottom: 1rpx solid var(--c-line);
    }

    &__date {
        flex: none;
        text-align: center;
        padding-right: var(--sp-3);
        border-right: 1rpx solid var(--c-line-strong);
    }

    &__date-d {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-37);
        font-weight: var(--fw-medium);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__date-w {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__meta {
        flex: 1;
        min-width: 0;
    }

    &__meta-b {
        display: block;
        font-size: 24rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta-p {
        display: block;
        margin-top: var(--sp-1);
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__badge {
        flex: none;
        height: 34rpx;
        padding: 0 12rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-warning-border);
        background: var(--c-warning-bg);
        color: var(--c-warning);
        font-size: var(--fs-18);
        line-height: 32rpx;
    }

    /* 锁档提示行：锁住=香槟，异常/失败=琥珀 */
    &__lock {
        display: flex;
        align-items: center;
        gap: 8rpx;
        margin-top: 18rpx;
        padding: 12rpx 16rpx;
        border-radius: var(--r-sm);
        background: var(--c-brand-bg);
    }

    &__lock-text {
        font-size: var(--fs-20);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
    }

    &__host {
        display: flex;
        align-items: center;
        gap: 18rpx;
        padding-top: 25rpx;
    }

    &__host-avatar {
        width: 58rpx;
        height: 58rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
        flex: none;
    }

    &__host-name {
        font-size: 24rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 200rpx;
    }

    &__pkg {
        margin-left: auto;
        font-size: 20rpx;
        color: var(--c-ink-3);
        max-width: 300rpx;
    }

    &__roles {
        margin-top: var(--sp-2);
        padding-top: var(--sp-2);
        border-top: 1rpx dashed var(--c-line-strong);
    }

    &__role {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        padding: var(--sp-1) 0;
    }

    &__role-k {
        flex: none;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__role-v {
        flex: 1;
        min-width: 0;
        font-size: 20rpx;
        color: var(--c-ink-2);
    }

    &__role-link {
        flex: none;
        font-size: 20rpx;
        color: var(--c-brand-text);
        text-decoration: underline;
        padding: 16rpx 0;
        margin: -16rpx 0;
    }

    &__addon {
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx dashed var(--c-line-strong);
    }

    &__addon-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__addon-amount {
        font-family: var(--font-serif);
        color: var(--c-ink-2);
    }

    &__addon-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 17rpx;
        font-size: var(--fs-xs);
    }

    &__addon-name {
        flex: 1;
        min-width: 0;
        color: var(--c-ink-2);
    }

    &__addon-price {
        flex: none;
        font-family: var(--font-serif);
        color: var(--c-ink);
    }

    &__contact {
        margin-top: var(--sp-3);
        padding-top: var(--sp-3);
        border-top: 1rpx dashed var(--c-line-strong);
    }

    &__contact-title {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
        padding: var(--sp-1) 0;
    }

    &__row-k {
        flex: none;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__row-v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink);
        text-align: right;
    }

    &__row-link {
        flex: none;
        font-size: 20rpx;
        color: var(--c-ink-3);
        text-decoration: underline;
        padding: 16rpx 0;
        margin: -16rpx 0;
    }
}

.upsell {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-top: 17rpx;
    padding-top: 17rpx;
    border-top: 1rpx dashed var(--c-line-strong);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }

    &__btn {
        flex: none;
        height: 42rpx;
        padding: 0 17rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        display: inline-flex;
        align-items: center;
    }

    &__btn-text {
        font-size: var(--fs-18);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }
}

/* ========== 支付安排卡 ========== */
.card2 {
    margin-top: 21rpx;
    padding: 27rpx 29rpx;
    background: var(--c-surface);
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-md);

    &__title {
        display: block;
        font-size: 24rpx;
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.sched {
    display: flex;
    align-items: flex-start;
    position: relative;
    margin-top: 14rpx;

    &::before {
        content: '';
        position: absolute;
        left: 8rpx;
        right: 50%;
        top: 15rpx;
        height: 1rpx;
        background: var(--c-line-strong);
    }

    &__step {
        flex: 1;
        position: relative;
        /* 31rpx = 圆点 top 8 + 圆点高 18 + 净空 5；改 ::before 的尺寸时要同步改这里。
           这是几何推导值，不是间距节奏，所以不令牌化 —— 用 --sp-4(32rpx) 会藏掉推导关系，
           且圆点高度一改就压到文字上。 */
        padding-top: 31rpx;

        &::before {
            content: '';
            position: absolute;
            top: 8rpx;
            left: 0;
            width: 18rpx;
            height: 18rpx;
            border-radius: var(--r-pill);
            border: 2rpx solid var(--c-line-strong);
            background: var(--c-surface);
            box-sizing: border-box;
        }

        &.is-now::before {
            border-color: var(--c-ink);
            background: var(--c-ink);
        }
    }

    &__b {
        display: block;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__s {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__e {
        display: block;
        margin-top: 10rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-25);
        color: var(--c-ink);
    }
}

.amounts {
    margin-top: 21rpx;
    padding-top: var(--sp-1);
    border-top: 1rpx solid var(--c-line);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10rpx 0;
    }

    &__k {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);

        &--total {
            font-size: 24rpx;
            color: var(--c-ink);
        }
    }

    &__v {
        font-family: var(--font-serif);
        font-size: var(--fs-25);
        color: var(--c-ink);
    }

    &__row--total {
        margin-top: 12rpx;
        padding-top: 17rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &__total {
        font-family: var(--font-serif);
        font-size: var(--fs-35);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }
}

/* ========== 提示块 ========== */
.block {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: 21rpx;
    padding: 21rpx 23rpx;
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &--warn,
    &--err {
        background: var(--c-warning-bg);
        border-color: var(--c-warning-border);
    }

    &__icon {
        flex: none;
        color: var(--c-warning);
        margin-top: 2rpx;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-warning);
    }

    &__row {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-2);
        width: 100%;
    }

    &__btn {
        flex: none;
        padding: 8rpx 18rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-warning);
    }

    &__btn-text {
        font-size: 20rpx;
        color: var(--c-warning);
    }
}

.agree-wrap {
    margin-top: 21rpx;
    padding-bottom: var(--sp-2);
}

.qfail {
    padding-top: var(--sp-4);

    &__back {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 88rpx;
    }

    &__back-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ========== 吸底栏 ========== */
.abp {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-18);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
    }

    &__na {
        font-size: 24rpx;
        color: var(--c-ink-3);
    }
}

.abtns {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

/* ========== 弹层 ========== */
.pop {
    background: var(--c-surface);
    border-radius: var(--r-md) var(--r-md) 0 0;
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 104rpx;
        padding: 0 var(--sp-2) 0 var(--sp-4);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        color: var(--c-ink-3);
    }

    &__body {
        padding: var(--sp-4);
    }

    &__foot {
        padding: 0 var(--sp-4) var(--sp-4);
    }

    &__empty {
        padding: var(--sp-6) 0;
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__back {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        height: 80rpx;
        color: var(--c-ink-2);
    }

    &__back-text {
        font-size: var(--fs-xs);
    }
}

.cand {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 104rpx;
    padding: var(--sp-2) 0;
    border-bottom: 1rpx solid var(--c-line);
    color: var(--c-ink-3);

    &:last-child {
        border-bottom: 0;
    }

    &__avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__name {
        font-size: 24rpx;
        color: var(--c-ink);
    }

    &__meta {
        font-size: 20rpx;
        color: var(--c-ink-3);
    }
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-lock {
    color: var(--c-warning);
}
.ic-lock.is-on {
    color: var(--c-brand-text);
}
</style>
