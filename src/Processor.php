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

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;
use SplObjectStorage;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;

class Processor {
	/** @var SplObjectStorage<ClassLike, string|null> */
	private SplObjectStorage $classes;

	/** @var SplObjectStorage<FunctionLike, string|null> */
	private SplObjectStorage $functions;

	/**
	 * @throws RuntimeException
	 */
	public function __construct(SplFileInfo $stubfile) {
		$this->classes = new SplObjectStorage();
		$this->functions = new SplObjectStorage();

		$parserFactory = new ParserFactory();
		$parser = $parserFactory->createForNewestSupportedVersion();

		$statements = $parser->parse($stubfile->getContents());

		if (is_array($statements)) {
			foreach ($statements as $statement) {
				$this->processStatement($statement);
			}
		}
	}

	public function addClass(ClassLike $class, ?string $namespace = null): void {
		$this->classes[$class] = $namespace;
	}

	public function addFunction(FunctionLike $function, ?string $namespace = null): void {
		$this->functions[$function] = $namespace;
	}

	public function count(): int {
		$count = 1;
		$count += $this->classes->count();
		$count += $this->functions->count();

		return $count;
	}

	/**
	 * @return iterable<ResultFile>
	 *
	 * @throws RuntimeException
	 */
	public function process(string $outputBasepath): iterable {
		$printer = new Standard();

		$classmap = [];
		foreach ($this->classes as $class) {
			$namespace = $this->classes[$class];

			if (!$class->name instanceof Identifier) {
				throw new RuntimeException('missing class name ' . $class->getLine());
			}

			$classFullname = $class->name->name;
			$classFilepath = $classFullname . '.php';
			if (!is_null($namespace)) {
				$classFullname = $namespace . '\\' . $classFullname;
				$classFilepath = str_replace('\\', '/', $namespace) . '/' . $classFilepath;
			}
			$classFilepath = Path::canonicalize($outputBasepath . '/classes/' . $classFilepath);
			$classRelativePath = Path::makeRelative($classFilepath, $outputBasepath);

			$classFullname = strtolower($classFullname);
			$classmap[$classFullname] = $classRelativePath;

			$classData = [];
			$classData[] = '<?php';
			$classData[] = '';
			if (!is_null($namespace)) {
				$classData[] = 'namespace ' . $namespace . ';';
			}
			$classData[] = '';
			$classData[] = $printer->prettyPrint([$class]);

			yield new ResultFile(
				$classFilepath,
				implode("\n", $classData),
			);
		}
		ksort($classmap);

		$functionmap = [];
		foreach ($this->functions as $function) {
			$namespace = $this->functions[$function];

			if (!isset($function->name) || !$function->name instanceof Identifier) {
				throw new RuntimeException('missing function name ' . $function->getLine());
			}

			$functionFullname = $function->name->name;
			$functionFilepath = $functionFullname . '.php';
			if (!is_null($namespace)) {
				$functionFullname = $namespace . '\\' . $functionFullname;
				$functionFilepath = str_replace('\\', '/', $namespace) . '/' . $functionFilepath;
			}
			$functionFilepath = Path::canonicalize($outputBasepath . '/functions/' . $functionFilepath);
			$functionRelativePath = Path::makeRelative($functionFilepath, $outputBasepath);

			$functionmap[$functionFullname] = $functionRelativePath;

			$functionData = [];
			$functionData[] = '<?php';
			$functionData[] = '';
			if (!is_null($namespace)) {
				$functionData[] = 'namespace ' . $namespace . ';';
			}
			$functionData[] = '';
			$functionData[] = $printer->prettyPrint([$function]);

			yield new ResultFile(
				$functionFilepath,
				implode("\n", $functionData),
			);
		}
		ksort($functionmap);

		$autoloaderData = [];

		$autoloaderData[] = '<?php';
		$autoloaderData[] = '';
		$autoloaderData[] = 'spl_autoload_register(function(string $classname): void {';
		$autoloaderData[] = "\t" . '$classname = strtolower($classname);';
		$autoloaderData[] = '';
		$autoloaderData[] = "\t" . '$classmap = [];';
		foreach ($classmap as $classname => $classpath) {
			$autoloaderData[] = "\t" . '$classmap[\'' . $classname . '\'] = __DIR__ . \'/' . $classpath . '\';';
		}
		$autoloaderData[] = '';
		$autoloaderData[] = "\t" . 'if(isset($classmap[$classname])) {';
		$autoloaderData[] = "\t\t" . 'require_once $classmap[$classname];';
		$autoloaderData[] = "\t" . '}';
		$autoloaderData[] = '});';
		$autoloaderData[] = '';
		foreach ($functionmap as $functionname => $functionpath) {
			$autoloaderData[] = 'require __DIR__ . \'/' . $functionpath . '\'; // ' . $functionname;
		}

		$autoloaderPath = Path::makeAbsolute('./autoload.php', $outputBasepath);

		yield new ResultFile(
			$autoloaderPath,
			implode("\n", $autoloaderData),
		);
	}

	private function processStatement(Stmt $statement, ?string $namespace = null): void {
		if ($statement instanceof Namespace_) {
			$namespace = null;
			if ($statement->name instanceof Name) {
				$namespace = $statement->name->name;
			}

			foreach ($statement->stmts as $substatement) {
				$this->processStatement($substatement, $namespace);
			}

			return;
		}

		if ($statement instanceof ClassLike) {
			$this->addClass($statement, $namespace);

			return;
		}

		if ($statement instanceof FunctionLike) {
			$this->addFunction($statement, $namespace);

			return;
		}
	}
}
