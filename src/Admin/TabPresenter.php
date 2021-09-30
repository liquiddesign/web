<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\Tab;
use Web\DB\TabItem;
use Web\DB\TabItemRepository;
use Web\DB\TabRepository;
use Nette\Utils\Random;

class TabPresenter extends BackendPresenter
{
	/** @inject */
	public TabRepository $tabRepo;
	
	/** @inject */
	public TabItemRepository $tabItemRepo;
	
	public string $tTabs;
	
	public string $tTabItems;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tTabs = $this->_('tabs', 'Skupina tabů');
		$this->tTabItems = $this->_('tabItems', 'Položky skupiny');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tTabs;
		$this->template->headerTree = [
			[$this->tTabs],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newTab', 'Nová skupina tabů');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tTabs, 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('detailTab', 'Detail skupiny tabů');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tTabs, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderItems(Tab $tab): void
	{
		$this->template->headerLabel = $this->tTabItems . ': ' . $tab->name;
		$this->template->headerTree = [
			[$this->tTabs, 'default'],
			[$this->tTabItems],
		];
		$this->template->displayButtons = [$this->createBackButton('default'), $this->createNewItemButton('newItem', [$tab])];
		$this->template->displayControls = [$this->getComponent('itemsGrid')];
	}
	
	public function renderNewItem(Tab $tab): void
	{
		$tNewItem = $this->_('newTabItem', 'Nová položka');
		$this->template->headerLabel = $tNewItem;
		$this->template->headerTree = [
			[$this->tTabs, 'default'],
			[$this->tTabItems, 'items', $tab],
			[$tNewItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $tab)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function renderDetailItem(TabItem $tabItem): void
	{
		$tDetailItem = $this->_('detailTabItem', 'Detail položky');
		$this->template->headerLabel = $tDetailItem;
		$this->template->headerTree = [
			[$this->tTabs, 'default'],
			[$this->tTabItems, 'items', $tabItem->tab],
			[$tDetailItem],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $tabItem->tab)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function actionDetail(Tab $tab): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($tab->toArray());
	}
	
	public function actionDetailItem(TabItem $tabItem): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('itemForm');
		$form->setDefaults($tabItem->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('tabName', 'Název'));
		$form->addCheckbox('firstMobile', $this->_('firstMobile', 'První položka bude na mobilu rozbalena'));
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		
		/** @var \Web\DB\Tab $tab */
		$tab = $this->getParameter('tab');
		
		$form->addSubmits(!$tab);
		$form->onSuccess[] = function (AdminForm $form) use ($tab): void {
			$values = $form->getValues('array');
			$tab = $this->tabRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$tab]);
		};
		
		return $form;
	}
	
	public function createComponentItemForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('tabItem');
		$form->addLocaleText('name', $this->_('tabName', 'Název'));
		$form->addLocaleRichEdit('text', $this->_('content', 'Obsah'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		$form->addHidden('tab', (string) ($this->getParameter('tabItem') ? $this->getParameter('tabItem')->tab : $this->getParameter('tab')));
		
		/** @var \Web\DB\TabItem $tabItem */
		$tabItem = $this->getParameter('tabItem');
		
		$form->addSubmits(!$tabItem);
		$form->onSuccess[] = function (AdminForm $form) use ($tabItem): void {
			$values = $form->getValues('array');
			
			$tabItem = $this->tabItemRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detailItem', 'items', [$tabItem], [$tabItem->tab]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$tItems = $this->_('tabItems', 'Taby');
		$grid = $this->gridFactory->create($this->tabRepo->many(), 200, 'name', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('tab');
		$grid->addColumnText($this->_('tabName', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control tab-%s}', 'id');
		$grid->addColumnLink('Items', '<i title="'. $tItems .'" class="far fa-images"></i> '. $tItems .'');
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
	
	public function createComponentItemsGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->tabItemRepo->many()->where('fk_tab', $this->getParameter('tab')->getPK()), 200, 'priority', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('tabItem');
		$grid->addColumnText($this->_('tabName', 'Název'), 'name', '%s', 'name');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail('detailItem');
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
