<template>
    <div class="pro-designer-panel">
        <el-tabs
            class="pdp-tabs"
            :model-value="activeTab"
            @update:model-value="emit('update:activeTab', $event as ProPanelTab)"
        >
            <!-- 属性 -->
            <el-tab-pane label="属性" name="props">
                <el-empty
                    v-if="!activeLayer"
                    description="在左侧画布或「图层」面板选择一个元素"
                    :image-size="72"
                />
                <template v-else>
                    <div class="pdp-layer-head">
                        <span class="pdp-layer-name">{{ getFriendlyLayerLabel(activeLayer) }}</span>
                        <el-tag size="small" type="info">{{ getLayerTypeLabel(activeLayer.type) }}</el-tag>
                    </div>

                    <!-- 位置尺寸与对齐（repeater 不显示） -->
                    <template v-if="activeLayer.type !== 'repeater'">
                        <div class="pdp-section-title">位置尺寸与对齐</div>
                        <div class="pdp-grid pdp-grid-2">
                            <div class="pdp-field">
                                <span class="pdp-field-label">X</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :model-value="activeLayer.x"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('x', value)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">Y</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :model-value="activeLayer.y"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('y', value)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">宽</span>
                                <el-input-number
                                    size="small"
                                    :min="activeLayer.type === 'qrcode' ? qrcodeMinSize : 1"
                                    :model-value="activeLayer.w"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('w', value)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">高</span>
                                <el-input-number
                                    size="small"
                                    :min="activeLayer.type === 'line' ? 0 : activeLayer.type === 'qrcode' ? qrcodeMinSize : 1"
                                    :model-value="activeLayer.h"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('h', value)"
                                />
                            </div>
                        </div>
                        <div class="pdp-align-row">
                            <el-button
                                v-for="action in posterAlignShortcutActions"
                                :key="action.key"
                                size="small"
                                :title="action.label"
                                :disabled="activeLayer.locked === 1"
                                @click="handleAlignShortcut(action.key)"
                            >
                                <icon :name="action.icon" :size="15" />
                            </el-button>
                        </div>
                        <div class="pdp-grid pdp-grid-3">
                            <div class="pdp-field">
                                <span class="pdp-field-label">层级</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="99"
                                    :model-value="activeLayer.z"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('z', value)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">旋转</span>
                                <el-input-number
                                    size="small"
                                    :min="-360"
                                    :max="360"
                                    :model-value="activeLayer.rotate"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('rotate', value)"
                                />
                            </div>
                            <div v-if="activeLayer.type !== 'qrcode'" class="pdp-field">
                                <span class="pdp-field-label">不透明</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="1"
                                    :step="0.05"
                                    :model-value="activeLayer.opacity"
                                    @update:model-value="(value: number | undefined) => handleGeomChange('opacity', value)"
                                />
                            </div>
                        </div>
                    </template>

                    <!-- text -->
                    <el-collapse v-if="activeLayer.type === 'text'" class="pdp-collapse">
                        <el-collapse-item title="内容" name="content">
                            <el-input
                                type="textarea"
                                :rows="3"
                                :model-value="activeLayer.text || ''"
                                @update:model-value="(value: string) => handleFieldChange('text', value)"
                            />
                            <div class="pdp-chip-row">
                                <el-tag
                                    v-for="field in allDynamicFields"
                                    :key="field.token"
                                    class="pdp-chip"
                                    size="small"
                                    effect="plain"
                                    :title="field.desc || field.label"
                                    @click="insertToken(field.token)"
                                >
                                    {{ field.label }}
                                </el-tag>
                            </div>
                        </el-collapse-item>
                        <el-collapse-item title="艺术字样式" name="preset">
                            <div class="pdp-chip-row">
                                <el-button
                                    v-for="preset in posterTextArtPresets"
                                    :key="preset.value"
                                    size="small"
                                    round
                                    @click="applyTextPreset(preset)"
                                >
                                    {{ preset.label }}
                                </el-button>
                            </div>
                        </el-collapse-item>
                        <el-collapse-item title="文字样式" name="style">
                            <div class="pdp-grid pdp-grid-2">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">字号</span>
                                    <el-input-number
                                        size="small"
                                        :min="8"
                                        :max="200"
                                        :model-value="activeLayer.fontSize || 24"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('fontSize', value ?? 24)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">字重</span>
                                    <el-select
                                        size="small"
                                        :model-value="activeLayer.fontWeight || '400'"
                                        @update:model-value="(value: string) => handleFieldChange('fontWeight', value)"
                                    >
                                        <el-option
                                            v-for="weight in fontWeightOptions"
                                            :key="weight"
                                            :label="weight"
                                            :value="weight"
                                        />
                                    </el-select>
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">颜色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeLayer.color || '#FFFFFF'"
                                        @update:model-value="(value: string | null) => handleFieldChange('color', value || '#FFFFFF')"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">对齐</span>
                                    <div class="pdp-inline-btns">
                                        <el-button
                                            v-for="action in posterTextAlignActions"
                                            :key="action.key"
                                            size="small"
                                            :type="(activeLayer.align || 'center') === action.key ? 'primary' : 'default'"
                                            :title="action.label"
                                            @click="handleFieldChange('align', action.key)"
                                        >
                                            <icon :name="action.icon" :size="15" />
                                        </el-button>
                                    </div>
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">行高</span>
                                    <el-input-number
                                        size="small"
                                        :min="0.8"
                                        :max="3"
                                        :step="0.05"
                                        :model-value="activeLayer.lineHeight || 1.15"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('lineHeight', value ?? 1.15)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">字间距</span>
                                    <el-input-number
                                        size="small"
                                        :min="-10"
                                        :max="50"
                                        :model-value="activeLayer.letterSpacing || 0"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('letterSpacing', value ?? 0)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">横向缩放</span>
                                    <el-input-number
                                        size="small"
                                        :min="0.1"
                                        :max="3"
                                        :step="0.05"
                                        :model-value="activeLayer.scaleX || 1"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('scaleX', value ?? 1)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">纵向缩放</span>
                                    <el-input-number
                                        size="small"
                                        :min="0.1"
                                        :max="3"
                                        :step="0.05"
                                        :model-value="activeLayer.scaleY || 1"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('scaleY', value ?? 1)"
                                    />
                                </div>
                            </div>
                        </el-collapse-item>
                        <el-collapse-item title="填充" name="fill">
                            <div class="pdp-field">
                                <span class="pdp-field-label">填充类型</span>
                                <el-radio-group
                                    size="small"
                                    :model-value="activeLayer.fillType || 'solid'"
                                    @update:model-value="(value: string | number | boolean | undefined) => handleFieldChange('fillType', value as DesignLayer['fillType'])"
                                >
                                    <el-radio-button value="solid">纯色</el-radio-button>
                                    <el-radio-button value="linear">渐变</el-radio-button>
                                    <el-radio-button value="image">图片</el-radio-button>
                                </el-radio-group>
                            </div>
                            <div v-if="(activeLayer.fillType || 'solid') === 'solid'" class="pdp-field">
                                <span class="pdp-field-label">填充色</span>
                                <el-color-picker
                                    size="small"
                                    :model-value="activeLayer.color || '#FFFFFF'"
                                    @update:model-value="(value: string | null) => handleFieldChange('color', value || '#FFFFFF')"
                                />
                            </div>
                            <template v-if="(activeLayer.fillType || 'solid') === 'linear'">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">起始色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeLayer.gradientFrom || '#FFFFFF'"
                                        @update:model-value="(value: string | null) => handleFieldChange('gradientFrom', value || '#FFFFFF')"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">结束色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeLayer.gradientTo || '#FFFFFF'"
                                        @update:model-value="(value: string | null) => handleFieldChange('gradientTo', value || '#FFFFFF')"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">角度</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="360"
                                        :model-value="activeLayer.gradientAngle ?? 90"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('gradientAngle', value ?? 90)"
                                    />
                                </div>
                            </template>
                            <template v-if="activeLayer.fillType === 'image'">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">填充图</span>
                                    <MaterialPicker
                                        :model-value="activeLayer.fillImage || ''"
                                        :limit="1"
                                        @update:model-value="(value: any) => handleFillImageChange(value)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">填充方式</span>
                                    <el-radio-group
                                        size="small"
                                        :model-value="activeLayer.fillImageFit || 'cover'"
                                        @update:model-value="(value: string | number | boolean | undefined) => handleFieldChange('fillImageFit', value as DesignLayer['fillImageFit'])"
                                    >
                                        <el-radio-button value="cover">裁剪填充</el-radio-button>
                                        <el-radio-button value="contain">完整显示</el-radio-button>
                                        <el-radio-button value="stretch">拉伸</el-radio-button>
                                    </el-radio-group>
                                </div>
                            </template>
                        </el-collapse-item>
                        <el-collapse-item title="描边与阴影" name="stroke">
                            <div class="pdp-grid pdp-grid-2">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">描边色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeLayer.textStrokeColor || '#000000'"
                                        @update:model-value="(value: string | null) => handleFieldChange('textStrokeColor', value || '#000000')"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">描边宽</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="20"
                                        :model-value="activeLayer.textStrokeWidth || 0"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('textStrokeWidth', value ?? 0)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">描边透明</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="1"
                                        :step="0.05"
                                        :model-value="activeLayer.textStrokeOpacity ?? 1"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('textStrokeOpacity', value ?? 1)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">阴影色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeLayer.shadowColor || '#000000'"
                                        @update:model-value="(value: string | null) => handleFieldChange('shadowColor', value || '#000000')"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">阴影模糊</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="60"
                                        :model-value="activeLayer.shadowBlur || 0"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('shadowBlur', value ?? 0)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">偏移 X</span>
                                    <el-input-number
                                        size="small"
                                        :min="-100"
                                        :max="100"
                                        :model-value="activeLayer.shadowOffsetX || 0"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('shadowOffsetX', value ?? 0)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">偏移 Y</span>
                                    <el-input-number
                                        size="small"
                                        :min="-100"
                                        :max="100"
                                        :model-value="activeLayer.shadowOffsetY || 0"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('shadowOffsetY', value ?? 0)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">阴影透明</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="1"
                                        :step="0.05"
                                        :model-value="activeLayer.shadowOpacity ?? 0.35"
                                        @update:model-value="(value: number | undefined) => handleFieldChange('shadowOpacity', value ?? 0.35)"
                                    />
                                </div>
                            </div>
                        </el-collapse-item>
                    </el-collapse>

                    <!-- image -->
                    <template v-if="activeLayer.type === 'image'">
                        <div class="pdp-section-title">图片</div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">替换图片</span>
                            <MaterialPicker
                                :model-value="activeLayer.src || ''"
                                :limit="1"
                                @update:model-value="(value: any) => handleLayerImageChange(value)"
                            />
                        </div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">填充方式</span>
                            <el-radio-group
                                size="small"
                                :model-value="activeLayer.fit || 'cover'"
                                @update:model-value="(value: string | number | boolean | undefined) => handleFieldChange('fit', value as DesignLayer['fit'])"
                            >
                                <el-radio-button value="cover">裁剪填充</el-radio-button>
                                <el-radio-button value="contain">完整显示</el-radio-button>
                            </el-radio-group>
                        </div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">底色</span>
                            <el-color-picker
                                size="small"
                                :model-value="activeLayer.backgroundFill || 'transparent'"
                                @update:model-value="(value: string | null) => handleFieldChange('backgroundFill', value || 'transparent')"
                            />
                        </div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">圆角</span>
                            <el-input-number
                                size="small"
                                :min="0"
                                :max="500"
                                :model-value="activeLayer.radius || 0"
                                @update:model-value="(value: number | undefined) => handleFieldChange('radius', value ?? 0)"
                            />
                        </div>
                    </template>

                    <!-- qrcode -->
                    <template v-if="activeLayer.type === 'qrcode'">
                        <div class="pdp-section-title">系统统一二维码</div>
                        <div class="pdp-tip">小程序码由系统统一生成，尺寸不小于 {{ qrcodeMinSize }}px。</div>
                        <div class="pdp-grid pdp-grid-2">
                            <div class="pdp-field">
                                <span class="pdp-field-label">内边距</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="100"
                                    :model-value="activeLayer.padding ?? 0"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('padding', value ?? 0)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">圆角</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="200"
                                    :model-value="activeLayer.radius || 0"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('radius', value ?? 0)"
                                />
                            </div>
                        </div>
                    </template>

                    <!-- rect / line -->
                    <template v-if="activeLayer.type === 'rect' || activeLayer.type === 'line'">
                        <div class="pdp-section-title">{{ activeLayer.type === 'rect' ? '矩形样式' : '线条样式' }}</div>
                        <div v-if="activeLayer.type === 'rect'" class="pdp-field">
                            <span class="pdp-field-label">填充色</span>
                            <el-color-picker
                                size="small"
                                :model-value="activeLayer.fill || '#FFFFFF'"
                                @update:model-value="(value: string | null) => handleFieldChange('fill', value || '#FFFFFF')"
                            />
                        </div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">描边色</span>
                            <el-color-picker
                                size="small"
                                :model-value="activeLayer.stroke || ''"
                                @update:model-value="(value: string | null) => handleFieldChange('stroke', value || '')"
                            />
                        </div>
                        <div class="pdp-field">
                            <span class="pdp-field-label">线宽</span>
                            <el-input-number
                                size="small"
                                :min="0"
                                :max="50"
                                :model-value="activeLayer.strokeWidth || 0"
                                @update:model-value="(value: number | undefined) => handleFieldChange('strokeWidth', value ?? 0)"
                            />
                        </div>
                        <div v-if="activeLayer.type === 'rect'" class="pdp-field">
                            <span class="pdp-field-label">圆角</span>
                            <el-input-number
                                size="small"
                                :min="0"
                                :max="500"
                                :model-value="activeLayer.radius || 0"
                                @update:model-value="(value: number | undefined) => handleFieldChange('radius', value ?? 0)"
                            />
                        </div>
                    </template>

                    <!-- repeater -->
                    <template v-if="activeLayer.type === 'repeater'">
                        <div class="pdp-section-title">数据源与布局</div>
                        <div class="pdp-grid pdp-grid-2">
                            <div class="pdp-field">
                                <span class="pdp-field-label">数据源</span>
                                <el-select
                                    size="small"
                                    :model-value="activeLayer.source || 'ranking'"
                                    @update:model-value="(value: string) => handleFieldChange('source', value as DesignLayer['source'])"
                                >
                                    <el-option label="执行榜" value="ranking" />
                                    <el-option label="单王" value="top" />
                                </el-select>
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">列数</span>
                                <el-input-number
                                    size="small"
                                    :min="1"
                                    :max="6"
                                    :model-value="activeLayer.columns || 1"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('columns', value ?? 1)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">限制人数</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="120"
                                    :model-value="activeLayer.limit ?? 0"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('limit', value ?? 0)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">卡片宽</span>
                                <el-input-number
                                    size="small"
                                    :min="40"
                                    :max="1000"
                                    :model-value="activeLayer.card_width || 120"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('card_width', value ?? 120)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">卡片高</span>
                                <el-input-number
                                    size="small"
                                    :min="40"
                                    :max="1000"
                                    :model-value="activeLayer.card_height || 160"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('card_height', value ?? 160)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">横向间距</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="200"
                                    :model-value="activeLayer.gap_x || 0"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('gap_x', value ?? 0)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">纵向间距</span>
                                <el-input-number
                                    size="small"
                                    :min="0"
                                    :max="200"
                                    :model-value="activeLayer.gap_y || 0"
                                    @update:model-value="(value: number | undefined) => handleFieldChange('gap_y', value ?? 0)"
                                />
                            </div>
                            <div class="pdp-field">
                                <span class="pdp-field-label">排列</span>
                                <el-radio-group
                                    size="small"
                                    :model-value="activeLayer.align || 'left'"
                                    @update:model-value="(value: string | number | boolean | undefined) => handleFieldChange('align', String(value || 'left'))"
                                >
                                    <el-radio-button value="left">居左</el-radio-button>
                                    <el-radio-button value="center">居中</el-radio-button>
                                    <el-radio-button value="right">居右</el-radio-button>
                                </el-radio-group>
                            </div>
                        </div>

                        <div class="pdp-section-title">卡片子图层</div>
                        <div class="pdp-inline-btns">
                            <el-button
                                v-for="option in cardLayerAddOptions"
                                :key="option.type"
                                size="small"
                                plain
                                @click="addCardLayer(option.type)"
                            >
                                <icon :name="option.icon" :size="15" />
                                {{ option.label }}
                            </el-button>
                        </div>
                        <div class="pdp-card-layer-list">
                            <div
                                v-for="sub in cardLayers"
                                :key="sub.id"
                                class="pdp-card-layer-row"
                                :class="{ 'is-active': sub.id === activeCardLayerId }"
                                @click="activeCardLayerId = sub.id"
                            >
                                <span class="pdp-card-layer-name">{{ getFriendlyLayerLabel(sub) }}</span>
                                <span class="pdp-card-layer-type">{{ getLayerTypeLabel(sub.type) }}</span>
                                <el-switch
                                    size="small"
                                    :model-value="sub.visible"
                                    :active-value="1"
                                    :inactive-value="0"
                                    @click.stop
                                    @update:model-value="(value: string | number | boolean) => updateCardLayer(sub.id, { visible: Number(value) ? 1 : 0 })"
                                />
                                <el-button
                                    size="small"
                                    link
                                    title="复制"
                                    @click.stop="duplicateCardLayer(sub.id)"
                                >
                                    <icon name="el-icon-CopyDocument" :size="15" />
                                </el-button>
                                <el-button
                                    size="small"
                                    link
                                    type="danger"
                                    title="删除"
                                    @click.stop="removeCardLayer(sub.id)"
                                >
                                    <icon name="el-icon-Delete" :size="15" />
                                </el-button>
                            </div>
                        </div>
                        <template v-if="activeCardLayer">
                            <div class="pdp-grid pdp-grid-2">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">X</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :model-value="activeCardLayer.x"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { x: value ?? 0 })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">Y</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :model-value="activeCardLayer.y"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { y: value ?? 0 })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">宽</span>
                                    <el-input-number
                                        size="small"
                                        :min="1"
                                        :model-value="activeCardLayer.w"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { w: value ?? 1 })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">高</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :model-value="activeCardLayer.h"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { h: value ?? 0 })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">不透明</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="1"
                                        :step="0.05"
                                        :model-value="activeCardLayer.opacity"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { opacity: value ?? 1 })"
                                    />
                                </div>
                            </div>
                            <template v-if="activeCardLayer.type === 'text'">
                                <el-input
                                    type="textarea"
                                    :rows="2"
                                    :model-value="activeCardLayer.text || ''"
                                    @update:model-value="(value: string) => updateCardLayer(activeCardLayer!.id, { text: value })"
                                />
                                <div class="pdp-chip-row">
                                    <el-tag
                                        v-for="field in MONTHLY_CARD_FIELDS"
                                        :key="field.token"
                                        class="pdp-chip"
                                        size="small"
                                        effect="plain"
                                        :title="field.desc || field.label"
                                        @click="insertCardToken(activeCardLayer.id, field.token)"
                                    >
                                        {{ field.label }}
                                    </el-tag>
                                </div>
                                <div class="pdp-grid pdp-grid-2">
                                    <div class="pdp-field">
                                        <span class="pdp-field-label">字号</span>
                                        <el-input-number
                                            size="small"
                                            :min="8"
                                            :max="120"
                                            :model-value="activeCardLayer.fontSize || 20"
                                            @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { fontSize: value ?? 20 })"
                                        />
                                    </div>
                                    <div class="pdp-field">
                                        <span class="pdp-field-label">颜色</span>
                                        <el-color-picker
                                            size="small"
                                            :model-value="activeCardLayer.color || '#FFFFFF'"
                                            @update:model-value="(value: string | null) => updateCardLayer(activeCardLayer!.id, { color: value || '#FFFFFF' })"
                                        />
                                    </div>
                                </div>
                            </template>
                            <template v-if="activeCardLayer.type === 'image'">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">图片</span>
                                    <MaterialPicker
                                        :model-value="activeCardLayer.src || ''"
                                        :limit="1"
                                        @update:model-value="(value: any) => handleCardImageChange(activeCardLayer!.id, value)"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">圆角</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="500"
                                        :model-value="activeCardLayer.radius || 0"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { radius: value ?? 0 })"
                                    />
                                </div>
                            </template>
                            <template v-if="activeCardLayer.type === 'rect'">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">填充色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeCardLayer.fill || '#FFFFFF'"
                                        @update:model-value="(value: string | null) => updateCardLayer(activeCardLayer!.id, { fill: value || '#FFFFFF' })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">圆角</span>
                                    <el-input-number
                                        size="small"
                                        :min="0"
                                        :max="500"
                                        :model-value="activeCardLayer.radius || 0"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { radius: value ?? 0 })"
                                    />
                                </div>
                            </template>
                            <template v-if="activeCardLayer.type === 'line'">
                                <div class="pdp-field">
                                    <span class="pdp-field-label">线条色</span>
                                    <el-color-picker
                                        size="small"
                                        :model-value="activeCardLayer.stroke || '#FFFFFF'"
                                        @update:model-value="(value: string | null) => updateCardLayer(activeCardLayer!.id, { stroke: value || '#FFFFFF' })"
                                    />
                                </div>
                                <div class="pdp-field">
                                    <span class="pdp-field-label">线宽</span>
                                    <el-input-number
                                        size="small"
                                        :min="1"
                                        :max="50"
                                        :model-value="activeCardLayer.strokeWidth || 2"
                                        @update:model-value="(value: number | undefined) => updateCardLayer(activeCardLayer!.id, { strokeWidth: value ?? 2 })"
                                    />
                                </div>
                            </template>
                        </template>
                    </template>
                </template>
            </el-tab-pane>

            <!-- 图层 -->
            <el-tab-pane label="图层" name="layers">
                <div class="pdp-layer-list">
                    <div
                        v-for="layer in sortedLayers"
                        :key="layer.id"
                        class="pdp-layer-row"
                        :class="{ 'is-active': layer.id === activeLayerId }"
                        @click="handleSelectLayer(layer.id)"
                    >
                        <span class="pdp-layer-row-name">{{ getFriendlyLayerLabel(layer) }}</span>
                        <el-tag size="small" type="info" effect="plain">{{ getLayerTypeLabel(layer.type) }}</el-tag>
                        <el-switch
                            size="small"
                            :model-value="layer.locked"
                            :active-value="1"
                            :inactive-value="0"
                            title="锁定"
                            @click.stop
                            @update:model-value="(value: string | number | boolean) => toggleLayerLock(layer.id, value)"
                        />
                        <el-switch
                            size="small"
                            :model-value="layer.type === 'qrcode' ? 1 : layer.visible"
                            :active-value="1"
                            :inactive-value="0"
                            :disabled="layer.type === 'qrcode'"
                            title="显示"
                            @click.stop
                            @update:model-value="(value: string | number | boolean) => toggleLayerVisible(layer.id, value)"
                        />
                    </div>
                </div>
                <div class="pdp-layer-actions">
                    <el-button
                        size="small"
                        :disabled="!activeLayer || activeLayer.type === 'qrcode'"
                        @click="duplicateLayer"
                    >
                        <icon name="el-icon-CopyDocument" :size="15" />
                        复制
                    </el-button>
                    <el-button
                        size="small"
                        :disabled="!activeLayer"
                        @click="moveLayer(1)"
                    >
                        <icon name="el-icon-Top" :size="15" />
                        上移层级
                    </el-button>
                    <el-button
                        size="small"
                        :disabled="!activeLayer"
                        @click="moveLayer(-1)"
                    >
                        <icon name="el-icon-Bottom" :size="15" />
                        下移层级
                    </el-button>
                    <el-button
                        size="small"
                        type="danger"
                        plain
                        :disabled="!activeLayer || activeLayer.type === 'qrcode'"
                        @click="removeLayer"
                    >
                        <icon name="el-icon-Delete" :size="15" />
                        删除
                    </el-button>
                </div>
            </el-tab-pane>

            <!-- 画布 -->
            <el-tab-pane label="画布" name="canvas">
                <div class="pdp-section-title">画布尺寸</div>
                <div class="pdp-grid pdp-grid-2">
                    <div class="pdp-field">
                        <span class="pdp-field-label">宽</span>
                        <el-input-number
                            size="small"
                            :min="320"
                            :max="2160"
                            :model-value="design.canvas.width"
                            @update:model-value="(value: number | undefined) => updateCanvas({ width: value ?? 320 })"
                        />
                    </div>
                    <div class="pdp-field">
                        <span class="pdp-field-label">高</span>
                        <el-input-number
                            size="small"
                            :min="480"
                            :max="5200"
                            :model-value="design.canvas.height"
                            @update:model-value="(value: number | undefined) => updateCanvas({ height: value ?? 480 })"
                        />
                    </div>
                </div>
                <template v-if="templateType === 'ranking'">
                    <div class="pdp-field">
                        <span class="pdp-field-label">自动高度</span>
                        <el-switch
                            :model-value="design.canvas.auto_height === 1"
                            @update:model-value="(value: string | number | boolean) => updateCanvas({ auto_height: value ? 1 : 0 })"
                        />
                    </div>
                    <div class="pdp-field">
                        <span class="pdp-field-label">最小高度</span>
                        <el-input-number
                            size="small"
                            :min="480"
                            :max="5200"
                            :model-value="design.canvas.min_height || 480"
                            @update:model-value="(value: number | undefined) => updateCanvas({ min_height: value ?? 480 })"
                        />
                    </div>
                </template>
                <div class="pdp-section-title">背景</div>
                <div class="pdp-field">
                    <span class="pdp-field-label">背景类型</span>
                    <el-radio-group
                        size="small"
                        :model-value="design.background.type"
                        @update:model-value="(value: string | number | boolean | undefined) => updateBackground({ type: (value as DesignBackground['type']) || 'color' })"
                    >
                        <el-radio-button value="color">纯色</el-radio-button>
                        <el-radio-button value="image">图片</el-radio-button>
                    </el-radio-group>
                </div>
                <div v-if="design.background.type === 'color'" class="pdp-field">
                    <span class="pdp-field-label">背景色</span>
                    <el-color-picker
                        size="small"
                        :model-value="design.background.color || '#FFFFFF'"
                        @update:model-value="(value: string | null) => updateBackground({ color: value || '#FFFFFF' })"
                    />
                </div>
                <template v-if="design.background.type === 'image'">
                    <div class="pdp-field">
                        <span class="pdp-field-label">背景图</span>
                        <MaterialPicker
                            :model-value="design.background.image || ''"
                            :limit="1"
                            @update:model-value="(value: any) => handleBackgroundImageChange(value)"
                        />
                    </div>
                    <div class="pdp-field">
                        <span class="pdp-field-label">填充方式</span>
                        <el-radio-group
                            size="small"
                            :model-value="design.background.fit || 'cover'"
                            @update:model-value="(value: string | number | boolean | undefined) => updateBackground({ fit: (value as DesignBackground['fit']) || 'cover' })"
                        >
                            <el-radio-button value="cover">裁剪填充</el-radio-button>
                            <el-radio-button value="contain">完整显示</el-radio-button>
                        </el-radio-group>
                    </div>
                </template>
                <div class="pdp-field">
                    <span class="pdp-field-label">背景透明度</span>
                    <el-input-number
                        size="small"
                        :min="0"
                        :max="1"
                        :step="0.05"
                        :model-value="design.background.opacity ?? 1"
                        @update:model-value="(value: number | undefined) => updateBackground({ opacity: value ?? 1 })"
                    />
                </div>
            </el-tab-pane>
        </el-tabs>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import MaterialPicker from '@/components/material/picker.vue'

