<?php

namespace BrianHenryIE\ComposerFallbackToGit;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ComposerRepository;
use Composer\Util\Http\RequestProxy;

class FallbackPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {

        // Figure out what packages are not available on Packagist/other configured repositories.

        $config = $composer->getConfig();
        $httpDownloader = Factory::createHttpDownloader($io, $config);

        $composerRequires = array_merge(
			$composer->getPackage()->getRequires(),
	        $composer->getPackage()->getDevRequires()
        );
		unset($composerRequires['brianhenryie/composer-fallback-to-git']);

        $missingPackages = array();

        foreach( $composerRequires as $name => $composerRequire ) {

	        $onPackagist = false;
			foreach($composer->getRepositoryManager()->getRepositories() as $composerRepository) {
			    /**
			     * [TypeError]
			     * Composer\Util\Http\RequestProxy::__construct(): Argument #2 ($auth) must be of type ?string, array given,
			     * called in phar:///usr/local/bin/composer/src/Composer/Util/Http/ProxyManager.php on line 103
			     *
			     * @see RequestProxy
			     *
			     * Composer\Repository\ComposerRepository->findPackage() at /home/runner/work/bh-wp-slswc-client/bh-wp-slswc-client/vendor/brianhenryie/composer-fallback-to-git/src/FallbackPlugin.php:33
			     * BrianHenryIE\ComposerFallbackToGit\FallbackPlugin->activate() at phar:///usr/local/bin/composer/src/Composer/Plugin/PluginManager.php:392
			     */
				set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) use ($name, $composerRequire) {
					echo "\$composerRepository->findPackage( $name, $composerRequire->getConstraint() )){" . PHP_EOL;
					print_r(debug_backtrace());
					// Don't execute PHP internal error handler.
					return true;
				});

                if($composerRepository->findPackage($name, $composerRequire->getConstraint())){
                    $onPackagist = true;
            	}

				restore_error_handler();
			}
            if( ! $onPackagist ) {
                $missingPackages[$name] = $composerRequire;
            }
			unset($onPackagist);
        }

        foreach( $missingPackages as $name => $missingPackage ) {

            // Ignore packages that could not possibly correlate with a GitHub repo.
            if( false === stripos( $name, '/' ) ) {
                continue;
            }

            try {
                $response = $httpDownloader->get("https://github.com/$name");
            } catch (\Exception $e) {
                continue;
            }

            if( $response->getStatusCode() !== 200 ) {
                continue;
            }

            $repository = $composer->getRepositoryManager()->createRepository(
              'git',
                ["url"=> "https://github.com/$name",
                "type"=> "git"],
                $name
            );

			try {
				// This should maybe just use '*'.
				$is_in_repository = $repository->findPackage( $name, $missingPackage->getConstraint() );
			}catch (\Exception $e){
				// Fails when there is no composer.json in the repo.
				$is_in_repository = false;
			}

			if( $is_in_repository ) {
				$composer->getRepositoryManager()->addRepository( $repository );

				$io->write("Using https://github.com/{$name} for {$name}.");

				continue;
			}

	        $tags = json_decode($httpDownloader->get("https://api.github.com/repos/{$name}/tags")->getBody());
	        $branches = json_decode($httpDownloader->get("https://api.github.com/repos/{$name}/branches")->getBody());

			$tagNames = array_map(function($tag){
				return $tag->name;
			}, $tags);
			$branchNames = array_map(function($branch){
				return $branch->name;
			}, $branches);

			if(in_array($missingPackage->getConstraint()->getPrettyString(), $tagNames)) {
				$reference = $missingPackage->getConstraint()->getPrettyString();
			}elseif(in_array($missingPackage->getConstraint()->getPrettyString(), $branchNames)) {
				$reference = $missingPackage->getConstraint()->getPrettyString();
			}elseif(!empty( $tags )) {
				$reference = $tags[0]->name; // The most recent tag.
			}else {
				$master = array_filter($branches, function($branch){
					return $branch->name === 'master';
				});
				$main = array_filter($branches, function($branch){
					return $branch->name === 'main';
				});
				$reference = $master ? 'master' : ($main ? 'main' : 'dev-master');
			}

	        $repository = $composer->getRepositoryManager()->createRepository(
		        'package',
		        ["package"=>[
					"name" => $name,
					"version" => $reference, // TODO: A little worried about this but let's see does it work.
			        "source" => [
						"url"=> "https://github.com/$name",
		                "type"=> "git",
				        "reference" => $reference,
			        ],
			        "installation-source" => "source",
	            ]]
	        );
			$composer->getRepositoryManager()->addRepository($repository);

	        $io->write("Using https://github.com/{$name} for {$name}.");
        }
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }
}
