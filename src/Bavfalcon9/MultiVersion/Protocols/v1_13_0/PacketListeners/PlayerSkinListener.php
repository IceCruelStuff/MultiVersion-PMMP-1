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

namespace Bavfalcon9\MultiVersion\Protocols\v1_13_0\PacketListeners;

use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\RuntimeBlockMapping;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\Skin;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SkinAnimation;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SerializedImage;
use Bavfalcon9\MultiVersion\Utils\PacketListener;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\PlayerListPacket as PMPlayerList;

class PlayerSkinListener extends PacketListener {
    public function __construct() {
        parent::__construct('BatchPacket', BatchPacket::NETWORK_ID);
    }

    public function onPacketCheck(&$packet): Bool {
        foreach($packet->getPackets() as $buf){
            $pk = PacketPool::getPacket($buf);
            if($pk instanceof PMPlayerList){
                return true;
            }
        }
        return false;
    }

    public function onPacketMatch(&$packet): Void {
        foreach($packet->getPackets() as $buf){
            $pk = PacketPool::getPacket($buf);
            if($pk instanceof PMPlayerList){
                $newPacket = new \Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\PlayerListPacket();

                $pk = $this->decodePacketPayload($pk);
                $pk = $newPacket->translateCustomPacket($pk);
                $pk = $this->encodePacketPayload($pk);

                $newPayload = str_replace(strlen($buf) . $buf, $pk->buffer, $packet->payload);
                $packet->setBuffer($newPayload, $packet->offset);
            }
        }
    }

    private function encodePacketPayload($packet) {
        $packet->putByte($packet->type);
        $packet->putUnsignedVarInt(count($packet->entries));
        foreach($packet->entries as $entry){
            if($packet->type === PlayerListPacket::TYPE_ADD){
                $buildPlatform = (!isset($entry->buildPlatform)) ? -1 : $entry->buildPlatform;
                $isTeacher = (!isset($entry->isTeacher)) ? false : $entry->isTeacher;
                $isHost = (!isset($entry->isHost)) ? false : $entry->isHost;
                $packet->putUUID($entry->uuid);
                $packet->putEntityUniqueId($entry->entityUniqueId);
                $packet->putString($entry->username);
                $packet->putString($entry->xboxUserId);
                $packet->putString($entry->platformChatId);
                $packet->putLInt($buildPlatform);
                $skin = PlayerListPacket::convertFromLegacySkin($entry->skin); # i actually haven't tested this
                // PUT SKIN
                $packet->putString($skin->getSkinId());
                $packet->putString($skin->getSkinResourcePatch());
                //put image
                $image = $skin->getSkinData();
                $packet->putLInt($image->getWidth());
                $packet->putLInt($image->getHeight());
                $packet->putString($image->getData());

                $packet->putLInt(count($animations = $skin->getAnimations()));
                foreach($animations as $animation){
                    $packet->putImage($animation->getImage());
                    $packet->putLInt($animation->getType());
                    $packet->putLFloat($animation->getFrames());
                }
                $packet->putImage($skin->getCapeData());
                $packet->putString($skin->getGeometryData());
                $packet->putString($skin->getAnimationData());
                $packet->putBool($skin->isPremium());
                $packet->putBool($skin->isPersona());
                $packet->putBool($skin->isCapeOnClassic());
                $packet->putString($skin->getCapeId());
                $packet->putString($skin->getFullSkinId());
                // END OF PUT SKIN
                $packet->putBool($isTeacher);
                $packet->putBool($isHost);
            }else{
                $packet->putUUID($entry->uuid);
            }
        }
        return $packet;
    }

    private function decodePacketPayload($packet) {
        $packet->type = $packet->getByte();
        $count = $packet->getUnsignedVarInt();
        for($i = 0; $i < $count; ++$i){
            $entry = new PlayerListEntry();
            if($packet->type === PlayerListPacket::TYPE_ADD){
                $entry->uuid = $packet->getUUID();
                $entry->entityUniqueId = $packet->getEntityUniqueId();
                $entry->username = $packet->getString();
                $entry->xboxUserId = $packet->getString();
                $entry->platformChatId = $packet->getString();
                $entry->buildPlatform = $packet->getLInt();
                # SKIN
                $skinId = $packet->getString();
                $skinResourcePatch = $packet->getString();
                # IMAGE
                $width = $packet->getLInt();
                $height = $packet->getLInt();
                $data = $packet->getString();
                $skinData = new SerializedImage($width, $height, $data);
                # END OF IMAGE
                $animationCount = $packet->getLInt();
                $animations = [];
                for($i = 0, $count = $animationCount; $i < $count; ++$i){
                    $animations[] = new SkinAnimation($packet->getImage(), $packet->getLInt(), $packet->getLFloat());
                }
                # IMAGE
                $width = $packet->getLInt();
                $height = $packet->getLInt();
                $data = $packet->getString();
                $capeData = new SerializedImage($width, $height, $data);
                # END OF IMAGE
                $geometryData = $packet->getString();
                $animationData = $packet->getString();
                $premium = $packet->getBool();
                $persona = $packet->getBool();
                $capeOnClassic = $packet->getBool();
                $capeId = $packet->getString();
                $fullSkinId = $packet->getString();
                $entry->skin = new Skin($skinId, $skinResourcePatch, $skinData, $animations, $capeData, $geometryData, $animationData, $premium, $persona, $capeOnClassic, $capeId);
                $entry->isTeacher = $packet->getBool();
                $entry->isHost = $packet->getBool();
            }else{
                $entry->uuid = $packet->getUUID();
            }
            $packet->entries[$i] = $entry;
        }
        return $packet;
    }
}