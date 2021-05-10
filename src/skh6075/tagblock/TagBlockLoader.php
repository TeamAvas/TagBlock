<?php

namespace skh6075\tagblock;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\World;
use skh6075\tagblock\command\DeleteTagBlockCommand;
use skh6075\tagblock\command\PlaceTagBlockCommand;
use skh6075\tagblock\listener\EventListener;

function posToStr(Position $position): string{
    return intval($position->getX()) . ":" . intval($position->getY()) . ":" . intval($position->getZ()) . ":" . $position->getWorld()->getFolderName();
}

function strToPos(string $pos): Position{
    [$x, $y, $z, $world] = explode(":", $pos);
    return new Position(floatval($x), floatval($y), floatval($z), Server::getInstance()->getWorldManager()->getWorldByName($world));
}

final class TagBlockLoader extends PluginBase{
    use SingletonTrait;

    public static string $prefix = "§l§b[TagBlock]§r§7 ";

    /** @var TagBlock[] */
    private static array $tags = [];

    public static array $queue = [];

    protected function onLoad(): void{
        self::setInstance($this);
    }

    protected function onEnable(): void{
        if (!file_exists($file = $this->getDataFolder() . "config.json")) {
            file_put_contents($file, json_encode([]));
        }

        $json = json_decode(file_get_contents($file), true);
        foreach ($json as $pos => $text) {
            self::$tags[$pos] = new TagBlock($text, strToPos($pos));
        }

        $this->getServer()->getCommandMap()->registerAll(strtolower($this->getName()), [
            new PlaceTagBlockCommand($this),
            new DeleteTagBlockCommand($this)
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void{
            foreach ($this->getServer()->getOnlinePlayers() as $player)
                $this->refreshTagBlocks($player);
        }), 35);
    }

    protected function onDisable(): void{
        $data = [];
        foreach (self::$tags as $pos => $tag) {
            $data[$pos] = $tag->getText();
        }

        file_put_contents($this->getDataFolder() . "config.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getTagBlock(Position $position): ?TagBlock{
        return self::$tags[posToStr($position)] ?? null;
    }

    public function addTagBlock(Position $position, string $text): void{
        self::$tags[posToStr($position)] = new TagBlock($text, $position);
    }

    public function deleteTagBlock(Position $position): bool{
        if (($tag = $this->getTagBlock($position)) instanceof TagBlock) {
            foreach ($this->getServer()->getOnlinePlayers() as $player)
                $tag->sendRemovePacket($player);

            unset (self::$tags[posToStr($position)]);
            return true;
        }

        return false;
    }

    private function refreshTagBlocks(Player $player): void{
        foreach (self::$tags as $tag) {
            if ($tag->getPosition()->getWorld()->getFolderName() !== $player->getWorld()->getFolderName())
                continue;

            $tag->sendRemovePacket($player);
            if ($tag->getPosition()->distance($player->getPosition()) <= TagBlock::RADIUS) {
                $tag->sendAddPacket($player);
            }
        }
    }

    /** @return TagBlock[] */
    public function getWorldTags(World $world): array{
        $arr = [];
        foreach (self::$tags as $tag) {
            if ($tag->getPosition()->getWorld()->getFolderName() === $world->getFolderName())
                $arr[] = $tag;
        }

        return $arr;
    }
}
