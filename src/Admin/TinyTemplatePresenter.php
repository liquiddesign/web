<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\TinyTemplate;
use Web\DB\TinyTemplateRepository;

class TinyTemplatePresenter extends BackendPresenter
{
	/**
	 * @inject
	 */
	public TinyTemplateRepository $tinyRepo;

	public string $tTiny;

	public function beforeRender(): void
	{
		parent::beforeRender();

		$this->tTiny = $this->_('adminWebTinytemplate.layouts', 'Layouty');
	}

	public function renderDefault(): void
	{
		$this->template->headerLabel = $this->tTiny;
		$this->template->headerTree = [
			[$this->tTiny],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew(): void
	{
		$tNew = $this->_('adminWebTinytemplate.newTemplate', 'Nový layout');
		$this->template->headerLabel = $tNew;
		$this->template->headerTree = [
			[$this->tTiny, 'default'],
			[$tNew],
		];

		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderDetail(): void
	{
		$tDetail = $this->_('adminWebTinytemplate.detailTemplate', 'Detail layoutu');
		$this->template->headerLabel = $tDetail;
		$this->template->headerTree = [
			[$this->tTiny, 'default'],
			[$tDetail],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function actionDetail(TinyTemplate $template): void
	{
		$form = $this->getComponent('form');
		$form->setDefaults($template->toArray());
	}

	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create(true, true);
		$form->addLocaleText('name', $this->_('adminWebTinytemplate.name', 'Název'));
		$form->addLocaleText('description', $this->_('adminWebTinytemplate.description', 'Popis'));
		$form->addTextArea('html', $this->_('adminWebTinytemplate.content', 'Obsah'), null, 5)->setRequired();
		$form->addInteger('priority', $this->_('admin.priority', 'Pořadí'))->setRequired()->setDefaultValue(10);
		$form->addCheckbox('hidden', $this->_('admin.hidden', 'Skryto'));

		/** @var \Web\DB\TinyTemplate $template */
		$template = $this->getParameter('template');

		$form->addSubmits(!$template);

		$form->onSuccess[] = function (AdminForm $form) use ($template): void {
			$values = $form->getValues('array');

			$template = $this->tinyRepo->syncOne($values, null, true);

			$this->flashMessage($this->_('admin.saved', 'Uloženo'), 'success');
			$form->processRedirect('detail', 'default', [$template]);
		};

		return $form;
	}

	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->tinyRepo->many(), 20, 'priority', 'ASC', true);
		$grid->addColumnSelector();
		$grid->addColumnText($this->_('adminWebTinytemplate.name', 'Název'), 'name', '%s', 'name');
		$grid->addColumnText($this->_('adminWebTinytemplate.description', 'Popis'), 'description', '%s', 'description');
		$grid->addColumnInputInteger($this->_('admin.priority', 'Pořadí'), 'priority', '', '', 'priority', [], true);
		$grid->addColumnInputCheckbox('<i title="'. $this->_('admin.hidden', 'Skryto') .'" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');

		$grid->addColumnMutations('active', false);
		$grid->addColumnLinkDetail();
		$grid->addColumnActionDelete();
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();

		return $grid;
	}
}
