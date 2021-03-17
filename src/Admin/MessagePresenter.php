<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use App\Admin\Controls\AdminForm;
use App\Admin\Controls\AdminFormFactory;
use App\Admin\Controls\BulkFormFactory;
use App\Admin\PresenterTrait;
use Forms\Form;
use Messages\DB\Template;
use Messages\DB\TemplateRepository;
use StORM\DIConnection;

class MessagePresenter extends BackendPresenter
{
	/** @persistent */
	public string $tab = 'outgoing';

	/** @inject */
	public TemplateRepository $templateRepository;

	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->templateRepository->many()->where('type', $this->tab), 20, 'name', 'ASC', true);
		$grid->addColumnSelector();

		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnText('Předmět', 'subject', '%s', 'subject');
		$grid->addColumnText($this->tab === 'outgoing' ? 'Odesilatel' : 'Email', 'email', '%s', 'email');
		$grid->addColumnText('Kopie', 'cc', '%s', 'cc');
		$grid->addColumnInputCheckbox('Aktivní', 'active');
		$grid->addColumnLinkDetail('detail');
		
		$grid->addButtonSaveAll();
		$inputs = ['email', 'cc', 'alias'];
		$grid->addButtonBulkEdit('newForm', $inputs);

		$grid->addFilterTextInput('search', ['name', 'subject_cs', 'email'], null, 'Název, předmět, email');
	
		$grid->addFilterButtons();

		return $grid;
	}

	public function createComponentNewForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addText('name', 'Název emailu')->setHtmlAttribute('readonly', 'readonly');
		$form->addLocaleText('subject', 'Předmět');
		$form->addText('email', $this->tab === 'outgoing' ? 'Odesilatel' : 'Email')->setRequired();
		$form->addText('cc', 'Posílat kopie')
		->setHtmlAttribute('data-info','Zadejte emailové adresy oddělené středníkem ";".');
		$form->addText('alias', 'Alias');
		$form->addLocaleRichEdit('html', 'HTML');
		$form->addLocaleTextArea('text', 'Text');
		$form->addCheckbox('active', 'Aktivní');
		$form->addSubmits(!$this->getParameter('template'));
		$form->bind($this->templateRepository->getStructure());

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			
			$template = $this->templateRepository->syncOne($values, null, true);

			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$template]);
		};

		return $form;
	}

	public function renderDefault()
	{
		$this->template->headerLabel = 'Šablony emailů';
		$this->template->headerTree = [
			['Šablony emailů'],
		];
		
		$this->template->tabs = [
			'outgoing' => 'Klientské',
			'incoming' => 'Administrátorské',
		];
		
		$this->template->displayControls = [$this->getComponent('grid')];
	}

	public function renderNew()
	{
		$this->template->headerLabel = 'Nová položka';
		$this->template->headerTree = [
			['Šablony emailů', 'default'],
			['Nová položka'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail';
		$this->template->headerTree = [
			['Šablony emailů', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('newForm')];
	}

	public function actionDetail(Template $template)
	{
		/** @var Form $form */
		$form = $this->getComponent('newForm');

		$form->setDefaults($template->toArray());
	}
	
	public function actionBulkEdit(string $grid = 'grid')
	{
		$this['grid']['bulkForm']->onSuccess[] = function() {
			$this->flashMessage('Uloženo', 'success');
			$this->redirect('default');
		};
	}
}