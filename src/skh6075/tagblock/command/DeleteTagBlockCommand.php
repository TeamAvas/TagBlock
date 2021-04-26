<?php

namespace skh6075\tagblock\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use skh6075\tagblock\TagBlockLoader;

final class DeleteTagBlockCommand extends Command{

    private TagBlockLoader $plugin;

    public function __construct(TagBlockLoader $plugin) {
        parent::__construct("tagblock delete", "tagblock delete");
        $this->setPermission("tagblock.delete.permission");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $player, string $label, array $args): bool{
        if (!$player instanceof Player) {
            $player->sendMessage(TagBlockLoader::$prefix . "Please, this command use only in-game");
            return false;
        }

        if (!$this->testPermission($player)) {
            return false;
        }

        if (isset(TagBlockLoader::$queue[spl_object_hash($player)])) {
            unset(TagBlockLoader::$queue[spl_object_hash($player)]);
            $player->sendMessage(TagBlockLoader::$prefix . "The work in progress has been ended.");
            return true;
        }

        TagBlockLoader::$queue[spl_object_hash($player)] = ["delete"];
        $player->sendMessage(TagBlockLoader::$prefix . "Touch the tag block to be deleted.");
        return true;
    }
}