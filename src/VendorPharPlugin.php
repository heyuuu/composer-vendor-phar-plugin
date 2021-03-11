<?php

namespace VendorPharPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class VendorPharPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement activate() method.
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => EventHandler::class . '::handle',
            'post-update-cmd'  => EventHandler::class . '::handle',
        ];
    }
}
