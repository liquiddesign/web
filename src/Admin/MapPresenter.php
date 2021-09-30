<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Map;
use Web\DB\MapRepository;
use Nette\Utils\Random;

class MapPresenter extends BackendPresenter
{
	/** @inject */
	public MapRepository $mapRepo;
	
	public string $tMaps;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tMaps = $this->_('maps', 'Mapy');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tMaps;
		$this->template->headerTree = [
			[$this->tMaps],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNewMap = $this->_('newMap', 'Nová mapa');
		$this->template->headerLabel = $tNewMap;
		$this->template->headerTree = [
			[$this->tMaps, 'default'],
			[$tNewMap],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetailMap = $this->_('detailMap', 'Detail mapy');
		$this->template->headerLabel = $tDetailMap;
		$this->template->headerTree = [
			[$this->tMaps, 'default'],
			[$tDetailMap],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Map $map): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($map->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(false, false, true);
		$form->addText('name', $this->_('name', 'Název'))->setRequired();
		$form->addText('gpsx', $this->_('gpsx', 'GPS X'))->setRequired();
		$form->addText('gpsy', $this->_('gpsy', 'GPS Y'))->setRequired();
		$form->addInteger('zoom', $this->_('zoom', 'Přiblížení'))->setRequired()->setDefaultValue(17);
		$form->addText('width', $this->_('width', 'Šířka'))->setRequired(true)->setDefaultValue('100%')->setHtmlAttribute('size', 5);
		$form->addText('height', $this->_('height', 'Výška'))->setRequired(true)->setDefaultValue('400px')->setHtmlAttribute('size', 5);
		$form->addText('address', $this->_('address', 'Adresa'));
		
		/** @var \Web\DB\Map $map */
		$map = $this->getParameter('map');
		
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		$form->addSubmits(!$map);
		
		$form->onSuccess[] = function (AdminForm $form) use ($map): void {
			$values = $form->getValues('array');
			$map = $this->mapRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$map]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->mapRepo->many(), 200, 'name', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control map-%s}', 'id');
		$grid->addColumnText($this->_('address', 'Adresa'), 'address', '%s', 'address');
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
