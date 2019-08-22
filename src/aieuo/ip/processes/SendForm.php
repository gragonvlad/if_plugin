<?php

namespace aieuo\ip\processes;

use aieuo\ip\ifPlugin;

use aieuo\ip\form\Form;
use aieuo\ip\form\Elements;
use aieuo\ip\Session;
use aieuo\ip\utils\Language;

class SendForm extends Process {

    protected $id = self::SEND_FORM;
    protected $name = "@process.sendform.name";
    protected $description = "@process.sendform.description";

    public function getDetail(): string {
        $name = $this->getFormName();
        return Language::get("process.sendform.detail", [$name]);
    }

    public function getFormName() {
        return $this->getValues();
    }

    public function setFormName(string $name) {
        $this->setValues($name);
    }

    public function execute() {
        $player = $this->getPlayer();
        $name = $this->getFormName();
        $manager = ifPlugin::getInstance()->getFormIFManager();
        if (!$manager->isAdded($name)) {
            $player->sendMessage(Language::get("process.sendform.notfound", [$this->getName()]));
            return;
        }
        $form = json_encode($manager->getForm($name, $this->replaceDatas));
        Session::get($player)->setData("form_name", $name);
        Form::sendForm($player, $form, $this, "onRecive");
    }

    public function onRecive($player, $data) {
        $session = Session::get($player);
        if ($data === null) {
            $session->setValid(false, false);
            return;
        }
        $formName = $session->getData("form_name");
        $manager = ifPlugin::getInstance()->getFormIFManager();
        if (!$manager->isAdded($formName)) {
            $player->sendMessage(Language::get("process.sendform.notfound", [$this->getName()]));
            return;
        }
        $datas = $manager->getIF($formName);
        $form = $manager->getForm($formName, $this->replaceDatas);
        foreach ($datas["ifs"] as $ifdata) {
            $manager->executeIfMatchCondition(
                $player,
                $ifdata["if"],
                $ifdata["match"],
                $ifdata["else"],
                [
                    "player" => $player,
                    "form" => $form,
                    "form_name" => $formName,
                    "form_data" => $data,
                ]
            );
        }
    }

    public function getEditForm(string $default = "", string $mes = "") {
        $data = [
            "type" => "custom_form",
            "title" => $this->getName(),
            "content" => [
                Elements::getLabel($this->getDescription().(empty($mes) ? "" : "\n".$mes)),
                Elements::getInput(Language::get("process.sendform.form.name"), Language::get("input.example", ["aieuo"]), $default),
                Elements::getToggle(Language::get("form.delete")),
                Elements::getToggle(Language::get("form.cancel"))
            ]
        ];
        $json = Form::encodeJson($data);
        return $json;
    }

    public function parseFormData(array $datas) {
        $status = true;
        if ($datas[1] === "") $status = null;
        return ["status" => $status, "contents" => $datas[1], "delete" => $datas[2], "cancel" => $datas[3]];
    }
}