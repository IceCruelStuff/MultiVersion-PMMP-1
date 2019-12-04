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

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class TickSyncPacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::EXPLODE_PACKET;

    /** @var int */
    public $clientRequestTimestamp;
    /** @var int */
    public $serverReceptionTimestamp;

    protected function encodePayload(){
        $this->putLong($this->clientRequestTimestamp);
        $this->putLong($this->serverReceptionTimestamp);
    }

    protected function decodePayload(){
        $this->clientRequestTimestamp = $this->getLong();
        $this->serverReceptionTimestamp = $this->getLong();
    }

    public function handle(NetworkSession $session) : bool{
        return false;
    }
}