import {
    type PosterLayerAlignAction,
    posterAlignShortcutActions,
    posterTextAlignActions,
    posterTextArtPresets,
} from '../poster-designer-common'
import {
    MONTHLY_CARD_FIELDS,
    MONTHLY_DYNAMIC_FIELDS,
    QRCODE_MIN_SIZE,
    cloneDesign,
    defaultLine,
    defaultRect,
    defaultText,
    normalizeCardLayer,
    normalizeUrl,
    uid,
} from './presets'
import { getFriendlyLayerLabel, getLayerTypeLabel } from './simple-adapter'
import type {
    DesignBackground,
    DesignCanvas,
    DesignConfig,
    DesignLayer,
    DynamicFieldOption,
    MonthlyReportTemplateType,
    ProPanelTab,
} from './types'

const props = withDefaults(
    defineProps<{
        design: DesignConfig
        templateType: MonthlyReportTemplateType
        activeLayerId: string
        activeTab: ProPanelTab
        extraDynamicFields?: DynamicFieldOption[]
    }>(),
    {
        extraDynamicFields: () => [],
    },
)

const emit = defineEmits<{
    (e: 'update:design', nextDesign: DesignConfig): void
    (e: 'update:activeTab', tab: ProPanelTab): void
    (e: 'select-layer', layerId: string): void
    (e: 'commit-history'): void
}>()

