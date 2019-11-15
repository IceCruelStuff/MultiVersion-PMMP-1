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

namespace Bavfalcon9\MultiVersion\Protocols\v1_12_0\Packets;

use Bavfalcon9\MultiVersion\Utils\CustomTranslator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket as PMRespawn;

class RespawnPacket extends DataPacket implements CustomTranslator{
    public const NETWORK_ID = 0x2d;

    /** @var Vector3 */
    public $position;

    protected function decodePayload(){
        $this->position = $this->getVector3();
    }

    protected function encodePayload(){
        $this->putVector3($this->position);
    }

    public function handle(NetworkSession $session) : bool{
        return $session->handleRespawn($this);
    }

    /**
     * @param PMRespawn $packet
     *
     * @return $this
     */
    public function translateCustomPacket($packet){
        $this->position = $packet->position;

        return $this;
    }
}