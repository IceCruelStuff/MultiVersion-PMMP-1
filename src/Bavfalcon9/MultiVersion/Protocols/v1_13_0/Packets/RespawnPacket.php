<?php

declare(strict_types=1);

namespace Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets;

use Bavfalcon9\MultiVersion\Utils\CustomTranslator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket as PMRespawnPacket;

class RespawnPacket extends DataPacket implements CustomTranslator{
    public const NETWORK_ID = PMRespawnPacket::NETWORK_ID;

    public const STATE_SEARCHING_FOR_SPAWN = 0;
    public const STATE_READY_TO_SPAWN = 1;

    public const STATE_CLIENT_READY_TO_SPAWN = 2;

    /** @var Vector3 */
    public $position;
    /** @var int */
    public $state;
    /** @var int */
    public $entityRuntimeId = -1;

    protected function decodePayload(){
        $this->position = $this->getVector3();
        $this->state = $this->getByte();
        $this->entityRuntimeId = $this->getEntityRuntimeId();
    }

    protected function encodePayload(){
        $this->putVector3($this->position);
        $this->putByte($this->state);
        $this->putEntityRuntimeId($this->entityRuntimeId);
    }

    public function handle(NetworkSession $session) : bool{
        return false;
    }

    /**
     * @param PMRespawnPacket $packet
     *
     * @return RespawnPacket
     */
    public function translateCustomPacket($packet){
        $this->position = $packet->position;
        $this->state = self::STATE_SEARCHING_FOR_SPAWN;
        $this->entityRuntimeId = -1;

        return $this;
    }
}