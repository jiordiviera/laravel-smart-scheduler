# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Adjust illuminate dependencies to support Laravel 10.x, 11.x, and 12.x.
- Enable package auto-discovery for the SmartScheduler service provider.
- Add smart retry logic for failed runs.
- Provide reporting/analytics command (`smart-schedule:report`).
- Ship optional dashboard for real-time monitoring.

## [1.0.0] - 2025-10-30

### Added

- Smart scheduler wrapper command with overlap prevention and stuck-run detection.
- Persistent logging of every run with configurable database connection.
- Channel-based notifications (Mail, Slack webhook, Telegram) using pluggable interfaces.
- SmartSchedulerManager service and outcome object for clearer error handling.
- Pest + Orchestra Testbench coverage for success, skipped, stuck, and failure flows.
- Contributor guide (`AGENTS.md`) and refreshed README with installation/configuration instructions.
