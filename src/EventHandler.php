<?php

namespace VendorPharPlugin;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;

class EventHandler
{
    /**
     * @var Event
     */
    protected $event;

    /**
     * @var IOInterface
     */
    protected $io;

    public function handle(Event $event)
    {
        $this->event = $event;
        $this->io    = $event->getIO();

        // 若环境监测未通过，则直接返回
        if (!static::checkEnvironment()) {
            return;
        }

        // 获取目录地址
        $vendorDir    = $event->getComposer()->getConfig()->get('vendor-dir');
        $pharPath     = $vendorDir . '/vendor.phar';
        $autoloadFile = $vendorDir . '/autoload.php';

        // 重新生成 phar
        $this->rebuildPhar($vendorDir, $pharPath);

        // 替换自动加载入口
        $content = file_get_contents($autoloadFile);
        $content = str_replace('return ', '', $content);
        $content .= <<<AUTOLOAD

return require_once __DIR__ . '/vendor.phar';

AUTOLOAD;
        file_put_contents($autoloadFile, $content);

        // 输出日志
        $this->io->info('Create vendor.phar success');
    }

    protected function checkEnvironment(): bool
    {
        if (!extension_loaded('phar')) {
            $this->io->error('Please add extension `phar` to use ZipVendor Plugin');
            return false;
        }

        if (ini_get('phar.readonly')) {
            $this->io->error('Please set ini phar.readonly=off to use ZipVendor Plugin');
            return false;
        }

        return true;
    }

    protected function rebuildPhar(string $vendorDir, string $pharPath)
    {
        (new Filesystem())->remove($pharPath);
        $phar = new \Phar($pharPath);
        $phar->buildFromDirectory($vendorDir);

        // 替换suffix
        $oldSuffix    = static::getSuffix($vendorDir);
        $newSuffix    = md5(uniqid('', true));
        $replaceFiles = ['autoload.php', 'composer/autoload_real.php', 'composer/autoload_static.php'];
        foreach ($replaceFiles as $replaceFile) {
            $phar[$replaceFile] = str_replace($oldSuffix, $newSuffix, $phar[$replaceFile]->getContent());
        }

        $phar->setDefaultStub('autoload.php');
    }

    protected static function getSuffix(string $vendorDir)
    {
        $content = file_get_contents($vendorDir . '/autoload.php');
        if (preg_match('{ComposerAutoloaderInit([^:\s]+)::}', $content, $match)) {
            return $suffix = $match[1];
        }
        throw new \Exception('获取 autoload.php 中的 suffix 失败');
    }
}
