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

namespace Bavfalcon9\MultiVersion\Protocols\v1_14_0\Packets;

use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;
use pocketmine\network\mcpe\protocol\LoginPacket as PMLogin;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function unpack;

class LoginPacket extends PMLogin{

    protected function decodePayload(){
        $this->protocol = ((unpack("N", $this->get(4))[1] << 32 >> 32));
        $this->protocol = ProtocolInfo::CURRENT_PROTOCOL; // Hack to allow a bypass
        if($this->protocol !== ProtocolInfo::CURRENT_PROTOCOL){
            if($this->protocol > 0xffff){ //guess MCPE <= 1.1
                $this->offset -= 6;
                $this->protocol = ((unpack("N", $this->get(4))[1] << 32 >> 32));
            }
        }

        try{
            $this->decodeConnectionRequest();
        }catch(\Throwable $e){
            if($this->protocol === ProtocolInfo::CURRENT_PROTOCOL){
                throw $e;
            }

            $logger = MainLogger::getLogger();
            $logger->debug(get_class($e) . " was thrown while decoding connection request in login (protocol version " . ($this->protocol ?? "unknown") . "): " . $e->getMessage());
            foreach(Utils::printableTrace($e->getTrace()) as $line){
                $logger->debug($line);
            }
        }
    }

    public function translateLogin($packet){
        // $this->protocol =  Why did i do this?
        $this->protocol = ProtocolInfo::CURRENT_PROTOCOL; // required to assign a temporary bypass through the server.
        $this->clientData = $packet->clientData;
        $this->clientData['SkinGeometry'] = $packet->clientData['SkinGeometryData'];

        return $this;
    }
}