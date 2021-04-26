<?php

namespace skh6075\tagblock;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;

final class TagBlock{

    public const RADIUS = 15.5;

    private string $text;

    private Position $position;

    private AddPlayerPacket $addPacket;

    private RemoveActorPacket $removePacket;

    public function __construct(string $text, Position $position) {
        $this->text = $text;
        $this->position = $position;

        $this->addPacket = new AddPlayerPacket();
        $this->addPacket->entityRuntimeId = $this->addPacket->entityUniqueId = Entity::nextRuntimeId();
        $this->addPacket->position = $position->add(0.5, 0, 0.5);
        $this->addPacket->uuid = Uuid::uuid4();
        $this->addPacket->item = ItemStackWrapper::legacy(ItemStack::null());
        $this->addPacket->username = str_replace("(n)", "\n", $this->text);
        $this->addPacket->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty(1 << EntityMetadataFlags::IMMOBILE),
            EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01)
        ];

        $this->removePacket = RemoveActorPacket::create($this->addPacket->entityRuntimeId);
    }

    public function getPosition(): Position{
        return $this->position;
    }

    public function getText(): string{
        return $this->text;
    }

    public function setText(string $text): void{
        $this->text = $text;
        $this->addPacket->username = $this->text;
    }

    public function sendAddPacket(Player $player): void{
        $player->getNetworkSession()->sendDataPacket($this->addPacket);
    }

    public function sendRemovePacket(Player $player): void{
        $player->getNetworkSession()->sendDataPacket($this->removePacket);
    }
}