<?php

namespace Bavfalcon9\MultiVersion\Versions;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v1_13_1 extends Version {

    public function __construct() {
        $this->version = '1.13.0';
        $this->protocol = ProtocolVersion::VERSIONS['1.13.0'];
        $this->allowed = ['1.14.0'];
        $this->disabled = false;
    }

    public function onLoad(PacketManager $packetManager, $plugin): void {
        // 1.13 support on MCPE 1.14
        $newVersion = new ProtocolVersion(ProtocolVersion::VERSIONS["1.13.0"], "1.13.1", false);
            PacketPool::registerPacket(new TickSyncPacket());
            PacketPool::registerPacket(new RespawnPacket());
            $newVersion->setProtocolPackets([
                "LoginPacket" => 0x01
            ]);
            $newVersion->setListeners([]);
            $packetManager->registerProtocol($newVersion);
            define("MULTIVERSION_v1_13_0", $plugin->getDataFolder()."v1_13_0");
    }
}