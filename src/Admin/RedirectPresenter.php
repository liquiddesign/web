<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Forms\Form;
use Pages\DB\Redirect;
use Pages\DB\RedirectRepository;

class RedirectPresenter extends BackendPresenter
{
	/** @inject */
	public RedirectRepository $redirectRepository;
	
	public function createComponentGrid()
	{
		$grid = $this->gridFactory->create($this->redirectRepository->many(), 20, 'priority');
		$grid->addColumnSelector();
		$grid->addColumnText('Vytvořeno', "createdTs|date:'d.m.Y'", '%s', 'createdTs', ['class' => 'fit']);
		$grid->addColumnText('Z URL', 'fromUrl', '%s', 'fromUrl');
		$grid->addColumnText('Na URL', 'toUrl', '%s', 'toUrl');
		
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority', [], true);
		
		$grid->addColumnLinkDetail('Detail');
		$grid->addColumnActionDelete();
		
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		$grid->addFilterTextInput('search', ['fromUrl', 'toUrl'], null, 'URL');
		
		$grid->addFilterButtons();
		
		return $grid;
	}
	
	public function createComponentForm(): Form
	{
		$form = $this->formFactory->create();
		
		$form->addText('fromUrl', 'Z URL')
			->setHtmlAttribute('data-info', 'Relativní URL bez jazykového prefixu, např. "novinka/stara-adresa"')
			->setRequired();
		$form->addText('toUrl', 'Na URL')
			->setHtmlAttribute('data-info', 'Relativní URL bez jazykového prefixu, např. "novinka/nova-adresa"')
			->setRequired();
		$form->addText('priority', 'Priorita')->addRule($form::INTEGER)->setRequired()->setDefaultValue(0);
		
		$form->addSubmits(!$this->getParameter('redirect'));
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			$redirect = $this->redirectRepository->syncOne($values, null, false);
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detail', 'default', [$redirect]);
		};
		
		return $form;
	}
	
	public function renderDefault()
	{
		$this->template->headerLabel = 'Přesměrování';
		$this->template->headerTree = [
			['Přesměrování'],
		];
		$this->template->displayButtons = [$this->createNewItemButton('new')];
		$this->template->displayControls = [$this->getComponent('grid')];
	}
	
	public function renderNew()
	{
		$this->template->headerLabel = 'Nové přesměrování';
		$this->template->headerTree = [
			['Přesměrování', 'default'],
			['Nový přesměrování'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderDetail()
	{
		$this->template->headerLabel = 'Detail  přesměrování';
		$this->template->headerTree = [
			['Přesměrování', 'default'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('default')];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function actionDetail(Redirect $redirect)
	{
		/** @var Form $form */
		$form = $this->getComponent('form');
		$form->setDefaults($redirect->jsonSerialize());
	}
	
}