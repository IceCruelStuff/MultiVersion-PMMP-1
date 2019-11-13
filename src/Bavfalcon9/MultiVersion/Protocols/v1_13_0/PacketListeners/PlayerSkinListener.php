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

namespace Bavfalcon9\MultiVersion\Protocols\v1_13_0\PacketListeners;

use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\PlayerListPacket;
use Bavfalcon9\MultiVersion\Utils\PacketListener;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerListPacket as PMPlayerList;

class PlayerSkinListener extends PacketListener {
    public function __construct() {
        parent::__construct('BatchPacket', BatchPacket::NETWORK_ID);
    }

    /**
     * @param BatchPacket $packet
     *
     * @return bool
     */
    public function onPacketCheck(&$packet): Bool {
        foreach($packet->getPackets() as $buf){
            $pk = PacketPool::getPacket($buf);
            if($pk instanceof PMPlayerList){
                return true;
            }
        }

        return false;
    }

    /**
     * @param BatchPacket $packet
     *
     * @return Void
     */
    public function onPacketMatch(&$packet): Void {
        $newBatch = new BatchPacket();
        foreach($packet->getPackets() as $buf){
            $pk = PacketPool::getPacket($buf);
            $pk->decode();
            if($pk instanceof PMPlayerList){
                $newPacket = new PlayerListPacket;

                $pk = $newPacket->translateCustomPacket($pk);
            }

            $pk->encode();

            $newBatch->addPacket($pk);
        }

        $packet = $newBatch;
    }
}