<?php

namespace Bavfalcon9\MultiVersion\Versions;
use Bavfalcon9\MultiVersion\Utils\ProtocolVersion;
use Bavfalcon9\MultiVersion\Utils\PacketManager;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

abstract class Version {
    abstract public function onLoad(PacketManager $packetManager, $plugin): void;

    public function getVersionName(): String {
        return $this->version;
    }

    public function getProtocol(): Int {
        return $this->protocol;
    }

    public function disable(): ?Bool {
        $packetManager = PacketManager::getInstance();
        $protocol = $packetManager->getRegisteredProtocol($this->protocol);

        if (!$protocol) return null;

        $this->disabled = true;
        $packetManager->unregisterProtocol($protocol);

        return $this->onDisable();
    }

    public function onEnable(): bool {
        MainLogger::getLogger()->info("[MultiVersion]: §aLoaded support for: {$this->version}");
        return true;
    }

    public function onDisable(String $app=''): bool {
        MainLogger::getLogger()->info("[MultiVersion]: §cDisabled support for: {$this->version} " . $app);
        return true;
    } 

    public function onNotAllowed(): bool {
        MainLogger::getLogger()->info("[MultiVersion]: §cMultiversion does not support {$this->version} for your server protocol.");
        return true;
    } 

    public function isAllowed(String $version=''): Bool {
        if ($this->disabled) return false;
        return in_array($version, $this->allowed);
    }

    public function isDisabled(): Bool {
        return $this->disabled;
    }
}