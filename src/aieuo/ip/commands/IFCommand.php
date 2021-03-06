<?php

namespace aieuo\ip\commands;

use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;

use aieuo\ip\utils\Language;
use aieuo\ip\manager\IFManager;
use aieuo\ip\form\Form;
use aieuo\ip\Session;
use aieuo\ip\IFPlugin;
use aieuo\ip\IFAPI;
use pocketmine\Player;

class IFCommand extends PluginCommand implements CommandExecutor {

    /** @var IFPlugin */
    private $owner;
    /* @var Form */
    private $form;

    public function __construct(IFPlugin $owner) {
        parent::__construct('if', $owner);
        $this->setPermission('op');
        $this->setDescription(Language::get("command.if.description"));
        $this->setUsage(Language::get("command.if.usage"));
        $this->setExecutor($this);
        $this->owner = $owner;
        $this->form = new Form();
    }

    private function getOwner(): IFPlugin {
        return $this->owner;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
        if (!$sender->isOp()) return true;

        if (!isset($args[0]) and $sender instanceof Player) {
            $this->form->sendSelectIfTypeForm($sender);
            return true;
        } elseif (!isset($args[0])) {
            $sender->sendMessage(Language::get("command.if.usage.console"));
            return true;
        }

        switch ($args[0]) {
            case "language":
                if (!isset($args[1])) {
                    $sender->sendMessage(Language::get("command.language.usage"));
                    return true;
                }
                $languages = [];
                foreach ($this->getOwner()->getResources() as $resource) {
                    $filename = $resource->getFilename();
                    if (strrchr($filename, ".") == ".ini") $languages[] = basename($filename, ".ini");
                    if ($filename === $args[1].".ini") {
                        $messages = parse_ini_file($resource->getPathname());
                    }
                }
                if (!isset($messages)) {
                    $available = implode(", ", $languages);
                    $sender->sendMessage(Language::get("command.language.notfound", [$args[1], $available]));
                    return true;
                }
                $this->getOwner()->language->setMessages($messages);
                $this->getOwner()->config->set("language", $args[1]);
                if ($this->getOwner()->saveOnChange) $this->getOwner()->getConfig()->save();
                $sender->sendMessage(Language::get("language.selected", [Language::get("language.name")]));
                break;
            case "save":
                $this->getOwner()->getBlockManager()->save();
                $this->getOwner()->getCommandManager()->save();
                $this->getOwner()->getEventManager()->save();
                $this->getOwner()->getChainManager()->save();
                $this->getOwner()->getFormIFManager()->save();
                $this->getOwner()->getVariableHelper()->save();
                $sender->sendMessage(Language::get("command.save.success"));
                break;
            case "saveOnChange":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getOwner()->saveOnChange ? "true" : "false");
                    return true;
                }
                switch ($args[1]) {
                    case "true":
                    case "on":
                        $this->getOwner()->saveOnChange = true;
                        $this->getOwner()->getConfig()->set("saveOnChange", true);
                        $this->getOwner()->getConfig()->save();
                        break;
                    case "false":
                    case "off":
                        $this->getOwner()->saveOnChange = false;
                        $this->getOwner()->getConfig()->set("saveOnChange", false);
                        $this->getOwner()->getConfig()->save();
                        break;
                    default:
                        $sender->sendMessage(Language::get("command.saveOnChange.usage"));
                        return true;
                }
                $sender->sendMessage(Language::get("form.changed"));
                break;
            case 'block':
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $session = Session::getSession($sender);
                if (!isset($args[1])) {
                    $this->form->getBlockForm()->sendSelectActionForm($sender);
                    break;
                }
                switch ($args[1]) {
                    case "edit":
                        $sender->sendMessage(Language::get("command.block.edit"));
                        break;
                    case "check":
                        $sender->sendMessage(Language::get("command.block.check"));
                        break;
                    case "del":
                        $sender->sendMessage(Language::get("command.block.del"));
                        break;
                    case "copy":
                        $sender->sendMessage(Language::get("command.block.copy"));
                        break;
                    case "cancel":
                        $session->setValid(false);
                        $sender->sendMessage(Language::get("command.block.cancel"));
                        return true;
                    default:
                        $sender->sendMessage(Language::get("command.block.usage"));
                        return true;
                }
                $session->setValid()->set("if_type", IFManager::BLOCK)->set("action", $args[1]);
                break;
            case 'command':
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $session = Session::getSession($sender);
                if (!isset($args[1])) {
                    $this->form->getCommandForm()->sendSelectActionForm($sender);
                    break;
                }
                $session->setValid()->set("if_type", IFManager::COMMAND)->set("action", $args[1]);
                $manager = $this->getOwner()->getCommandManager();
                switch ($args[1]) {
                    case "add":
                    case "add_empty":
                        $this->form->getCommandForm()->sendAddCommandForm($sender);
                        break;
                    case "edit":
                        if (!isset($args[2])) {
                            $this->form->getCommandForm()->sendSelectCommandForm($sender);
                            break;
                        }
                        if (!$manager->exists($args[2])) {
                            $sender->sendMessage(Language::get("command.command.not_added"));
                            $session->setValid(false);
                            break;
                        }
                        $session->set("if_key", $args[2]);
                        $data = $manager->get($args[2]);
                        $mes = IFAPI::createIFMessage($data["if"], $data["match"], $data["else"]);
                        $form = $this->form->getCommandForm()->getEditIfForm($mes);
                        Form::sendForm($sender, $form, $this->form->getCommandForm(), "onEditIf");
                        break;
                    case "check":
                        if (!isset($args[2])) {
                            $this->form->getCommandForm()->sendSelectCommandForm($sender);
                            break;
                        }
                        if (!$manager->exists($args[2])) {
                            $sender->sendMessage(Language::get("command.command.not_added"));
                            $session->setValid(false);
                            break;
                        }
                        $data = $manager->get($args[2]);
                        $mes = IFAPI::createIFMessage($data["if"], $data["match"], $data["else"]);
                        $sender->sendMessage($mes);
                        $session->setValid(false);
                        break;
                    case "del":
                        if (!isset($args[2])) {
                            $this->form->getCommandForm()->sendSelectCommandForm($sender);
                            break;
                        }
                        if (!$manager->exists($args[2])) {
                            $sender->sendMessage(Language::get("command.command.not_added"));
                            $session->setValid(false);
                            break;
                        }
                        $session->set("if_key", $args[2]);
                        $this->form->confirmDelete($sender, [$this->form, "onDeleteIf"]);
                        break;
                    case "cancel":
                        $session->setValid(false);
                        $sender->sendMessage(Language::get("command.command.cancel"));
                        return true;
                    default:
                        $sender->sendMessage(Language::get("command.command.usage"));
                        return true;
                }
                break;
            case 'event':
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $form = $this->form->getEventForm()->getSelectEventForm();
                Form::sendForm($sender, $form, $this->form->getEventForm(), "onSelectEvent");
                break;
            case "chain":
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $session = Session::getSession($sender);
                if (isset($args[1])) {
                    $session = Session::getSession($sender);
                    switch ($args[1]) {
                        case 'add':
                            $session->set("action", "add");
                            $this->form->getChainForm()->sendAddChainIfForm($sender);
                            break;
                        case 'edit':
                            $session->set("action", "edit");
                            $this->form->getChainForm()->sendEditChainIfForm($sender);
                            break;
                        case 'del':
                            $session->set("action", "del");
                            $this->form->getChainForm()->sendEditChainIfForm($sender);
                            break;
                        case 'list':
                            $this->form->getChainForm()->sendChainIfListForm($sender);
                            break;
                        default:
                            $this->form->getChainForm()->sendSelectActionForm($sender);
                            break;
                    }
                    $session->set("if_type", Session::CHAIN);
                    $session->setValid();
                    return true;
                }
                $this->form->getChainForm()->sendSelectActionForm($sender);
                return true;
            case "form":
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $session = Session::getSession($sender);
                $session->setValid(true)->set("if_type", Session::FORM);
                if (!isset($args[1])) {
                    $form = $this->form->getFormIFForm()->getSelectActionForm();
                    Form::sendForm($sender, $form, $this->form->getFormIFForm(), "onSelectAction");
                    break;
                }
                switch ($args[1]) {
                    case "add":
                        $session->set("action", "add");
                        $form = $this->form->getFormIFForm()->getAddIFformForm();
                        Form::sendForm($sender, $form, $this->form->getFormIFForm(), "onAddIFformForm");
                        break;
                    case "edit":
                    case "del":
                        $session->set("action", $args[1]);
                        $form = $this->form->getFormIFForm()->getSelectIFformForm();
                        Form::sendForm($sender, $form, $this->form->getFormIFForm(), "onSelectIFformForm");
                        break;
                    default:
                        $sender->sendMessage(Language::get("command.form.usage"));
                        $session->setValid(false);
                        break;
                }
                break;
            case "import":
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.noconsole"));
                    return true;
                }
                $form = $this->form->getImportForm()->getImportListForm();
                Form::sendForm($sender, $form, $this->form->getImportForm(), "onImportList");
                break;
            default:
                if (!($sender instanceof Player)) {
                    $sender->sendMessage(Language::get("command.if.usage.console"));
                    return true;
                }
                $this->form->sendSelectIfTypeForm($sender);
                break;
        }
        return true;
    }
}