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
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\TickSyncPacket;
use pocketmine\Player;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use function array_push;
use function array_search;
use function array_splice;
use function in_array;

class PacketManager {
    /** @var ProtocolVersion[] */
    private $registered = [];
    /** @var Main */
    private $plugin;
    /** @var string[] */
    private $oldplayers = [];
    /** @var array */
    private $queue = []; // Packet queue to prevent duplications

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
     * @param DataPacketReceiveEvent $event
     * @return void
     */
    public function handlePacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $nId = $packet::NETWORK_ID;
        if ($packet instanceof LoginPacket) {
            $protocol = $packet->protocol;
            if (isset($this->queue[$packet->username]) and in_array($nId, $this->queue[$packet->username])) {
                $oldProto = $this->oldplayers[$packet->username];
                $this->plugin->getLogger()->debug("§eUser: {$packet->username} [attempting to hack login for protocol: $oldProto]");
                $pc = $this->registered[$oldProto];
                $pc->translateLogin($packet);
                array_splice($this->queue[$packet->username], array_search($nId, $this->queue[$packet->username]));
            } else if ($protocol !== ProtocolInfo::CURRENT_PROTOCOL) {
                if (!isset($this->registered[$protocol])) {
                    if (isset($this->queue[$packet->username])) {
                        unset($this->queue[$packet->username]);
                    }

                    $this->plugin->getLogger()->critical("{$packet->username} tried to join with protocol: $protocol");
                    $player->close('', '§c[MultiVersion]: Your game version is not yet supported here. [$protocol]');
                    $event->setCancelled();
                } else {
                    $this->plugin->getLogger()->debug("§e {$packet->username} joining with protocol: $protocol");
                    $this->oldplayers[$packet->username] = $protocol;
                    $this->queue[$packet->username] = [];
                    array_push($this->queue[$packet->username], $nId);
                    $pc = $this->registered[$protocol];
                    $pkN = $pc->getPacketName($nId);
                    $pc->changePacket($pkN, $packet, 'RECEIVE');

                    $this->handleOldReceived($packet, $player);
                    $event->setCancelled();
                }
            }

            return;
        } else if ($packet instanceof TickSyncPacket){
            $event->setCancelled();

            return;
        }

        if (!isset($this->oldplayers[$player->getName()])) {
            return;
        }

        if (!isset($this->queue[$player->getName()])) {
            $this->queue[$player->getName()] = [];
        }

        if (isset($this->queue[$player->getName()]) and in_array($nId, $this->queue[$player->getName()])) {
            array_splice($this->queue[$player->getName()], array_search($nId, $this->queue[$player->getName()]));
        } else {
            array_push($this->queue[$player->getName()], $nId);
            $protocol = $this->oldplayers[$player->getName()];
            $protocol = $this->registered[$protocol];
            $pkN = $protocol->getPacketName($nId);
            $protocol->changePacket($pkN, $packet, 'RECEIVE');
            $this->handleOldReceived($packet, $player);
            $event->setCancelled();
        }
    }

    /**
     * @param DataPacketSendEvent $event
     * @return void
     */
    public function handlePacketSent(DataPacketSendEvent $event): void {
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $nId = $packet::NETWORK_ID;
        if (!isset($this->oldplayers[$player->getName()])) {
            return;
        }

        if (isset($this->queue[$player->getName()]) and in_array($nId, $this->queue[$player->getName()])) {
            array_splice($this->queue[$player->getName()], array_search($nId, $this->queue[$player->getName()]));
        } else {
            if (!isset($this->queue[$player->getName()])) {
                $this->queue[$player->getName()] = [];
            }

            if (!isset($this->oldplayers[$player->getName()])) {
                return;
            }

            $protocol = $this->oldplayers[$player->getName()];
            $protocol = $this->registered[$protocol];
            $pkN = $protocol->getPacketName($nId);
            $success = $protocol->changePacket($pkN, $packet, 'SENT');
            if ($success === null) {
                $this->plugin->getLogger()->critical("Tried to send an unknown packet[$nId] to player: {$player->getName()}");

                return;
            }

            array_push($this->queue[$player->getName()], $nId);
            $player->sendDataPacket($packet);
            $event->setCancelled();
        }
    }

    /**
     * @param DataPacket $packet
     * @param Player     $player
     */
    private function handleOldReceived(DataPacket $packet, Player $player) {
        /* This needs some updating to handle updated/outdated packets, right now its only for the servers interpretation. */
        $adapter = new PlayerNetworkSessionAdapter($this->plugin->getServer(), $player);
		$adapter->handleDataPacket($packet);
    }
}
