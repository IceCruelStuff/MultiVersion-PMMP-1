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

abstract class PacketListener {
    /** @var int */
    private static $listeners = 0;

    /** @var Int */
    private $networkId;
    /** @var Int */
    private $registered;
    /** @var String */
    private $packetName;

    public function __construct(String $packetName, Int $networkId) {
        static::$listeners++;
        $this->registered = static::$listeners;
        $this->networkId = $networkId;
        $this->packetName = $packetName;
    }

    public function getPacketName(): String {
        return $this->packetName;
    }

    public function getPacketNetworkID(): Int {
        return $this->networkId;
    }

    public function getName(): ?String {
        return "Listener$this->registered";
    }

    public static function getAmount(): int {
        return static::$listeners;
    }

    abstract public function onPacketCheck(&$packet): Bool;

    abstract public function onPacketMatch(&$packet): Void;
}