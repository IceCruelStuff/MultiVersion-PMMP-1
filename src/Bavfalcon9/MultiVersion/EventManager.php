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
        if (ProtocolInfo::MINECRAFT_VERSION_NETWORK === "1.12.0") {
            // 1.13 support on MCPE 1.12
            $newVersion = new ProtocolVersion(ProtocolVersion::VERSIONS["1.13.0"], "1.13.0", false);
            PacketPool::registerPacket(new TickSyncPacket());
            PacketPool::registerPacket(new RespawnPacket());
            $newVersion->setProtocolPackets([
                "AddActorPacket" => 0x0d,
                "LoginPacket" => 0x01,
                "PlayerListPacket" => 0x3f,
                "PlayerSkinPacket" => 0x5d,
                "RespawnPacket" => 0x2d,
                "StartGamePacket" => 0x0b,
                "UpdateBlockPacket" => 0x15
            ]);
            $newVersion->setListeners([]);
            $newVersion = $this->packetManager->registerProtocol($newVersion);
            define("MULTIVERSION_v1_13_0", $this->plugin->getDataFolder()."v1_13_0");
            if (!$newVersion) {
                MainLogger::getLogger()->critical("[MultiVersion]: Failed to add version: 1.13.x");
            } else {
                MainLogger::getLogger()->info("[MultiVersion]: §aLoaded support for: 1.13.x");
            }
        }

        if (ProtocolInfo::MINECRAFT_VERSION_NETWORK === "1.13.0") {
            // 1.12 support on MCPE 1.13
            $newVersion = new ProtocolVersion(ProtocolVersion::VERSIONS["1.12.0"], "1.12.0", false);
            $newVersion->setProtocolPackets([
                "LoginPacket" => 0x01,
                "StartGamePacket" => 0x0b,
                "RespawnPacket" => 0x2d,
                "PlayerListPacket" => 0x3f,
                "PlayerSkinPacket" => 0x5d,
                "ExplodePacket" => 0x17,
                "ResourcePackDataInfoPacket" => 0x52
            ]);
            $newVersion = $this->packetManager->registerProtocol($newVersion);
            define("MULTIVERSION_v1_12_0", $this->plugin->getDataFolder()."v1_12_0");
            if (!$newVersion) {
                MainLogger::getLogger()->critical("[MULTIVERSION]: Failed to add version: 1.12.x");
            } else {
                MainLogger::getLogger()->info("[MultiVersion]: §aLoaded support for: 1.12.x");
            }
        }
    }
}