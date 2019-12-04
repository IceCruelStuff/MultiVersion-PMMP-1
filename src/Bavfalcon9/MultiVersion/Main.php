<?php

/**
 *    ___  ___      _ _   _ _   _               _             
 *    |  \/  |     | | | (_) | | |             (_)            
 *    | .  . |_   _| | |_ _| | | | ___ _ __ ___ _  ___  _ __  
 *    | |\/| | | | | | __| | | | |/ _ \ '__/ __| |/ _ \| '_ \ 
 *    | |  | | |_| | | |_| \ \_/ /  __/ |  \__ \ | (_) | | | |
 *    \_|  |_/\__,_|_|\__|_|\___/ \___|_|  |___/_|\___/|_| |_|
 * 
 * Copyright (C) 2019 Olybear9 (Bavfalcon9)                            
 *                                                            
 */

declare(strict_types=1);

namespace Bavfalcon9\MultiVersion;

use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use function define;
use function scandir;

class Main extends PluginBase {

    public function onEnable() {
        if (!isset(ProtocolVersion::VERSIONS[ProtocolInfo::MINECRAFT_VERSION_NETWORK])) {
            $this->getLogger()->critical("Server version:". ProtocolInfo::MINECRAFT_VERSION_NETWORK . "not supported by multiversion.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        define('MultiVersionFile', $this->getFile());
        $this->getServer()->getPluginManager()->registerEvents(new EventManager($this), $this);
        $this->saveAllResources();
    }

    private function saveAllResources(){
        $resourcePath = $this->getFile() . "resources";
        $versions = scandir($resourcePath);
        foreach ($versions as $version) {
            if ($version === '.' || $version === '..') {
                continue;
            }

            $files = scandir($resourcePath . "/" . $version);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $this->saveResource($version . "/" . $file);
            }
        }
    }
}