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

namespace Bavfalcon9\MultiVersion\Protocols\v1_14_0\types;

use pocketmine\block\BlockIds;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\BinaryDataException;
use RuntimeException;
use function file_get_contents;
use function getmypid;
use function mt_rand;
use function mt_srand;
use function shuffle;

/**
 * @internal
 */
final class RuntimeBlockMapping{

    /** @var int[] */
    private static $legacyToRuntimeMap = [];
    /** @var int[] */
    private static $runtimeToLegacyMap = [];
    /** @var mixed[]|null */
    private static $bedrockKnownStates = null;

    private function __construct(){
        //NOOP
    }

    public static function init() : void{
        try{
            /** @var CompoundTag $tag */
            $tag = (new BigEndianNBTStream())->read(file_get_contents(MULTIVERSION_v1_13_0 . "/runtime_block_states.dat"));
        }catch(BinaryDataException $e){
            throw new RuntimeException("", 0, $e);
        }
        $decompressed = [];
        $states = $tag->getListTag("Palette");
        foreach($states as $state){
            /** @var CompoundTag $state */
            $block = $state->getCompoundTag("block");
            $decompressed[] = [
                "name" => $block->getString("name"),
                "states" => $block->getCompoundTag("states"),
                "data" => $state->getShort("meta"),
                "legacy_id" => $state->getShort("id"),
            ];
        }
        self::$bedrockKnownStates = self::randomizeTable($decompressed);
        foreach(self::$bedrockKnownStates as $k => $obj){
            if($obj["data"] > 15){
                //TODO: in 1.12 they started using data values bigger than 4 bits which we can't handle right now
                continue;
            }
            //this has to use the json offset to make sure the mapping is consistent with what we send over network, even though we aren't using all the entries
            self::registerMapping($k, $obj["legacy_id"], $obj["data"]);
        }
    }

    private static function lazyInit() : void{
        if(self::$bedrockKnownStates === null){
            self::init();
        }
    }

    /**
     * Randomizes the order of the runtimeID table to prevent plugins relying on them.
     * Plugins shouldn't use this stuff anyway, but plugin devs have an irritating habit of ignoring what they
     * aren't supposed to do, so we have to deliberately break it to make them stop.
     *
     * @param array $table
     *
     * @return array
     */
    private static function randomizeTable(array $table) : array{
        $postSeed = mt_rand(); //save a seed to set afterwards, to avoid poor quality randoms
        mt_srand(getmypid() ?: 0); //Use a seed which is the same on all threads. This isn't a secure seed, but we don't care.
        shuffle($table);
        mt_srand($postSeed); //restore a good quality seed that isn't dependent on PID

        return $table;
    }

    /**
     * @param int $id
     * @param int $meta
     *
     * @return int
     */
    public static function toStaticRuntimeId(int $id, int $meta = 0) : int{
        self::lazyInit();
        /*
         * try id+meta first
         * if not found, try id+0 (strip meta)
         * if still not found, return update! block
         */
        return self::$legacyToRuntimeMap[($id << 4) | $meta] ?? self::$legacyToRuntimeMap[$id << 4] ?? self::$legacyToRuntimeMap[BlockIds::INFO_UPDATE << 4];
    }

    /**
     * @param int $runtimeId
     *
     * @return int[] [id, meta]
     */
    public static function fromStaticRuntimeId(int $runtimeId) : array{
        self::lazyInit();
        $v = self::$runtimeToLegacyMap[$runtimeId];

        return [$v >> 4, $v & 0xf];
    }

    private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta) : void{
        self::$legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
        self::$runtimeToLegacyMap[$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
    }

    /**
     * @return array
     */
    public static function getBedrockKnownStates() : array{
        self::lazyInit();
        return self::$bedrockKnownStates;
    }
}