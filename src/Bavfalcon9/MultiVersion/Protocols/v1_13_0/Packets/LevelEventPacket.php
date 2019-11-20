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

namespace Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets;

use Bavfalcon9\MultiVersion\Utils\BatchCheck;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket as PMLevelEvent;
use pocketmine\network\mcpe\protocol\types\RuntimeBlockMapping as PMRuntimeBlockMapping;

class LevelEventPacket extends BatchCheck {
    /**
     * @param LevelEventPacket $packet
     *
     * @return void
     */
    public function onPacketMatch(&$packet) : Void {
        $packet->decode();
        if ($packet->evid === PMLevelEvent::EVENT_PARTICLE_DESTROY) {
            list($id, $meta) = PMRuntimeBlockMapping::fromStaticRuntimeId($packet->data);
            $packet->data = RuntimeBlockMapping::toStaticRuntimeId($id, $meta);
        }
        $packet->encode();
    }
}