const allDynamicFields = computed(() => [...MONTHLY_DYNAMIC_FIELDS, ...(props.extraDynamicFields || [])])

const qrcodeMinSize = QRCODE_MIN_SIZE
const activeCardLayerId = ref('')
const fontWeightOptions = ['300', '400', '500', '600', '700', '800', '900']
const cardLayerAddOptions: Array<{ type: 'text' | 'image' | 'rect' | 'line'; label: string; icon: string }> = [
    { type: 'text', label: '文本', icon: 'el-icon-EditPen' },
    { type: 'image', label: '图片', icon: 'el-icon-Picture' },
    { type: 'rect', label: '矩形', icon: 'el-icon-Crop' },
    { type: 'line', label: '分割线', icon: 'el-icon-Minus' },
]

const activeLayer = computed<DesignLayer | null>(() => {
    return props.design.layers.find((layer) => layer.id === props.activeLayerId) || null
})

const sortedLayers = computed<DesignLayer[]>(() => {
    return [...props.design.layers].sort((a, b) => (b.z || 0) - (a.z || 0))
})

const cardLayers = computed<DesignLayer[]>(() => {
    if (!activeLayer.value || activeLayer.value.type !== 'repeater') return []
    return activeLayer.value.card_layers || []
})

const activeCardLayer = computed<DesignLayer | null>(() => {
    return cardLayers.value.find((layer) => layer.id === activeCardLayerId.value) || null
})

