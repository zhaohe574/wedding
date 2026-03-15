# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a wedding service management platform with four main applications:
- **server/** - ThinkPHP 8.0 backend API (PHP 8.0+)
- **admin/** - Vue 3 + TypeScript admin dashboard
- **pc/** - Nuxt 3 customer-facing website
- **uniapp/** - Cross-platform mobile app (WeChat Mini Program, H5, iOS, Android)

## Development Commands

### Backend (server/)
```bash
# Install dependencies
composer install

# Run development server
php think run

# Database migrations (manual SQL execution)
# Primary SQL: server/sql/2.0.0.20260201/update.sql (v2.0.0 complete schema)
# Legacy migrations: server/sql/wedding/*.sql (incremental updates)
# Version updates: server/sql/1.x.x.yyyymmdd/update.sql

# Service discovery
php think service:discover
```

### Admin Dashboard (admin/)
```bash
cd admin
npm install
npm run dev          # Development server (auto-opens browser)
npm run build        # Production build
npm run type-check   # TypeScript validation
npm run lint         # ESLint
```

### PC Website (pc/)
```bash
cd pc
npm install
npm run dev          # Development server (http://localhost:3000)
npm run build:ssr    # Production build with SSR
npm run build        # Static generation
npm run preview      # Preview production build
```

### Mobile App (uniapp/)
```bash
cd uniapp
npm install
npm run dev:h5          # H5 web version (port 8991)
npm run dev:mp-weixin   # WeChat mini-program
npm run dev:app         # Native app
npm run build:h5        # Production H5 build
npm run build:mp-weixin # Production WeChat build
```

## Architecture

### Backend Multi-App Structure
The ThinkPHP backend uses a multi-app architecture:
- **app/adminapi/** - Admin panel API endpoints
- **app/api/** - Mobile/H5 frontend API endpoints
- **app/common/** - Shared models, services, and logic

### Backend Layered Architecture
Controllers delegate to specialized layers:
- **Controller** → thin HTTP handlers
- **Logic** → business logic (e.g., `StaffLogic`, `OrderChangeLogic`)
- **Lists** → data listing with search/sort/export (extends `BaseAdminDataLists`)
- **Model** → database ORM (in `app/common/model/`)
- **Service** → reusable services (e.g., `StaffPriceService`, `RedisLockService`)
- **Validate** → input validation rules

### Staff Permission Scoping
Staff members can only manage their own data via `StaffService::getStaffScopeId()`:
- Controllers check: `if ($staffScopeId > 0 && $staffId !== $staffScopeId)` to restrict access
- Separate endpoints exist for staff self-management: `/myProfile`, `/myProfileUpdate`

### Price Resolution Hierarchy
`StaffPriceService` implements a 5-tier pricing system:
1. Staff custom slot prices (per time slot)
2. Staff custom price (unified)
3. Staff package base price
4. Package slot prices
5. Package default price

### Distributed Locking for Schedules
`RedisLockService` prevents double-booking using Redis SETNX with:
- Atomic lock acquisition with expiration
- Lua scripts for safe release (only token holder can release)
- Lock renewal support for long operations
- Batch locking for multi-schedule operations

### Order Change Workflow
`OrderChange` model implements a state machine for date changes, staff replacements, and add-ons:
- States: Pending → Approved → Executed (or Rejected/Cancelled)
- Temporary schedule locking during approval
- Automatic price differential calculation
- Atomic execution with database transactions

### Frontend Dynamic Routing
Admin panel routes are fetched from backend based on permissions:
- `filterAsyncRoutes()` generates routes dynamically
- Menu types: `CATALOGUE` (folder) and `MENU` (page)
- Components loaded via `import.meta.glob('/src/views/**/*.vue')`

### Database Patterns
- **Soft Deletes**: Uses `delete_time` timestamp (null = active, timestamp = deleted)
- **Table Prefix**: `la_` (from likeadmin framework)
- **Migrations**: Manual SQL execution required
  - Current version: `server/sql/2.0.0.20260201/update.sql` (complete v2.0.0 schema)
  - Legacy incremental: `server/sql/wedding/*.sql`
  - Version upgrades: `server/sql/1.x.x.yyyymmdd/update.sql`
- **Auto Timestamps**: `create_time`, `update_time` handled automatically

### File Storage Abstraction
`FileService` supports multiple storage engines:
- Local, Aliyun OSS, Qcloud COS, Qiniu
- Automatic URL completion/stripping via `getImageAttr`/`setImageAttr` in BaseModel

## Key Business Entities

- **Staff** (工作人员) - Service providers (photographers, planners, etc.)
- **Services** (服务) - Wedding packages and offerings
- **Orders** (订单) - Bookings and transactions
- **Schedule** (排期) - Calendar and availability management
- **OrderChange** (改期) - Date changes, staff replacements, add-ons
- **Finance** (财务) - Payments, refunds, invoices, settlements
- **Consumers** (客户) - End users/customers

## Code Generation

The backend includes `GenerateService` for rapid CRUD scaffolding:
- Generates Controller, Logic, Model, Validate, Lists, and Vue files
- Maintains consistent patterns across the application
- Templates create both PHP backend and Vue/TypeScript frontend code

## Testing

No automated test suite exists. The project relies on manual testing.

## Important Notes

- **Mobile Privacy**: Staff model masks mobile numbers in getters (`138****5678`), use `mobile_full` for complete data
- **API Format**: RESTful-style with namespace routing (e.g., `/ops.staff/lists`)
- **Authentication**: JWT-based, middleware injects `adminInfo`/`userInfo` into request
- **Build Output**: Admin builds to `server/public/admin/`
- **Git Workflow**: Work on `dev` branch, merge to `main`
