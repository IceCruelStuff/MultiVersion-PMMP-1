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
    public const DEVELOPER = false; // set to true for debug
    public const VERSIONS = [
        '1.12.0' => 361,
        '1.13.0' => 388
    ];

    /** @var int */
    private $protocol;
    /** @var array */
    private $protocolPackets = [];
    /** @var bool */
    private $restricted = false;
    /** @var string */
    private $dir = '';
    /** @var string */
    private $dirPath = '';
    /** @var string */
    private $minecraftVersion = '1.13.0';
    /** @var array */
    private $packetListeners = [];

    /**
     * @param int    $protocol
     * @param String $MCPE
     * @param bool   $restrict
     * @param array  $listeners
     */
    public function __construct(int $protocol, String $MCPE, Bool $restrict = false, Array $listeners = []) {
        $fixedMCPE = 'v'.implode('_', explode('.', $MCPE));
        $this->protocol = $protocol;
        $this->dirPath = "Bavfalcon9\\MultiVersion\\Protocols\\".$fixedMCPE."\\";
        $this->dir = $this->dirPath . "Packets\\";
        $this->restricted = $restrict;
        $this->minecraftVersion = $MCPE;
        $this->wantedListeners = $listeners;
        $this->registerListeners();
    }

    /**
     * @param array $packets
     */
    public function setProtocolPackets(Array $packets) {
        $this->protocolPackets = $packets;
    }

    /**
     * @param array $listeners
     */
    public function setListeners(Array $listeners) {
        $this->wantedListeners = $listeners;
        $this->registerListeners();
    }

    /**
     * @param PacketListener $listener
     *
     * @return bool
     */
    public function addPacketListener(PacketListener $listener): Bool {
        if (isset($this->packetListeners[$listener->getName()])) {
            return false;
        } else {
            $this->packetListeners[$listener->getName()] = $listener;
            return true;
        }
    }

    /**
     * @return Array[PacketListener]
     */

    public function getPacketListeners(): Array {
        return $this->packetListeners;
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
     * @return String
     */
    public function getDir(): String {
        return $this->dir;
    }

    /**
     * @return String
     */
    public function getDirPath(): String {
        return $this->dirPath;
    }

    /**
     * @param Float $id
     *
     * @return String
     */
    public function getPacketName(Float $id): String {
        foreach ($this->protocolPackets as $name => $pid) {
            if ($id == $pid) {
                return $name;
            }
        }

        return "$id";
    }

    /**
     * @return Bool
     */
    public function getRestricted(): Bool {
        return $this->restricted;
    }

    public function registerListeners() {
        foreach ($this->wantedListeners as $lsn) {
            $listener = $this->dirPath . "PacketListeners\\$lsn";
            $listener = new $listener;
            $this->addPacketListener($listener);
        }
    }
}