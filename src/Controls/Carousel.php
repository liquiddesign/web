<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\CarouselRepository;

class Carousel extends Control
{
	private CarouselRepository $carouselRepository;
	
	private string $id;
	
	public function __construct(string $id, CarouselRepository $carouselRepository)
	{
		$this->carouselRepository = $carouselRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->carousel = $this->carouselRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Carousel.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
