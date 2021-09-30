<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Hubspot;
use Web\DB\HubspotRepository;
use Nette\Utils\Random;

class HubspotPresenter extends BackendPresenter
{
	public string $tHubspots;
	
	/** @inject */
	public HubspotRepository $hubspotRepo;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tHubspots = $this->_('hubspots', 'Formuláře');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tHubspots;
		$this->template->headerTree = [
			[$this->tHubspots],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newForm', 'Nový formulář');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tHubspots, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detailForm', 'Detail formuláře');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tHubspots, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Hubspot $hubspot): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($hubspot->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('name', 'Název'))->setRequired();
		$form->addTextArea('script', $this->_('formText', 'Script formuláře'))->setRequired();
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		
		/** @var \Web\DB\Hubspot $hubspot */
		$hubspot = $this->getParameter('hubspot');
		
		$form->addSubmits(!$hubspot);
		
		$form->onSuccess[] = function (AdminForm $form) use ($hubspot): void {
			$values = $form->getValues('array');
			$hubspot = $this->hubspotRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$hubspot]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->hubspotRepo->many(), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control hubspot-%s}', 'id');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