watch(
    () => props.activeLayerId,
    () => {
        activeCardLayerId.value = ''
    },
)

// ---------- 通用更新 ----------

function commit(next: DesignConfig): void {
    emit('update:design', next)
    emit('commit-history')
}

function updateLayer(layerId: string, patch: Partial<DesignLayer>): void {
    const next = cloneDesign(props.design)
    const target = next.layers.find((layer) => layer.id === layerId)
    if (!target) return
    Object.assign(target, patch)
    commit(next)
}

function handleFieldChange(field: keyof DesignLayer, value: any): void {
    const layer = activeLayer.value
    if (!layer) return
    updateLayer(layer.id, { [field]: value })
}

function handleGeomChange(field: 'x' | 'y' | 'w' | 'h' | 'z' | 'rotate' | 'opacity', value: number | undefined): void {
    const layer = activeLayer.value
    if (!layer) return
    let next = Number(value ?? 0)
    if (layer.type === 'qrcode' && (field === 'w' || field === 'h')) {
        next = Math.max(QRCODE_MIN_SIZE, next)
    }
    if (layer.type === 'line' && field === 'h') {
        next = Math.max(0, next)
    }
    updateLayer(layer.id, { [field]: next })
}

function handleAlignShortcut(action: PosterLayerAlignAction): void {
    const layer = activeLayer.value
    if (!layer || layer.locked === 1) return
    const canvasWidth = Number(props.design.canvas?.width || 0)
    const canvasHeight = Number(props.design.canvas?.height || 0)
    const effectiveHeight = layer.type === 'line' ? 0 : layer.h
    const patch: Partial<DesignLayer> = {}
    switch (action) {
        case 'left':
            patch.x = 0
            break
        case 'horizontalCenter':
            patch.x = Math.max(0, Math.round((canvasWidth - layer.w) / 2))
            break
        case 'right':
            patch.x = Math.max(0, canvasWidth - layer.w)
            break
        case 'top':
            patch.y = 0
            break
        case 'verticalCenter':
            patch.y = Math.max(0, Math.round((canvasHeight - effectiveHeight) / 2))
            break
        case 'bottom':
            patch.y = Math.max(0, canvasHeight - effectiveHeight)
            break
    }
    updateLayer(layer.id, patch)
}

