<?php

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Random;
use Web\DB\Banner;
use Web\DB\BannerRepository;

class BannerPresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public BannerRepository $bannerRepo;
	
	public string $tBanners;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tBanners = $this->_('banners', 'Banery');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tBanners;
		$this->template->headerTree = [
			[$this->tBanners],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newBanner', 'Nový baner');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tBanners, 'default'],
			[$tNew],
		];
		
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detailBanner', 'Detail baneru');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tBanners, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Banner $banner): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($banner->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('headline', $this->_('headline', 'Nadpis'));
		$form->addLocaleText('text', $this->_('text', 'Text'));
		$imagePicker = $form->addImagePicker('image', $this->_('picture', 'Obrázek'), [
			Banner::IMAGE_DIR . '/image' => null,
		]);
		
		$imageBackground = $form->addImagePicker('background', $this->_('backgroundImage', 'Obrázek na pozadí'), [
			Banner::IMAGE_DIR . '/background' => null,
		]);
		
		/** @var \Web\DB\Banner $banner */
		$banner = $this->getParameter('banner');
		
		if ($banner) {
			$imagePicker->onDelete[] = function () use ($banner): void {
				$banner->update(['image' => null]);
				$this->redirect('this');
			};
			
			$imageBackground->onDelete[] = function () use ($banner): void {
				$banner->update(['background' => null]);
				$this->redirect('this');
			};
		}

		$this->formFactory->addShopsContainerToAdminForm($form, false);
		
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$banner);
		$form->onSuccess[] = function (AdminForm $form) use ($banner): void {
			$values = $form->getValues('array');
			$this->generateDirectories([Banner::IMAGE_DIR], ['image', 'background']);
			
			$values['image'] = $form['image']->upload($values['uuid'] . '.%2$s');
			$values['background'] = $form['background']->upload($values['uuid'] . '.%2$s');
			
			$banner = $this->bannerRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$banner]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->bannerRepo->many(), 200, 'uuid', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnImage('image', 'banner', 'image', $this->_('picture', 'Obrázek'));
		$grid->addColumnImage('background', 'banner', 'background', $this->_('backgroundImage', 'Obrázek na pozadí'));
		$grid->addColumnText($this->_('headline', 'Nadpis'), 'headline', '%s', 'headline');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control banner-%s}', 'id');
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
