<?php

/*
 * This file is part of ShinePress.
 *
 * (c) Shine United LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ShinePress\StubDivider;

use Symfony\Component\Filesystem\Path;

class ResultFile {
	private string $filename;
	private string $contents;

	public function __construct(string $filename, string $contents) {
		$this->filename = $filename;
		$this->contents = $contents;
	}

	public function getFilename(): string {
		return $this->filename;
	}

	public function getContents(): string {
		return $this->contents;
	}

	public function save(): void {
		$dirpath = Path::getDirectory($this->filename);

		if (!file_exists($dirpath)) {
			mkdir($dirpath, 0777, true);
		}

		file_put_contents(
			$this->filename,
			$this->contents,
		);
	}
}
