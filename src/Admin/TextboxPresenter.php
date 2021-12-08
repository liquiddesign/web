<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Utils\Random;
use Web\DB\Textbox;
use Web\DB\TextboxRepository;
use Web\Helpers;

class TextboxPresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public TextboxRepository $textboxRepo;
	
	public string $tTextbox;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->tTextbox = $this->_('textbox', 'Textové boxy');
	}
	
	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tTextbox;
		$this->template->headerTree = [
			[$this->tTextbox],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew(): void
	{
		$tNewTextbox = $this->_('newTextbox', 'Nový textový box');
		$this->template->headerLabel = $tNewTextbox;
		$this->template->headerTree = [
			[$this->tTextbox, 'default'],
			[$tNewTextbox],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail(): void
	{
		$tNewTextbox = $this->_('detailTextbox', 'Detail textového boxu');
		$this->template->headerLabel = $tNewTextbox;
		$this->template->headerTree = [
			[$this->tTextbox, 'default'],
			[$tNewTextbox],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Textbox $textbox): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');
		$form->setDefaults($textbox->toArray());
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true, true, true);
		$form->setLogging('textbox');
		$form->addLocaleText('name', $this->_('name', 'Název'));
		$form->addLocaleRichEdit('text', $this->_('content', 'Obsah'));
		$form->addInteger('priority', $this->_('.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addHidden('id')->setDefaultValue(Random::generate(4));
		/** @var \Web\DB\Textbox $textbox */
		$textbox = $this->getParameter('textbox');
		
		$form->addSubmits(!$textbox);
		
		$form->onSuccess[] = function (AdminForm $form) use ($textbox): void {
			$values = $form->getValues('array');
			$changedProperties = $form->getChangedProperties();

			$values['text'] = Helpers::sanitizeMutationsStrings($values['text']);

			$textbox = $this->textboxRepo->syncOne($values, $changedProperties, true, true);

			$this->flashMessage($this->_('.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$textbox]);
		};
		
		return $form;
	}
	
	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->textboxRepo->many(), 200, 'priority', 'ASC');
		$grid->addColumnSelector();
		$grid->setLogging('textbox');
		$grid->addColumnText($this->_('name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('.code', 'Kód'), 'id', '{control textbox-%s}', 'id');
		$grid->addColumnPriority();
		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDeleteSystemic();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected(null, false, function (Textbox $textbox) {
			return !$textbox->isSystemic();
		}, 'this.uuid');
		
		return $grid;
	}
}
