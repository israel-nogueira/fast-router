<?php

namespace IsraelNogueira\FastRouter;

use Composer\Installer\LibraryInstaller;

class ComposerInstaller extends LibraryInstaller
{
    public function getInstallPath(\Composer\Package\PackageInterface $package)
    {
        $name = $package->getPrettyName();
        if (strpos($name, 'israel-nogueira/') !== 0) {
            throw new \InvalidArgumentException('Unable to install package ' . $name . '. This installer is intended to be used only for packages with a name that starts with "israel-nogueira/".');
        }
        return 'src';
    }

    public function supports($packageType)
    {
        return 'library' === $packageType;
    }
}
