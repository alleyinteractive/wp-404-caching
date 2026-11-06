<?php
/**
 * Rector Configuration
 *
 * @link https://getrector.com/documentation
 * @package wp-404-caching
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
	->withParallel()
	->withIndent(
		indentChar: '	',
		indentSize: 1,
	)
	->withRootFiles()
	->withPaths( [
		__DIR__ . '/src',
		__DIR__ . '/tests',
	] )
	/**
	 * --------------------------------------------------------------------------
	 * Enabled rector rules/rulesets.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/find-rule
	 */
	->withPreparedSets(
		codeQuality: true,
		deadCode: true,
		earlyReturn: true,
		typeDeclarations: true,
	)
	/**
	 * --------------------------------------------------------------------------
	 * Enable Rector to keep your code up-to-date with the latest features from the PHP version in your composer.json file
	 * --------------------------------------------------------------------------
	 */
	->withPhpSets()
	/**
	 * --------------------------------------------------------------------------
	 * PHPUnit rules, matched to the PHPUnit version in composer.json.
	 * --------------------------------------------------------------------------
	 */
	->withComposerBased( phpunit: true )
	->withAttributesSets( phpunit: true )
	/**
	 * --------------------------------------------------------------------------
	 * Rector rules to skip.
	 * --------------------------------------------------------------------------
	 *
	 * @link https://getrector.com/documentation/ignoring-rules-or-paths
	 */
	// WPCS requires the doc tags the first two strip, and closures can't be unhooked with remove_action().
	->withSkip( [
		ArrayToFirstClassCallableRector::class,
		RemoveUselessReturnTagRector::class,
		RemoveUselessVarTagRector::class,
	] );
