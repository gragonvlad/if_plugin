<?php

namespace aieuo\ip\processes;

use aieuo\ip\IFPlugin;
use aieuo\ip\task\KickTask;

use aieuo\ip\form\Form;
use aieuo\ip\form\Elements;
use aieuo\ip\utils\Language;

class Kick extends Process {

    protected $id = self::KICK;
    protected $name = "@process.kick.name";
    protected $description = "@process.kick.description";

    public function getDetail(): string {
        $reason = $this->getReason();
        return Language::get("process.kick.detail", [$reason]);
    }

    public function getReason(): ?string {
        $reason = $this->getValues();
        return is_string($reason) ? $reason : null;
    }

    public function setReason(string $reason) {
        $this->setValues($reason);
    }

    public function execute() {
        $player = $this->getPlayer();
        $reason = $this->getReason();
        IFPlugin::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($player, $reason), 5);
    }

    public function getEditForm(string $default = "", string $mes = "") {
        $data = [
            "type" => "custom_form",
            "title" => $this->getName(),
            "content" => [
                Elements::getLabel($this->getDescription().(empty($mes) ? "" : "\n".$mes)),
                Elements::getInput(Language::get("process.kick.form.reason"), Language::get("input.example", ["aieuo"]), $default),
                Elements::getToggle(Language::get("form.delete")),
                Elements::getToggle(Language::get("form.cancel"))
            ]
        ];
        $json = Form::encodeJson($data);
        return $json;
    }

    public function parseFormData(array $data) {
        $status = true;
        if ($data[1] === "") $status = null;
        return ["status" => $status, "contents" => $data[1], "delete" => $data[2], "cancel" => $data[3]];
    }
}