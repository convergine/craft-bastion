<?php

namespace convergine\craftbastion\services;

use craft\base\Component;
use Craft;

class DiskSpaceUsage extends Component {

	/**
	 * Get disk space usage data
	 *
	 * @return array
	 */
	public function getDiskSpaceData(): array {
		$path = $this->_getBasePath();

		$totalBytes = @disk_total_space($path);
		$freeBytes = @disk_free_space($path);

		if ($totalBytes === false || $freeBytes === false) {
			return [
				'error' => true,
				'message' => Craft::t('craft-bastion', 'Unable to determine disk space'),
				'used' => 0,
				'total' => 0,
				'free' => 0,
				'percentage' => 0,
				'status' => 'error'
			];
		}

		$usedBytes = $totalBytes - $freeBytes;
		$percentage = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100) : 0;

		$usedFormatted = $this->_formatBytesWithUnit($usedBytes);
		$totalFormatted = $this->_formatBytesWithUnit($totalBytes);

		return [
			'error' => false,
			'used' => $this->_formatBytes($usedBytes),
			'usedRaw' => $usedBytes,
			'total' => $this->_formatBytes($totalBytes),
			'totalRaw' => $totalBytes,
			'free' => $this->_formatBytes($freeBytes),
			'freeRaw' => $freeBytes,
			'percentage' => $percentage,
			'status' => $this->_getStatus($percentage),
			'usedDisplay' => $usedFormatted['value'],
			'usedUnit' => $usedFormatted['unit'],
			'usedUnitFull' => $usedFormatted['unitFull'],
			'totalDisplay' => $totalFormatted['value'],
			'totalUnit' => $totalFormatted['unit']
		];
	}

	/**
	 * Get base path for disk space calculation
	 *
	 * @return string
	 */
	private function _getBasePath(): string {
		// Use Craft's base path or web root
		$basePath = Craft::getAlias('@root');

		if ($basePath && is_dir($basePath)) {
			return $basePath;
		}

		// Fallback to storage path
		$storagePath = Craft::getAlias('@storage');
		if ($storagePath && is_dir($storagePath)) {
			return $storagePath;
		}

		// Last resort - current directory
		return getcwd() ?: '/';
	}

	/**
	 * Format bytes to human readable format
	 *
	 * @param int $bytes
	 * @param int $precision
	 * @return string
	 */
	private function _formatBytes(int $bytes, int $precision = 2): string {
		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * Format bytes to value and unit separately
	 *
	 * @param int $bytes
	 * @param int $precision
	 * @return array{value: float, unit: string, unitFull: string}
	 */
	private function _formatBytesWithUnit(int $bytes, int $precision = 2): array {
		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		$unitsFull = ['BYTES', 'KILOBYTES', 'MEGABYTES', 'GIGABYTES', 'TERABYTES', 'PETABYTES'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$value = $bytes / pow(1024, $pow);

		return [
			'value' => round($value, $precision),
			'unit' => $units[$pow],
			'unitFull' => $unitsFull[$pow]
		];
	}

	/**
	 * Determine status based on percentage used
	 *
	 * @param int $percentage
	 * @return string
	 */
	private function _getStatus(int $percentage): string {
		if ($percentage >= 80) {
			return 'error';
		}

		if ($percentage >= 60) {
			return 'warning';
		}

		return 'success';
	}
}