function insertToken(token: string): void {
    const layer = activeLayer.value
    if (!layer || layer.type !== 'text') return
    updateLayer(layer.id, { text: `${layer.text || ''}${token}` })
}

function applyTextPreset(preset: (typeof posterTextArtPresets)[number]): void {
    const layer = activeLayer.value
    if (!layer || layer.type !== 'text') return
    updateLayer(layer.id, { ...cloneDesign(preset.config), artPreset: preset.value })
}

function handleFillImageChange(value: any): void {
    const layer = activeLayer.value
    if (!layer) return
    const url = normalizeUrl(value)
    updateLayer(layer.id, { fillImage: url, fillImageUrl: url })
}

function handleLayerImageChange(value: any): void {
    const layer = activeLayer.value
    if (!layer) return
    const url = normalizeUrl(value)
    updateLayer(layer.id, { src: url, src_url: url })
}

// ---------- 图层操作 ----------

function handleSelectLayer(layerId: string): void {
    emit('select-layer', layerId)
    emit('update:activeTab', 'props')
}

function toggleLayerLock(layerId: string, value: string | number | boolean): void {
    updateLayer(layerId, { locked: Number(value) ? 1 : 0 })
}

function toggleLayerVisible(layerId: string, value: string | number | boolean): void {
    const layer = props.design.layers.find((item) => item.id === layerId)
    if (!layer || layer.type === 'qrcode') return
    updateLayer(layerId, { visible: Number(value) ? 1 : 0 })
}

