<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Forms\Form;
use Messages\DB\Template;
use Messages\DB\TemplateRepository;

class MessagePresenter extends BackendPresenter
{
	/**
	 * @persistent
	 */
	public string $tab = 'outgoing';

	/**
	 * @inject
	 */
	public TemplateRepository $templateRepository;

	public function createComponentGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->templateRepository->many()->where('type', $this->tab), 20, 'name', 'ASC', true);
		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnText('Předmět', 'subject', '%s', 'subject');
		$grid->addColumnText($this->tab === 'outgoing' ? 'Odesílatel' : 'E-mail', 'email', '%s', 'email');
		$grid->addColumnText('Kopie', 'cc', '%s', 'cc');
		$grid->addColumnInputCheckbox('Aktivní', 'active');
		$grid->addColumnLinkDetail('detail');
		
		$grid->addButtonSaveAll();
		$inputs = ['email', 'cc', 'alias'];
		$grid->addButtonBulkEdit('newForm', $inputs);

		$grid->addFilterTextInput('search', ['name', 'subject_cs', 'email'], null, 'Název, předmět, e-mail');
	
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create(true);

		$form->addText('name', 'Název e-mailu')->setHtmlAttribute('readonly', 'readonly');
		$form->addLocaleText('subject', 'Předmět');
		$form->addText('email', $this->tab === 'outgoing' ? 'Odesílatel' : 'E-mail')->setRequired();
		$form->addText('cc', 'Posílat kopie')
		->setHtmlAttribute('data-info', 'Zadejte e-mailové adresy oddělené středníkem ";".');
		$form->addText('replyTo', 'Adresa pro odpověď');
		$form->addText('alias', 'Alias');
		$form->addLocaleRichEdit('html', 'HTML');
		$form->addLocaleTextArea('text', 'Text');
		$form->addCheckbox('active', 'Aktivní');
		$form->addSubmits(!$this->getParameter('template'));
		$form->bind($this->templateRepository->getStructure());

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');
			
			$template = $this->templateRepository->syncOne($values, null, true);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$template]);
		};

		return $form;
	}

	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Šablony e-mailů';
		$this->template->headerTree = [
			['Šablony e-mailů'],
		];
		
		$this->template->tabs = [
			'outgoing' => 'Klientské',
			'incoming' => 'Administrátorské',
		];
		
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew(): void
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Šablony e-mailů', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function renderDetail(): void
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Šablony e-mailů', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function actionDetail(Template $template): void
	{
		/** @var \Forms\Form $form */
		$form = $this->getComponent('newForm');

		$form->setDefaults($template->toArray());
	}
}
