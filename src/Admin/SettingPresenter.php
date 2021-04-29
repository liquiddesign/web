<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Web\DB\ContactItem;
use Web\DB\ContactItemRepository;
use Web\DB\SettingRepository;
use Nette\Forms\Form;

class SettingPresenter extends BackendPresenter
{
	protected const CONFIGURATION = [
		'groups' => ['company', 'support', 'map'],
	];
	
	/** @inject */
	public SettingRepository $settingsRepo;
	
	/** @inject */
	public ContactItemRepository $contactItemRepo;
	
	public function beforeRender()
	{
		parent::beforeRender();
		
		$this->template->tabs = [
			'@default' => 'Hlavní informace a mapa',
			'@contacts' => 'Kontakty',
			'@social' => 'Sítě',
		];
	}
	
	public function actionDefault()
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('form');
		
		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}
	
	public function actionSocial()
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('form');
		
		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}
	
	public function renderDefault()
	{
		$this->template->headerLabel = 'Kontakty a sítě';
		$this->template->headerTree = [
			['Kontakty a sítě'],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('form')];
	}
	
	public function renderSocial()
	{
		$this->template->headerLabel = 'Kontakty a sítě';
		$this->template->headerTree = [
			['Kontakty a sítě'],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('socialForm')];
	}
	
	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create();
		
		if (\in_array('company', static::CONFIGURATION['groups'])) {
			$form->addGroup('Společnost');
			$form->addText('companyName', 'Název společnosti')->setNullable();
			$form->addTextArea('legalInfo', 'Informace o zápisu')->setHtmlAttribute('cols', 70)->setNullable();
		}
		
		if (\in_array('support', static::CONFIGURATION['groups'])) {
			$form->addGroup('Podpora');
			$form->addText('supportEmail', 'Email')->setNullable()->addCondition(Form::FILLED)->addRule($form::EMAIL);
			$form->addText('supportPhone', 'Telefon')->setNullable();
			$form->addText('supportPhoneTime', 'Dostupnost telefonu')->setNullable()->setHtmlAttribute('data-info', 'Zvolte libovolný formát');
		}
		
		if (\in_array('map', static::CONFIGURATION['groups'])) {
			$form->addGroup('Mapa');
			$form->addText('contactStreet', 'Ulice')->setHtmlAttribute('data-info', 'Např.: Josefská 15')->setNullable();
			$form->addText('contactCity', 'Město')->setHtmlAttribute('data-info', 'Např.: 602 00 Brno')->setNullable();
			$form->addText('contactGPSx', 'GPS souřadnice X')->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 16.6125203')->setNullable();
			$form->addText('contactGPSy', 'GPS souřadnice Y')->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 49.1920700')->setNullable();
		}
		
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
	
	public function createComponentSocialForm(): AdminForm
	{
		$form = $this->formFactory->create();
		
		$form->addGroup('Sítě');
		
		$form->addText('socialFacebook', 'Facebook')->setNullable();
		$form->addText('socialInstagram', 'Instagram')->setNullable();
		$form->addText('socialTwitter', 'Twitter')->setNullable();
		
		$form->addSubmit('submit', 'Uložit');
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			
			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne(['name' => $key, 'value' => $value]);
			}
			
			$this->flashMessage('Nastavení uloženo', 'success');
			$form->processRedirect('social');
		};
		
		return $form;
	}
	
	public function renderContacts(): void
	{
		$this->template->headerLabel = 'Kontakty a sítě';
		$this->template->headerTree = [
			['Kontakty a sítě', 'default']
		];
		
		
		$this->template->displayButtons = [$this->createNewItemButton('newContact')];
		$this->template->displayControls = [$this->getComponent('contactsGrid')];
	}
	
	public function actionDetailContact(ContactItem $contactItem): void
	{
		/** @var AdminForm $form */
		$form = $this->getComponent('contactForm');
		$form->setDefaults($contactItem->toArray());
	}
	
	public function renderNewContact(): void
	{
		$this->template->headerLabel = 'Kontakty a sítě';
		$this->template->headerTree = [
			['Kontakty a sítě', 'default'],
			['Nový kontakt']
		];
		
		$this->template->displayButtons = [$this->createBackButton('contacts')];
		$this->template->displayControls = [$this->getComponent('contactForm')];
		$this->template->activeTab = 'contacts';
	}
	
	public function renderDetailContact(ContactItem $contactItem): void
	{
		$this->template->headerLabel = 'Nastavení webu - Kontakty';
		$this->template->headerTree = [
			['Nastavení webu', 'default'],
			['Kontakty', 'contacts'],
			['Detail']
		];
		$this->template->displayButtons = [$this->createBackButton('contacts')];
		$this->template->displayControls = [$this->getComponent('contactForm')];
	}
	
	public function createComponentContactForm(): AdminForm
	{
		$form = $this->formFactory->create(true);
		
		$form->addLocaleText('name', 'Název');
		$form->addText('phone', 'Telefonní čísla')->setHtmlAttribute('data-info', 'Zadejte telefonní čísla oddělená středníkem ";"');
		$form->addText('email', 'Emaily')->setHtmlAttribute('data-info', 'Zadejte emaily oddělené středníkem ";"');
		$form->addInteger('priority', 'Priorita')->setDefaultValue(10)->setRequired();
		$form->addCheckbox('hidden', 'Skryto');
		
		$form->addSubmits(!$this->getParameter('contactItem'));
		
		$form->onSuccess[] = function (AdminForm $form) {
			$values = $form->getValues('array');
			
			$contactItem = $this->contactItemRepo->syncOne($values, null, true);
			
			$this->flashMessage('Uloženo', 'success');
			$form->processRedirect('detailContact', 'contacts', [$contactItem]);
		};
		
		return $form;
	}
	
	public function createComponentContactsGrid(): AdminGrid
	{
		$grid = $this->gridFactory->create($this->contactItemRepo->many(), 20, 'priority');
		$grid->addColumnSelector();
		
		$grid->addColumnText('Název', 'name', '%s', 'name');
		$grid->addColumnText('Telefon', 'phone', '<a href="tel:%1$s"><i class="fa fa-phone-alt"></i> %1$s</a>')->onRenderCell[] = [$grid, 'decoratorEmpty'];
		$grid->addColumnText('Email', 'email', '<a href="mailto:%1$s"><i class="far fa-envelope"></i> %1$s</a>')->onRenderCell[] = [$grid, 'decoratorEmpty'];
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority');
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');
		
		$grid->addColumnLinkDetail('detailContact');
		$grid->addColumnActionDelete();
		
		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();
		
		$grid->addFilterTextInput('search', ['name_cs', 'phone', 'email'], null, 'Název, telefon, email');
		$grid->addFilterButtons(['contacts']);
		
		return $grid;
	}
}