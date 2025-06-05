<?php

namespace ShinePress\StubDivider;

use LogicException;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;


class Application extends SingleCommandApplication {



	public function __construct() {
		parent::__construct('divide-stubs');
		$this->setVersion('1.0.0');

		$this->addArgument(
			'stubfile',
			InputArgument::REQUIRED,
			'stub file to divide',
		);

		$this->addArgument(
			'outdir',
			InputArgument::OPTIONAL,
			'output directory',
			'./%NAME%',
		);
	}

	/**
	 * @throws RuntimeException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$workingDir = getcwd();
		if($workingDir === false) {
			throw new RuntimeException('unexpected failure loading current working directory');
		}

		$stubfileFullPath = $input->getArgument('stubfile');
		if(!is_string($stubfileFullPath)) {
			throw new RuntimeException(sprintf(
				'stubfile must be a string, "%s" given',
				gettype($stubfileFullPath),
			));
		}

		if(Path::isRelative($stubfileFullPath)) {
			$stubfileFullPath = Path::makeAbsolute($stubfileFullPath, $workingDir);
		}
		$stubfileRelativePathname = Path::makeRelative($stubfileFullPath, $workingDir);
		$stubfileRelativePath = Path::getDirectory($stubfileRelativePathname);
		$stubfile = new SplFileInfo(
			$stubfileFullPath,
			$stubfileRelativePath,
			$stubfileRelativePathname,
		);


		$outdirFullPath = $input->getArgument('outdir');
		if(!is_string($outdirFullPath)) {
			throw new RuntimeException(sprintf(
				'outdir must be a string, "%s" given',
				gettype($outdirFullPath),
			));
		}
		$outdirFullPath = str_replace('%NAME%', $stubfile->getFilenameWithoutExtension(), $outdirFullPath);
		if(Path::isRelative($outdirFullPath)) {
			$outdirFullPath = Path::makeAbsolute($outdirFullPath, $workingDir);
		}
		$outdirRelativePathname = Path::makeRelative($outdirFullPath, $workingDir);
		$outdirRelativePath = Path::getDirectory($outdirRelativePathname);
		$outdir = new SplFileInfo(
			$outdirFullPath,
			$outdirRelativePath,
			$outdirRelativePathname,
		);

		$processor = new Processor($stubfile);

		$progressBar = new ProgressBar($output, $processor->count());

		foreach($processor->process($outdir->getPathname()) as $file) {
			$file->save();
			$progressBar->advance();
		}

		$progressBar->finish();
		$output->writeln(' Complete');

		return 0;
	}
}