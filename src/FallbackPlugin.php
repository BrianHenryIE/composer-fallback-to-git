<?php

namespace BrianHenryIE\ComposerFallbackToGit;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ComposerRepository;

class FallbackPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {

        // Figure out what packages are not available on Packagist

        $config =  $composer->getConfig();
        $httpDownloader = Factory::createHttpDownloader($io, $config);
        $composerRepository = new ComposerRepository(array (
            'type' => 'composer',
            'url' => 'https://repo.packagist.org',
        ), $io, $config,$httpDownloader);

        $composerRequires = array_merge(
			$composer->getPackage()->getRequires(),
	        $composer->getPackage()->getDevRequires()
        );

        $missingPackages = array();

        foreach( $composerRequires as $name => $composerRequire ) {
			// TODO: This should check all custom repositories, not just Packagist.
            $onPackagist = $composerRepository->findPackage($name,$composerRequire->getConstraint());
            if( ! $onPackagist ) {
                $missingPackages[$name] = $composerRequire;
            }
        }

        foreach( $missingPackages as $name => $missingPackage ) {

            if('brianhenryie/composer-fallback-to-git' === $name){
                continue;
            }

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
            $composer->getRepositoryManager()->addRepository($repository);
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
