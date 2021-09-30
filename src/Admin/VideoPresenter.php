<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Video;
use Web\DB\VideoRepository;
use Nette\Utils\Random;

class VideoPresenter extends BackendPresenter
{
	/** @inject */
	public VideoRepository $videoRepo;
	
	public string $tVideos;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tVideos = $this->_('videos', 'Videa');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tVideos;
		$this->template->headerTree = [
			[$this->tVideos],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNewVideo = $this->_('newVideo', 'Nové video');
		$this->template->headerLabel = $tNewVideo;
		$this->template->headerTree = [
			[$this->tVideos, 'default'],
			[$tNewVideo],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetailVideo = $this->_('detailVideo', 'Detail videa');
		$this->template->headerLabel = $tDetailVideo;
		$this->template->headerTree = [
			[$this->tVideos, 'default'],
			[$tDetailVideo],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Video $video): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($video->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('name', 'Název'));
		$form->addText('link', $this->_('link', 'Link'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		
		/** @var \Web\DB\Video $video */
		$video = $this->getParameter('video');
		
		$form->addSubmits(!$video);
		$form->onSuccess[] = function (AdminForm $form) use ($video): void {
			$values = $form->getValues('array');
			$video = $this->videoRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$video]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->videoRepo->many(), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control video-%s}', 'id');
		$grid->addColumnText($this->_('link', 'Link'), 'link', '%s', 'link');
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
