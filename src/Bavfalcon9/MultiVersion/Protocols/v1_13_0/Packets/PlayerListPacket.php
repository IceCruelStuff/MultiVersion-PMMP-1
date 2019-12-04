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
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\CustomTranslator;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\Skin;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SkinAnimation;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Entity\SerializedImage;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\PlayerListEntry;
use pocketmine\entity\Skin as PMSkin;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry as PMListEntry;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PlayerListPacket as PMListPacket;
use pocketmine\utils\MainLogger;
use pocketmine\Server;
use function count;

class PlayerListPacket extends DataPacket implements BatchCheck, CustomTranslator{

    public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;
    public const TYPE_ADD = 0;
    public const TYPE_REMOVE = 1;

    /** @var PlayerListEntry[] */
    public $entries = [];
    /** @var int */
    public $type;
    /** @var bool */
    public $inBound = false;
    private $mode = false;

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

                // Assuming they're 1.12 or older because they aren't in the array
                if (!isset(PacketManager::$protocolPlayers[$entry->username])) {
                    /* THIS IS HACKY */
                    $cached = Server::getInstance()->getPlayer($entry->username);
                    var_dump($cached);
                    $cached = (!$cached) ? NULL : $cached->getSkin();
                    $skinId = ($cached !== NULL) ? $cached->getSkinId() : $this->getString();
                    $skinData = ($cached !== NULL) ? $cached->getSkinData() : $this->getString();
                    $capeData = ($cached !== NULL) ? $cached->getCapeData() : $this->getString();
                    $geometryName = ($cached !== NULL) ? $cached->getGeometryName() : $this->getString();
				    $geometryData = ($cached !== NULL) ? $cached->getGeometryData() : $this->getString();
                    $entry->skin = new PMSkin(
                        $skinId,
                        $skinData,
                        $capeData,
                        $geometryName,
                        $geometryData
                    );
                    $entry->xboxUserId = $this->getString();
                    $entry->platformChatId = $this->getString();
                    $entry->skin = (!$cached) ? Skin::null() : Skin::convertFromLegacySkin($cached);
                    $entry->buildPlatform = -1;
                    $entry->isTeacher = false;
                    $entry->isHost = false;
                } else {
                    if (PacketManager::$protocolPlayers[$entry->username] !== ProtocolVersion::VERSIONS['1.13.0']) throw new \Exception('Not sure what to do here');
                    $entry->xboxUserId = $this->getString();
                    $entry->platformChatId = $this->getString();
                    $entry->buildPlatform = $this->getLInt();
                    $entry->skin = $this->getSkin();
                    $entry->isTeacher = $this->getBool();
                    $entry->isHost = $this->getBool();
                }
            } else {
                $entry->uuid = $this->getUUID();
            }
            $this->entries[$i] = $entry;
        }
    }

    protected function encodePayload() {
        if (!$this->type) $this->type = self::TYPE_ADD;
        $this->putByte($this->type);
        $this->putUnsignedVarInt(count($this->entries));
        foreach($this->entries as $entry){
            if($this->type === self::TYPE_ADD){
                $buildPlatform = (!isset($entry->buildPlatform)) ? -1 : $entry->buildPlatform;
                $isTeacher = (!isset($entry->isTeacher)) ? false : $entry->isTeacher;
                $isHost = (!isset($entry->isHost)) ? false : $entry->isHost;
                $skin = (!$entry->skin) ? $entry->skin : ($entry->skin instanceof PMSkin) ? Skin::convertFromLegacySkin($entry->skin) : $entry->skin;
                $this->putUUID($entry->uuid);
                $this->putEntityUniqueId($entry->entityUniqueId);
                $this->putString($entry->username);
                $this->putString($entry->xboxUserId);
                $this->putString($entry->platformChatId);
                $this->putLInt($buildPlatform);
                $this->putSkin($skin);
                $this->putBool($isTeacher);
                $this->putBool($isHost);
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
     * @return $this
     */
    public function translateCustomPacket($packet) {
        if ($packet->type !== self::TYPE_ADD) {
            return $packet;
        }

        $this->type = $packet->type;
        $this->entries = $packet->entries;

        foreach($this->entries as $key => $entry) {
            $buildPlatform = (!isset($entry->buildPlatform)) ? -1 : $entry->buildPlatform;
            $isTeacher = (!isset($entry->isTeacher)) ? false : $entry->isTeacher;
            $isHost = (!isset($entry->isHost)) ? false : $entry->isHost;

            $newEntry = new PlayerListEntry();
            $newEntry->uuid = $entry->uuid;
            $newEntry->entityUniqueId = $entry->entityUniqueId;
            $newEntry->username = $entry->username;
            $newEntry->xboxUserId = $entry->xboxUserId;
            $newEntry->platformChatId = $entry->platformChatId;
            $newEntry->buildPlatform = $buildPlatform;
            $newEntry->skin = $entry->skin instanceof PMSkin ? Skin::convertFromLegacySkin($entry->skin) : $entry->skin;
            $newEntry->isTeacher = $isTeacher;
            $newEntry->isHost = $isHost;

            $this->entries[$key] = $newEntry;
        }

        return $this;
    }


    public function onPacketMatch(&$packet): Void {
        if ($packet instanceof PMListPacket) {
            $newPacket = new PlayerListPacket;
            $newPacket->setBuffer($packet->buffer, $packet->offset);
            $newPacket->decode(); // decodes packet
            $newPacket->encode(); 
        }
    }
}
