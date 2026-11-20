<template>
    <!-- 透明导航只在「顶部真有图承接」时成立：
         步骤页有 .bhero 大图（配 0.56 深色 scrim），浅色返回键才看得清；
         loading 的 .sk__hero 是米色块、无图时是 .bhero__blank，白图标压上去会糊成一片，
         所以 navLight 额外要求 heroImage 已就位（见 script 里的 computed）。
         error 态是整页空态、没有 hero，连透明都不成立：nav-variant 一并切实底，
         导航底色沿用全站默认 --c-surface，与其它 solid 页一致。 -->
    <page-shell
        bg="page"
        :bottom-bar="true"
        :title="isConfirm ? '确认订单' : ''"
        :nav-variant="!isConfirm && !error ? 'transparent' : 'solid'"
        :nav-tone="navLight ? 'light' : 'dark'"
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
            <!-- 步骤页统一头图：放大到 860rpx，只保留「日期/地区入口 + 标题 + 价格」三层信息 -->
            <view v-if="!isConfirm" class="bhero">
                <image
                    v-if="heroImage"
                    class="bhero__img"
                    :src="imageUrl(heroImage)"
                    mode="aspectFill"
                />
                <view v-else class="bhero__blank" />
                <view class="bhero__scrim" aria-hidden="true" />

                <!-- 日期 + 地区胶囊：同一点击入口，弹层里一次选完 -->
                <view
                    class="bhero__date"
                    :class="{ 'is-empty': !date || !regionReady }"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="选择服务日期与地区"
                    @tap="openDatePicker"
                >
                    <tn-icon name="calendar" :size="24" />
                    <text class="bhero__date-text">{{ dateCapsuleText }}</text>
                    <tn-icon name="down" :size="20" />
                </view>

                <view class="bhero__info">
                    <text class="bhero__eyebrow">{{ heroEyebrow }}</text>
                    <text class="bhero__name ellipsis">{{ heroTitle }}</text>
                    <view v-if="heroPrice != null" class="bhero__price">
                        <price size="lg" :content="heroPrice" color="var(--c-brand-soft)" />
                        <text class="bhero__unit">{{ heroUnit }}</text>
                    </view>
                </view>
            </view>

            <!-- 步骤条：2–4 段由后台「预约关联」开关决定，确认页不显示 -->
            <view v-if="!isConfirm" class="steps-wrap">
                <steps :steps="flowLabels" :current="step - 1" />
            </view>

            <!-- 日期/地区缺失：每一步都顶一条，点了直接开弹层，不允许带着空条件往下走 -->
            <view
                v-if="!isConfirm && (!date || !regionReady)"
                class="needbar"
                hover-class="is-pressed"
                aria-role="button"
                @tap="openDatePicker"
            >
                <tn-icon name="warning" :size="26" class="needbar__icon" />
                <text class="needbar__text">{{ needBarText }}</text>
                <text class="needbar__action">去选择</text>
            </view>

            <!-- ============ 06 选择套餐 ============ -->
            <view v-if="step === 1" class="panel">
                <view class="stephead">
                    <text class="stephead__title">选择服务套餐</text>
                    <text class="stephead__hint">左右滑动查看，价格按所选地区计算</text>
                </view>

                <scroll-view v-if="packages.length" scroll-x class="opts" :show-scrollbar="false">
                    <view class="opts__track">
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
                            <view class="optcard__head">
                                <text class="optcard__name ellipsis-2">{{ pkgName(p) }}</text>
                                <view class="optcard__tick" aria-hidden="true">
                                    <tn-icon name="check" :size="18" />
                                </view>
                            </view>
                            <view class="optcard__price">
                                <price size="sm" :content="pkgPrice(p)" />
                                <text class="optcard__unit">/ 场</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
                <base-empty
                    v-else
                    status="empty"
                    empty-text="这档服务暂时不可选"
                    desc="换一位主持人，或过两天再来"
                    action-text="返回详情"
                    show-action
                    @action="onBack"
                />

                <view v-if="packageRules" class="bkline">
                    <text class="bkline__sum">{{ packageRules }}</text>
                </view>
            </view>

            <!-- ============ 07 选择附加项 ============ -->
            <view v-else-if="step === 2" class="panel">
                <view class="stephead">
                    <text class="stephead__title">选择附加项</text>
                    <text class="stephead__hint">可多选，左右滑动查看；不需要可直接跳过</text>
                </view>

                <template v-if="addons.length">
                    <scroll-view scroll-x class="opts" :show-scrollbar="false">
                        <view class="opts__track">
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
                                <view class="optcard__head">
                                    <text class="optcard__name ellipsis-2">{{ a.name }}</text>
                                    <view class="optcard__tick" aria-hidden="true">
                                        <tn-icon name="check" :size="18" />
                                    </view>
                                </view>
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
                    </scroll-view>

                    <view class="bkline">
                        <text class="bkline__sum">
                            已选
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
                    desc="直接进入下一步即可"
                    :action-text="nextButtonText"
                    show-action
                    @action="nextStep"
                />
            </view>

            <!-- ============ 可选服务：婚礼管家 / 婚礼督导（由分类「预约关联」开关决定是否出现） ============ -->
            <view v-else-if="activeRole" class="panel">
                <view class="stephead">
                    <text class="stephead__title">是否需要{{ activeRole.role_label }}？</text>
                    <text class="stephead__hint">不同人员价格不同，可进详情对比后再选</text>
                </view>

                <view class="rolechoice">
                    <view
                        class="rolechoice__card"
                        :class="{ 'is-on': !activeRoleNeeded }"
                        hover-class="is-pressed"
                        aria-role="radio"
                        :aria-checked="!activeRoleNeeded"
                        @tap="setActiveRoleNeeded(false)"
                    >
                        <text class="rolechoice__title">不需要</text>
                        <text class="rolechoice__desc">不增加该项服务</text>
                        <view class="rolechoice__tick" aria-hidden="true">
                            <tn-icon name="check" :size="18" />
                        </view>
                    </view>
                    <view
                        class="rolechoice__card"
                        :class="{
                            'is-on': activeRoleNeeded,
                            'is-disabled': activeRole.schedule_available === false
                        }"
                        hover-class="is-pressed"
                        aria-role="radio"
                        :aria-checked="activeRoleNeeded"
                        @tap="setActiveRoleNeeded(true)"
                    >
                        <text class="rolechoice__title">需要</text>
                        <text class="rolechoice__desc">{{ activeRolePriceText }}</text>
                        <view class="rolechoice__tick" aria-hidden="true">
                            <tn-icon name="check" :size="18" />
                        </view>
                    </view>
                </view>

                <!-- 人员列表：横向滑动，卡片可直接选中，「详情」进人员主页对比价格与资料 -->
                <view v-if="activeRoleNeeded" class="roster">
                    <view class="roster__head">
                        <text class="roster__title">选择{{ activeRole.role_label }}</text>
                        <text v-if="activeRoleCandidates.length" class="roster__count">
                            {{ activeRoleCandidates.length }} 位可选
                        </text>
                    </view>

                    <scroll-view
                        v-if="activeRoleCandidates.length"
                        scroll-x
                        class="opts"
                        :show-scrollbar="false"
                    >
                        <view class="opts__track">
                            <view
                                v-for="c in activeRoleCandidates"
                                :key="c.staff_id || c.id"
                                class="pcard"
                                :class="{ 'is-on': isRolePicked(activeRole.role_key, c) }"
                                hover-class="is-pressed"
                                aria-role="radio"
                                :aria-checked="isRolePicked(activeRole.role_key, c)"
                                @tap="pickRoleCandidate(c)"
                            >
                                <view class="pcard__top">
                                    <image
                                        v-if="c.avatar"
                                        class="pcard__avatar"
                                        :src="imageUrl(c.avatar)"
                                        mode="aspectFill"
                                    />
                                    <view v-else class="pcard__avatar pcard__avatar--blank">
                                        <text class="pcard__avatar-text">{{
                                            String(c.name || '').slice(0, 1)
                                        }}</text>
                                    </view>
                                    <view class="pcard__body">
                                        <text class="pcard__name ellipsis">{{ c.name }}</text>
                                        <text v-if="candidateMeta(c)" class="pcard__meta ellipsis">
                                            {{ candidateMeta(c) }}
                                        </text>
                                    </view>
                                    <view class="pcard__tick" aria-hidden="true">
                                        <tn-icon name="check" :size="18" />
                                    </view>
                                </view>
                                <view class="pcard__foot">
                                    <price size="sm" :content="c.price" />
                                    <text class="pcard__detail" @tap.stop="openRolePersonDetail(c)"
                                        >详情</text
                                    >
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <view v-else-if="activeRoleLoading" class="inline-note">
                        <text class="inline-note__text">正在加载可安排人员…</text>
                    </view>
                    <view v-else class="inline-note">
                        <text class="inline-note__text"
                            >当前日期暂无可安排人员，可换个日期或选择「不需要」</text
                        >
                    </view>
                </view>

                <view class="bkline">
                    <text class="bkline__sum">{{ activeRoleFootText }}</text>
                </view>
            </view>

            <!-- ============ 08 订单确认 ============ -->
            <!--
                确认页是「一份能逐项核对的确认单」，不是收银台：
                · 墨色摘要卡负责 3 秒确认「哪天 / 什么套餐 / 跟谁 / 在哪」；
                · 01–06 六个块各回答一个问题，每块可单独回改；
                · 报价失败只坏 05/06，摘要与已选信息始终可读；
                · 金额只读服务端 quote，前端不做加减。
            -->
            <view v-else class="panel cf">
                <!-- 00 服务摘要 -->
                <view class="cf-sum">
                    <view class="cf-sum__top">
                        <view class="cf-sum__datewrap">
                            <text class="cf-sum__eyebrow">ORDER REVIEW</text>
                            <text class="cf-sum__date">{{ dateDay }}</text>
                            <text class="cf-sum__week">{{ dateYearWeek }}</text>
                        </view>
                        <status-tag :label="orderBadge.label" :tone="orderBadge.tone" />
                    </view>
                    <text class="cf-sum__name ellipsis-2">{{
                        currentPackageName || '服务套餐'
                    }}</text>
                    <view class="cf-sum__meta">
                        <text class="cf-sum__host ellipsis">{{
                            staffName || '服务人员待安排'
                        }}</text>
                        <text class="cf-sum__dot" aria-hidden="true">·</text>
                        <text class="cf-sum__city ellipsis">{{ cityVenueText }}</text>
                    </view>
                    <view class="cf-sum__lock" :class="`is-${lockState}`">
                        <tn-icon
                            :name="lockState === 'failed' ? 'warning' : 'clock'"
                            :size="22"
                            class="cf-sum__lock-ic"
                        />
                        <text class="cf-sum__lock-text">{{ lockSummaryText }}</text>
                        <text
                            v-if="!orderId"
                            class="cf-sum__edit"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="修改服务日期"
                            @tap="openDatePicker"
                            >{{ lockState === 'failed' ? '换日期' : '改期' }}</text
                        >
                    </view>
                </view>

                <!-- 订单已提交但服务端判定不可支付：页面进入「待确认」只读态 -->
                <view v-if="orderPendingConfirm" class="cf-alert cf-alert--warning">
                    <tn-icon name="clock" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text class="cf-alert__title">订单已提交，等待商家确认</text>
                        <text class="cf-alert__text"
                            >当前无法支付，订单状态为「待确认」。商家确认后即可继续支付，通常 1
                            小时内完成。</text
                        >
                    </view>
                    <view class="cf-alert__btn" hover-class="is-pressed" @tap="goOrderDetail">
                        <text class="cf-alert__btn-text">查看订单</text>
                    </view>
                </view>

                <!-- 锁档失败：不踢回上一步，留在本页给「换日期」 -->
                <view v-else-if="lockState === 'failed'" class="cf-alert cf-alert--danger">
                    <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text class="cf-alert__title">该档期刚被订走</text>
                        <text class="cf-alert__text"
                            >支付成功前档期不会为你预留，请换一个日期或主持人。</text
                        >
                    </view>
                    <view class="cf-alert__btn" hover-class="is-pressed" @tap="openDatePicker">
                        <text class="cf-alert__btn-text">换日期</text>
                    </view>
                </view>

                <!-- 锁档超时：可原地重锁，和「被别人订走」不是同一件事 -->
                <view v-else-if="lockState === 'expired'" class="cf-alert cf-alert--warning">
                    <tn-icon name="clock" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text class="cf-alert__title">档期保留已超时</text>
                        <text class="cf-alert__text"
                            >可以重新锁定后继续支付；若已被他人预订，请换日期。</text
                        >
                    </view>
                    <view class="cf-alert__btn" hover-class="is-pressed" @tap="relockSchedule">
                        <text class="cf-alert__btn-text">重新锁档</text>
                    </view>
                </view>

                <!-- 01 套餐与服务 -->
                <confirm-section
                    index="01"
                    title="套餐与服务"
                    :action="orderId ? '' : '修改'"
                    @action="editStep(1)"
                >
                    <view class="cf-row">
                        <text class="cf-row__k">套餐</text>
                        <text class="cf-row__v ellipsis">{{ currentPackageName || '服务套餐' }}</text>
                        <text v-if="packagePriceText" class="cf-row__m">{{ packagePriceText }}</text>
                    </view>
                    <view v-if="packageDurationText" class="cf-row">
                        <text class="cf-row__k">服务时长</text>
                        <text class="cf-row__v">{{ packageDurationText }}</text>
                    </view>
                    <view class="cf-divider" aria-hidden="true" />
                    <view class="cf-staff">
                        <image
                            v-if="staffAvatar"
                            class="cf-staff__avatar"
                            :src="imageUrl(staffAvatar)"
                            mode="aspectFill"
                        />
                        <view v-else class="cf-staff__avatar cf-staff__avatar--blank" />
                        <view class="cf-staff__body">
                            <text class="cf-staff__name ellipsis">{{
                                staffName || '平台安排'
                            }}</text>
                            <text v-if="staffMetaText" class="cf-staff__meta ellipsis">{{
                                staffMetaText
                            }}</text>
                        </view>
                    </view>
                    <view v-for="r in quoteRoleRows" :key="r.role_key" class="cf-row">
                        <text class="cf-row__k">{{ r.role_label }}</text>
                        <text class="cf-row__v ellipsis">{{ r.text }}</text>
                    </view>
                    <text v-if="packageRules" class="cf-note">{{ packageRules }}</text>
                </confirm-section>

                <!-- 02 附加项 -->
                <confirm-section
                    index="02"
                    :title="addonTitle"
                    :action="orderId ? '' : selectedAddonList.length ? '修改' : '去挑选'"
                    @action="editStep(2)"
                >
                    <template v-if="selectedAddonList.length">
                        <view v-for="a in selectedAddonList" :key="addonId(a)" class="cf-line">
                            <text class="cf-line__n ellipsis">{{ a.name }}</text>
                            <text class="cf-line__m">+¥{{ money(addonPrice(a)) }}</text>
                        </view>
                        <view v-if="selectedAddonList.length > 1" class="cf-line cf-line--sub">
                            <text class="cf-line__n">小计</text>
                            <text class="cf-line__m">{{ addonSumText }}</text>
                        </view>
                    </template>
                    <text v-else class="cf-empty-line">未加购附加项，可直接提交</text>
                    <!-- 加购推荐：只给「还能加什么 + 多少钱」，不编百分比 -->
                    <view v-if="upsellAddon && !orderId" class="cf-upsell">
                        <text class="cf-upsell__t ellipsis"
                            >还可以加「{{ upsellAddon.name }}」</text
                        >
                        <view class="cf-upsell__btn" hover-class="is-pressed" @tap="addUpsell">
                            <text class="cf-upsell__btn-text"
                                >+¥{{ money(addonPrice(upsellAddon)) }} 加上</text
                            >
                        </view>
                    </view>
                </confirm-section>

                <!-- 03 联系人 -->
                <confirm-section
                    id="cf-contact"
                    index="03"
                    title="联系人"
                    :action="orderId ? '' : '修改'"
                    :flash="flashTarget === 'contact'"
                    @action="openContactSheet"
                >
                    <text class="cf-value" :class="{ 'is-missing': !contactLine }">{{
                        contactLine || '未填写联系人'
                    }}</text>
                    <text class="cf-note">用于档期确认与到场联系。</text>
                    <text v-if="contactBlockError" class="cf-block-err" aria-live="polite">{{
                        contactBlockError
                    }}</text>
                </confirm-section>

                <!-- 04 服务地址 -->
                <confirm-section
                    id="cf-address"
                    index="04"
                    title="服务地址"
                    :action="orderId ? '' : '修改'"
                    :flash="flashTarget === 'address'"
                    @action="openAddressSheet"
                >
                    <view class="cf-region">
                        <text class="cf-region__text ellipsis">{{
                            serviceRegionText || '未选择服务地区'
                        }}</text>
                        <text class="cf-region__badge">定价地区</text>
                    </view>
                    <text
                        class="cf-value cf-value--addr ellipsis-2"
                        :class="{ 'is-missing': !form.service_address }"
                        >{{ form.service_address || '未填写详细地址' }}</text
                    >
                    <text class="cf-note">地区决定套餐价格；详细地址用于服务人员到场。</text>
                    <text v-if="addressBlockError" class="cf-block-err" aria-live="polite">{{
                        addressBlockError
                    }}</text>
                </confirm-section>

                <!-- 05 价格明细 -->
                <confirm-section index="05" title="价格明细">
                    <view v-if="quoteState === 'failed'" class="cf-alert cf-alert--danger">
                        <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                        <view class="cf-alert__body">
                            <text class="cf-alert__title">报价没有算出来</text>
                            <text class="cf-alert__text"
                                >价格必须以服务端报价为准，请重新获取。你已选的套餐与附加项不会丢失。</text
                            >
                        </view>
                    </view>
                    <template v-else-if="quoteState === 'ready'">
                        <view class="cf-line">
                            <text class="cf-line__n">套餐金额</text>
                            <text class="cf-line__m">{{ quoteText('service_amount') }}</text>
                        </view>
                        <view class="cf-line">
                            <text class="cf-line__n"
                                >附加项（{{ selectedAddonList.length }} 项）</text
                            >
                            <text class="cf-line__m">¥{{ money(addonOnlyAmount) }}</text>
                        </view>
                        <view v-if="rolePriceSum > 0" class="cf-line">
                            <text class="cf-line__n">管家 / 督导</text>
                            <text class="cf-line__m">¥{{ money(rolePriceSum) }}</text>
                        </view>
                        <view class="cf-total">
                            <text class="cf-total__k">合计</text>
                            <text class="cf-total__m">{{ quoteText('total_amount') }}</text>
                        </view>
                        <text class="cf-note">价格由系统按服务日期与地区计算，以提交时为准。</text>
                    </template>
                    <view v-else class="cf-sk" aria-label="正在核价">
                        <view class="cf-sk__row" />
                        <view class="cf-sk__row" />
                        <view class="cf-sk__row cf-sk__row--total" />
                        <text class="cf-note">正在核价…</text>
                    </view>

                    <view v-if="amountMismatch" class="cf-alert cf-alert--danger">
                        <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                        <view class="cf-alert__body">
                            <text class="cf-alert__title">报价金额不一致</text>
                            <text class="cf-alert__text"
                                >定金 + 尾款 ≠ 总额，已为你阻断支付，请重新获取报价。</text
                            >
                        </view>
                    </view>
                    <view v-if="quoteState === 'failed' || amountMismatch" class="cf-foot">
                        <base-button
                            text="重新获取报价"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            @click="runPreview"
                        />
                    </view>
                </confirm-section>

                <!-- 06 支付安排：只回答「什么时候付多少」，不重复明细 -->
                <confirm-section
                    v-if="quoteState !== 'failed'"
                    index="06"
                    title="支付安排"
                >
                    <view v-if="quoteState !== 'ready'" class="cf-sk">
                        <view class="cf-sk__row" />
                        <view class="cf-sk__row cf-sk__row--total" />
                        <text class="cf-note">正在计算支付安排…</text>
                    </view>
                    <view v-else class="cf-pay">
                        <view class="cf-pay__step is-now">
                            <view class="cf-pay__dot" aria-hidden="true" />
                            <view class="cf-pay__top">
                                <text class="cf-pay__label">{{
                                    isFullPayment ? '今天 · 支付全款' : '今天 · 支付定金'
                                }}</text>
                                <text class="cf-pay__amt">{{
                                    isFullPayment ? quoteText('total_amount') : depositText
                                }}</text>
                            </view>
                            <text class="cf-pay__sub">微信支付，支付后档期即锁定</text>
                        </view>
                        <view v-if="!isFullPayment" class="cf-pay__step">
                            <view class="cf-pay__dot" aria-hidden="true" />
                            <view class="cf-pay__top">
                                <text class="cf-pay__label">尾款</text>
                                <text class="cf-pay__amt">{{ balanceText }}</text>
                            </view>
                            <text class="cf-pay__sub">{{ balanceDueText }}</text>
                        </view>
                    </view>
                </confirm-section>

                <!-- 07 备注：不影响金额，放在价格之后，不打断核对节奏 -->
                <confirm-section
                    title="备注"
                    :action="orderId ? '' : form.remark ? '修改' : '填写'"
                    @action="openRemarkSheet"
                >
                    <text class="cf-value ellipsis" :class="{ 'is-missing': !form.remark }">{{
                        form.remark || '选填，可填写特殊要求'
                    }}</text>
                </confirm-section>

                <!-- 服务端校验：error 与 warn 分开，避免所有提示长一个样 -->
                <view v-if="validationErrors.length" class="cf-alert cf-alert--danger">
                    <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text v-for="(v, i) in validationErrors" :key="i" class="cf-alert__line">{{
                            v.message
                        }}</text>
                    </view>
                </view>
                <view v-if="validationWarns.length" class="cf-alert cf-alert--warning">
                    <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text v-for="(v, i) in validationWarns" :key="i" class="cf-alert__line">{{
                            v.message
                        }}</text>
                    </view>
                </view>

                <!-- 支付失败：留在本页就地重试，不换页 -->
                <view v-if="payError" class="cf-alert cf-alert--danger">
                    <tn-icon name="warning" :size="26" class="cf-alert__ic" />
                    <view class="cf-alert__body">
                        <text class="cf-alert__title">未能完成支付</text>
                        <text class="cf-alert__text">{{ payError }}</text>
                    </view>
                    <view
                        v-if="orderId"
                        class="cf-alert__btn"
                        hover-class="is-pressed"
                        @tap="goOrderDetail"
                    >
                        <text class="cf-alert__btn-text">查看订单</text>
                    </view>
                </view>

                <!-- 08 协议：默认未勾；未勾不置灰按钮，点按在这里出红字 -->
                <view class="cf-agree">
                    <agreement-check v-model="agreed" :error="agreeError">
                        <text>
                            我已阅读并同意《预约服务协议》与《售后服务规则》，定金支付后档期即锁定。
                        </text>
                    </agreement-check>
                </view>
            </view>
        </template>

        <!-- 吸底操作栏：左侧金额 + 右侧主操作，随 step 变化 -->
        <template #bottom>
            <bottom-bar v-if="!loading && !error">
                <view class="abp">
                    <text class="abp__label">{{ barLabelView }}</text>
                    <view class="abp__row">
                        <view v-if="barAmountView != null" class="abp__amount">
                            <price size="lg" :content="barAmountView" :trim-zeros="false" />
                        </view>
                        <text v-else class="abp__na">—</text>
                        <text v-if="totalHintText" class="abp__hint">{{ totalHintText }}</text>
                    </view>
                </view>
                <template #actions>
                    <view class="abtns">
                        <base-button
                            v-if="step > 1 && !isConfirm"
                            text="上一步"
                            variant="ghost"
                            size="md"
                            :block="false"
                            @click="prevStep"
                        />
                        <base-button
                            v-if="!isConfirm"
                            :text="nextButtonText"
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
                            @click="onPrimaryTap"
                        />
                    </view>
                </template>
            </bottom-bar>
        </template>
    </page-shell>

    <!-- 日期 + 地区选择：弹层内一次选完，内容整块可滚，不会被裁切 -->
    <tn-popup
        v-model="datePickerVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="pop pop--picker">
            <view class="pop__head">
                <text class="pop__title">服务日期与地区</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="datePickerVisible = false"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>

            <scroll-view scroll-y class="pop__scroll">
                <!-- 日历：自绘月历，块高固定，不依赖组件测量 -->
                <view class="dcal">
                    <view class="dcal__nav">
                        <view
                            class="dcal__nav-btn"
                            :class="{ 'is-disabled': !canShiftMonth(-1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="上个月"
                            @tap="shiftMonth(-1)"
                        >
                            <tn-icon name="left" :size="28" />
                        </view>
                        <text class="dcal__nav-title">{{ pickerMonthLabel }}</text>
                        <view
                            class="dcal__nav-btn"
                            :class="{ 'is-disabled': !canShiftMonth(1) }"
                            hover-class="is-pressed"
                            aria-role="button"
                            aria-label="下个月"
                            @tap="shiftMonth(1)"
                        >
                            <tn-icon name="right" :size="28" />
                        </view>
                    </view>

                    <view class="dcal__week">
                        <text v-for="w in WEEK_LABELS" :key="w" class="dcal__week-item">{{
                            w
                        }}</text>
                    </view>

                    <view class="dcal__grid">
                        <view
                            v-for="cell in calendarCells"
                            :key="cell.key"
                            class="dcal__cell"
                            :class="{
                                'is-blank': !cell.day,
                                'is-disabled': cell.disabled,
                                'is-on': cell.selected,
                                'is-today': cell.today
                            }"
                            @tap="pickDay(cell)"
                        >
                            <text v-if="cell.day" class="dcal__day">{{ cell.day }}</text>
                        </view>
                    </view>
                </view>

                <!-- 地区：与查档期页共用 components/region-columns（三栏联动、同一套选中态） -->
                <view class="pop__region">
                    <region-columns
                        :key="pickerKey"
                        v-model="pickerRegion"
                        height="288rpx"
                        title="服务地区"
                    />
                </view>
            </scroll-view>

            <view class="pop__foot">
                <text v-if="pickerTip" class="pop__tip">{{ pickerTip }}</text>
                <base-button
                    :text="pickerConfirmText"
                    variant="ink"
                    size="md"
                    :disabled="!pickerConfirmable"
                    @click="confirmPicker"
                />
            </view>
        </view>
    </tn-popup>

    <!--
        联系 / 地址 / 备注拆成三个弹层：
        改电话不用跨过地址字段，改地址不用看到备注；地区单独标注「决定价格」，
        改地区必须回到「日期 + 地区」弹层，因为两者共同决定报价与锁档。
    -->
    <tn-popup
        v-model="sheetVisible"
        open-direction="bottom"
        :safe-area-inset-bottom="true"
        :radius="28"
    >
        <view class="pop">
            <view class="pop__head">
                <text class="pop__title">{{ sheetTitle }}</text>
                <view
                    class="pop__close"
                    hover-class="is-pressed"
                    aria-label="关闭"
                    @tap="sheetVisible = false"
                >
                    <tn-icon name="close" :size="32" />
                </view>
            </view>
            <view class="pop__body">
                <template v-if="sheetMode === 'contact'">
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
                    <text class="pop__hint">仅用于档期确认与到场联系，不会对外展示。</text>
                </template>

                <template v-else-if="sheetMode === 'address'">
                    <view class="reg">
                        <view class="reg__main">
                            <text class="reg__k">服务地区 · 决定价格</text>
                            <text class="reg__v">{{ serviceRegionText || '未选择' }}</text>
                        </view>
                        <text class="reg__a" hover-class="is-pressed" @tap="switchRegionFromSheet"
                            >修改地区</text
                        >
                    </view>
                    <base-input
                        v-model="form.service_address"
                        label="详细地址"
                        placeholder="请输入详细地址"
                        maxlength="80"
                        required
                        :error="errors.service_address"
                        @blur="validateField('service_address')"
                    />
                    <text class="pop__hint">修改地区会重新计算价格并重新锁定档期，需要回到日期与地区选择。</text>
                </template>

                <template v-else>
                    <textarea
                        v-model="form.remark"
                        class="ta"
                        placeholder="选填，例如场地限制、特殊流程、希望避开的时间…"
                        placeholder-class="field__ph"
                        maxlength="200"
                        :cursor-spacing="120"
                    />
                    <text class="ta__count">{{ (form.remark || '').length }}/200</text>
                </template>
            </view>
            <view class="pop__foot">
                <base-button
                    :text="sheetMode === 'remark' ? '保存备注' : '保存'"
                    variant="ink"
                    size="md"
                    @click="saveSheet"
                />
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
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { onLoad, onShow, onUnload } from '@dcloudio/uni-app'
import {
    getStaffDetail,
    getBookingRoleCandidates,
    getScheduleRule,
    lockSchedule,
    releaseLock,
    checkAvailable
} from '@/api/catalog'
import { previewOrder, previewBooking, createOrder } from '@/api/order'
import { prepay } from '@/api/pay'
import { requestPayment } from '@/platform/pay'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import cache from '@/utils/cache'
import { resolveBookingContext, saveBookingContext } from '@/utils/booking-context'
import { EMPTY_REGION, hasRegionContext, regionParams, type RegionContext } from '@/utils/region'
import { report } from '@/platform/report'
import { formatDate, formatMoney, parseDateValue } from '@shared/utils/format'
import type { Tone } from '@shared/contracts'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { FB } from '@/constants/feedback'

