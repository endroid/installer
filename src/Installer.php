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
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;

final class Installer implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    private $io;
    private $filesystem;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->filesystem = new Filesystem();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'post-install-cmd' => 'onPostInstallCmd',
            'post-update-cmd' => 'onPostUpdateCmd',
        ];
    }

    public function onPostInstallCmd(CommandEvent $event): void
    {
        $this->install($event);
    }

    public function onPostUpdateCmd(CommandEvent $event): void
    {
        $this->install($event);
    }

    private function install(CommandEvent $event): void
    {
        die('f');
    }
}