<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Forms\Service;

use OCA\Forms\Db\Form;
use OCA\Forms\Db\Submission;

class SubmissionPdfService {
	private const MAX_PDF_LINES = 50;

	/**
	 * @param array<int, array{question: string, answer: string}> $answerSummaries
	 */
	public function createPdf(Form $form, Submission $submission, array $answerSummaries = []): string {
		$submissionTimestamp = max(0, $submission->getTimestamp());
		$headerLines = [
			'Nextcloud Forms submission',
			'Form: ' . $form->getTitle(),
			'Submission ID: ' . $submission->getId(),
			'Submitted at (UTC): ' . gmdate('Y-m-d H:i:s', $submissionTimestamp),
			'',
			'Responses:',
		];

		$responseLines = [];
		if ($answerSummaries === []) {
			$responseLines[] = '- No text responses captured';
		} else {
			foreach ($answerSummaries as $summary) {
				$responseLines[] = '- ' . $summary['question'] . ':';

				$normalizedAnswer = str_replace(["\r\n", "\r"], "\n", $summary['answer']);
				foreach (explode("\n", $normalizedAnswer) as $answerLine) {
					$responseLines[] = '  ' . ($answerLine === '' ? '[empty line]' : $answerLine);
				}
			}
		}

		$lines = array_merge($headerLines, $responseLines);
		$pdfLines = $this->normalizePdfLines($lines);
		$contentStream = $this->createContentStream($pdfLines);

		return $this->assemblePdf($contentStream);
	}

	public function createFilename(Form $form, Submission $submission): string {
		$title = trim($form->getTitle());
		$base = $title === '' ? 'form' : $title;
		$base = preg_replace('/[^\p{L}\p{N}\-_. ]+/u', '_', $base) ?? 'form';
		$base = preg_replace('/\s+/', '_', trim($base)) ?? 'form';
		$base = trim($base, '._-');

		if ($base === '') {
			$base = 'form';
		}

		return sprintf('%s-submission-%d.pdf', $base, $submission->getId());
	}

	/**
	 * @param list<string> $lines
	 * @return list<string>
	 */
	private function normalizePdfLines(array $lines): array {
		$normalizedLines = [];
		foreach ($lines as $line) {
			$encodedLine = $this->encodeLine($line);
			foreach ($this->wrapLine($encodedLine, 96) as $wrappedLine) {
				$normalizedLines[] = $wrappedLine;
				if (count($normalizedLines) >= self::MAX_PDF_LINES) {
					$normalizedLines[count($normalizedLines) - 1] = '...';
					return $normalizedLines;
				}
			}
		}

		return $normalizedLines;
	}

	private function encodeLine(string $line): string {
		$encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $line);
		if ($encoded === false) {
			return '';
		}

		return $encoded;
	}

	/**
	 * @return list<string>
	 */
	private function wrapLine(string $line, int $limit): array {
		if ($line === '') {
			return [''];
		}

		$words = preg_split('/\s+/', $line) ?: [];
		$current = '';
		$result = [];

		foreach ($words as $word) {
			if ($word === '') {
				continue;
			}

			$candidate = $current === '' ? $word : $current . ' ' . $word;
			if (strlen($candidate) <= $limit) {
				$current = $candidate;
				continue;
			}

			if ($current !== '') {
				$result[] = $current;
				$current = '';
			}

			while (strlen($word) > $limit) {
				$result[] = substr($word, 0, $limit);
				$word = substr($word, $limit);
			}

			$current = $word;
		}

		if ($current !== '') {
			$result[] = $current;
		}

		return $result === [] ? [''] : $result;
	}

	/**
	 * @param list<string> $pdfLines
	 */
	private function createContentStream(array $pdfLines): string {
		$content = "BT\n/F1 11 Tf\n14 TL\n50 792 Td\n";
		foreach ($pdfLines as $line) {
			$escapedLine = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
			$content .= '(' . $escapedLine . ") Tj\nT*\n";
		}
		$content .= 'ET';

		return $content;
	}

	private function assemblePdf(string $contentStream): string {
		$objects = [
			1 => '<< /Type /Catalog /Pages 2 0 R >>',
			2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
			3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
			4 => '<< /Length ' . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream",
			5 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
		];

		$pdf = "%PDF-1.4\n";
		$offsets = [0 => 0];

		foreach ($objects as $id => $object) {
			$offsets[$id] = strlen($pdf);
			$pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
		}

		$startXref = strlen($pdf);
		$pdf .= "xref\n0 6\n";
		$pdf .= sprintf("%010d 65535 f \n", 0);
		for ($i = 1; $i <= 5; $i++) {
			$pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
		}

		$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
		$pdf .= "startxref\n" . $startXref . "\n%%EOF";

		return $pdf;
	}
}
