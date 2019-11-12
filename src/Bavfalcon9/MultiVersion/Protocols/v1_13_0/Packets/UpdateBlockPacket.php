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

use Bavfalcon9\MultiVersion\Protocols\CustomTranslator;
use Bavfalcon9\MultiVersion\Protocols\v1_13_0\types\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\RuntimeBlockMapping as PMRuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket as PMUpdateBlock;

class UpdateBlockPacket implements CustomTranslator{

    /**
     * @param PMUpdateBlock $packet
     *
     * @return PMUpdateBlock
     */
    public function translateCustomPacket($packet) {
        list($id, $meta) = PMRuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId);
        $packet->blockRuntimeId = RuntimeBlockMapping::toStaticRuntimeId($id, $meta);

        return $packet;
    }
}