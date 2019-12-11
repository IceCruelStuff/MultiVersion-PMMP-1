<?php

namespace Bavfalcon9\MultiVersion\Versions;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v1_12_0 extends Version {

    public function __construct() {
        $this->version = '1.12.0';
        $this->protocol = ProtocolVersion::VERSIONS['1.12.0'];
        $this->allowed = ['1.13.0', '1.14.0'];
        $this->disabled = true;
    }

    public function onLoad(PacketManager $packetManager, $plugin): void {
        $newVersion = new ProtocolVersion(ProtocolVersion::VERSIONS["1.12.0"], "1.12.0", false);
        $newVersion->setProtocolPackets([
            "LoginPacket" => 0x01,
            "StartGamePacket" => 0x0b,
            "RespawnPacket" => 0x2d,
            //"PlayerListPacket" => 0x3f,
            //"PlayerSkinPacket" => 0x5d,
            "ExplodePacket" => 0x17
            //"ResourcePackDataInfoPacket" => 0x52
        ]);
        $packetManager->registerProtocol($newVersion);
        define("MULTIVERSION_v1_12_0", $plugin->getDataFolder()."v1_12_0");
    }
}