const appStore = useAppStore()
const userStore = useUserStore()

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
/** 详情页带下来的服务地区：套餐展示价、报价预检、下单结算必须同一份 */
const region = ref<RegionContext>({ ...EMPTY_REGION })
const packages = ref<any[]>([])
const addons = ref<any[]>([])
const roleConfigs = ref<any[]>([])

const packageId = ref(0)
const selectedAddons = ref<number[]>([])
/**
 * 管家 / 督导：用户指定具体人员（每人价格不同）。
 * role_key -> 已选人员；未选即视为「不需要」。
 */
interface RolePickedPerson {
    staff_id: number
    package_id: number
    name: string
    avatar?: string
    price: number
}
const rolePicked = reactive<Record<string, RolePickedPerson>>({})
/** 用户对「需不需要」的选择；与是否已选人分开，避免「需要但还没选人」的状态无处存放 */
const roleWanted = reactive<Record<string, boolean>>({})
/** role_key -> 候选人员（按日期 + 地区算价，含每人套餐价） */
const roleCandidateMap = ref<Record<string, any[]>>({})
const roleLoadingMap = ref<Record<string, boolean>>({})
/** 从人员详情页回来时用来校验「这次返回是选人」 */
const pendingPickRole = ref('')

const staffName = ref('')
const staffAvatar = ref('')
const staffCover = ref('')
/** 确认页 01 块的副信息：有值才拼，不编「好评率」这类后端没有的数据 */
const staffRole = ref('')
const staffRating = ref(0)
const staffOrderCount = ref(0)
const city = ref('')

