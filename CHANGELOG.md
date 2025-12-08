# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/jiordiviera/laravel-smart-scheduler/compare/feat: stuck task detection + documentation improvements...HEAD)

## [1.1.0](https://github.com/jiordiviera/laravel-smart-scheduler/compare/v1.0.0...v1.1.0) - 2025-12-08

### Added

- Stuck task detection feature
  - New `STATUS_STUCK` status for tasks that exceed timeout
  - `smart-scheduler:detect-stuck` artisan command with `--timeout`, `--dry-run`, and `--no-notify` options
  - Automatic stuck task detection when new task execution starts
  - `stuck_timeout_minutes` configuration option (default: 60 minutes)
  - Email notifications for stuck tasks with dedicated template
  - `isStuck()` helper method on `ScheduleRun` model
  - `sendStuckNotification()` method on `SmartNotifierInterface`
  
- `notify_on_stuck` configuration option to control stuck task notifications

### Changed

- `SmartSchedulerManager` now auto-detects and marks stuck tasks before checking for overlaps
- Updated `SmartNotifierInterface` contract with `sendStuckNotification()` method

## [1.0.0](https://github.com/jiordiviera/laravel-smart-scheduler/releases/tag/v1.0.0) - 2025-11-03

### Added

- Initial release
- Task execution tracking with `smart_schedule_runs` table
- Overlap prevention for concurrent task executions
- Email notifications for failed tasks
- `smart-scheduler:purge` command with `--days` and `--dry-run` options
- Configurable purge retention period
- Server name tracking for multi-server deployments
- Task output and exception capture
- `ScheduleRun` model with status helper methods (`isStarting()`, `isSuccess()`, `isFailed()`, `isIgnored()`)
- Publishable configuration, migrations, and views

## [feat: stuck task detection + documentation improvements](https://github.com/jiordiviera/laravel-smart-scheduler/compare/v1.1.0...feat: stuck task detection + documentation improvements) - 2025-12-08

### Summary

- **Stuck Task Detection**: Automatically detect and handle tasks that are stuck (server crash, process killed)
- **Documentation**: Complete README overhaul with badges, improved docs, and LICENSE

### Changes

#### Stuck Task Feature

- New `STATUS_STUCK` status for tasks exceeding timeout
- `smart-scheduler:detect-stuck` artisan command
- Auto-detection when new task starts
- Email notifications for stuck tasks
- Configurable timeout (default: 60 minutes)

#### Documentation

- README with badges, flow diagram, and comprehensive docs
- LICENSE.md (MIT)
- CHANGELOG.md following Keep a Changelog format
- GitHub Actions workflow for auto-changelog on PR merge
- Updated repo description and topics

### Test plan

- [x] All 36 tests passing
- [x] Code style check (pint) passing
- [ ] Test stuck task detection manually
- [ ] Verify badges display correctly on GitHub
