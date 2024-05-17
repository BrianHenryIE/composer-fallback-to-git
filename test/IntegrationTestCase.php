<?php
/**
 * Creates a deletes a temp directory for tests.
 *
 * Could just system temp directory, but this is useful for setting breakpoints and seeing what has happened.
 */

namespace BrianHenryIE\ComposerFallbackToGit;

use Composer\Console\Application as Composer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class IntegrationTestCase
 * @coversNothing
 */
class IntegrationTestCase extends TestCase
{
    protected $testsWorkingDir;

	protected Composer $composer;

    public function setUp(): void
    {
        parent::setUp();

        $this->testsWorkingDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . 'fallback' . DIRECTORY_SEPARATOR;

        if ('Darwin' === PHP_OS) {
            $this->testsWorkingDir = DIRECTORY_SEPARATOR . 'private' . $this->testsWorkingDir;
        }

        if (file_exists($this->testsWorkingDir)) {
            $this->deleteDir($this->testsWorkingDir);
        }

	    $this->composer = new Composer();
	    $this->composer->setAutoExit(false);

        @mkdir($this->testsWorkingDir);
    }


    protected function runComposer(string $command) {
        try {
            return $this->composer->run(new ArgvInput(explode(' ', $command)));
        }catch( \Exception $exception ) {
            self::fail( $exception->getMessage());
        }
    }


    /**
     * Delete $this->testsWorkingDir after each test.
     *
     * @see https://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $dir = $this->testsWorkingDir;

        $this->deleteDir($dir);
    }

    protected function deleteDir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (is_link($file)) {
                unlink($file);
            } elseif ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