function duplicateLayer(): void {
    const layer = activeLayer.value
    if (!layer || layer.type === 'qrcode') return
    const next = cloneDesign(props.design)
    const source = next.layers.find((item) => item.id === layer.id)
    if (!source) return
    const copy: DesignLayer = cloneDesign(source)
    copy.id = uid(layer.type)
    copy.z = next.layers.reduce((max, item) => Math.max(max, item.z || 0), 0) + 1
    next.layers.push(copy)
    commit(next)
    emit('select-layer', copy.id)
}

function moveLayer(direction: number): void {
    const layer = activeLayer.value
    if (!layer) return
    updateLayer(layer.id, { z: Math.max(0, (layer.z || 0) + direction) })
}

function removeLayer(): void {
    const layer = activeLayer.value
    if (!layer || layer.type === 'qrcode') return
    const next = cloneDesign(props.design)
    next.layers = next.layers.filter((item) => item.id !== layer.id)
    commit(next)
    emit('select-layer', '')
}

// ---------- repeater 卡片子图层 ----------

function withRepeater(next: DesignConfig, callback: (repeater: DesignLayer) => void): void {
    const repeater = activeLayer.value
    if (!repeater || repeater.type !== 'repeater') return
    const target = next.layers.find((layer) => layer.id === repeater.id)
    if (!target) return
    if (!Array.isArray(target.card_layers)) {
        target.card_layers = []
    }
    callback(target)
    commit(next)
}

