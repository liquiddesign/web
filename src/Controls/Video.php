<?php

namespace Web\Controls;

use Nette\Application\UI\Control;
use StORM\Exception\NotFoundException;
use Web\DB\VideoRepository;

class Video extends Control
{
	private VideoRepository $videoRepository;
	
	private string $id;
	
	public function __construct(string $id, VideoRepository $videoRepository)
	{
		$this->videoRepository = $videoRepository;
		$this->id = $id;
	}
	
	public function render(): void
	{
		try {
			$video = $this->videoRepository->one(['id' => $this->id], true);
			$this->template->videoCode = $video ? $video->getYoutubeCode() : null;
			$this->template->setFile($this->template->getFile() ?: __DIR__ . '/Video.latte');
			$this->template->render();
		} catch (NotFoundException $x) {
			echo "*** widget #'$this->id' was deleted ***";
		}
	}
}
