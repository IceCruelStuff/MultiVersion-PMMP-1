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

namespace Bavfalcon9\MultiVersion\Utils;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\utils\MainLogger;
use function explode;
use function implode;

class ProtocolVersion {
    public const DEVELOPER = true; // set to true for debug
    public const VERSIONS = [
        '1.12.0' => 361,
        '1.13.0' => 388
    ];

    /** @var int */
    private $protocol;
    /** @var array */
    private $protocolPackets = [];
    /** @var string */
    private $dir = '';
    /** @var string */
    private $dirPath = '';
    /** @var string */
    private $minecraftVersion = '1.13.0';

    /**
     * @param int    $protocol
     * @param String $MCPE
     */
    public function __construct(int $protocol, String $MCPE) {
        $fixedMCPE = 'v'.implode('_', explode('.', $MCPE));
        $this->protocol = $protocol;
        $this->dirPath = "Bavfalcon9\\MultiVersion\\Protocols\\".$fixedMCPE."\\";
        $this->dir = $this->dirPath . "Packets\\";
        $this->minecraftVersion = $MCPE;
    }

    /**
     * @param array $packets
     */
    public function setProtocolPackets(Array $packets) {
        $this->protocolPackets = $packets;
    }

    /**
     * @return int
     */
    public function getProtocol(): int {
        return $this->protocol;
    }

    /**
     * @return array
     */
    public function getProtocolPackets(): array {
        return $this->protocolPackets;
    }

    /**
     * @return String
     */
    public function getMinecraftVersion(): String {
        return $this->minecraftVersion;
    }

    /**
     * @param Float $id
     *
     * @return String
     */
    public function getPacketName(Float $id): String {
        foreach ($this->protocolPackets as $pid => $name) {
            if ($id == $pid) {
                return $name;
            }
        }

        return "$id";
    }

    /**
     * @param String     $name
     * @param DataPacket &$packet
     * @param String     $type
     *
     * @return void
     */
    public function changePacket(String $name, DataPacket &$packet, String $type = 'SENT'): void {
        if (!isset($this->protocolPackets[$packet->pid()])) {
            if (self::DEVELOPER) {
                MainLogger::getLogger()->info("§c[MultiVersion] DEBUG:§e Packet §8[§f{$packet->getName()} §8| §f".$packet::NETWORK_ID."§8]§e requested a change but no change supported §a{$type}§e.");
            }

            return;
        }

        $pk = $this->dir . $name;
        /** @var DataPacket $pk */
        $pk = new $pk;
        $pk->setBuffer($packet->buffer, $packet->offset);

        if ($pk instanceof CustomTranslator) {
            $pk = $pk->translateCustomPacket($packet);
        }

        $packet = $pk;
        
        if (self::DEVELOPER) {
            MainLogger::getLogger()->info("§6[MultiVersion] DEBUG: Modified Packet §8[§f{$packet->getName()} §8| §f".$packet::NETWORK_ID."§8]§6 §a{$type}§6.");
        }

        return;
    }

    public function translateLogin(&$packet){
        $pk = $this->dir . 'LoginPacket';
        $pk = new $pk;
        $pk->translateLogin($packet);
    }
}