function updateCardLayer(subLayerId: string, patch: Partial<DesignLayer>): void {
    const next = cloneDesign(props.design)
    withRepeater(next, (repeater) => {
        const sub = (repeater.card_layers || []).find((layer) => layer.id === subLayerId)
        if (!sub) return
        Object.assign(sub, patch)
    })
}

function addCardLayer(type: 'text' | 'image' | 'rect' | 'line'): void {
    const repeater = activeLayer.value
    if (!repeater || repeater.type !== 'repeater') return
    const cardWidth = Number(repeater.card_width || 120)
    const cardHeight = Number(repeater.card_height || 160)
    const next = cloneDesign(props.design)
    withRepeater(next, (target) => {
        const layers = target.card_layers || []
        const nextZ = layers.reduce((max, layer) => Math.max(max, layer.z || 0), 0) + 1
        let created: DesignLayer
        if (type === 'text') {
            created = defaultText(uid('card_text'), 8, 8, cardWidth - 16, 40, nextZ, '文本', 20, '#FFFFFF')
        } else if (type === 'image') {
            created = normalizeCardLayer({
                id: uid('card_image'),
                type: 'image',
                x: 0,
                y: 0,
                w: cardWidth,
                h: cardHeight,
                z: nextZ,
                visible: 1,
                locked: 0,
                opacity: 1,
                rotate: 0,
                src: '',
                src_url: '',
                fit: 'cover',
                backgroundFill: 'transparent',
                radius: 0,
            })
        } else if (type === 'rect') {
            created = defaultRect(uid('card_rect'), 0, 0, cardWidth, cardHeight, nextZ, 'transparent', { radius: 0 })
        } else {
            created = defaultLine(uid('card_line'), 0, cardHeight - 1, cardWidth, nextZ, '#FFFFFF', 2)
        }
        layers.push(created)
        activeCardLayerId.value = created.id
    })
}