const quote = ref<any>({})
/** 报价明细行：v2 把它放在返回根层，quote 里只有金额，必须单独存 */
const quoteItems = ref<any[]>([])
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
/** 提交校验失败后：块内红字 + 滚动定位 + 闪烁，三者同时发生 */
const formErrorVisible = ref(false)
const flashTarget = ref<'' | 'contact' | 'address'>('')
let flashTimer: ReturnType<typeof setTimeout> | null = null

/** 字段在弹层里，页面上按「块」给字段级文案；点块右上角「修改」即可就地编辑 */
const contactBlockError = computed(() => {
    if (!formErrorVisible.value) return ''
    const missing: string[] = []
    if (errors.contact_name) missing.push('联系人姓名')
    if (errors.contact_mobile) missing.push('手机号')
    return missing.length ? `请填写${missing.join('、')}` : ''
})
const addressBlockError = computed(() =>
    formErrorVisible.value && errors.service_address ? '请填写详细地址' : ''
)

/** 订单已创建但服务端判定当前不可支付（status=0 待确认） */
const orderPendingConfirm = ref(false)

/* ---------------- 日期 + 地区弹层 ---------------- */

const datePickerVisible = ref(false)
const pickerDate = ref('')
const sheetVisible = ref(false)
/**
 * 三种编辑弹层共用一套壳：
 *  - contact：只改姓名 / 手机号，不让人跨过地址字段；
 *  - address：只改详细地址，地区单独标注「决定价格」并跳回日期弹层；
 *  - remark：多行备注，不影响报价。
 */
const sheetMode = ref<'contact' | 'address' | 'remark'>('contact')
const sheetTitle = computed(() =>
    sheetMode.value === 'address' ? '服务地址' : sheetMode.value === 'remark' ? '备注' : '联系人'
)
/** 档期规则：只用来限制可选日历范围，不提供默认日期 */
const minSelectableDate = ref('')
const maxSelectableDate = ref('')

/** 弹层内日历月份；地区选择由 region-columns 负责 */
const pickerMonth = reactive({ year: 0, month: 0 })

/* ---------------- 步骤 / 分支 ---------------- */

/**
 * 后台「服务分类 → 预约关联」的两个开关：
 *  - booking_butler_enabled + category_id → role_key=butler（婚礼管家）
 *  - booking_director_enabled + category_id → role_key=director（婚礼督导）
 * 详情接口已按顺序下发 related_role_configs，本页据此插入对应步骤；
 * 未启用则整步跳过，步骤数回落 3 步（套餐 / 附加项 / 确认）。
 */
const flowRoles = computed(() =>
    roleConfigs.value.filter((r) => r?.role_key === 'butler' || r?.role_key === 'director')
)
const preConfirmCount = computed(() => 2 + flowRoles.value.length)
const confirmStep = computed(() => preConfirmCount.value + 1)
const isConfirm = computed(() => step.value === confirmStep.value)
const flowLabels = computed(() => [
    '选套餐',
    '附加项',
    ...flowRoles.value.map((r) => String(r.role_label || ''))
])

/** 当前步骤对应的可选角色（step 3 起）；确认页和套餐/附加项页返回 null */
const activeRole = computed(() => {
    if (isConfirm.value) return null
    const index = step.value - 3
    return index >= 0 ? flowRoles.value[index] || null : null
})
const activeRoleNeeded = computed(() =>
    activeRole.value ? !!roleWanted[activeRole.value.role_key] : false
)
const activeRolePicked = computed(() =>
    activeRole.value ? rolePicked[activeRole.value.role_key] || null : null
)
const activeRoleCandidates = computed(() =>
    activeRole.value ? roleCandidateMap.value[activeRole.value.role_key] || [] : []
)
const activeRoleLoading = computed(() =>
    activeRole.value ? !!roleLoadingMap.value[activeRole.value.role_key] : false
)
/** 金额小字：整数不带 .00，有角分才保留两位 */
function shortMoney(v: any) {
    const n = Number(v || 0)
    if (!Number.isFinite(n)) return '0'
    return Number.isInteger(n) ? money(n).replace(/\.00$/, '') : money(n)
}

