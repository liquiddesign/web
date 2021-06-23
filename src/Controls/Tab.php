<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\TabRepository;

class Tab extends Control
{
	private TabRepository $tabRepository;
	
	private string $id;
	
	public function __construct(string $id, TabRepository $tabRepository)
	{
		$this->tabRepository = $tabRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->tab = $this->tabRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Tab.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
