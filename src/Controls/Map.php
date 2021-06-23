<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\MapRepository;

class Map extends Control
{
	private MapRepository $mapRepository;
	
	private string $id;
	
	public function __construct(string $id, MapRepository $mapRepository)
	{
		$this->mapRepository = $mapRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->map = $this->mapRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Map.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
