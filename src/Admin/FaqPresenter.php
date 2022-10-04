<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Random;
use Pages\DB\PageRepository;
use Web\DB\AuthorRepository;
use Web\DB\Faq;
use Web\DB\FaqItem;
use Web\DB\FaqItemRepository;
use Web\DB\FaqItemTag;
use Web\DB\FaqItemTagRepository;
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
	
	/**
	 * @inject
	 */
	public FaqItemTagRepository $faqItemTagRepo;
	
	/** @inject */
	public AuthorRepository $authorRepository;
	
	/** @inject */
	public PageRepository $pageRepository;
	
	public string $tItems;
	
	public string $tTags;
	
	/** @persistent */
	public string $tab = 'items';
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tItems = $this->_('faqItems', 'Položky');
		$this->tTags = $this->_('faqItemTags', 'Tagy');
	}
	
	public function renderDefault(): void
	{
		$this->template->tabs = [
			'items' => $this->_('faqs', 'Faq'),
			'tags' => $this->_('faqItemTags', 'Tagy'),
		];
		
		if ($this->tab === 'items') {
			$this->template->headerLabel = 'Faq';
			$this->template->headerTree = [
				['Faq'],
			];
			$this->template->displayButtons = [$this->createNewItemButton('new')];
			$this->template->displayControls = [$this->getComponent('grid')];
		} elseif ($this->tab === 'tags') {
			$this->template->headerLabel = 'Faq';
			$this->template->headerTree = [
				['Faq'],
			];
			$this->template->displayButtons = [$this->createNewItemButton('newTag')];
			$this->template->displayControls = [$this->getComponent('gridTags')];
		}
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
	
	public function renderNewTag(): void
	{
		$tNew = $this->_('newFaqTag', 'Nový tag');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$tNew],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('formTag')];
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
	
	public function renderDetailTag(): void
	{
		$tDetail = $this->_('newFaqItemTag', 'Detail tag');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			['Faq', 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('formTag')];
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
	
	public function actionDetailTag(FaqItemTag $faqItemTag): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('formTag');
		$form->setDefaults($faqItemTag->toArray());
	}
	
	public function actionDetailItem(FaqItem $faqItem): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('itemForm');
		$form->setDefaults($faqItem->toArray(['tags']));
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
	
	public function createComponentFormTag(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true);
		$nameInput = $form->addLocaleText('name', $this->_('name', 'Název'))->setDefaults(['cs' => '', 'en' => '']);
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('.hidden', 'Skryto'));
		
		$form->addPageContainer('faq', ['tag' => $this->getParameter('faqItemTag')], $nameInput);
		
		/** @var \Web\DB\FaqItemTag $faqItemTag */
		$faqItemTag = $this->getParameter('faqItemTag');
		
		$form->addSubmits(!$faqItemTag);
		$form->onSuccess[] = function (AdminForm $form) use ($faqItemTag): void {
			$values = $form->getValues('array');
			
			$faqItemTag = $this->faqItemTagRepo->syncOne($values, null, true);
			
			$form->syncPages(function () use ($faqItemTag, $values): void {
				$values['page']['params'] = \Pages\Helpers::serializeParameters(['tag' => $faqItemTag->getPK()]);
				$this->pageRepository->syncOne($values['page']);
			});
			
			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detailTag', 'default', [$faqItemTag]);
		};
		
		return $form;
	}
	
	public function createComponentItemForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('faqItem');
		$form->addLocaleText('question', $this->_('question', 'Dozat'));
		$form->addLocaleRichEdit('answer', $this->_('answer', 'Odpověď'));
		$form->addMultiSelect2('tags', $this->_('tags', 'Tagy'), $this->faqItemTagRepo->getArrayForSelect());
		$form->addSelect('author', $this->_('author', 'Autor'))->setItems($this->authorRepository->getArrayForSelect())->setPrompt('- bez autora -');
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addLocaleText('extendedLink', $this->_('extendedLink', 'Rozšířený odkaz'));
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
		$grid->addColumnLink('Items', '<i title="' . $this->tItems . '" class="fas fa-list-ul"></i> ' . $this->tItems . '');
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
	
	public function createComponentItemsGrid(): AdminGrid
	{
		$mutationSuffix = $this->faqItemRepo->getConnection()->getMutationSuffix();
		
		$grid = $this->gridFactory->create(
			$this->faqItemRepo->many()->where('fk_faq', $this->getParameter('faq')->getPK())
				->join(['tagsNxN' => 'web_faqitem_nxn_web_faqitemtag'], 'this.uuid = tagsNxN.fk_item')
				->join(['tag' => 'web_faqitemtag'], 'tagsNxN.fk_tag = tag.uuid')
				->setGroupBy(['this.uuid'])
				->select(['itemTags' => "GROUP_CONCAT(tag.name$mutationSuffix SEPARATOR ', ')"]),
			200,
			'priority',
			'ASC',
		);
		$grid->addColumnSelector();
		$grid->setLogging('faqItem');
		$grid->addColumnText($this->_('question', 'Dotaz'), 'question', '%s', 'question');
		$grid->addColumnText($this->_('tags', 'Tagy'), 'itemTags', '%s');
		$grid->addColumnHidden();
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail('detailItem');
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
	
	public function createComponentGridTags(): AdminGrid
	{
		$this->tTags = $this->_('faqItemTags', 'Tagy');
		$grid = $this->gridFactory->create($this->faqItemTagRepo->many(), 200, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnInputInteger($this->translator->translate('admin.Priority', 'Priorita'), 'priority', '', '', 'priority', [], true);
		$grid->addColumnHidden();
		$grid->addColumnLinkDetail('detailTag');
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		return $grid;
	}
}
