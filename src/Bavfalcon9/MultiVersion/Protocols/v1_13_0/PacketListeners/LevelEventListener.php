<?php

declare(strict_types=1);

namespace Bavfalcon9\MultiVersion\Protocols\v1_13_0\PacketListeners;

use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\RuntimeBlockMapping;
use Bavfalcon9\MultiVersion\Utils\PacketListener;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\RuntimeBlockMapping as PMRuntimeBlockMapping;

class LevelEventListener extends PacketListener{

    public function __construct(){
        parent::__construct("LevelEventPacket", LevelEventPacket::NETWORK_ID);
    }

    /**
     * @param LevelEventPacket $packet
     *
     * @return bool
     */
    public function onPacketCheck(&$packet) : Bool{
        echo "\n\n\n\n\nreacheddd\n\n\n\n\n";
        if($packet->evid == LevelEventPacket::EVENT_PARTICLE_DESTROY){
            return true;
        }

        return false;
    }

    /**
     * @param LevelEventPacket $packet
     *
     * @return Void
     */
    public function onPacketMatch(&$packet) : Void{
        list($id, $meta) = PMRuntimeBlockMapping::fromStaticRuntimeId($packet->data);
        $packet->data = RuntimeBlockMapping::toStaticRuntimeId($id, $meta);
    }
}