<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Random;
use Web\DB\Faq;
use Web\DB\FaqItem;
use Web\DB\FaqItemRepository;
use Web\DB\FaqRepository;
use Web\Helpers;

class FaqPresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public FaqRepository $faqRepo;
	
	/**
	 * @inject
	 */
	public FaqItemRepository $faqItemRepo;
	
	public string $tItems;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tItems = $this->_('faqItems', 'Položky');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = "Faq";
		$this->template->headerTree = [
			['Faq'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNew = $this->_('newFaq', 'Nový faq');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tDetail = $this->_('newFaq', 'Detail faq');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderItems(Faq $faq): void
	{
		$this->template->headerLabel = $this->tItems . ': ' . $faq->name;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$this->tItems],
		];
		$this->template->displayButtons = [$this->createBackButton('default'), $this->createNewItemButton('newItem', [$faq])];
		$this->template->displayControls = [$this->getComponent('itemsGrid')];
	}
	
	public function renderNewItem(Faq $faq): void
	{
		$tItemNew = $this->_('itemNew', 'Nová položka');
		$this->template->headerLabel = $tItemNew;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$this->tItems, 'items', $faq],
			[$tItemNew],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $faq)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function renderDetailItem(FaqItem $faqItem): void
	{
		$tItemDetail = $this->_('itemDetail', 'Detail položky');
		$this->template->headerLabel = $tItemDetail;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$this->tItems, 'items', $faqItem->faq],
			[$tItemDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('items', $faqItem->faq)];
		$this->template->displayControls = [$this->getComponent('itemForm')];
	}
	
	public function actionDetail(Faq $faq): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($faq->toArray());
	}
	
	public function actionDetailItem(FaqItem $faqItem): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('itemForm');
		$form->setDefaults($faqItem->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$form->addLocaleText('name', $this->_('name', 'Název'));
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		
		/** @var \Web\DB\Faq $faq */
		$faq = $this->getParameter('faq');
		
		$form->addSubmits(!$faq);
		$form->onSuccess[] = function (AdminForm $form) use ($faq): void {
			$values = $form->getValues('array');
			
			$faq = $this->faqRepo->syncOne($values, null, true);
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$faq]);
		};
		
		return $form;
	}
	
	public function createComponentItemForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('faqItem');
		$form->addLocaleText('question', $this->_('question', 'Dozat'));
		$form->addLocaleRichEdit('answer', $this->_('answer', 'Odpověď'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		$form->addHidden('faq', (string) ($this->getParameter('faqItem') ? $this->getParameter('faqItem')->faq : $this->getParameter('faq')));
		
		/** @var \Web\DB\FaqItem $faqItem */
		$faqItem = $this->getParameter('faqItem');
		
		$form->addSubmits(!$faqItem);
		$form->onSuccess[] = function (AdminForm $form) use ($faqItem): void {
			$values = $form->getValues('array');

			$values['answer'] = Helpers::sanitizeMutationsStrings($values['answer']);

			$faqItem = $this->faqItemRepo->syncOne($values, null, true);
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detailItem', 'items', [$faqItem], [$faqItem->faq]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$this->tItems = $this->_('faqItems', 'Položky');
		$grid = $this->gridFactory->create($this->faqRepo->many(), 200, 'name', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control faq-%s}', 'id');
		$grid->addColumnLink('Items', '<i title="'. $this->tItems .'" class="fas fa-list-ul"></i> '. $this->tItems .'');
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
	
	public function createComponentItemsGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->faqItemRepo->many()->where('fk_faq', $this->getParameter('faq')->getPK()), 200, 'priority', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('faqItem');
		$grid->addColumnText($this->_('question', 'Dotaz'), 'question', '%s', 'question');
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
