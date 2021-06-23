<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\TextboxRepository;

class Textbox extends Control
{
	private TextboxRepository $textboxRepository;
	
	private string $id;
	
	public function __construct(string $id, TextboxRepository $textboxRepository)
	{
		$this->textboxRepository = $textboxRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			echo $this->textboxRepository->one(['id' => $this->id], true)->text;
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
