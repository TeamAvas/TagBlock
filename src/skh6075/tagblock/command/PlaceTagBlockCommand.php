<?php

namespace skh6075\tagblock\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use skh6075\formlibrary\FormLibrary;
use skh6075\tagblock\TagBlockLoader;

final class PlaceTagBlockCommand extends Command{

    private TagBlockLoader $plugin;

    public function __construct(TagBlockLoader $plugin) {
        parent::__construct("tagblock place", "tagblock place");
        $this->setPermission("tagblock.place.permission");
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

        switch ($text = array_shift($args) ?? "") {
            case "form":
            case "ui":
                if (!class_exists(FormLibrary::class)) {
                    $player->sendMessage(TagBlockLoader::$prefix . "FormLibrary could not be found.");
                    return false;
                }

                $form = FormLibrary::createCustomForm(function (Player $player, $data): void{
                    $text = $data[1] ?? null;
                    if (is_null($text))
                        return;

                    TagBlockLoader::$queue[spl_object_hash($player)] = ["create", $text];
                    $player->sendMessage(TagBlockLoader::$prefix . "Touch the block to create TagBlock");
                }, "Place TagBlock Menu");
                $form->addLabel("Please write your text in the blank space\nLine down is possible with (n).")
                    ->addInput("TagBlock Text", "");

                $player->sendForm($form);
                break;
            default:
                if (trim($text) === "") {
                    $player->sendMessage(TagBlockLoader::$prefix . "/" . $this->getName() . " [text/form]");
                    return false;
                }

                TagBlockLoader::$queue[spl_object_hash($player)] = ["create", $text];
                $player->sendMessage(TagBlockLoader::$prefix . "Touch the block to create TagBlock");
                break;
        }

        return true;
    }
}