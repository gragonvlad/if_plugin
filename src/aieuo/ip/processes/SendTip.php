<?php

namespace aieuo\ip\processes;

use aieuo\ip\form\Form;
use aieuo\ip\form\Elements;

class SendTip extends TypeMessage
{
	public $id = self::SENDTIP;

	public function getName()
	{
		return "tip欄にメッセージを送る";
	}

	public function getDescription()
	{
		return "tip欄にメッセージ§7<message>§fを送る";
	}

	public function execute()
	{
		$player = $this->getPlayer();
		$player->sendTip($this->getMessage());
	}
}