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

use Bavfalcon9\MultiVersion\Utils\CustomTranslator;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\Skin;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SkinAnimation;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SerializedImage;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\PlayerListEntry;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PlayerListPacket as PMListPacket;
use pocketmine\utils\MainLogger;
use function count;

class PlayerListPacket extends DataPacket implements CustomTranslator {

    public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;
    public const TYPE_ADD = 0;
    public const TYPE_REMOVE = 1;

    /** @var PlayerListEntry[] */
    public $entries = [];
    /** @var int */
    public $type;

    public function clean() {
        $this->entries = [];
        return parent::clean();
    }

    protected function decodePayload() {
        $this->type = $this->getByte();
        $count = $this->getUnsignedVarInt();
        for($i = 0; $i < $count; ++$i){
            $entry = new PlayerListEntry();
            if($this->type === self::TYPE_ADD) {
                $entry->uuid = $this->getUUID();
                $entry->entityUniqueId = $this->getEntityUniqueId();
                $entry->username = $this->getString();
                $entry->xboxUserId = $this->getString();
                $entry->platformChatId = $this->getString();
                $entry->buildPlatform = $this->getLInt();
                $entry->skin = $this->getSkin();
                $entry->isTeacher = $this->getBool();
                $entry->isHost = $this->getBool();
            } else {
                $entry->uuid = $this->getUUID();
            }

            $this->entries[$i] = $entry;
        }
    }

    protected function encodePayload() {
        $this->putByte($this->type);
        $this->putUnsignedVarInt(count($this->entries));
        foreach($this->entries as $entry){
            if($this->type === self::TYPE_ADD){
                $this->putUUID($entry->uuid);
                $this->putEntityUniqueId($entry->entityUniqueId);
                $this->putString($entry->username);
                $this->putString($entry->xboxUserId);
                $this->putString($entry->platformChatId);
                $this->putLInt($entry->buildPlatform);
                $this->putSkin($entry->skin);
                $this->putBool($entry->isTeacher);
                $this->putBool($entry->isHost);
            }else{
                $this->putUUID($entry->uuid);
            }
        }
    }

    public function getSkin() : Skin {
        $skinId = $this->getString();
        $skinResourcePatch = $this->getString();
        $skinData = $this->getImage();
        $animationCount = $this->getLInt();
        $animations = [];
        for($i = 0, $count = $animationCount; $i < $count; ++$i){
            $animations[] = new SkinAnimation($this->getImage(), $this->getLInt(), $this->getLFloat());
        }

        $capeData = $this->getImage();
        $geometryData = $this->getString();
        $animationData = $this->getString();
        $premium = $this->getBool();
        $persona = $this->getBool();
        $capeOnClassic = $this->getBool();
        $capeId = $this->getString();
        $fullSkinId = $this->getString();

        return new Skin($skinId, $skinResourcePatch, $skinData, $animations, $capeData, $geometryData, $animationData, $premium, $persona, $capeOnClassic, $capeId);
    }

    public function putSkin(Skin $skin) : void {
        $this->putString($skin->getSkinId());
        $this->putString($skin->getSkinResourcePatch());
        $this->putImage($skin->getSkinData());
        $this->putLInt(count($animations = $skin->getAnimations()));
        foreach($animations as $animation){
            $this->putImage($animation->getImage());
            $this->putLInt($animation->getType());
            $this->putLFloat($animation->getFrames());
        }

        $this->putImage($skin->getCapeData());
        $this->putString($skin->getGeometryData());
        $this->putString($skin->getAnimationData());
        $this->putBool($skin->isPremium());
        $this->putBool($skin->isPersona());
        $this->putBool($skin->isCapeOnClassic());
        $this->putString($skin->getCapeId());
        $this->putString($skin->getFullSkinId());
    }

    public function putImage(SerializedImage $image) : void {
        $this->putLInt($image->getWidth());
        $this->putLInt($image->getHeight());
        $this->putString($image->getData());
    }

    public function getImage() : SerializedImage{
        $width = $this->getLInt();
        $height = $this->getLInt();
        $data = $this->getString();

        return new SerializedImage($width, $height, $data);
    }

    public function handle(NetworkSession $session) : bool {
        return $session->handlePlayerSkin($this);
    }

    /**
     * @param PMListPacket $packet
     *
     * @return PlayerListPacket|PMListPacket
     */
    public function translateCustomPacket($packet) {
        if ($packet->type !== self::TYPE_ADD) {
            return $packet;
        }

        $this->type = $packet->type;

        foreach($packet->entries as $key => $entry) {
            $newEntry = new PlayerListEntry();
            $newEntry->uuid = $entry->uuid;
            $newEntry->entityUniqueId = $entry->entityUniqueId;
            $newEntry->username = $entry->username;
            $newEntry->xboxUserId = $entry->xboxUserId;
            $newEntry->platformChatId = $entry->platformChatId;
            $newEntry->buildPlatform = -1;
            $newEntry->skin = Skin::convertFromLegacySkin($entry->skin);
            $newEntry->isTeacher = false;
            $newEntry->isHost = false;

            $this->entries[$key] = $newEntry;
        }

        return $this;
    }
}