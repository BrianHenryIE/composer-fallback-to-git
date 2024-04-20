<?php

namespace BrianHenryIE\ComposerFallbackToGit;

class FallbackTest extends IntegrationTestCase {

    public function test_one(): void {

        $projectDirectory = realpath(getcwd().'/..');

        $composerJsonString = <<<EOD
{
  "name": "brianhenryie/composer-fallback-to-git-test",
  "repositories": [
   {
            "type": "path",
            "url": "$projectDirectory"
        }
    ],
  "config": {
    "secure-http": false
  },
    "minimum-stability": "dev",
  "prefer-stable": true
}
EOD;
        json_decode($composerJsonString,JSON_THROW_ON_ERROR);

        file_put_contents($this->testsWorkingDir . 'composer.json', $composerJsonString);

        chdir($this->testsWorkingDir);

        $this->runComposer("composer install");
        $this->runComposer("composer config allow-plugins.brianhenryie/composer-fallback-to-git true");
        $this->runComposer("composer require brianhenryie/composer-fallback-to-git:dev-main");
        $this->runComposer("composer require schneiderundschuetz/document-generator-for-openapi:dev-master");

        self::assertDirectoryExists($this->testsWorkingDir . 'vendor/schneiderundschuetz/document-generator-for-openapi');
    }

	public function test_ext_json(): void {

		$projectDirectory = realpath(getcwd().'/..');

		$composerJsonString = <<<EOD
{
  "name": "brianhenryie/composer-fallback-to-git-test",
  "repositories": [
   {
            "type": "path",
            "url": "$projectDirectory"
        }
    ],
  "require": {
    "php": ">=7.4",
    "ext-json": "*"
  },
  "config": {
    "secure-http": false
  },
    "minimum-stability": "dev",
  "prefer-stable": true
}
EOD;
		json_decode($composerJsonString,JSON_THROW_ON_ERROR);

		file_put_contents($this->testsWorkingDir . 'composer.json', $composerJsonString);

		chdir($this->testsWorkingDir);

		$this->runComposer("composer install");
		$this->runComposer("composer config allow-plugins.brianhenryie/composer-fallback-to-git true");
		$this->runComposer("composer require brianhenryie/composer-fallback-to-git:dev-main");
		$this->runComposer("composer require schneiderundschuetz/document-generator-for-openapi:dev-master");

		self::assertDirectoryExists($this->testsWorkingDir . 'vendor/schneiderundschuetz/document-generator-for-openapi');
	}
}