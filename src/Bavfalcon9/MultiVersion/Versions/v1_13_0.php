<?php

namespace Bavfalcon9\MultiVersion\Versions;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v1_13_0 extends Version {

    public function __construct() {
        $this->version = '1.13.0';
        $this->protocol = ProtocolVersion::VERSIONS['1.13.0'];
        $this->allowed = ['1.12.0'];
        $this->disabled = false;
    }

    public function onLoad(PacketManager $packetManager, $plugin): void {
        // 1.13 support on MCPE 1.12
        $newVersion = new ProtocolVersion(ProtocolVersion::VERSIONS["1.13.0"], "1.13.0", false);
            PacketPool::registerPacket(new TickSyncPacket());
            PacketPool::registerPacket(new RespawnPacket());
            $newVersion->setProtocolPackets([
                "AddActorPacket" => 0x0d,
                "LevelEventPacket" => 0x19,
                "LevelSoundEventPacket" => 0x18,
                "LoginPacket" => 0x01,
                "PlayerListPacket" => 0x3f,
                "PlayerSkinPacket" => 0x5d,
                "RespawnPacket" => 0x2d,
                "StartGamePacket" => 0x0b,
                "UpdateBlockPacket" => 0x15
            ]);
            $newVersion->setListeners([]);
            $packetManager->registerProtocol($newVersion);
            define("MULTIVERSION_v1_13_0", $plugin->getDataFolder()."v1_13_0");
    }
}