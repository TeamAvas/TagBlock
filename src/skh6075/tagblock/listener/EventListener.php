<?php

namespace skh6075\tagblock\listener;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use skh6075\tagblock\TagBlockLoader;

final class EventListener implements Listener{

    private TagBlockLoader $plugin;

    public function __construct(TagBlockLoader $plugin) {
        $this->plugin = $plugin;
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void{
        if ($event->isCancelled())
            return;

        /** @var Player $player */
        if (!($player = $event->getEntity()) instanceof Player)
            return;

        foreach ($this->plugin->getWorldTags($event->getFrom()->getWorld()) as $tag)
            $tag->sendRemovePacket($player);
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();
        if (isset(TagBlockLoader::$queue[spl_object_hash($player)])) {
            $mode = TagBlockLoader::$queue[spl_object_hash($player)][0];
            switch ($mode) {
                case "create":
                    $text = TagBlockLoader::$queue[spl_object_hash($player)][1];
                    $this->plugin->addTagBlock($event->getBlock()->getPos(), $text);
                    $player->sendMessage(TagBlockLoader::$prefix . "You have created a tag. End the operation.");
                    unset(TagBlockLoader::$queue[spl_object_hash($player)]);
                    break;
                case "delete":
                    if (!$this->plugin->deleteTagBlock($event->getBlock()->getPos())) {
                        $player->sendMessage(TagBlockLoader::$prefix . "The tagblock cannot be found at that location.");
                        return;
                    }

                    $player->sendMessage(TagBlockLoader::$prefix . "The tag for the location has been deleted.");
                    break;
                default:
                    unset(TagBlockLoader::$queue[spl_object_hash($player)]);
                    break;
            }
        }
    }
}