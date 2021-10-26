<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Web\DB\ContactItemRepository;
use Web\DB\SettingRepository;

/**
 * Class IntegrationPresenter
 * @package Web\Admin
 * @deprecated Only basic functionality, for full functionality use IntegrationPresenter from package Eshop
 */
class IntegrationPresenter extends BackendPresenter
{
	/** @inject */
	public SettingRepository $settingsRepo;
	
	/** @inject */
	public ContactItemRepository $contactItemRepo;
	
	public function beforeRender(): void
	{
		parent::beforeRender();
		
		$this->template->tabs = [
			'@default' => 'Měření a nástroje',
			'@zasilkovna' => 'Zásilkovna',
			'@mailerLite' => 'MailerLite',
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
		$form->addText('zasilkovnaApiKey', 'Klíč API')->setNullable();
		$form->addText('zasilkovnaApiPassword', 'Heslo API')->setNullable();

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

	public function actionMailerLite()
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('mailerLiteForm');

		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}

	public function renderMailerLite()
	{
		$this->template->headerLabel = 'Integrace';
		$this->template->headerTree = [
			['Integrace'],
			['MailerLite']
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('mailerLiteForm')];
	}

	public function createComponentMailerLiteForm(): AdminForm
	{
		$form = $this->formFactory->create();
		$form->addText('mailerLiteApiKey', 'Klíč API')->setNullable();

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne(['name' => $key, 'value' => $value]);
			}

			$this->flashMessage('Nastavení uloženo', 'success');
			$form->processRedirect('mailerLite');
		};

		return $form;
	}
}