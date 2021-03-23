<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Web\DB\ContactItemRepository;
use Web\DB\SettingRepository;

class IntegrationPresenter extends BackendPresenter
{
	/** @inject */
	public SettingRepository $settingsRepo;
	
	/** @inject */
	public ContactItemRepository $contactItemRepo;
	
	public function beforeRender()
	{
		parent::beforeRender();
		
		$this->template->tabs = [
			'@default' => 'Měření a nástroje',
			'@zasilkovna' => 'Zásilkovna',
		];
	}
	
	public function actionDefault()
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('form');
		
		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}
	
	public function renderDefault()
	{
		$this->template->headerLabel = 'Integrace';
		$this->template->headerTree = [
			['Integrace'],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create();
		$form->addText('integrationGTM', 'GTM (Google Tag Manager)')->setNullable();
		
		$form->addSubmit('submit', 'Uložit');
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			
			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne(['name' => $key, 'value' => $value]);
			}
			
			$this->flashMessage('Nastavení uloženo', 'success');
			$form->processRedirect('default');
		};
		
		return $form;
	}

	public function actionZasilkovna()
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('zasilkovnaForm');

		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}

	public function renderZasilkovna()
	{
		$this->template->headerLabel = 'Integrace';
		$this->template->headerTree = [
			['Integrace'],
			['Zásilkovna']
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('zasilkovnaForm')];
	}

	public function createComponentZasilkovnaForm(): AdminForm
	{
		$form = $this->formFactory->create();
		$form->addText('zasilkovnaApiKey', 'API klíč')->setNullable();

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne(['name' => $key, 'value' => $value]);
			}

			$this->flashMessage('Nastavení uloženo', 'success');
			$form->processRedirect('zasilkovna');
		};

		return $form;
	}
	
}