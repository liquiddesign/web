<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Nette\Forms\Form;
use Web\DB\ContactItem;
use Web\DB\ContactItemRepository;
use Web\DB\SettingRepository;

class SettingPresenter extends BackendPresenter
{
	protected const CONFIGURATION = [
		'groups' => [
			'company',
			'support',
			'map',
		],
		'tabs' => [
			'@default' => 'Hlavní informace a mapa',
			'@contacts' => 'Kontakty',
			'@social' => 'Sítě',
		],
		'allowedSettings' => [],
	];

	/**
	 * @inject
	 */
	public SettingRepository $settingsRepo;

	/**
	 * @inject
	 */
	public ContactItemRepository $contactItemRepo;

	public function beforeRender(): void
	{
		parent::beforeRender();

		$this->template->tabs = $this::CONFIGURATION['tabs'];
	}

	public function actionDefault(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');

		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}

	public function actionSocial(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('socialForm');

		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}

	public function actionOthers(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('othersForm');

		$form->setDefaults($this->settingsRepo->many()->setIndex('name')->toArrayOf('value'));
	}

	public function renderDefault(): void
	{
		$this->template->headerLabel = 'Kontakty';
		$this->template->headerTree = [
			['Kontakty'],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('form')];
	}

	public function renderSocial(): void
	{
		$this->template->headerLabel = 'Sítě';
		$this->template->headerTree = [
			['Sítě'],
		];
		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('socialForm')];
	}

	public function createComponentForm(): AdminForm
	{
		$form = $this->formFactory->create();

		if (\in_array('company', self::CONFIGURATION['groups'])) {
			$form->addGroup('Společnost');
			$form->addText('companyName', 'Název společnosti')->setNullable();
			$form->addTextArea('legalInfo', 'Informace o zápisu')->setHtmlAttribute('cols', 70)->setNullable();
		}

		if (\in_array('support', self::CONFIGURATION['groups'])) {
			$form->addGroup('Podpora');
			$form->addText('supportEmail', 'E-mail')->setNullable()->addCondition(Form::FILLED)->addRule($form::EMAIL);
			$form->addText('supportPhone', 'Telefon')->setNullable();
			$form->addText('supportPhoneTime', 'Dostupnost telefonu')->setNullable()->setHtmlAttribute('data-info', 'Zvolte libovolný formát');
		}

		if (\in_array('map', self::CONFIGURATION['groups'])) {
			$form->addGroup('Mapa');
			$form->addText('contactStreet', 'Ulice')->setHtmlAttribute('data-info', 'Např.: Josefská 15')->setNullable();
			$form->addText('contactCity', 'Město')->setHtmlAttribute('data-info', 'Např.: 602 00 Brno')->setNullable();
			$form->addText('contactGPSx', 'GPS souřadnice X')->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 16.6125203')->setNullable();
			$form->addText('contactGPSy', 'GPS souřadnice Y')->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 49.1920700')->setNullable();
		}

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form): void {
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

		$form->onSuccess[] = function (AdminForm $form): void {
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
			['Kontakty a sítě', 'default'],
		];


		$this->template->displayButtons = [$this->createNewItemButton('newContact')];
		$this->template->displayControls = [$this->getComponent('contactsGrid')];
	}

	public function actionDetailContact(ContactItem $contactItem): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('contactForm');
		$form->setDefaults($contactItem->toArray());
	}

	public function renderNewContact(): void
	{
		$this->template->headerLabel = 'Kontakty a sítě';
		$this->template->headerTree = [
			['Kontakty a sítě', 'default'],
			['Nový kontakt'],
		];

		$this->template->displayButtons = [$this->createBackButton('contacts')];
		$this->template->displayControls = [$this->getComponent('contactForm')];
		$this->template->activeTab = 'contacts';
	}

	public function renderDetailContact(ContactItem $contactItem): void
	{
		unset($contactItem);
		$this->template->headerLabel = 'Nastavení webu - Kontakty';
		$this->template->headerTree = [
			['Nastavení webu', 'default'],
			['Kontakty', 'contacts'],
			['Detail'],
		];
		$this->template->displayButtons = [$this->createBackButton('contacts')];
		$this->template->displayControls = [$this->getComponent('contactForm')];

		$this->template->activeTab = 'contacts';
	}

	public function createComponentContactForm(): AdminForm
	{
		$form = $this->formFactory->create(true);

		$form->addLocaleText('name', 'Název');
		$form->addText('phone', 'Telefonní čísla')->setHtmlAttribute('data-info', 'Zadejte telefonní čísla oddělená středníkem ";"');
		$form->addText('email', 'E-maily')->setHtmlAttribute('data-info', 'Zadejte e-maily oddělené středníkem ";"');
		$form->addInteger('priority', 'Priorita')->setDefaultValue(10)->setRequired();
		$form->addCheckbox('hidden', 'Skryto');

		$form->addSubmits(!$this->getParameter('contactItem'));

		$form->onSuccess[] = function (AdminForm $form): void {
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
		$grid->addColumnText('E-mail', 'email', '<a href="mailto:%1$s"><i class="far fa-envelope"></i> %1$s</a>')->onRenderCell[] = [$grid, 'decoratorEmpty'];
		$grid->addColumnInputInteger('Priorita', 'priority', '', '', 'priority');
		$grid->addColumnInputCheckbox('<i title="Skryto" class="far fa-eye-slash"></i>', 'hidden', '', '', 'hidden');

		$grid->addColumnMutations('name', false);
		$grid->addColumnLinkDetail('detailContact');
		$grid->addColumnActionDelete();

		$grid->addButtonSaveAll();
		$grid->addButtonDeleteSelected();

		$grid->addFilterTextInput('search', ['name_cs', 'phone', 'email'], null, 'Název, telefon, e-mail');
		$grid->addFilterButtons(['contacts']);

		return $grid;
	}

	public function renderOthers(): void
	{
		$this->template->headerLabel = 'Ostatní nastavení';
		$this->template->headerTree = [
			['Ostatní nastavení', 'default'],
		];

		$this->template->displayButtons = [];
		$this->template->displayControls = [$this->getComponent('othersForm')];
	}

	public function createComponentOthersForm(): AdminForm
	{
		$form = $this->formFactory->create(true);

		if (isset($this::CONFIGURATION['allowedSettings']) && \in_array('headCode', $this::CONFIGURATION['allowedSettings'])) {
			$form->addTextArea('headCode', 'HTML kód hlavičky')->setNullable()->setHtmlAttribute('data-info', 'Tento kód bude vložen jako poslední prvek hlavičky.');
		}

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne(['name' => $key, 'value' => $value]);
			}

			$this->flashMessage('Uloženo', 'success');
			$this->redirect('this');
		};

		return $form;
	}
}
