# 后台菜单清单（单一事实源）

## 这是什么

`menu_manifest.php` 是后台侧边栏菜单的**唯一事实源**。菜单不再是「谁在后台点几下就变成什么样」的运行时数据，而是一份进版本库、可 diff、可回滚的声明。

```
menu_manifest.php     ← 声明式菜单树（M 目录 / C 菜单；A 型按钮权限不在此，由 migrations 维护）
legacy_id_map.php     ← 库内 id → menu_key，排查用；id 不是身份，key 才是
snapshots/*.json      ← 库导出快照（对账与回滚的基线）
bootstrap.mjs         ← 从 like.sql + 库快照生成上述两份 PHP（一次性 / 重建时用）
```

## 为什么需要它

改造前的三处问题：

1. **同一份菜单两处维护**：后端 `la_system_menu` 一份，前端 `admin/src/router/index.ts` 又硬编码注入了 6 条 `staff_center` 子路由、40 条权限别名。
2. **魔法数字**：`MenuLogic::PUBLIC_MENU_IDS = [497,501,502,503]`、`AuthLogic::[498,499,500]`（后者在基线里根本不存在），主键一换就静默失效。
3. **基线漂移**：`like.sql` 483 条 vs 线上 557 条，且 440 段 id 整体偏移过——`id` 不能当身份用。

## 怎么改

```bash
# 只改 menu_manifest.php，不要直接改库
php server/think admin_menu:sync                       # 看差异（默认 dry-run）
php server/think admin_menu:sync --apply --batch=xxx   # 落库（自动快照）
php server/think admin_menu:check --strict             # 校验（CI 必跑）
php server/think admin_menu:sync --rollback=xxx        # 回滚
```

## 三条硬规则

1. **`menu_key IS NULL` = 未托管，同步命令永不改写。**
   运营手工菜单、A 型按钮、历史残留都在这个桶里。要纳管必须显式
   `php server/think admin_menu:sync --adopt=<id>:<key>`，且会被 `component`/`paths` 二次校验拦住误认领。

2. **退役不删除。** 清单里写 `'retired' => true`，落库时置 `is_disable=1, is_show=0`。
   `la_system_role_menu` 的授权保留，回滚才能还原。

3. **`component` 必须与文件路径完全一致。**
   前端 `loadRouteView` 用 `includes()` 模糊匹配，写错不报错、只白屏。`admin_menu:check` 会校验「恰好命中一个 .vue」。

## 字段约定

| 字段 | 说明 |
|---|---|
| `key` | 稳定身份，唯一且永不复用。格式 `nav.<paths>`（目录）/ `page.<component 去 /index>`（菜单） |
| `type` | `M` 目录 / `C` 菜单。`A` 按钮不在此清单 |
| `path` | 仅本段路径，与 DB 的 `paths` 一致（前端由 vue-router 嵌套拼接） |
| `component` | 相对 `admin/src/views` 的路径，不含 `.vue` |
| `perms` | 必须等于后端控制器的「点分路径/方法」，如 `ops.order/lists` |
| `legacy_ids` | 库内既有行 id（认领用）；空数组表示清单有、库里没有，同步时新建 |
| `retired` | `true` = 停用但不删除 |
| `route_aliases` | 需继续被 `getRoutePath()` 命中的历史字符串（B2 收敛期用） |

## policy

替代代码里的魔法数字：

| bucket | 含义 |
|---|---|
| `public` | 所有后台账号可见，不依赖角色授权（当前：快捷工具 4 项） |
| `staff_only` | 仅绑定服务人员的账号可见 |
| `leader_only` | 仅服务人员队长可见 |
| `never_ship` | 永不进侧边栏 |

解析失败会写 `[admin_menu] policy.xxx 的 key 在库中不存在` 日志，**不静默跳过**——否则又会回到「菜单莫名消失、查不出原因」。

## 重建清单

`like.sql` 变动后需要重建（注意必须带 `--snapshot`，否则会把线上新值改回旧基线值）：

```bash
php server/think admin_menu:sync --export=server/database/menu/snapshots/prod-$(date +%Y%m%d).json
node server/database/menu/bootstrap.mjs --snapshot=server/database/menu/snapshots/prod-<日期>.json
```

`bootstrap.mjs` 里的 `STALE_BASELINE` 记录「基线有、线上已替换」的节点，重建时会被跳过，避免凭空多出菜单。