/** 角色页「需要」卡片的说明：未选显示候选里的最低价，已选显示人员与价格 */
const activeRolePriceText = computed(() => {
    const role = activeRole.value
    if (!role) return ''
    if (activeRolePicked.value) {
        return `${activeRolePicked.value.name} · ¥${shortMoney(activeRolePicked.value.price)}`
    }
    // 候选都已按当前日期/地区算过价，取最低价才是真的「起」
    const prices = activeRoleCandidates.value.map((c) => Number(c?.price || 0)).filter((p) => p > 0)
    const price = prices.length ? Math.min(...prices) : Number(role.default_price || 0)
    return price > 0 ? `¥${shortMoney(price)} 起 · 可选人员` : '按所选人员计价'
})
const activeRoleFootText = computed(() => {
    const role = activeRole.value
    if (!role) return ''
    if (!activeRoleNeeded.value) return '未加入该项服务，不产生费用。'
    if (activeRoleLoading.value) return '正在加载可安排人员…'
    if (!activeRoleCandidates.value.length) return '当前日期暂无可安排人员，可换个日期。'
    if (!activeRolePicked.value) return '请选择具体人员，不同人员价格不同。'
    return `已选 ${activeRolePicked.value.name} · ¥${shortMoney(activeRolePicked.value.price)}`
})

const nextButtonText = computed(() => {
    if (step.value === 1) return '下一步 · 附加项'
    const nextRole = flowRoles.value[step.value - 2]
    return nextRole ? `下一步 · ${nextRole.role_label}` : '下一步 · 确认订单'
})