function duplicateCardLayer(subLayerId: string): void {
    const next = cloneDesign(props.design)
    withRepeater(next, (repeater) => {
        const layers = repeater.card_layers || []
        const source = layers.find((layer) => layer.id === subLayerId)
        if (!source) return
        const copy: DesignLayer = cloneDesign(normalizeCardLayer(source))
        copy.id = uid(`card_${source.type}`)
        copy.z = layers.reduce((max, layer) => Math.max(max, layer.z || 0), 0) + 1
        layers.push(copy)
        activeCardLayerId.value = copy.id
    })
}

function removeCardLayer(subLayerId: string): void {
    const next = cloneDesign(props.design)
    withRepeater(next, (repeater) => {
        repeater.card_layers = (repeater.card_layers || []).filter((layer) => layer.id !== subLayerId)
    })
    if (activeCardLayerId.value === subLayerId) {
        activeCardLayerId.value = ''
    }
}

function insertCardToken(subLayerId: string, token: string): void {
    const sub = activeCardLayer.value
    if (!sub || sub.id !== subLayerId || sub.type !== 'text') return
    updateCardLayer(subLayerId, { text: `${sub.text || ''}${token}` })
}

function handleCardImageChange(subLayerId: string, value: any): void {
    const url = normalizeUrl(value)
    updateCardLayer(subLayerId, { src: url, src_url: url })
}

// ---------- 画布与背景 ----------

function updateCanvas(patch: Partial<DesignCanvas>): void {
    const next = cloneDesign(props.design)
    Object.assign(next.canvas, patch)
    commit(next)
}

function updateBackground(patch: Partial<DesignBackground>): void {
    const next = cloneDesign(props.design)
    Object.assign(next.background, patch)
    commit(next)
}

function handleBackgroundImageChange(value: any): void {
    const url = normalizeUrl(value)
    updateBackground({ image: url, image_url: url })
}
</script>

<style scoped>
.pro-designer-panel {
    padding: 0 4px;
}

.pdp-layer-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.pdp-layer-name {
    font-weight: 600;
    font-size: 14px;
}

.pdp-section-title {
    margin: 12px 0 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--el-text-color-secondary);
}

.pdp-grid {
    display: grid;
    gap: 8px;
    margin-bottom: 8px;
}

.pdp-grid-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.pdp-grid-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.pdp-field {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.pdp-field > .el-input-number,
.pdp-field > .el-select {
    flex: 1;
    min-width: 0;
}

.pdp-field-label {
    flex-shrink: 0;
    width: 48px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.pdp-align-row {
    display: flex;
    gap: 4px;
    margin-bottom: 10px;
}

.pdp-align-row .el-button {
    flex: 1;
    padding: 8px 0;
}

.pdp-inline-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 8px;
}

.pdp-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}

.pdp-chip {
    cursor: pointer;
}

.pdp-collapse {
    border-top: none;
    margin-top: 8px;
}

.pdp-layer-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pdp-layer-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
}

.pdp-layer-row:hover {
    background: var(--el-fill-color-light);
}

.pdp-layer-row.is-active {
    background: var(--el-color-primary-light-9);
}

.pdp-layer-row-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
}

.pdp-layer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid var(--el-border-color-lighter);
}

.pdp-card-layer-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 10px;
}

.pdp-card-layer-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
}

.pdp-card-layer-row:hover {
    background: var(--el-fill-color-light);
}

.pdp-card-layer-row.is-active {
    background: var(--el-color-primary-light-9);
}

.pdp-card-layer-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
}

.pdp-card-layer-type {
    flex-shrink: 0;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.pdp-tip {
    margin-bottom: 8px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}
</style>
