# 婚庆服务预约系统

小程序承载客户预约、支付、履约、问卷、售后和活动；管理后台负责 CRM、内容、财务、档期海报与月报；PC 端只展示品牌、案例、资讯和联系入口。

## 快速开始

1. 在 `server` 执行 `composer install`，将站点根目录指向 `server/public`，访问 `/install/install.php` 安装空数据库。安装器使用 `.example.env` 生成本机 `.env`、随机密码盐和管理员账号。
2. 在 `admin`、`uniapp`、`pc` 各自将 `.env.example` 作为本机 `.env` 的起点，填写后端域名，执行 `npm ci`。
3. 在后台配置微信小程序、服务号、微信支付、存储和验证码短信；小程序 `src/manifest.json` 的 `mp-weixin.appid` 必须与后台小程序配置一致。
4. 按[部署配置与验收](docs/部署配置与验收.md)准备独立本机测试数据库，再执行 `npm run qa:all`。

## 核心文档

- [现行功能](docs/现行功能.md)
- [业务状态与接口](docs/业务状态与接口.md)
- [部署配置与验收](docs/部署配置与验收.md)
- [变更记录](docs/变更记录.md)
- [核心状态机](docs/architecture/core-state-machine.md)

服务号仅用于关注绑定和业务通知，业务页面统一跳转微信小程序；支付仅支持微信小程序支付和受控线下收款，验证码短信保留。
