<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Forms\Tests\Unit\Service;

use OCA\Forms\Db\Form;
use OCA\Forms\Db\Submission;
use OCA\Forms\Service\SubmissionPdfService;
use Test\TestCase;

class SubmissionPdfServiceTest extends TestCase {
	public function testCreatePdfIncludesSubmissionMetadataAndResponses(): void {
		$service = new SubmissionPdfService();
		$form = new Form();
		$form->setTitle('Customer Survey');
		$submission = new Submission();
		$submission->setId(99);
		$submission->setTimestamp(1700000000);

		$pdf = $service->createPdf($form, $submission, [
			[
				'question' => 'Email',
				'answer' => 'user@example.com',
			],
		]);

		$this->assertTrue(str_starts_with($pdf, '%PDF-1.4'));
		$this->assertStringContainsString('Nextcloud Forms submission', $pdf);
		$this->assertStringContainsString('Form: Customer Survey', $pdf);
		$this->assertStringContainsString('Submission ID: 99', $pdf);
		$this->assertStringContainsString('Email', $pdf);
		$this->assertStringContainsString('user@example.com', $pdf);
	}

	public function testCreatePdfUsesFallbackTextForMissingResponses(): void {
		$service = new SubmissionPdfService();
		$form = new Form();
		$form->setTitle('Customer Survey');
		$submission = new Submission();
		$submission->setId(100);
		$submission->setTimestamp(1700000000);

		$pdf = $service->createPdf($form, $submission);

		$this->assertStringContainsString('- No text responses captured', $pdf);
	}

	public function testCreateFilenameSanitizesFormTitle(): void {
		$service = new SubmissionPdfService();
		$form = new Form();
		$form->setTitle('  Customer Survey: 2026 / Berlin?  ');
		$submission = new Submission();
		$submission->setId(123);

		$filename = $service->createFilename($form, $submission);

		$this->assertSame('Customer_Survey__2026___Berlin-submission-123.pdf', $filename);
	}

	public function testCreateFilenameUsesDefaultForEmptyTitle(): void {
		$service = new SubmissionPdfService();
		$form = new Form();
		$form->setTitle('   ');
		$submission = new Submission();
		$submission->setId(7);

		$filename = $service->createFilename($form, $submission);

		$this->assertSame('form-submission-7.pdf', $filename);
	}
}
