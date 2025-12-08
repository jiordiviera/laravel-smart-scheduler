# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-12-08

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

## [1.0.0] - 2025-11-03

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

[Unreleased]: https://github.com/jiordiviera/laravel-smart-scheduler/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/jiordiviera/laravel-smart-scheduler/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jiordiviera/laravel-smart-scheduler/releases/tag/v1.0.0
