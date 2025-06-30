# Changelog

All notable changes to `WP 404 Caching` will be documented in this file.

## 1.2.1

- Allow cache group to be filtered by a filter.

## 1.2.0

- Fix issue where a empty 404 page would be returned when the cache is not yet
  set. The 404 page will now function normally until the cache is set.
- Add default 'Cache-Control' header to the 404 response.
- Bumped minimum PHP version to 8.2.

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