function onBack() {
    if (step.value > 1) {
        // 返回上一步只是回改，不释放锁：onUnload 才释放。
        // 因此这里不弹「将释放档期」的假警告，避免用户以为要重新抢档。
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
function addonId(a: any) {
    return Number(a?.id || a?.addon_id || 0)
}
function addonPrice(a: any) {
    return a?.price ?? 0
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

/** 地区是否已经选到区县级（后端算价要求城市 + 区县同时存在） */
const regionReady = computed(() => hasRegionContext(region.value))

/** 头图胶囊文案：缺什么就提示什么，不显示假日期 */
const dateCapsuleText = computed(() => {
    if (!date.value && !regionReady.value) return '选择日期与地区'
    if (!date.value) return `选择日期 · ${city.value || '已选地区'}`
    if (!regionReady.value) return `${dateLabel.value} · 选择地区`
    return dateLabel.value
})

const needBarText = computed(() => {
    if (!date.value && !regionReady.value) return '请先选择服务日期与地区，价格按地区计算'
    if (!date.value) return '请先选择服务日期，未选日期无法算价'
    return '请先选择服务地区，未选地区无法算价'
})

const dateDay = computed(() => (date.value ? formatDate(date.value, 'MM.DD', '待定') : '待定'))
const dateYearWeek = computed(() => {
    const d = parseDateValue(date.value)
    if (!d) return '日期待定'
    const w = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][d.getDay()]
    return `${d.getFullYear()} ${w}`
})

/* hero 文案：套餐页=当前套餐，附加项页=已选合计，角色页=当前角色。
   头图放大后只保留「eyebrow + 标题 + 价格」，介绍性长文案全部收进步骤头一句话。 */
const heroImage = computed(() => {
    // 角色步骤用后台分类配置的默认图，绝不拿某个服务人员的头像顶替
    if (activeRole.value) return activeRole.value.image || ''
    return staffCover.value
})
/* 导航基调：只有「真有头图承接」的步骤页才允许浅色。
   注意 prop 名是 page-shell 的 nav-tone，写成 tone 会被当成普通属性透传、导航永远是深色。
   loading 骨架（.sk__hero 米色块）与无图的 .bhero__blank 只有一层薄 scrim，
   白图标压上去对比度不够，这两种状态回落到深色。 */
const navLight = computed(
    () => !isConfirm.value && !error.value && !loading.value && !!heroImage.value
)
const heroEyebrow = computed(() => {
    const prefix = `STEP ${step.value} / ${preConfirmCount.value}`
    if (activeRole.value) return `${prefix} · 可选服务`
    return `${prefix} · ${step.value === 1 ? '服务套餐' : '附加项'}`
})
const heroTitle = computed(() => {
    // 确认页用套餐名（订单卡第二行要显示「套餐 · 14:00」）
    if (isConfirm.value) return currentPackageName.value || '服务套餐'
    if (activeRole.value) return activeRole.value.role_label || '可选服务'
    if (step.value === 1) return currentPackageName.value || '服务套餐'
    return selectedAddons.value.length ? `已选 ${selectedAddons.value.length} 项附加` : '附加项'
})
const heroPrice = computed(() => {
    if (isConfirm.value) return currentPackage.value ? pkgPrice(currentPackage.value) : null
    if (activeRole.value) {
        const price = Number(activeRole.value.default_price || 0)
        return price > 0 ? price : null
    }
    if (step.value === 1) return currentPackage.value ? pkgPrice(currentPackage.value) : null
    const sum = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
    return sum > 0 ? sum : null
})
const heroUnit = computed(() => {
    if (isConfirm.value || step.value === 1) return '/ 场'
    if (activeRole.value) return '/ 位'
    return '/ 项'
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

/** 报价里的关联服务人员行（item_type=3），金额以服务端为准 */
const quoteRoleItems = computed(() =>
    quoteItems.value.filter((i: any) => Number(i?.item_type) === 3)
)
const rolePriceSum = computed(() => {
    const quoted = quoteRoleItems.value.reduce(
        (acc: number, i: any) => acc + Number(i?.price || 0),
        0
    )
    // 报价未回时用已选人员的本地价兜底，保证确认页金额拆分不出现负数
    if (quoted > 0) return quoted
    return Object.values(rolePicked).reduce((acc, p) => acc + Number(p?.price || 0), 0)
})

/** 步骤页运行合计：已选人员的实际价格（确认页仍以服务端报价为准） */
const roleEstimateSum = computed(() =>
    Object.values(rolePicked).reduce((acc, p) => acc + Number(p?.price || 0), 0)
)

/** 确认页角色行：用户选的人 + 报价里该角色的人员名 */
const quoteRoleRows = computed(() =>
    flowRoles.value
        .filter((r) => !!rolePicked[r.role_key])
        .map((r) => {
            const picked = rolePicked[r.role_key]
            const quoted = quoteRoleItems.value.find(
                (i: any) => String(i?.item_meta?.role_key || '') === r.role_key
            )
            const name = quoted?.staff?.name || picked.name
            return {
                role_key: r.role_key,
                role_label: String(r.role_label || '关联服务'),
                text: `${name} · ¥${money(picked.price)}`
            }
        })
)

/**
 * 服务端把「关联服务人员」也计入 addon_amount。
 * 确认页把这一部分单列成「管家 / 督导」，附加项行只展示纯附加项：
 * 附加项行 = addon_amount - rolePriceSum，总计仍直接用服务端 total_amount。
 */
const addonOnlyAmount = computed(() =>
    Math.max(0, Number(quote.value?.addon_amount || 0) - rolePriceSum.value)
)

const packageRules = computed(() => {
    const p = currentPackage.value
    const rules = p?.rules || p?.package?.rules || p?.duration_desc || ''
    return rules ? String(rules) : ''
})

/* ---------------- 确认页展示口径 ---------------- */

/** 套餐时长：后端 duration_desc 是唯一可信来源，没给就整行不渲染 */
const packageDurationText = computed(() => {
    const p = currentPackage.value
    const text = p?.duration_desc || p?.package?.duration_desc || ''
    return text ? String(text) : ''
})

/** 套餐展示价：优先用服务端报价里的 service_amount，避免 01 与 05 出现两个价 */
const packagePriceText = computed(() => {
    const quoted = Number(quote.value?.service_amount || 0)
    const price = quoted > 0 ? quoted : Number(currentPackage.value ? pkgPrice(currentPackage.value) : 0)
    return price > 0 ? `¥${money(price)}` : ''
})

/** 人员副信息：有评分/单量才拼，不编「好评率」这类后端没有的字段 */
const staffMetaText = computed(() => {
    const parts: string[] = []
    if (staffRole.value) parts.push(staffRole.value)
    if (staffRating.value > 0) parts.push(`${staffRating.value} 分`)
    if (staffOrderCount.value > 0) parts.push(`${staffOrderCount.value} 单`)
    return parts.join(' · ')
})

const addonTitle = computed(() =>
    selectedAddonList.value.length
        ? `附加项 · ${selectedAddonList.value.length} 项`
        : '附加项'
)

/** 联系人一行：姓名 + 脱敏手机；任一缺失都算「未填写」，不显示半截信息 */
const contactLine = computed(() => {
    const name = String(form.contact_name || '').trim()
    const mobile = String(form.contact_mobile || '').trim()
    if (!name || !mobile) return ''
    return `${name} · ${maskMobile(mobile)}`
})

/** 服务端校验按 level 拆开：error 阻断、warn 只提醒，视觉权重不同 */
const validationErrors = computed(() =>
    validations.value.filter((v: any) => String(v?.level || '') !== 'warn')
)
const validationWarns = computed(() =>
    validations.value.filter((v: any) => String(v?.level || '') === 'warn')
)

/** 本次预约的服务地区（省 · 市 · 区）；地区缺失时退回人员城市 */
const serviceRegionText = computed(() => {
    const r = region.value
    const text = [r.province_name, r.city_name, r.district_name].filter(Boolean).join(' · ')
    return text || city.value
})

const cityVenueText = computed(() => {
    const addr = String(form.service_address || '').trim()
    const area = serviceRegionText.value
    if (area && addr) return `${area} · ${addr}`
    return addr || area || '场地待确认'
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
const balanceStepText = computed(() => {
    const d = quote.value?.balance_pay_desc || quote.value?.balance_desc
    return d ? String(d) : '婚礼前 7 天开放支付'
})

/** 没有尾款（或尾款为 0）= 全款：支付安排收成一个节点，不摆一条 ¥0 尾款 */
const isFullPayment = computed(() => !(balanceCents.value && balanceCents.value > 0))

const balanceDueText = computed(() => balanceStepText.value)

function quoteText(key: string) {
    const v = quote.value?.[key]
    if (v === null || v === undefined || v === '') return '—'
    return `¥${money(v)}`
}

/** 应付金额：步骤页显示“套餐 + 附加项 + 已选角色”的运行合计；确认页只认服务端报价 */
const barAmount = computed<number | string | null>(() => {
    if (step.value === 1) {
        return currentPackage.value ? pkgPrice(currentPackage.value) : null
    }
    if (!isConfirm.value) {
        const base = Number(currentPackage.value ? pkgPrice(currentPackage.value) : 0)
        const add = selectedAddonList.value.reduce((acc, a) => acc + Number(addonPrice(a) || 0), 0)
        return base + add + roleEstimateSum.value
    }
    const dep = quote.value?.deposit_amount
    if (dep !== undefined && dep !== null && Number(dep) > 0) return dep
    const t = quote.value?.total_amount
    return t === undefined || t === null || t === '' ? null : t
})

const barLabel = computed(() => {
    if (step.value === 1) return '当前套餐'
    if (!isConfirm.value) return '当前合计'
    // 没有尾款 = 全款：这时 deposit 往往等于 total，不能写「应付定金」
    if (!(balanceCents.value && balanceCents.value > 0)) return '应付金额'
    return quote.value?.deposit_amount && Number(quote.value.deposit_amount) > 0
        ? '应付定金'
        : '应付金额'
})

/**
 * 锁档状态（08 屏进入即锁，15 分钟 TTL）。
 * - locked：已锁住，显示真实剩余时间
 * - failed：业务失败（别人先锁）→ 必须拦，留在本页引导改期
 * - expired：保留超时 → 可原地重锁，和「被抢」不是同一件事
 * - error：接口异常 → 不拦，说真话「支付成功前不预留」
 *
 * failed 与 expired 的下一步动作不同：前者只能换日期，后者可以重新锁档。
 * 之前把超时也塞进 failed，倒计时归零后仍提示「该档期刚被锁定」，文案是错的。
 */
const lockState = ref<'idle' | 'locking' | 'locked' | 'failed' | 'expired' | 'error'>('idle')
const lockLeft = ref(0)
let lockTimer: ReturnType<typeof setInterval> | null = null

/** 摘要卡胶囊：状态 + 语义色，组件只接受 Tone */
const orderBadge = computed<{ label: string; tone: Tone }>(() => {
    if (lockState.value === 'failed') return { label: '档期被占用', tone: 'danger' }
    if (lockState.value === 'expired') return { label: '档期已释放', tone: 'warning' }
    if (lockState.value === 'locked') return { label: '档期已保留', tone: 'success' }
    if (lockState.value === 'locking') return { label: '锁档中', tone: 'neutral' }
    return { label: '待支付', tone: 'warning' }
})

/** 摘要卡锁档行：接口异常时不说「已保留」，支付成功前就是没预留 */
const lockSummaryText = computed(() => {
    if (lockState.value === 'locked') {
        const mm = String(Math.floor(lockLeft.value / 60)).padStart(2, '0')
        const ss = String(lockLeft.value % 60).padStart(2, '0')
        return `保留 ${mm}:${ss}，超时重选`
    }
    if (lockState.value === 'locking') return '正在为你锁定档期…'
    if (lockState.value === 'failed') return '该档期刚被订走，请改期'
    if (lockState.value === 'expired') return '保留时间已到，可重新锁档'
    return '支付成功前档期不为你预留'
})

/** 锁档被占 / 订单待确认时，吸底不再摆金额，避免「付不了钱却亮着价格」 */
const barAmountView = computed(() =>
    isConfirm.value && (lockState.value === 'failed' || orderPendingConfirm.value)
        ? null
        : barAmount.value
)

const barLabelView = computed(() => {
    if (!isConfirm.value) return barLabel.value
    if (orderPendingConfirm.value) return '待确认'
    if (lockState.value === 'failed') return '档期已被占用'
    if (lockState.value === 'expired') return '档期已释放'
    return barLabel.value
})

/** 吸底第二行：只有「合计 ≠ 当前应付」时才补，避免全款时同一数字出现两次 */
const totalHintText = computed(() => {
    if (!isConfirm.value || quoteState.value !== 'ready' || orderPendingConfirm.value) return ''
    const total = Number(quote.value?.total_amount || 0)
    const pay = Number(barAmount.value || 0)
    if (!(total > 0) || Math.abs(total - pay) < 0.01) return ''
    return `合计 ¥${money(total)}`
})

function stopLockTimer() {
    if (lockTimer) clearInterval(lockTimer)
    lockTimer = null
}

async function doLock() {
    if (!staffId.value || !date.value) return
    if (lockState.value === 'locking') return
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
                // 超时 ≠ 被别人订走：前者可以原地重锁，后者只能改期
                lockState.value = 'expired'
            }
        }, 1000)
    } catch (e: any) {
        // 区分「业务失败（别人已锁）」与「接口异常」：前者必须拦，后者不能拦。
        // 不能用 code===0 兜底 —— 请求层把 5xx/“系统繁忙”也映射成 code 0，
        // 那会把服务端异常误判成“被抢档”，把用户从确认页踢回上一步。
        const msg = String(e?.message || '')
        const isBusiness = /锁定|已被|不可约|占用|已订/.test(msg)
        if (isBusiness) {
            // 不强制回退：确认页的套餐、附加项、地址都还在，踢回上一步只会让用户
            // 重新核对一遍。留在本页，摘要卡变红 + 顶部条给「换日期」。
            lockState.value = 'failed'
            toast.info('该档期刚被订走，请换一个日期')
        } else {
            lockState.value = 'error'
            report('api_error', {
                endpoint: 'schedule.lockSchedule',
                message: '档期锁定失败（降级放行）'
            })
        }
    }
}

/**
 * 主按钮文案本身就是「下一步动作」，不做无解释的灰按钮：
 * 报价失败 → 重新获取报价；档期被占 → 重新选择日期；超时 → 重新锁档。
 */
const payButtonText = computed(() => {
    if (submitting.value) return '提交中…'
    if (paying.value) return '支付中…'
    if (!isConfirm.value) return '下一步'
    if (orderPendingConfirm.value) return '查看订单'
    if (orderId.value && payError.value) return '重新支付'
    if (lockState.value === 'failed') return '重新选择日期'
    if (lockState.value === 'expired') return '重新锁档'
    if (quoteState.value === 'failed') return '重新获取报价'
    if (quoteState.value === 'loading' || quoteState.value === 'idle') return '正在核价…'
    if (barAmount.value === null) return '金额待确认'
    return '去支付'
})

/* ---------------- 选择与校验 ---------------- */

/** 报价已失效：套餐/附加项/人员/日期任一变更后，旧 quote_hash 不能再用于下单 */
function invalidateQuote() {
    quoteHash.value = ''
    quote.value = {}
    quoteItems.value = []
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
    invalidateQuote()
}

function addUpsell() {
    const a = upsellAddon.value
    if (!a) return
    if (!selectedAddons.value.includes(addonId(a))) selectedAddons.value.push(addonId(a))
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

/**
 * 校验定位：确认页的必填字段在弹层里，页面上定位到它所属的块。
 * 先滚到块（导航高度 + 呼吸位），再让块闪 3 次；不自动开弹层 ——
 * 弹层会立刻盖住刚定位的位置，用户反而看不到「错在哪一块」。
 * 看清后点块右上角「修改」即可就地编辑。
 */
function scrollToBlock(target: 'contact' | 'address') {
    const selector = target === 'contact' ? '#cf-contact' : '#cf-address'
    nextTick(() => {
        const query = uni.createSelectorQuery()
        query.selectViewport().scrollOffset(() => undefined)
        query.select(selector).boundingClientRect(() => undefined)
        query.exec((res: any[]) => {
            const current = Number(res?.[0]?.scrollTop || 0)
            const rect = res?.[1]
            if (!rect) return
            uni.pageScrollTo({
                scrollTop: Math.max(0, current + Number(rect.top || 0) - 120),
                duration: 280
            })
        })
    })
}

/** 提交失败：块内红字 + 滚动到首个出错块 + 闪烁，同时阻止提交 */
function focusFirstError() {
    formErrorVisible.value = true
    const target: 'contact' | 'address' =
        errors.contact_name || errors.contact_mobile ? 'contact' : 'address'
    flashTarget.value = target
    if (flashTimer) clearTimeout(flashTimer)
    flashTimer = setTimeout(() => {
        flashTarget.value = ''
    }, 1500)
    scrollToBlock(target)
    toast.info(target === 'contact' ? '请完善联系人信息' : '请填写服务地址')
}

/** 三种弹层共用一套壳，字段按 mode 切换（见模板与 sheetTitle） */
function openSheet(mode: 'contact' | 'address' | 'remark') {
    sheetMode.value = mode
    sheetVisible.value = true
}

function openContactSheet() {
    openSheet('contact')
}

function openAddressSheet() {
    openSheet('address')
}

function openRemarkSheet() {
    openSheet('remark')
}

/** 地址弹层里改地区：先关弹层再开日期+地区（两个 tn-popup 不能同时开） */
function switchRegionFromSheet() {
    sheetVisible.value = false
    // 等弹层收起动画走完再开下一个，避免两个 popup 在 mp 上抢同一层
    setTimeout(() => openDatePicker(), 260)
}

function saveSheet() {
    if (sheetMode.value === 'contact') {
        validateField('contact_name')
        validateField('contact_mobile')
        if (errors.contact_name || errors.contact_mobile) return
    } else if (sheetMode.value === 'address') {
        validateField('service_address')
        if (errors.service_address) return
    }
    sheetVisible.value = false
}

/* ---------------- 步骤流转 ---------------- */

/**
 * 切步后把页面滚回顶部。step 一改，.bhero / 面板 / 导航占位块会同时变化，
 * 等 DOM 更新完再滚比直接调用稳。确认步按 confirmStep 动态判断，不再写死 3。
 */
function resetScrollAfterStepChange() {
    nextTick(() => uni.pageScrollTo({ scrollTop: 0, duration: 0 }))
}

function nextStep() {
    // 日期与地区是算价前置条件：缺一个都不许往下走，直接开弹层引导补全
    if (!ensureDateTime()) return

    if (step.value === 1) {
        if (!packageId.value) {
            toast.info('请选择套餐')
            return
        }
        step.value = 2
        resetScrollAfterStepChange()
        return
    }
    // 角色步骤：选了人就必须带完整的人员 + 套餐才能进确认页（价格随人不同）
    const role = activeRole.value
    if (role) {
        const wanted = !!roleWanted[role.role_key]
        const picked = rolePicked[role.role_key]
        if (wanted && (!picked || !picked.staff_id || !picked.package_id)) {
            toast.info(`请选择${role.role_label}人员`)
            return
        }
    }
    if (isConfirm.value) return

    step.value += 1
    resetScrollAfterStepChange()
    if (isConfirm.value) {
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

/** 确认页各块「修改」：直接跳到对应步骤，不再要求逐级退 */
function editStep(target: number) {
    if (!isConfirm.value) return
    step.value = Math.min(Math.max(1, target), confirmStep.value)
    resetScrollAfterStepChange()
}

/** 保留超时后原地重锁；和「被别人订走」不同，后者只能改期 */
function relockSchedule() {
    stopLockTimer()
    lockLeft.value = 0
    lockState.value = 'idle'
    doLock()
}

/**
 * 吸底主按钮：按当前阻断原因路由。
 * 报价失败 / 锁档被占 / 锁档超时 都不是「禁用」，而是各自给一个可执行动作。
 */
function onPrimaryTap() {
    if (submitting.value || paying.value) return
    // 订单已创建：待确认只能去看订单，支付失败则直接重试 prepay，绝不重复 createOrder
    if (orderPendingConfirm.value) {
        goOrderDetail()
        return
    }
    if (orderId.value) {
        payOrder(orderId.value)
        return
    }
    if (lockState.value === 'failed') {
        openDatePicker()
        return
    }
    if (lockState.value === 'expired') {
        relockSchedule()
        return
    }
    if (quoteState.value === 'failed') {
        runPreview()
        return
    }
    if (quoteState.value === 'loading' || quoteState.value === 'idle') {
        toast.info('正在核价，请稍候')
        return
    }
    if (barAmount.value === null) {
        runPreview()
        return
    }
    handleSubmit()
}

/** 未选日期/地区时拦截：提示 + 直接打开选择弹层（不静默放行） */
function ensureDateTime(): boolean {
    if (date.value && regionReady.value) return true
    toast.info(!date.value ? '请先选择服务日期' : '请先选择服务地区')
    openDatePicker()
    return false
}

/* ---------------- 日期 + 地区弹层 ---------------- */

const WEEK_LABELS = ['日', '一', '二', '三', '四', '五', '六']

function pad2(n: number) {
    return String(n).padStart(2, '0')
}

function dateKeyOf(year: number, month: number, day: number) {
    return `${year}-${pad2(month)}-${pad2(day)}`
}

const todayKey = computed(() => {
    const d = new Date()
    return dateKeyOf(d.getFullYear(), d.getMonth() + 1, d.getDate())
})

const pickerMonthLabel = computed(() => `${pickerMonth.year} 年 ${pickerMonth.month} 月`)

/**
 * 自绘月历：6 行固定高度，切月不跳、不会被弹层裁掉。
 * 早于规则最早可约日 / 晚于最晚可约日一律禁用。
 */
const calendarCells = computed(() => {
    const { year, month } = pickerMonth
    if (!year || !month) return [] as any[]
    const startWeekday = new Date(year, month - 1, 1).getDay()
    const daysInMonth = new Date(year, month, 0).getDate()
    const min = minSelectableDate.value || todayKey.value
    const max = maxSelectableDate.value

    const cells: any[] = []
    for (let i = 0; i < startWeekday; i++) cells.push({ key: `blank-${i}`, day: 0 })
    for (let d = 1; d <= daysInMonth; d++) {
        const key = dateKeyOf(year, month, d)
        cells.push({
            key,
            day: d,
            date: key,
            disabled: (!!min && key < min) || (!!max && key > max),
            selected: key === pickerDate.value,
            today: key === todayKey.value
        })
    }
    while (cells.length % 7 !== 0) cells.push({ key: `tail-${cells.length}`, day: 0 })
    return cells
})

function canShiftMonth(delta: number) {
    if (!pickerMonth.year) return false
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    const key = dateKeyOf(target.getFullYear(), target.getMonth() + 1, 1).slice(0, 7)
    const min = minSelectableDate.value
    const max = maxSelectableDate.value
    if (delta < 0 && min && key < min.slice(0, 7)) return false
    if (delta > 0 && max && key > max.slice(0, 7)) return false
    return true
}

function shiftMonth(delta: number) {
    if (!canShiftMonth(delta)) return
    const target = new Date(pickerMonth.year, pickerMonth.month - 1 + delta, 1)
    pickerMonth.year = target.getFullYear()
    pickerMonth.month = target.getMonth() + 1
}

function pickDay(cell: any) {
    if (!cell?.day || cell.disabled) return
    pickerDate.value = String(cell.date || '')
}

/** 弹层内已选地区：由 region-columns 回传，只有选到区县才有值 */
const pickerRegion = ref<RegionContext | null>(null)
/** 每次打开弹层换一次 key，强制地区三栏按当前值重挂，清掉上次的半选状态 */
const pickerKey = ref(0)

const pickerConfirmable = computed(() => !!pickerDate.value && !!pickerRegion.value?.district_code)

const pickerConfirmText = computed(() => {
    if (!pickerDate.value) return '请选择日期'
    if (!pickerRegion.value) return '请选择地区'
    return '确定'
})

/** 只在没选完时给提示；选完后地区栏已有摘要，不再重复一遍 */
const pickerTip = computed(() => {
    if (!pickerDate.value) return '请先在上方日历选择服务日期'
    if (!pickerRegion.value) return '请选择省 / 市 / 区，价格按区县计算'
    return ''
})

async function loadScheduleRule() {
    try {
        const rule: any = await getScheduleRule()
        minSelectableDate.value = String(rule?.min_selectable_date || '')
        maxSelectableDate.value = String(rule?.max_selectable_date || '')
    } catch {
        // 规则接口失败不阻断：日历按「今天起」兜底
    }
}

function openDatePicker() {
    pickerDate.value = date.value || ''
    // 已有地区就回填（三栏组件自己按 code 反查），没选过保持未选中
    pickerRegion.value = regionReady.value ? { ...region.value } : null
    const base = parseDateValue(pickerDate.value) || new Date()
    pickerMonth.year = base.getFullYear()
    pickerMonth.month = base.getMonth() + 1
    datePickerVisible.value = true
    pickerKey.value += 1
    if (!minSelectableDate.value) loadScheduleRule()
}

function confirmPicker() {
    if (!pickerConfirmable.value) return
    const nextRegion = pickerRegion.value as RegionContext
    const nextDate = pickerDate.value
    const prevDate = date.value

    const changed =
        nextDate !== date.value ||
        nextRegion.district_code !== region.value.district_code ||
        nextRegion.city_code !== region.value.city_code

    date.value = nextDate
    region.value = { ...nextRegion }
    city.value = nextRegion.city_name || city.value
    saveBookingContext({ date: nextDate, region: nextRegion })

    datePickerVisible.value = false
    if (!changed) return

    invalidateQuote()
    // 日期/地区变了，候选人员与价格也会变：清掉旧列表与已选人员，进入角色步骤时重新拉
    roleCandidateMap.value = {}
    for (const key of Object.keys(rolePicked)) delete rolePicked[key]
    for (const key of Object.keys(roleWanted)) delete roleWanted[key]
    // 静默重拉配置，保留已选套餐/附加项
    load({ silent: true })

    if (!isConfirm.value) return
    // 确认页改档期：旧锁对应的是旧日期，先放掉再按新日期重锁，避免「已保留」是假状态
    if (prevDate && prevDate !== nextDate && lockState.value === 'locked') {
        releaseLock({ staff_id: staffId.value, date: prevDate }).catch(() => undefined)
        stopLockTimer()
        lockState.value = 'idle'
    }
    runPreview()
    doLock()
}

/* ---------------- 可选服务（指定人员） ---------------- */

/** 候选人员：按当前日期 + 地区算价，同一天缓存一份，来回切步骤不重复请求 */
async function loadRoleCandidates(roleKey: string, force = false) {
    if (!roleKey) return
    if (!force && roleCandidateMap.value[roleKey]?.length) return
    if (roleLoadingMap.value[roleKey]) return
    roleLoadingMap.value = { ...roleLoadingMap.value, [roleKey]: true }
    try {
        const list: any = await getBookingRoleCandidates({
            staff_id: staffId.value,
            role_key: roleKey,
            date: date.value || undefined,
            ...regionParams(region.value)
        })
        roleCandidateMap.value = {
            ...roleCandidateMap.value,
            [roleKey]: Array.isArray(list) ? list : list?.lists || []
        }
    } catch {
        roleCandidateMap.value = { ...roleCandidateMap.value, [roleKey]: [] }
    } finally {
        roleLoadingMap.value = { ...roleLoadingMap.value, [roleKey]: false }
    }
}

function isRolePicked(roleKey: string, candidate: any) {
    const picked = rolePicked[roleKey]
    return !!picked && Number(picked.staff_id) === Number(candidate?.staff_id)
}

/** 「不需要」：清掉已选人员；「需要」：展开人员列表等用户挑 */
function setActiveRoleNeeded(needed: boolean) {
    const role = activeRole.value
    if (!role) return
    const key = role.role_key
    if (!needed) {
        if (roleWanted[key] || rolePicked[key]) {
            delete roleWanted[key]
            delete rolePicked[key]
            invalidateQuote()
        }
        return
    }
    roleWanted[key] = true
    loadRoleCandidates(key)
    if (role.schedule_available === false) {
        toast.info(`${role.role_label}在当前日期暂无可安排人员，可换个日期`)
    }
    // 头图占了大半屏，展开人员列表后自动滚到底，让用户马上看到可选的人
    nextTick(() => uni.pageScrollTo({ scrollTop: 9999, duration: 200 }))
}

function pickRoleCandidate(candidate: any) {
    const role = activeRole.value
    if (!role || !candidate) return
    if (candidate.schedule_available === false) {
        toast.info(candidate.schedule_message || '该人员当前日期不可约')
        return
    }
    rolePicked[role.role_key] = {
        staff_id: Number(candidate.staff_id ?? candidate.id ?? 0),
        package_id: Number(candidate.package_id || 0),
        name: String(candidate.name || ''),
        avatar: String(candidate.avatar || ''),
        price: Number(candidate.price || 0)
    }
    roleWanted[role.role_key] = true
    invalidateQuote()
}

/** 卡片副信息只放评分/单量；套餐名太长，留给详情页展示 */
function candidateMeta(c: any) {
    const parts: string[] = []
    if (Number(c?.rating) > 0) parts.push(`${Number(c.rating).toFixed(1)} 分`)
    if (Number(c?.order_count) > 0) parts.push(`${c.order_count} 单`)
    return parts.join(' · ')
}

/**
 * 进入该人员详情页「只看不选」，用于横向对比价格与信息。
 * 详情页底栏会换成「选择 TA」，选完写缓存并返回；本页 onShow 读取。
 */
function openRolePersonDetail(candidate: any) {
    const role = activeRole.value
    if (!role || !candidate) return
    const id = Number(candidate.staff_id ?? candidate.id ?? 0)
    if (!id) return
    pendingPickRole.value = role.role_key
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: {
            id,
            pick_role: role.role_key,
            pick_package_id: Number(candidate.package_id || 0),
            ...(date.value ? { date: date.value } : {}),
            ...regionParams(region.value)
        }
    })
}

/** 详情页返回的选择结果：role_key 必须与本次待选角色一致，且 2 分钟内有效 */
function applyPickedFromDetail(expectedRole: string) {
    const picked: any = cache.get('booking_role_pick')
    if (!picked || String(picked.role_key || '') !== expectedRole) return
    if (Date.now() - Number(picked.ts || 0) > 120000) {
        cache.remove('booking_role_pick')
        return
    }
    rolePicked[expectedRole] = {
        staff_id: Number(picked.staff_id || 0),
        package_id: Number(picked.package_id || 0),
        name: String(picked.name || ''),
        avatar: String(picked.avatar || ''),
        price: Number(picked.price || 0)
    }
    roleWanted[expectedRole] = true
    cache.remove('booking_role_pick')
    invalidateQuote()
}

/* ---------------- 数据 ---------------- */

/** 组装后端要求的参数（字段名来自 BookingFlowService，与既有下单链路一致） */
function buildParams() {
    const params: Record<string, any> = {
        staff_id: staffId.value,
        package_id: packageId.value,
        // 服务地区：报价预检与 /order/create 都会按它算价并写入订单
        ...regionParams(region.value)
    }
    if (date.value) params.date = date.value
    if (selectedAddons.value.length) params.addon_ids = [...selectedAddons.value]
    // 用户指定的人员与套餐：价格随人不同，必须整组传给后端校验
    for (const key of Object.keys(rolePicked)) {
        const picked = rolePicked[key]
        if (!picked?.staff_id || !picked?.package_id) continue
        params[`${key}_staff_id`] = picked.staff_id
        params[`${key}_package_id`] = picked.package_id
    }
    return params
}

/**
 * 拉取预约配置（人员 + 套餐 + 附加项 + 关联角色）。
 * silent=true 用于「弹层改完日期/地区后原地刷新」：不闪骨架、不清掉用户已选的套餐与附加项。
 */
async function load(options: { silent?: boolean } = {}) {
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    if (!options.silent) loading.value = true
    error.value = false
    try {
        const detail: any = await getStaffDetail({
            id: staffId.value,
            date: date.value || undefined,
            ...regionParams(region.value)
        })
        if (!detail || !detail.id) throw new Error('empty')

        staffName.value = detail.name || ''
        staffAvatar.value = detail.avatar || ''
        staffCover.value = detail.cover || detail.avatar || ''
        staffRating.value = Number(detail.rating || 0)
        staffOrderCount.value = Number(detail.order_count || 0)
        staffRole.value = String(detail.category_name || detail.role_name || '主持人')
        // 展示用城市优先取本次预约的服务地区（不是人员常驻城市），
        // 让用户在页面上能核对“这次按哪个地区算价”。
        city.value = region.value.city_name || detail.city || detail.city_name || ''
        packages.value = Array.isArray(detail.packages) ? detail.packages : []
        addons.value = Array.isArray(detail.addons) ? detail.addons : []
        roleConfigs.value = Array.isArray(detail.related_role_configs)
            ? detail.related_role_configs
            : []

        if (packages.value.length) {
            const preferred = packages.value.find((p) => pkgId(p) === Number(packageId.value))
            packageId.value = preferred ? packageId.value : pkgId(packages.value[0])
        }

        if (options.silent) {
            // 静默刷新：只丢掉已经不可售的附加项，其余选择保持不变
            selectedAddons.value = selectedAddons.value.filter((id) =>
                addons.value.some((a) => addonId(a) === id)
            )
            for (const key of Object.keys(rolePicked)) {
                if (!flowRoles.value.some((r) => r.role_key === key)) {
                    delete rolePicked[key]
                    delete roleWanted[key]
                }
            }
        } else {
            // 附加项默认不选（避免用户在不知情的情况下多付钱；设计稿「默认勾前二」不采用，见交付说明）
            selectedAddons.value = []
        }
    } catch (e) {
        rawError.value = e
        if (!options.silent) error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '预约配置加载失败' })
    } finally {
        if (!options.silent) loading.value = false
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
        quoteItems.value = []
        quoteHash.value = ''
        validations.value = []
        report('api_error', { endpoint: 'order.preview', message: e?.message || '报价获取失败' })
    }
}

function applyPreview(res: any) {
    quote.value = res?.quote || res || {}
    // v1 把 items 放在 data 里，v2 放在返回根层；两处都收，确认页才能拆出「管家 / 督导」
    quoteItems.value = Array.isArray(res?.items) ? res.items : []
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

    // ② 表单：块内红字 + 滚动定位 + 闪烁，阻止提交
    if (!validate()) {
        focusFirstError()
        return
    }
    formErrorVisible.value = false

    // ②' 锁档拦截：锁失败（别人先锁）必须拦，锁接口异常放行（网络问题拦了就是白丢单）
    if (lockState.value === 'failed') {
        toast.info('该档期刚被订走，请换一个日期')
        return
    }
    // 保留超时：先重锁，不带着「可能已被别人订走」的档期去下单
    if (lockState.value === 'expired') {
        relockSchedule()
        toast.info('正在重新锁定档期，请稍候')
        return
    }

    // ③ 报价与金额断言
    if (quoteState.value !== 'ready' || !quoteHash.value) {
        runPreview()
        toast.info('正在重新获取报价，请稍候')
        return
    }
    if (amountMismatch.value) {
        toast.info('报价金额不一致，已阻断支付')
        return
    }
    if (barAmount.value === null) {
        toast.info('应付金额缺失，正在重新核价')
        runPreview()
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
                payError.value = av.message || '该档期刚被订走，请换一个日期'
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

        // 订单已创建：未付款订单不占用档期，服务端会释放临时锁，这里同步前端状态，
        // 避免页面继续显示「档期已保留」误导用户。
        lockState.value = 'idle'
        stopLockTimer()

        // 后端时序：create → status=0 待确认 → 确认后才可 prepay。能不能现在付只看服务端字段。
        const stage = String(res?.current_pay_stage || '')
        const needPay = String(res?.need_pay || '')
        const payable = stage ? ['deposit', 'full', 'balance'].includes(stage) : needPay !== 'none'
        if (!payable) {
            // 订单已创建、但服务端判定当前不可支付：状态进入「待确认」。
            // 这里不留在确认页继续点「去支付」——重复点击只会得到同一个结果。
            showPendingConfirm(id)
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

/**
 * 订单已创建但当前不可支付（待确认）。
 * 弹窗只有一个出口「查看订单」：确认页继续留着只会诱导用户反复点「去支付」。
 */
function showPendingConfirm(id: number) {
    orderPendingConfirm.value = true
    uni.showModal({
        title: '订单已提交',
        content:
            '当前无法支付，订单状态已变为「待确认」。商家确认后即可继续支付，通常 1 小时内完成。',
        showCancel: false,
        confirmText: '查看订单',
        success: () => {
            uni.redirectTo({ url: `/packages/pkg-order/pages/detail/detail?id=${id}` })
        }
    })
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
            payError.value = '你取消了支付，可重新发起'
            return
        }
        if (result === 'fail') {
            payError.value = '支付没有成功，可重新支付或查看订单'
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
        const msg = String(e?.message || '')
        // prepay 也可能返回「订单未确认 / 当前不可支付」：这同样属于待确认，
        // 不能当成普通支付失败让用户无限重试。
        if (/待确认|不可支付|未确认|不能支付/.test(msg)) {
            payError.value = ''
            showPendingConfirm(id)
            return
        }
        payError.value = msg || '支付发起失败，请稍后重试'
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

/**
 * 登录返回后补一次被 401 打断的动作。
 *
 * 登录页是压栈打开的，登录成功后 navigateBack 回来，本页实例还在 ——
 * 已经选好的套餐、附加项、联系人输入都在内存里，不需要重建。
 * 但被打断的那一次请求不会自己重来：报价失败会停在下单页的「报价没有算出来」，
 * 锁档失败会留下 error 态。这里只在「登录态由未登录 → 已登录」的这一次 onShow 补，
 * 不做无脑刷新（从支付结果页退回等场景也会走 onShow）。
 *
 * ⚠️ 只补读接口：createOrder 是花钱动作，必须由用户重新点一次，不自动重放。
 */
let wasLogin = userStore.isLogin
onShow(() => {
    // 从「人员详情页」选完人返回：只认本次待选角色，避免把别的页面留下的缓存误当选择
    if (pendingPickRole.value) {
        applyPickedFromDetail(pendingPickRole.value)
        pendingPickRole.value = ''
    }

    const now = userStore.isLogin
    if (!wasLogin && now && isConfirm.value) {
        if (quoteState.value !== 'ready') runPreview()
        if (lockState.value === 'idle' || lockState.value === 'error') doLock()
    }
    wasLogin = now
})

/** 进入某个角色步骤就拉候选；同一角色来回切只请求一次 */
watch(activeRole, (role) => {
    if (role) loadRoleCandidates(role.role_key)
})

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
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    // 日期/地区只认「入口参数 + 上次选择」，不再补系统默认值：
    // 没带就留空，页面顶出提示条并强制用户在弹层里选完才能继续。
    resolveBookingContext(options || {}).then((ctx) => {
        date.value = ctx.date
        region.value = ctx.region
        loadScheduleRule()
        load().then(() => {
            // 首屏就缺条件时直接开弹层，避免用户点了「下一步」才知道要选日期
            if (!date.value || !regionReady.value) nextTick(() => openDatePicker())
        })
    })
})
</script>

<style lang="scss" scoped>
/* ========== 骨架 ========== */
.sk {
    &__hero {
        height: 720rpx;
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

/* ========== Bk Hero：放大到 860rpx（约 447px@390），下方只留一屏能滑完的选项 ========== */
.bhero {
    position: relative;
    height: 860rpx;
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
        /* 按头图高度取百分比（约 200rpx@860rpx）：换图高不用再改这个值 */
        top: 23%;
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

        /* 未选日期/地区：金色描边 + 轻微底色，第一眼就能看到「这里还没填」 */
        &.is-empty {
            border-color: var(--c-brand-soft);
            background: rgba(198, 161, 91, 0.24);
        }
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
    margin-top: var(--sp-2);
}

/* ========== 步骤面板（套餐 / 附加项 / 管家 / 督导共用同一套间距） ========== */
.panel {
    padding: 24rpx 42rpx 0;
}

.stephead {
    margin-bottom: 20rpx;

    &__title {
        display: block;
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__hint {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* 日期/地区未选提示条：横跨内容区，点整条都能打开弹层 */
.needbar {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin: 20rpx 42rpx 0;
    padding: 18rpx 20rpx;
    border: 1rpx solid var(--c-warning-border);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
}

.needbar__icon {
    flex: none;
    color: var(--c-warning);
}

.needbar__text {
    flex: 1;
    min-width: 0;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-warning);
}

.needbar__action {
    flex: none;
    font-size: var(--fs-20);
    color: var(--c-brand-text);
    text-decoration: underline;
}

/* 套餐 / 附加项：横向滑动，纯文字卡，不再占图片位 */
.opts {
    /* 负边距吃掉 .panel 的左右留白，让轨道从屏幕边缘起跑 */
    margin: 0 -42rpx;
    width: auto;
    white-space: nowrap;
}

.opts__track {
    display: inline-flex;
    align-items: stretch;
    gap: 20rpx;
    padding: 0 42rpx;
}

.optcard {
    width: 300rpx;
    flex: none;
    min-width: 0;
    position: relative;
    padding: 22rpx 22rpx 20rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-surface);

    &.is-on {
        /* 选中描边用深金：--c-brand 对 --c-surface 只有 2.39:1，撑不起状态 */
        border-color: var(--c-brand-text);

        .optcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__head {
        display: flex;
        align-items: flex-start;
        gap: 8rpx;
        min-height: 88rpx;
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-24);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__tick {
        flex: none;
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

    &__price {
        display: flex;
        align-items: baseline;
        gap: 4rpx;
        margin-top: 12rpx;
    }

    &__unit {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

/* ========== 管家 / 督导：不需要 / 需要 两选一 ========== */
.rolechoice {
    display: flex;
    gap: 20rpx;
}

.rolechoice__card {
    flex: 1;
    min-width: 0;
    position: relative;
    padding: 24rpx 22rpx;
    min-height: 150rpx;
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-sm);
    background: var(--c-surface);

    &.is-disabled {
        opacity: 0.55;
    }

    &.is-on {
        border-color: var(--c-brand-text);

        .rolechoice__tick {
            opacity: 1;
            transform: scale(1);
        }
    }
}

.rolechoice__title {
    display: block;
    font-size: var(--fs-28);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
    line-height: var(--lh-tight);
}

.rolechoice__desc {
    display: block;
    margin-top: 10rpx;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.rolechoice__tick {
    position: absolute;
    right: 14rpx;
    top: 14rpx;
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
    transition: opacity var(--dur-fast) var(--ease-out), transform var(--dur-fast) var(--ease-out);
}

/* ========== 管家 / 督导：人员选择 ========== */
.roster {
    margin-top: var(--sp-3);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__count {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

/* 人员卡：头像 + 姓名 + 价格，横向滑动；点卡片=选中，点「详情」=看主页 */
.pcard {
    width: 320rpx;
    flex: none;
    padding: 20rpx;
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-sm);
    background: var(--c-surface);

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .pcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__top {
        display: flex;
        align-items: center;
        gap: 14rpx;
        min-width: 0;
    }

    &__avatar {
        flex: none;
        width: 76rpx;
        height: 76rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);

        &--blank {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__avatar-text {
        font-size: var(--fs-24);
        color: var(--c-ink-3);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-24);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__tick {
        flex: none;
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

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 14rpx;
        padding-top: 14rpx;
        border-top: 1rpx dashed var(--c-line);
    }

    &__detail {
        flex: none;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        text-decoration: underline;
        padding: 10rpx 0;
        margin: -10rpx 0;
    }
}

.inline-note {
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* 步骤页脚注：套餐规则 / 已选附加 / 角色安排共用 */
.bkline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 20rpx;
    font-size: var(--fs-20);
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
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        text-decoration: underline;
    }
}

/* ========== 08 订单确认：摘要 + 六个可核对块 ========== */
/* 底色/描边/圆角由 confirm-section 承担；这里只写页面私有的行、金额与提示。 */
.cf {
    padding-top: 0;
}

/* ---- 00 摘要卡：页面唯一深色面 ---- */
.cf-sum {
    position: relative;
    padding: 30rpx 30rpx 0;
    border-radius: var(--r-lg);
    background: linear-gradient(135deg, var(--c-dark-1) 0%, var(--c-dark-2) 100%);
    color: var(--c-on-dark);
    overflow: hidden;

    /* 右上角香槟光晕：让深色卡不至于像一块死黑，也不抢信息 */
    &::after {
        content: '';
        position: absolute;
        right: -60rpx;
        top: -80rpx;
        width: 300rpx;
        height: 300rpx;
        border-radius: var(--r-pill);
        background: radial-gradient(circle, rgba(198, 161, 91, 0.2), rgba(198, 161, 91, 0) 68%);
        pointer-events: none;
    }

    &__top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__datewrap {
        min-width: 0;
    }

    &__eyebrow {
        display: block;
        font-family: var(--font-serif);
        font-size: 20rpx;
        letter-spacing: 4rpx;
        color: var(--c-brand-soft);
    }

    &__date {
        display: block;
        margin-top: 12rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-number);
        line-height: var(--lh-none);
        letter-spacing: 1rpx;
    }

    &__week {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        letter-spacing: 1rpx;
        color: var(--c-on-dark-2);
    }

    &__name {
        position: relative;
        z-index: 1;
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: 1.35;
    }

    &__meta {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10rpx;
        margin-top: 10rpx;
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__host {
        flex: none;
        max-width: 220rpx;
    }

    &__dot {
        flex: none;
        color: var(--c-on-dark-2);
    }

    &__city {
        flex: 1;
        min-width: 0;
    }

    &__lock {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10rpx;
        margin-top: var(--sp-3);
        padding: 20rpx 0;
        border-top: 1rpx solid rgba(255, 255, 255, 0.12);
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    /* 失败/超时只换文字色，不换整张卡的底色：摘要仍是事实，只是状态变了 */
    &__lock.is-failed {
        color: var(--c-danger-border);
    }
    &__lock.is-failed &__lock-ic {
        color: var(--c-danger-border);
    }
    &__lock.is-expired {
        color: var(--c-warning-on-dark);
    }
    &__lock.is-expired &__lock-ic {
        color: var(--c-warning-on-dark);
    }

    &__lock-ic {
        flex: none;
        color: var(--c-brand-soft);
    }

    &__lock-text {
        flex: 1;
        min-width: 0;
        line-height: var(--lh-body);
    }

    &__edit {
        flex: none;
        padding: 16rpx 0;
        margin: -16rpx 0;
        color: var(--c-on-dark);
        text-decoration: underline;
        text-underline-offset: 3rpx;
    }
}

/* ---- 通用行 ---- */
.cf-row {
    display: flex;
    align-items: baseline;
    gap: var(--sp-3);
    padding: 12rpx 0;
    font-size: var(--fs-md);

    &__k {
        flex: none;
        min-width: 112rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        color: var(--c-ink);
    }

    &__m {
        flex: none;
        font-family: var(--font-serif);
        color: var(--c-ink);
    }
}

.cf-divider {
    height: 1rpx;
    margin: var(--sp-2) 0;
    background: var(--c-line);
}

/* ---- 人员 ---- */
.cf-staff {
    display: flex;
    align-items: center;
    gap: 18rpx;
    padding: 10rpx 0 6rpx;

    &__avatar {
        flex: none;
        width: 60rpx;
        height: 60rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);

        &--blank {
            border: 1rpx solid var(--c-line);
        }
    }

    &__body {
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__meta {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cf-note {
    display: block;
    margin-top: 12rpx;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

/* 校验红字：贴在块内最后一行，告诉用户「这块缺什么」 */
.cf-block-err {
    display: block;
    margin-top: 12rpx;
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);
}

/* ---- 附加项 ---- */
.cf-line {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: 10rpx 0;
    font-size: var(--fs-md);

    &__n {
        flex: 1;
        min-width: 0;
        color: var(--c-ink-2);
    }

    &__m {
        flex: none;
        font-family: var(--font-serif);
        color: var(--c-ink);
    }

    &--sub {
        margin-top: 6rpx;
        padding-top: 16rpx;
        border-top: 1rpx solid var(--c-line);
    }

    &--sub &__n {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.cf-empty-line {
    display: block;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.cf-upsell {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-top: 18rpx;
    padding: 14rpx 16rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__t {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__btn {
        flex: none;
        display: flex;
        align-items: center;
        height: 52rpx;
        padding: 0 18rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
    }

    &__btn-text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }
}

/* ---- 联系与地址 ---- */
.cf-value {
    display: block;
    font-size: var(--fs-md);
    font-weight: var(--fw-medium);
    line-height: var(--lh-body);
    color: var(--c-ink);

    &.is-missing {
        font-weight: var(--fw-regular);
        color: var(--c-danger);
    }

    &--addr {
        margin-top: 6rpx;
        font-weight: var(--fw-regular);
    }
}

.cf-region {
    display: flex;
    align-items: center;
    gap: 12rpx;
    flex-wrap: wrap;

    &__text {
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__badge {
        flex: none;
        height: 40rpx;
        padding: 0 10rpx;
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        border: 1rpx solid var(--c-primary-border);
        color: var(--c-brand-text);
        font-size: var(--fs-xs);
        line-height: 38rpx;
    }
}

/* ---- 价格 ---- */
.cf-total {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: var(--sp-3);
    margin-top: 12rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid var(--c-line);

    &__k {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__m {
        font-family: var(--font-serif);
        font-size: var(--fs-40);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
    }
}

.cf-sk {
    padding: 8rpx 0 4rpx;

    &__row {
        width: 62%;
        height: 28rpx;
        margin: 16rpx 0;
        border-radius: var(--r-xs);
        background: var(--c-surface-2);
    }

    &__row--total {
        width: 40%;
        height: 40rpx;
        margin: 26rpx 0 20rpx auto;
    }
}

.cf-foot {
    display: flex;
    justify-content: flex-end;
    margin-top: 18rpx;
}

/* ---- 支付时间轴 ---- */
.cf-pay {
    position: relative;
    padding-left: 34rpx;

    /* 竖线只连到第二个节点圆点上沿，不在末尾拖一截 */
    &::before {
        content: '';
        position: absolute;
        left: 8rpx;
        top: 18rpx;
        bottom: 44rpx;
        width: 1rpx;
        background: var(--c-line-strong);
    }

    &__step {
        position: relative;
        padding: 6rpx 0 28rpx;

        &:last-child {
            padding-bottom: 0;
        }
    }

    &__dot {
        position: absolute;
        left: -34rpx;
        top: 14rpx;
        width: 18rpx;
        height: 18rpx;
        border-radius: var(--r-pill);
        border: 3rpx solid var(--c-line-strong);
        background: var(--c-surface);
        box-sizing: border-box;
    }

    &__step.is-now &__dot {
        border-color: var(--c-ink);
        background: var(--c-ink);
    }

    &__top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__amt {
        flex: none;
        font-family: var(--font-serif);
        font-size: 32rpx;
        font-weight: var(--fw-semibold);
    }

    &__sub {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* ---- 内联提示：danger 阻断 / warning 提醒 ---- */
.cf-alert {
    display: flex;
    align-items: flex-start;
    gap: 14rpx;
    margin-top: 24rpx;
    padding: 22rpx 24rpx;
    border-radius: var(--r-sm);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);

    &--danger {
        background: var(--c-danger-bg);
        border: 1rpx solid var(--c-danger-border);
        color: var(--c-danger);
    }

    &--warning {
        background: var(--c-warning-bg);
        border: 1rpx solid var(--c-warning-border);
        color: var(--c-warning);
    }

    &__ic {
        flex: none;
        margin-top: 2rpx;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        margin-bottom: 4rpx;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
    }

    &__text,
    &__line {
        display: block;
    }

    &__btn {
        flex: none;
        align-self: center;
        display: flex;
        align-items: center;
        height: 56rpx;
        padding: 0 20rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid currentColor;
    }

    &__btn-text {
        font-size: var(--fs-xs);
        white-space: nowrap;
    }
}

.cf-agree {
    margin-top: 28rpx;
    padding-bottom: var(--sp-2);
}

/* ---- 吸底：label 一行 + 金额/合计一行，不写第三行 ---- */
.abp {
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    min-width: 0;

    &__label {
        font-size: var(--fs-xs);
        letter-spacing: 1rpx;
        color: var(--c-ink-3);
    }

    &__row {
        display: flex;
        align-items: baseline;
        gap: 12rpx;
        min-width: 0;
    }

    &__na {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__hint {
        overflow: hidden;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.abtns {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

/* ---- 弹层内：地区只读行 / 备注 ---- */
.reg {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);
    padding: 20rpx 24rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__main {
        min-width: 0;
    }

    &__k {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__a {
        flex: none;
        padding: 16rpx 0;
        margin: -16rpx 0;
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.pop__hint {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.ta {
    width: 100%;
    height: 180rpx;
    padding: 20rpx 24rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    font-size: var(--fs-md);
    line-height: var(--lh-body);
    color: var(--c-ink);
    box-sizing: border-box;
}

.ta__count {
    display: block;
    margin-top: 10rpx;
    text-align: right;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
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
        flex: none;
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
        flex: none;
    }
}

/* 日期 + 地区弹层：整块限高，内容区自己滚，标题与按钮常驻 —— 不再出现「弹层被裁掉」 */
.pop--picker {
    display: flex;
    flex-direction: column;
    max-height: 82vh;
}

.pop__scroll {
    /* 不能用 flex:1：弹层高度是内容撑开的（没有定高），flex 会把滚动区压成一条缝。
       这里按内容自然高度走，超过 62vh 才在内部滚动。 */
    flex: none;
    max-height: 62vh;
}

.pop__foot {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);
}

.pop__tip {
    font-size: var(--fs-18);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

/* ---- 自绘月历 ---- */
.dcal {
    padding: var(--sp-3) var(--sp-4) 0;
}

.dcal__nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
}

.dcal__nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72rpx;
    height: 72rpx;
    border-radius: var(--r-pill);
    color: var(--c-ink-2);

    &.is-disabled {
        color: var(--c-control-border);
    }
}

.dcal__nav-title {
    flex: 1;
    text-align: center;
    font-size: var(--fs-24);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
}

.dcal__week {
    display: flex;
    margin-top: var(--sp-2);
}

.dcal__week-item {
    flex: 1;
    text-align: center;
    font-size: var(--fs-18);
    color: var(--c-ink-3);
}

.dcal__grid {
    display: flex;
    flex-wrap: wrap;
    margin-top: 8rpx;
}

.dcal__cell {
    width: calc(100% / 7);
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-blank {
        visibility: hidden;
    }

    &.is-disabled .dcal__day {
        color: var(--c-control-border);
        text-decoration: line-through;
    }

    &.is-today .dcal__day {
        color: var(--c-brand-text);
    }

    &.is-on {
        .dcal__day {
            width: 56rpx;
            height: 56rpx;
            line-height: 56rpx;
            border-radius: var(--r-pill);
            background: var(--c-ink);
            color: var(--c-on-dark);
        }
    }
}

.dcal__day {
    display: block;
    width: 56rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    font-size: var(--fs-22);
    color: var(--c-ink);
}

/* ---- 地区三栏：控件在 components/region-columns，这里只给外边距 ---- */
.pop__region {
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-lock {
    color: var(--c-warning);
}
.ic-lock.is-on {
    color: var(--c-brand-text);
}
</style>
