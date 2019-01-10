<?php

namespace aieuo\ip\processes;

use pockemine\Server;
use pocketmine\command\ConsoleCommandSender;

use aieuo\ip\form\Form;
use aieuo\ip\form\Elements;

class CommandConsole extends TypeCommand
{
	public $id = self::COMMAND_CONSOLE;

	public function getName()
	{
		"コンソールからコマンドを実行する";
	}

	public function getDescription()
	{
		"コンソールからコマンド§7<command>§rを実行する";
	}

	public function excute()
	{
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender, $this->getCommand());
	}
}