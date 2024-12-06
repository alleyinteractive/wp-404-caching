# Changelog

All notable changes to `WP 404 Caching` will be documented in this file.

## 1.1.0

- Drop requirement for `alleyinteractive/wp-type-extensions`.
- Drop `wp_404_caching_features` filter.
- Github Action: add support for the memcached service;
- Github Action: test against PHP 8.3

## 1.0.3

- Added `caching_enabled` method to be able to check the `wp_404_caching_enabled` filter later in methods where it is needed.

## 1.0.2

- Fixed issue with Feature_Manager setup
- Added `wp_404_caching_enabled` filter to allow disabling the cache

## 1.0.1

- Initial release
