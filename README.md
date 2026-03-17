<h1 align="center">婚庆服务预约管理系统</h1>
<h4 align="center">💒 婚庆服务 | 📅 档期管理 | 📱 小程序预约 | 💰 在线支付 | 📊 数据分析</h4>

<p align="center">
<a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.0-8892bf"></a>
<a href="https://www.tslang.cn/"><img src="https://img.shields.io/badge/TypeScript-5-294e80"></a>
<a href="#"><img src="https://img.shields.io/badge/ThinkPHP-8.0-6fb737"></a>
<a href="#"><img src="https://img.shields.io/badge/Vue.js-3.5-4eb883"></a>
<a href="#"><img src="https://img.shields.io/badge/Element Plus-2.9-409eff"></a>
<a href="https://uniapp.dcloud.io/"><img src="https://img.shields.io/badge/UniApp-3-d85806"></a>
<a href="https://vue3.tuniaokj.com/"><img src="https://img.shields.io/badge/TuniaoUI-图鸟UI-00d4ff"></a>
</p>

<p align="center">
<a href="https://mp.weixin.qq.com/"><img src="https://img.shields.io/badge/微信-小程序-05ce66"></a>
<a href="https://pay.weixin.qq.com/"><img src="https://img.shields.io/badge/微信-支付API3-05ce66"></a>
<a href="https://cloud.tencent.com/"><img src="https://img.shields.io/badge/腾讯云-COS-00a3ff"></a>
<a href="https://cloud.tencent.com/"><img src="https://img.shields.io/badge/腾讯云-短信-00a3ff"></a>
<a href="https://www.aliyun.com/"><img src="https://img.shields.io/badge/阿里云-OSS-ff6a00"></a>
<a href="https://www.aliyun.com/"><img src="https://img.shields.io/badge/阿里云-短信-ff6a00"></a>
</p>

## 📖 项目简介

基于 **LikeAdmin** 快速开发框架打造的婚庆服务预约管理系统，为婚庆公司提供完整的数字化解决方案。

### 🎯 核心价值

- **用户端**：便捷查看工作人员信息、档期，实现线上预约和支付
- **工作人员端**：移动化管理个人档期、订单、收益和作品展示
- **管理端**：PC端完整管理功能 + 小程序端移动数据看板
- **社区互动**：工作人员发布作品动态，用户观看、点赞、评论

## 🚀 技术架构

### 后端技术栈
- **框架**：ThinkPHP 8.0
- **语言**：PHP >= 8.0
- **数据库**：MySQL
- **缓存**：Redis
- **架构**：Controller → Logic → Service → Model

### 前端技术栈
- **管理后台**：Vue 3.5 + Element Plus + Vite
- **小程序端**：UniApp + TuniaoUI（图鸟UI）
- **PC前台**：Nuxt 3.6 + Element Plus
- **CSS**：Tailwind CSS + SCSS

