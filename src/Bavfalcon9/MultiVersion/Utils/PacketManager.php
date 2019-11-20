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
use Bavfalcon9\MultiVersion\Utils\BatchCheck;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\RespawnPacket;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\Packets\TickSyncPacket;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PacketPool;
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
    /** @var array */
    public static $protocolPlayers = [];

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
        self::$protocolPlayers = $this->oldplayers;

        if ($packet instanceof LoginPacket) {
            $protocol = $packet->protocol;
            if (isset($this->queue[$packet->username]) and in_array($nId, $this->queue[$packet->username])) {
                $oldProto = $this->oldplayers[$packet->username];
                $this->plugin->getLogger()->debug("§eUser: {$packet->username} [attempting to hack login for protocol: $oldProto]");
                $pc = $this->registered[$oldProto];
                $this->translateLogin($pc, $packet);
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
                    $this->changePacket($pc, $pkN, $packet, $player, 'RECEIVE');

                    $this->handleOldReceived($packet, $player);
                    $event->setCancelled();
                }
            }

            return;
        } else if ($packet instanceof TickSyncPacket){
            $event->setCancelled();

            return;
        } else if ($packet instanceof RespawnPacket) {
            $pk = new RespawnPacket();
            $pk->position = $packet->position;
            $pk->state = RespawnPacket::STATE_READY_TO_SPAWN;
            $pk->entityRuntimeId = $player->getId();
            $player->dataPacket($pk);

            return;
        } else if ($packet instanceof DisconnectPacket) {
            if (isset($this->oldPlayers[$player->getName()])) unset($this->oldPlayers[$player->getName()]);
            self::$protocolPlayers = $this->oldplayers;
            return;
        }

        if (!isset($this->oldplayers[$player->getName()])) {
            return;
        }

        if ($packet instanceof BatchPacket) {
            $newBatch = new BatchPacket();
            $protocol = $this->registered[$this->oldplayers[$player->getName()]];
            $packets = $protocol->getProtocolPackets();
            foreach ($packet->getPackets() as $buf) {
                $pk = PacketPool::getPacket($buf);
                $name = $pk->getName();

                if (!isset($packets[$name])) {
                    $newBatch->addPacket($pk);
                    continue;
                } else {
                    $newpacket = $protocol->getDir() . $name;
                    $newpacket = new $newpacket;
                    $newpacket->inBound = true;
                    if (!$newpacket instanceof BatchCheck) {
                        $pk->decode();
                        $pk->encode();
                        $newBatch->addPacket($pk);
                        continue;
                    } else {
                        $newpacket->onPacketMatch($pk);
                        $pk = $newpacket;
                        $newBatch->addPacket($pk);
                        continue;
                    }
                }
            }
            $packet = $newBatch;
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
            $this->changePacket($protocol, $pkN, $packet, $player, 'RECEIVE');
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

            if ($packet instanceof BatchPacket) {
                $newBatch = new BatchPacket();
                $protocol = $this->registered[$this->oldplayers[$player->getName()]];
                $packets = $protocol->getProtocolPackets();
                foreach ($packet->getPackets() as $buf) {
                    $pk = PacketPool::getPacket($buf);
                    $name = $pk->getName();

                    if (!isset($packets[$name])) {
                        $pk->decode();
                        $pk->encode();
                        $newBatch->addPacket($pk);
                        continue;
                    } else {
                        $newpacket = $protocol->getDir() . $name;
                        $newpacket = new $newpacket;
                        if (!$newpacket instanceof BatchCheck) {
                            if ($packet instanceof RespawnPacket){
                                return;
                            }
                            $pk->decode();
                            $protocol = $this->oldplayers[$player->getName()];
                            $protocol = $this->registered[$protocol];
                            $pkN = $protocol->getPacketName($nId);
                            $success = $this->changePacket($protocol, $pkN, $pk, $player, 'SENT');
                            if ($success === null) {
                                $this->plugin->getLogger()->critical("Tried to send an unknown packet[$nId] to player: {$player->getName()}");

                                return;
                            }
                            $pk->encode();
                            $newBatch->addPacket($pk);
                        } else {
                            $newpacket->inBound = false;
                            $newpacket->onPacketMatch($pk);
                            $pk = $newpacket;
                            var_dump($pk);
                            $newBatch->addPacket($pk);
                            # $pk->encode();
                            continue;
                        }
                    }
                }
                $packet = $newBatch;
                return;
            }

            if ($packet instanceof RespawnPacket){
                return;
            }

            $protocol = $this->oldplayers[$player->getName()];
            $protocol = $this->registered[$protocol];
            $pkN = $protocol->getPacketName($nId);
            $success = $this->changePacket($protocol, $pkN, $packet, $player, 'SENT');
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
        $adapter = new PlayerNetworkSessionAdapter($this->plugin->getServer(), $player);
		$adapter->handleDataPacket($packet);
    }

    /**
     * @param ProtocolVersion $protocol
     * @param String $name
     * @param mixed  $oldPacket
     * @param Player $player - Note this may vary based on protocol which is why it's not data typed.
     * @param String $type
     *
     * @return mixed
     */
    private function changePacket(ProtocolVersion $protocol, String $name, &$oldPacket, $player, String $type = 'SENT') {
        foreach ($protocol->getPacketListeners() as $listener) {
            if ($listener->getPacketName() === $oldPacket->getName() && $oldPacket::NETWORK_ID === $listener->getPacketNetworkID()) {
                $success = $listener->onPacketCheck($oldPacket);
                if (!$success) {
                    continue;
                } else {
                    $listener->inBound = ($type === 'SENT') ? false : true;
                    $listener->onPacketMatch($oldPacket);
                    $modified = true;
                    continue;
                }
            }
        }

        if (!isset($protocol->getProtocolPackets()[$name]) && $protocol->getRestricted()) {
            return null;
        }

        if (!isset($protocol->getProtocolPackets()[$name])) {
            if ($protocol::DEVELOPER) {
                MainLogger::getLogger()->info("§c[MultiVersion] DEBUG:§e Packet §8[§f {$oldPacket->getName()} §8| §f".$oldPacket::NETWORK_ID."§8]§e requested a change but no change supported §a{$type}§e.");
            }

            return $oldPacket;
        }

        $pk = $protocol->getDir() . $name;
        $pk = new $pk;
        $pk->setBuffer($oldPacket->buffer, $oldPacket->offset);

        if (!$oldPacket instanceof DataPacket) {
            if ($protocol::DEVELOPER) {
                MainLogger::getLogger()->info("§8[MultiVersion]: Packet change requested on non DataPacket typing. {$oldPacket->getName()} | " . $oldPacket::NETWORK_ID);
            }
        }

        if ($pk instanceof CustomTranslator) {
            $pk = $pk->translateCustomPacket($oldPacket);
        }

        $oldPacket = $pk;
        
        if ($protocol::DEVELOPER) {
            MainLogger::getLogger()->info("§6[MultiVersion] DEBUG: Modified Packet §8[§f {$oldPacket->getName()} §8| §f".$oldPacket::NETWORK_ID."§8]§6 §a{$type}§6.");
        }

        return $oldPacket;
    }

    /**
     * @param ProtocolVersion $protocol
     * @param Mixed $packet
     * 
     * @return Mixed
     */
    private function translateLogin(ProtocolVersion $protocol, $packet) {
        if (!isset($protocol->protocolPackets['LoginPacket'])) {
            return $packet;
        } else {
            $pk = $protocol->getDir() . 'LoginPacket';
            $pk = new $pk;
            $pk->translateLogin($packet);
            $pk->setBuffer($packet->buffer, $packet->offset);

            return $pk;
        }
    }
}
