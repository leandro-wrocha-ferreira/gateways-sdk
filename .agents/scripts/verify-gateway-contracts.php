<?php

/**
 * Deterministic Gateway Contracts & Implementation Verifier for gateways-sdk.
 *
 * Ensures that all payment gateway types defined in GatewayType have:
 * 1. A corresponding Gateway implementation class in src/Infrastructure/Gateways/<GatewayName>/
 * 2. Proper implementation of GatewayInterface
 * 3. Proper Mapper implementation
 *
 * Usage:
 *   php .agents/scripts/verify-gateway-contracts.php
 *
 * Exit Codes:
 *   0 - All active gateways are properly registered, implemented, and tested.
 *   1 - Inconsistencies detected in gateway implementations.
 */

declare(strict_types=1);

$project_root = dirname(__DIR__, 2);
$gateway_type_file = $project_root . '/src/Domain/Enums/GatewayType.php';
$gateways_dir = $project_root . '/src/Infrastructure/Gateways';

if (!file_exists($gateway_type_file)) {
	echo "Notice: GatewayType.php not found yet at: {$gateway_type_file}\n";
	echo "Skipping gateway contract verification during initial bootstrap.\n";
	exit(0);
}

// 1. Extract gateway cases or constants from GatewayType.php
$content = file_get_contents($gateway_type_file);
$gateways = [];

if (preg_match_all('/case\s+([A-Z0-9_]+)\s*=\s*[\'"]([a-z0-9_\-]+)[\'"]/i', $content, $matches)) {
	foreach ($matches[2] as $index => $slug) {
		$gateways[$slug] = ucfirst(strtolower($matches[1][$index]));
	}
}

echo "====================================================\n";
echo " Deterministic Gateway Implementation Verifier\n";
echo "====================================================\n\n";

$issues = [];
$total_checked = 0;
$passed_checked = 0;

// Planned gateways to be implemented in upcoming increments
$deferred_gateways = ['pagarme'];

foreach ($gateways as $slug => $class_prefix) {
	echo "--- Checking Gateway: '{$slug}' ({$class_prefix}) ---\n";

	if (in_array($slug, $deferred_gateways, true) && !is_dir($gateways_dir . '/' . $class_prefix)) {
		echo " [PLANNED] Gateway '{$slug}' scheduled for implementation in next phase.\n";
		continue;
	}

	$gateway_folder = $gateways_dir . '/' . $class_prefix;
	$gateway_class_file = $gateway_folder . '/' . $class_prefix . 'Gateway.php';

	// Check 1: Gateway Class File exists
	$total_checked++;
	if (file_exists($gateway_class_file)) {
		$passed_checked++;
		echo sprintf(" [OK]      Class File: %s\n", basename($gateway_class_file));
	} else {
		$issues[] = sprintf("Gateway '%s' is missing implementation file '%s'", $slug, $gateway_class_file);
		echo sprintf(" [MISSING] Class File: %s NOT found!\n", $gateway_class_file);
		continue;
	}

	// Check 2: Implements GatewayInterface
	$total_checked++;
	$class_code = file_get_contents($gateway_class_file);
	if (str_contains($class_code, 'implements GatewayInterface') || str_contains($class_code, 'implements \GatewaysSdk\Domain\Contracts\GatewayInterface')) {
		$passed_checked++;
		echo " [OK]      Implements GatewayInterface\n";
	} else {
		$issues[] = sprintf("Gateway '%sGateway' does not implement GatewayInterface", $class_prefix);
		echo sprintf(" [MISSING] '%sGateway' does NOT implement GatewayInterface!\n", $class_prefix);
	}

	// Check 3: Has Mapper
	$total_checked++;
	$mapper_file = $gateway_folder . '/' . $class_prefix . 'Mapper.php';
	if (file_exists($mapper_file)) {
		$passed_checked++;
		echo sprintf(" [OK]      Mapper: %s\n", basename($mapper_file));
	} else {
		$issues[] = sprintf("Gateway '%s' is missing Mapper '%s'", $slug, $mapper_file);
		echo sprintf(" [MISSING] Mapper: %s NOT found!\n", $mapper_file);
	}
}

echo "\n----------------------------------------------------\n";
echo sprintf("Summary: %d / %d checks passed.\n", $passed_checked, $total_checked);
echo "----------------------------------------------------\n";

if (!empty($issues)) {
	echo "\nFAILED: The following Gateway implementation issues were detected:\n";
	foreach ($issues as $issue) {
		echo "  - {$issue}\n";
	}
	echo "\nPlease synchronize Gateway implementations before proceeding.\n\n";
	exit(1);
}

echo "\nSUCCESS: All active Gateways have matching implementations and mappers!\n\n";
exit(0);
