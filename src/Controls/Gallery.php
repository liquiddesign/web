<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\GalleryRepository;

class Gallery extends Control
{
	private GalleryRepository $galleryRepository;
	
	private string $id;
	
	public function __construct(string $id, GalleryRepository $galleryRepository)
	{
		$this->galleryRepository = $galleryRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->gallery = $this->galleryRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Gallery.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}