<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Carousel;
use Web\DB\CarouselRepository;
use Web\DB\CarouselSlide;
use Web\DB\CarouselSlideRepository;
use Nette\Utils\Random;

class CarouselPresenter extends BackendPresenter
{
	/** @inject */
	public CarouselRepository $carouselRepo;
	
	/** @inject */
	public CarouselSlideRepository $carouselSlideRepo;
	
	public string $tDefault;
	
	public string $tItems;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tDefault = $this->_('carousels', 'Carousely');
		$this->tItems = $this->_('carouselItems', 'Položky carouselu');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tDefault;
		$this->template->headerTree = [
			[$this->tDefault],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newCarousel', 'Nový carousel');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tDefault, 'default'],
			[$tNew],
		];
		
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(Carousel $carousel): void
	{
		$tDetail = $this->_('detailCarousel', 'Detail carouselu');
		$this->template->headerLabel = $tDetail . ': ' . $carousel->name;
		$this->template->headerTree = [
			[$this->tDefault, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderItems(Carousel $carousel): void
	{
		$this->template->headerLabel = $this->tItems . ': ' . $carousel->name;
		$this->template->headerTree = [
			[$this->tDefault, 'default'],
			[$this->tItems],
		];
		$this->template->displayButtons = [$this->createBackButton('default'), $this->createNewItemButton('newItem', [$carousel])];
		$this->template->displayControls = [$this->getComponent('itemsGrid')];
	}
	
	public function renderNewItem(Carousel $carousel): void
	{
		$tNewItem = $this->_('carouselNewItem', 'Nová položka');
		$this->template->headerLabel = $tNewItem;
		$this->template->headerTree = [
			[$this->tDefault, 'default'],
			[$this->tItems, 'items', $carousel],
			[$tNewItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $carousel)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function renderDetailItem(CarouselSlide $carouselSlide): void
	{
		$tDetailItem = $this->_('carouselDetailItem', 'Detail položky');
		$this->template->headerLabel = $tDetailItem;
		$this->template->headerTree = [
			[$this->tDefault, 'default'],
			[$this->tItems, 'items', $carouselSlide->carousel],
			[$tDetailItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $carouselSlide->carousel)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function actionDetail(Carousel $carousel): void
	{
		$form = $this->getComponent('form');
		$form->setDefaults($carousel->toArray());
	}
	
	public function actionDetailItem(CarouselSlide $carouselSlide): void
	{
		$form = $this->getComponent('itemForm');
		$form->setDefaults($carouselSlide->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('carouselName', 'Název'));
		
		/** @var \Web\DB\Carousel $carousel */
		$carousel = $this->getParameter('carousel');
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$carousel);
		$form->onSuccess[] = function (AdminForm $form) use ($carousel): void {
			$values = $form->getValues('array');
			$carousel = $this->carouselRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$carousel]);
		};
		
		return $form;
	}
	
	public function createComponentItemForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('title', $this->_('carouselName', 'Název'));
		$form->addLocaleRichEdit('text', $this->_('carouselContent', 'Obsah'));
		$imagePicker = $form->addImagePicker('image', $this->_('carouselBgImage', 'Obrázek v pozadí'), [
			Carousel::IMAGE_DIR . '/' => null,
		]);
		
		/** @var \Web\DB\CarouselSlide $carouselSlide */
		$carouselSlide = $this->getParameter('carouselSlide');
		
		$imagePicker->onDelete[] = function () use ($carouselSlide): void {
			if ($carouselSlide) {
				$carouselSlide->update(['image' => null]);
				$this->redirect('this');
			}
		};
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		$form->addHidden('carousel', (string) ($this->getParameter('carouselSlide') ? $this->getParameter('carouselSlide')->carousel : $this->getParameter('carousel')));
		
		$form->addSubmits(!$carouselSlide);
		$form->onSuccess[] = function (AdminForm $form) use ($carouselSlide): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Carousel::IMAGE_DIR]);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			$carouselSlide = $this->carouselSlideRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detailItem', 'items', [$carouselSlide], [$carouselSlide->carousel]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$tPictures = $this->_('carouselPictures', 'Obrázky');
		$grid = $this->gridFactory->create($this->carouselRepo->many(), 200, 'name', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('carouselName', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control carousel-%s}', 'id');
		$grid->addColumnLink('Items', '<i title="'. $tPictures .'" class="far fa-images"></i> '. $tPictures .'');
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
	
	public function createComponentItemsGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->carouselSlideRepo->many()->where('fk_carousel', $this->getParameter('carousel')->getPK()), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('image', Carousel::IMAGE_DIR, '', $this->_('carouselPreview', 'Náhled'));
		$grid->addColumnText($this->_('carouselName', 'Název'), 'title', '%s', 'title');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnLinkDetail('detailItem');
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