### UI组件库
- **小程序端**：[TuniaoUI](https://vue3.tuniaokj.com) - 原生 Vue 3 支持，60+ 组件
- **管理后台**：Element Plus - Vue 3 官方推荐

## ✨ 核心功能

### 👥 用户角色

#### 普通用户（客户）
- 浏览人员信息、查看档期、购物车管理
- 在线预约下单、订单管理、支付、评价
- 动态社区互动、收藏管理

#### 工作人员
- 档期管理、订单管理（确认/拒绝）
- 动态发布与管理、个人资料与作品管理
- 收益统计与提现

#### 小程序管理员（移动端）
- 数据看板、订单监控、人员业绩
- 财务数据、内容审核、快捷操作

#### PC管理后台
- 综合数据看板、人员管理、订单管理、档期管理
- 动态管理、评价管理、财务管理、消息管理
- 用户管理、系统设置

### 📋 核心业务流程

#### 预约流程
```
浏览人员 → 查看档期 → 加入购物车 → 填写信息 → 
提交订单（待确认） → 工作人员确认 → 用户支付 → 服务履约 → 完成/评价
```

#### 订单状态流转
```
主链路：待确认 → 待支付 → 已支付 → 服务中 → 已完成 → 已评价
异常支路：已取消 / 已暂停 / 已退款
```

#### 支付方式
- **线上支付**：微信支付、余额支付、组合支付
- **线下支付**：用户上传凭证 → 管理员审核 → 通过后确认收款（拒绝则退回待支付）

## 🎨 功能模块

### 1. 人员展示
- 人员分类、列表、详情
- 筛选（价格、评分、档期）、排序
- 作品展示、评价展示、档期日历
- 收藏、加入购物车、立即预约、分享

### 2. 购物车
- 多人员组合预约
- 档期冲突检测
- 费用明细计算
- 批量操作管理
- **分享购物车**：支持分享给未婚伴侣/闺蜜，共同挑选人员、协同支付
- **保存方案**：支持保存多个预约方案，方案对比功能
- **预算计算器**：实时显示总价、优惠金额、实付金额

### 3. 订单管理
- 订单列表（按状态分类）
- 订单详情（完整信息、服务人员列表、费用明细）
- 支付流程（线上/线下支付、凭证上传审核）
- 退款流程（整单/部分退款、线上/线下退款）
- 订单评价（多维度评分、图文评价、匿名选项）
- **婚礼倒计时时间轴**：根据婚期自动生成任务清单
- **订单变更**：改期、换人、加项申请
- **订单暂停**：特殊情况延期处理
- **订单转让**：转让给他人

### 4. 档期管理
- 日历视图（月历展示、状态标识）
- 批量设置（批量设置档期、复制上月档期）
- 规则配置（提前预约天数、单日接单数、间隔时间）
- 档期统计（利用率、收入预估）
- **防重复接单**：分布式锁机制，防止超卖
- **备选方案推荐**：档期冲突时自动推荐同价位替代人员
- **档期锁定**：VIP客户优先锁定档期
- **档期共享**：多人员联合服务（摄影师+化妆师组合）

### 5. 动态社区
- 动态发布（图文/视频、话题标签、审核机制）
- 动态互动（点赞、评论、分享）
- 话题管理（话题创建、热门话题）
- 内容审核（敏感词过滤、违规处理）
- **模板化发片**：内置 9 宫格、对比图、横版封面模板
- **客片交付系统**：婚礼结束 7 天内自动创建"云相册"

### 6. 评价系统
- 多维度评分（综合评分、服务态度、专业水平、性价比）
- 图文评价（文字评价、图片上传、标签快选）
- 评价管理（审核、隐藏、删除、优质评价标记）
- 数据分析（评分分布、好评率、关键词分析）
- **评价风控**：管理员手动审核、敏感词过滤、工作人员申诉通道
- **视频评价**：15 秒竖屏口碑视频
- **评价有礼**：评价后自动发放积分
- **晒单奖励**：上传照片额外奖励积分

### 7. 财务管理
- 收入报表（订单收入、支付方式分析）
- 退款管理（退款审核、退款统计）
- 成本利润（成本统计、利润分析）
- 财务对账（对账报表、数据导出）

### 8. 增长运营
- 活动管理（档期活动、推荐专题）
- 积分系统（积分规则、积分兑换）
- 推荐有礼（推荐奖励、关系查询）
- **社交裂变**：请帖模板带小程序码，分享返佣

### 9. 合同与协议管理
- **合同模板管理**：合同模板创建与编辑、模板变量配置
- **合同归档与查询**：合同列表查询、合同详情查看、合同下载
- **违约条款自动提醒**：违约条款配置、自动计算违约金
- **合同到期提醒**：服务日期临近提醒、合同履约提醒

### 10. 客户跟进与CRM
- **客户意向等级**：A/B/C/D级客户分类、自动评级
- **跟进记录**：通话记录、到店记录、试妆记录
- **自动分配销售顾问**：按区域、负载均衡、专长匹配分配
- **客户流失预警**：长时间未跟进预警、流失风险评估
- **客户生命周期管理**：客户阶段标识、阶段自动流转

### 11. 现场服务管理
- **服务签到打卡**：工作人员到场签到（GPS定位）
- **现场照片实时上传**：服务过程照片上传、照片自动归档
- **服务进度实时同步**：服务节点打卡、进度实时推送
- **突发情况上报**：现场问题快速上报、紧急联系人通知
- **现场物料清单核对**：物料清单模板、出发前核对

### 12. 售后服务
- **售后工单系统**：工单创建、分类、分配、处理流程
- **投诉处理流程**：投诉提交、分级、处理时限、结果反馈
- **补拍/重拍申请**：补拍申请提交、审核流程、档期安排
- **服务质量回访**：自动回访任务生成、回访问卷配置
- **问题升级机制**：问题升级规则、升级通知、升级处理优先级

### 13. 消息通知增强
- **短信通知**：重要节点短信提醒、短信模板管理
- **企业微信通知**：企业微信消息推送、工作人员订单通知
- **服务提醒**：婚期前7天/3天/1天提醒
- **档期变更通知**：档期修改、取消、释放通知
- **支付成功通知**：用户支付成功、工作人员收款通知

## 🔒 风险控制

### 业务风险控制

#### 防跳单机制
- 工作人员联系方式脱敏显示
- 订单确认后才显示完整联系方式
- 图片添加平台水印
- 建立信用评分体系，跳单行为记录黑名单

#### 防超卖机制
- Redis 分布式锁，锁定档期查询到下单的整个流程
- 数据库乐观锁（version 字段）
- 下单前二次确认弹窗，实时检测档期状态
- 超卖赔偿机制（自动推荐替代人员 + 人工补偿方案）

#### 评价风控机制
- 管理员手动审核（图片、文字内容）
- 敏感词过滤（自定义词库，自动标记待审核）
- 工作人员申诉通道（申诉理由 + 证据上传）
- 恶意差评识别（异常评分模式、短时间多次差评）

#### 用户流失控制
- 智能推荐同价位替代人员
- 提供人工回访与专属方案挽留
- 档期预约提醒（档期释放时自动通知）
- 候补订单机制（支持排队等待档期释放）

### 技术风险控制

#### 高并发处理
- Redis 缓存热点数据（人员信息、档期状态）
- 消息队列异步处理（订单创建、通知发送）
- 数据库读写分离、分库分表

#### 数据安全
- 用户敏感信息加密存储
- API 接口签名验证
- 防 SQL 注入、XSS 攻击
- 定期数据备份

#### 系统稳定性
- 服务降级策略（非核心功能降级）
- 熔断机制（第三方服务异常时熔断）
- 限流策略（接口访问频率限制）
- 监控告警（异常日志、性能指标）

## 📊 数据库设计

### 核心数据表（55张）

**用户相关（3张）**
- la_user - 用户表
- la_user_auth - 用户授权表
- la_user_account_log - 账户日志表

**工作人员相关（3张）**
- la_staff - 工作人员表
- la_staff_work - 作品表
- la_staff_certificate - 证书表

**服务相关（4张）**
- la_service_category - 服务分类表
- la_service_package - 服务套餐表
- la_style_tag - 风格标签表
- la_staff_tag - 标签关联表

**档期相关（4张）**
- la_schedule - 档期表
- la_schedule_rule - 档期规则表
- la_schedule_lock - 档期锁定表
- la_schedule_share - 档期共享表

**购物车相关（2张）**
- la_cart - 购物车表
- la_cart_plan - 购物车方案表

**订单相关（7张）**
- la_order - 订单主表
- la_order_item - 订单项表
- la_order_log - 订单日志表
- la_order_change - 订单变更表
- la_order_pause - 订单暂停表
- la_order_transfer - 订单转让表
- la_refund - 退款表

**动态社区相关（5张）**
- la_moment - 动态表
- la_moment_comment - 评论表
- la_moment_like - 点赞表
- la_moment_topic - 话题表
- la_moment_topic_relation - 话题关联表

**评价相关（4张）**
- la_review - 评价表
- la_review_reply - 评价回复表
- la_review_appeal - 评价申诉表
- la_review_reward - 评价奖励表

**合同相关（3张）**
- la_contract - 合同表
- la_contract_template - 合同模板表
- la_contract_sign - 合同签署记录表

**CRM相关（4张）**
- la_customer - 客户表
- la_follow_record - 跟进记录表
- la_customer_assign - 客户分配表
- la_sales_advisor - 销售顾问表

**现场服务相关（4张）**
- la_service_checkin - 服务签到表
- la_service_photo - 现场照片表
- la_service_progress - 服务进度表
- la_service_material - 物料清单表

**售后相关（3张）**
- la_after_sale_ticket - 售后工单表
- la_complaint - 投诉表
- la_reshoot - 补拍/重拍申请表

**通知相关（2张）**
- la_sms_record - 短信发送记录表
- la_notification_config - 通知配置表

**其他（9张）**
- la_favorite - 收藏表
- la_activity - 活动表
- la_notice_record - 消息记录表
- la_subscribe_record - 订阅消息表
- la_config - 系统配置表
- la_dict_data - 字典数据表
- la_waitlist - 候补订单表
- la_commission - 佣金表
- la_calendar_event - 黄历事件表

## 📁 项目结构

```
wedding-management/
├── server/              # PHP后端
│   ├── app/            # 应用代码
│   │   ├── adminapi/   # 管理后台API
│   │   ├── api/        # 客户端API
│   │   └── common/     # 公共代码
│   ├── config/         # 配置文件
│   ├── public/         # Web根目录
│   └── vendor/         # 依赖包
├── admin/              # Vue3管理后台
│   ├── src/
│   │   ├── api/        # API接口
│   │   ├── views/      # 页面组件
│   │   ├── components/ # 公共组件
│   │   ├── router/     # 路由配置
│   │   └── stores/     # 状态管理
│   └── package.json
├── uniapp/             # UniApp小程序
│   ├── src/
│   │   ├── pages/      # 页面
│   │   ├── components/ # 组件
│   │   ├── api/        # API接口
│   │   └── utils/      # 工具函数
│   └── package.json
├── pc/                 # Nuxt3 PC端
│   ├── pages/          # 页面
│   ├── components/     # 组件
│   ├── api/            # API接口
│   └── package.json
└── doc/                # 项目文档
    └── 功能设计汇总.md
```

## 🛠️ 安装部署

### 环境要求
- PHP >= 8.0
- MySQL >= 5.7
- Redis >= 5.0
- Node.js >= 16.0
- Composer

### 后端部署

1. 克隆项目
```bash
git clone https://github.com/zhaohe574/wedding.git
cd wedding-management/server
```

2. 安装依赖
```bash
composer install
```

3. 配置环境
```bash
cp .example.env .env
# 编辑 .env 文件，配置数据库、Redis等信息
```

4. 导入数据库
```bash
# 导入 SQL 文件到 MySQL 数据库
```

5. 配置 Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/wedding-management/server/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 管理后台部署

```bash
cd admin
npm install
npm run build
# 构建产物会输出到 server/public/admin
```

### 小程序部署

```bash
cd uniapp
npm install
# 使用 HBuilderX 打开项目
# 或使用命令行编译
npm run dev:mp-weixin  # 微信小程序
npm run dev:h5         # H5
```

### PC端部署

```bash
cd pc
npm install
npm run build
npm run start
```

## 📝 开发文档

详细的功能设计文档请查看：[功能设计汇总](./doc/功能设计汇总.md)

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

## 📄 开源协议

本项目基于 [MIT](./LICENSE) 协议开源

## 📮 联系方式

- 项目地址：https://github.com/zhaohe574/wedding
- 问题反馈：https://github.com/zhaohe574/wedding/issues

---

**项目版本:** v2.0  
**创建日期:** 2026-01-19  
**基于框架:** LikeAdmin PHP (ThinkPHP 8.0 + Vue 3 + UniApp)
