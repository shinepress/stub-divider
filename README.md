# shinepress/stub-divider

[![License](https://img.shields.io/packagist/l/shinepress/stub-divider)](https://github.com/shinepress/stub-divider/blob/main/LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/shinepress/stub-divider?label=latest)](https://packagist.org/packages/shinepress/stub-divider/)
[![PHP Version](https://img.shields.io/packagist/dependency-v/shinepress/stub-divider/php?label=php)](https://www.php.net/releases/index.php)
[![Main Status](https://img.shields.io/github/actions/workflow/status/shinepress/stub-divider/verify.yml?branch=main&label=main)](https://github.com/shinepress/stub-divider/actions/workflows/verify.yml?query=branch%3Amain)
[![Release Status](https://img.shields.io/github/actions/workflow/status/shinepress/stub-divider/verify.yml?branch=release&label=release)](https://github.com/shinepress/stub-divider/actions/workflows/verify.yml?query=branch%3Arelease)
[![Develop Status](https://img.shields.io/github/actions/workflow/status/shinepress/stub-divider/verify.yml?branch=develop&label=develop)](https://github.com/shinepress/stub-divider/actions/workflows/verify.yml?query=branch%3Adevelop)


## Description

A tool for dividing up large stub files to speed up static analysis.


## Installation

The recommendend installation method is with composer:
```sh
$ composer require shinepress/stub-divider
```


## Usage


Point the script at the stub file you want to divide:
```sh
$ ./vendor/bin/divide-stubs ./path/to/my-stubfile.php
```

Then register the autoloader as a bootstrap file in your phpstan config:
```yaml
parameters:
    bootstrapFiles:
	    - ./my-stubfile/autoload.php
```
