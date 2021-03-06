<?php

namespace aieuo\ip\conditions;

use aieuo\ip\IFPlugin;

use aieuo\ip\utils\Language;
use pocketmine\utils\TextFormat;

class TakeMoney extends TypeMoney {

    protected $id = self::TAKEMONEY;
    protected $name = "@condition.takemoney.name";
    protected $description = "@condition.takemoney.description";

    public function getDetail(): string {
        return Language::get("condition.takemoney.detail", [$this->getAmount()]);
    }

    public function check() {
        $player = $this->getPlayer();
        $economy = IFPlugin::getInstance()->getEconomy();
        if ($economy === null) {
            $player->sendMessage(TextFormat::RED.Language::get("economy.notfound"));
            return self::ERROR;
        }
        $mymoney = $economy->getMoney($player->getName());
        if ($mymoney >= $this->getAmount()) {
            $economy->takeMoney($player->getName(), $this->getAmount());
            return self::MATCHED;
        }
        return self::NOT_MATCHED;
    }
}