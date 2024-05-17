<?php
/**
 * @see \BrianHenryIE\ComposerFallbackToGit\FallbackPlugin
 */

namespace BrianHenryIE\ComposerFallbackToGit;

use Composer\Factory;
use Composer\IO\NullIO;

class FallbackTest extends IntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

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
		$this->runComposer("composer require brianhenryie/composer-fallback-to-git:dev-main --dev");
	}

    public function test_one(): void {

        $this->runComposer("composer require schneiderundschuetz/document-generator-for-openapi:dev-master");

        self::assertDirectoryExists($this->testsWorkingDir . 'vendor/schneiderundschuetz/document-generator-for-openapi');
    }

	public function test_ext_json(): void {

		$this->runComposer("composer require ext-json:*");

		$this->runComposer("composer require schneiderundschuetz/document-generator-for-openapi:dev-master");

		self::assertDirectoryExists($this->testsWorkingDir . 'vendor/schneiderundschuetz/document-generator-for-openapi');
	}

	public function test_brianhenryie_composer_phpstorm(): void {

		$this->runComposer("composer require brianhenryie/composer-phpstorm:dev-master --dev");

		self::assertDirectoryExists($this->testsWorkingDir . 'vendor/brianhenryie/composer-phpstorm');
	}
	public function test_handle_404_on_github(): void {

		$this->runComposer("composer config repositories.outlandishideas/wpackagist composer https://wpackagist.org");
		$this->runComposer("composer config allow-plugins.composer/installers true");
		$this->runComposer("composer require wpackagist-plugin/document-generator-for-openapi:*");

		self::assertDirectoryExists($this->testsWorkingDir . 'wp-content/plugins/document-generator-for-openapi');
	}

	/**
	 * No valid composer.json was found in any branch or tag of https://github.com/wordpress/wordpress, could not load a package from it.
     */
	public function test_no_composerjson_in_github_repo(): void {

		$this->runComposer('composer require wordpress/wordpress:* --dev');

		$sut = new FallbackPlugin();

		$sut->activate($this->composer->getComposer(), new NullIO());

		self::assertDirectoryExists($this->testsWorkingDir . 'vendor/wordpress/wordpress');
	}
}