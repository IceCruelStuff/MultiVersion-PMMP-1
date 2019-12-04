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

namespace Bavfalcon9\MultiVersion\Utils;

use Bavfalcon9\MultiVersion\Main;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\RespawnPacket;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\TickSyncPacket;
use function in_array;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use function strtolower;

class PacketManager {
    /** @var ProtocolVersion[] */
    private $registered = [];
    /** @var Main */
    private $plugin;
    /** @var string[] */
    private $oldplayers = [];

    /**
     * PacketManager constructor.
     * @param Main $pl
     */
    public function __construct(Main $pl) {
        $this->plugin = $pl;
    }

    /**
     * @param ProtocolVersion $pv
     * @return bool
     */
    public function registerProtocol(ProtocolVersion $pv): Bool {
        if (isset($this->registered[$pv->getProtocol()])) {
            return false;
        }

        $this->registered[$pv->getProtocol()] = $pv;

        return true;
    }

    /**
     * @param ProtocolVersion $pv
     * @return bool
     */
    public function unregisterProtocol(ProtocolVersion $pv): Bool {
        if (!isset($this->registered[$pv->getProtocol()])) {
            return false;
        }

        unset($this->registered[$pv->getProtocol()]);

        return true;
    }

    /**
     * @param Player     $player
     * @param DataPacket $packet
     *
     * @return bool
     */
    public function handlePacketReceive(Player $player, DataPacket &$packet): bool{
        if ($packet instanceof LoginPacket) {
            $protocol = $packet->protocol;
            $this->plugin->getLogger()->debug("§eUser: {$packet->username} [attempting to hack login for protocol: $protocol]");
            if ($protocol !== ProtocolInfo::CURRENT_PROTOCOL) {
                if (!isset($this->registered[$protocol])) {
                    $player->close('', '§c[MultiVersion]: Your game version is not yet supported here. [$protocol]');
                } else {
                    $this->plugin->getLogger()->debug("§e{$packet->username} joining with protocol: $protocol");
                    $this->oldplayers[strtolower($packet->username)] = $protocol;
                    $pc = $this->registered[$protocol];
                    $pc->translateLogin($packet);
                }
            }

            return true;
        } else if ($packet instanceof TickSyncPacket) {
            return false;
        } else if ($packet instanceof RespawnPacket) {
            $pk = new RespawnPacket();
            $pk->position = $packet->position;
            $pk->state = RespawnPacket::STATE_READY_TO_SPAWN;
            $pk->entityRuntimeId = $player->getId();
            $player->dataPacket($pk);

            return false;
        }

        if (!isset($this->oldplayers[$player->getLowerCaseName()])) {
            return true;
        }

        $protocol = $this->oldplayers[$player->getLowerCaseName()];
        $pv = $this->registered[$protocol];
        $pv->changePacket($packet->getName(), $packet, 'RECEIVE');

        return true;
    }

    /**
     * @param Player     $player
     * @param DataPacket $packet
     *
     * @return bool
     */
    public function handlePacketSent(Player $player, DataPacket &$packet): bool{
        if (!isset($this->oldplayers[$player->getLowerCaseName()])) {
            return true;
        }

        if ($packet instanceof RespawnPacket) {
            return true;
        }

        $protocol = $this->oldplayers[$player->getLowerCaseName()];
        $pv = $this->registered[$protocol];
        $pv->changePacket($packet->getName(), $packet, 'SENT');

        return true;
    }
}
