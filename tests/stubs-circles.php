<?php

declare(strict_types=1);

/**
 * Test-only stub for Circles classes to satisfy unit/integration tests when Circles is absent.
 */

namespace OCA\Circles\Model;

class Circle {
	public function getDisplayName(): string {
		return '';
	}

	public function getUrl(): string {
		return '';
	}

	/**
	 * @return Member[]
	 */
	public function getInheritedMembers(): array {
		return [];
	}

	public function getInitiator(): ?Member {
		return null;
	}

	public function getSingleId(): string {
		return '';
	}
}

class Member {
	public const TYPE_USER = 0;
	public const TYPE_GROUP = 1;
	public const LEVEL_MEMBER = 10;

	public function getUserType(): int {
		return self::TYPE_USER;
	}

	public function getUserId(): string {
		return '';
	}

	public function getLevel(): int {
		return self::LEVEL_MEMBER;
	}
}

namespace OCA\Circles;

class CirclesManager {
	public function startSuperSession(): void {
	}

	public function startSession($user): void {
	}

	public function stopSession(): void {
	}

	public function getCircle($id): ?Model\Circle {
		return null;
	}

	public function getFederatedUser($userId, $type) {
		return null;
	}

	public function getLocalFederatedUser($userId) {
		return null;
	}

	/**
	 * @return Model\Member[]
	 */
	public function probeCircles(): array {
		return [];
	}
}
