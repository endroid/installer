<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\Installer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

final class Installer implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;

    private $projectTypes = [
        'symfony' => [
            'config/packages',
            'public',
        ],
    ];

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => ['install', 1],
            ScriptEvents::POST_UPDATE_CMD => ['install', 1],
        ];
    }

    public function install(): void
    {
        $enabled = $this->composer->getPackage()->getExtra()['endroid']['installer']['enabled'] ?? true;
        $exclude = $this->composer->getPackage()->getExtra()['endroid']['installer']['exclude'] ?? [];

        if (!$enabled) {
            $this->io->write('<info>Endroid Installer was disabled</>');

            return;
        }

        $projectType = $this->detectProjectType();

        if (null === $projectType) {
            $this->io->write('<info>Endroid Installer did not detect a compatible project type for auto-configuration</>');

            return;
        }

        $processedPackages = [];
        $this->io->write('<info>Endroid Installer detected project type "'.$projectType.'"</>');
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();

        foreach ($packages as $package) {
            // Avoid handling duplicates: getPackages sometimes returns duplicates
            if (in_array($package->getName(), $processedPackages)) {
                continue;
            }
            $processedPackages[] = $package->getName();

            // Skip excluded packages
            if (in_array($package->getName(), $exclude)) {
                $this->io->write('- Skipping <info>'.$package->getName().'</>');
                continue;
            }

            // Check for installation files and install
            $packagePath = $this->composer->getInstallationManager()->getInstallPath($package);
            $sourcePath = $packagePath.DIRECTORY_SEPARATOR.'.install'.DIRECTORY_SEPARATOR.$projectType;
            if (file_exists($sourcePath)) {
                $this->io->write('- Configuring <info>'.$package->getName().'</>');
                $this->copy($sourcePath, getcwd());
            }
        }
    }

    private function detectProjectType()
    {
        foreach ($this->projectTypes as $projectType => $paths) {
            foreach ($paths as $path) {
                if (!file_exists(getcwd().DIRECTORY_SEPARATOR.$path)) {
                    continue 2;
                }
            }

            return $projectType;
        }

        return null;
    }

    private function copy(string $sourcePath, string $targetPath): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $target = $targetPath.DIRECTORY_SEPARATOR.$iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target);
                }
            } elseif (!file_exists($target)) {
                $this->copyFile($item, $target);
            }
        }
    }

    public function copyFile(string $source, string $target): void
    {
        if (file_exists($target)) {
            return;
        }

        copy($source, $target);
        @chmod($target, fileperms($target) | (fileperms($source) & 0111));
    }
}
