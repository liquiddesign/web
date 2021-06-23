<?php

declare(strict_types=1);

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\BannerRepository;

class Banner extends Control
{
	private BannerRepository $bannerRepository;
	
	private string $id;
	
	public function __construct(string $id, BannerRepository $bannerRepository)
	{
		$this->bannerRepository = $bannerRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$this->template->banner = $this->bannerRepository->one(['id' => $this->id], true);
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Banner.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
