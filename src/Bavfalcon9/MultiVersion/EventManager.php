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

use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\RespawnPacket;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\TickSyncPacket;
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\VersionIdentifier;
use Bavfalcon9\MultiVersion\Versions\v1_12_0;
use Bavfalcon9\MultiVersion\Versions\v1_13_0;
use Bavfalcon9\MultiVersion\Versions\v1_14_0;

use pocketmine\event\Listener;
use pocketmine\utils\MainLogger;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use function define;

class EventManager implements Listener {
    /** @var Main */
    private $plugin;
    /** @var PacketManager */
    private $packetManager;
    /** @var Array */
    private $versions = [];

    /**
     * EventManager constructor.
     *
     * @param Main $pl
     */
    public function __construct(Main $pl) {
        $this->plugin = $pl;
        $this->packetManager = new PacketManager($pl);
        $this->loadMultiVersion();
    }

    /**
     * @param DataPacketReceiveEvent $event
     *
     * @return void
     */
    public function onReceive(DataPacketReceiveEvent $event): void {
        $this->packetManager->handlePacketReceive($event);
    }

    /**
     * @param DataPacketSendEvent $event
     *
     * @return void
     */
    public function onSend(DataPacketSendEvent $event): void {
        $this->packetManager->handlePacketSent($event);
    }

    private function loadMultiVersion(): void {
        $config = $this->plugin->getSavedData();
        $supported = ($config) ? $config->get('support-versions') : [];
        $versions = [
            "1.12.0" => new v1_12_0(),
            "1.13.0" => new v1_13_0(),
            "1.13.1" => new v1_13_1(),
            "1.14.0" => new v1_14_0()
        ];

        foreach ($supported as $version) {
            $supportedVersions = new VersionIdentifier($version);
            $supportedVersions = $supportedVersions->getSupported(array_keys($versions));
            
            if (empty($supportedVersions)) {
                MainLogger::getLogger()->critical("[MultiVersion]: Tried to load non-supported version: {$version}");
                continue;
            }

            foreach ($supportedVersions as $v) {
                $version = $versions[$version];
                if ($version->isDisabled()) {
                    $version->onDisable('[Version Disabled]');
                    continue;
                }

                if (ProtocolInfo::MINECRAFT_VERSION_NETWORK === $version->getVersionName()) {
                    $version->onMatchesVersion();
                    continue;    
                }

                if (!$version->isAllowed(ProtocolInfo::MINECRAFT_VERSION_NETWORK)) {
                    $version->onNotAllowed();
                    continue;
                }
                try {
                    $version->onLoad($this->packetManager, $this->plugin);
                    $version->onEnable();
                    $this->versions[$version->getVersionName()] = $version;
                } catch (\Throwable $e) {
                    # echo $e;
                    $version->onDisable('[Error Occurred]');
                    continue;
                }
            }
        }
    }
}