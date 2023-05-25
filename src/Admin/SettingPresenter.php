<?php

declare(strict_types=1);

namespace Web\Admin;

use Admin\BackendPresenter;
use Admin\Controls\AdminForm;
use Admin\Controls\AdminGrid;
use Base\ShopsConfig;
use Nette\DI\Attributes\Inject;
use Nette\Forms\Form;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
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

	#[Inject]
	public SettingRepository $settingsRepo;

	#[Inject]
	public ContactItemRepository $contactItemRepo;

	#[Inject]
	public ShopsConfig $shopsConfig;

	public function beforeRender(): void
	{
		parent::beforeRender();

		$this->template->tabs = $this::CONFIGURATION['tabs'];
	}

	public function actionDefault(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('form');

		$form->setDefaults(
			$this->shopsConfig->filterShopsInShopEntityCollection(
				$this->settingsRepo->many()->setIndex('name'),
				showOnlyEntitiesWithSelectedShops: true
			)->toArrayOf('value')
		);
	}

	public function actionSocial(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('socialForm');

		$form->setDefaults(
			$this->shopsConfig->filterShopsInShopEntityCollection(
				$this->settingsRepo->many()->setIndex('name'),
				showOnlyEntitiesWithSelectedShops: true
			)->toArrayOf('value')
		);
	}

	public function actionOthers(): void
	{
		/** @var \Admin\Controls\AdminForm $form */
		$form = $this->getComponent('othersForm');

		$form->setDefaults(
			$this->shopsConfig->filterShopsInShopEntityCollection(
				$this->settingsRepo->many()->setIndex('name'),
				showOnlyEntitiesWithSelectedShops: true
			)->toArrayOf('value')
		);
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

		$shopIcon = $this->shopsConfig->getAvailableShops() ? '<i class="fas fa-store-alt fa-sm mr-1" title="Specifické nastavení pro zvolený obchod"></i>' : null;

		if (Arrays::contains(self::CONFIGURATION['groups'], 'company')) {
			$form->addGroup('Společnost');
			$form->addText('companyName', Html::fromHtml("$shopIcon Název společnosti"))->setNullable();
			$form->addTextArea('legalInfo', Html::fromHtml("$shopIcon Informace o zápisu"))->setHtmlAttribute('cols', 70)->setNullable();
		}

		if (Arrays::contains(self::CONFIGURATION['groups'], 'support')) {
			$form->addGroup('Podpora');
			$form->addText('supportEmail', Html::fromHtml("$shopIcon E-mail"))->setNullable()->addCondition(Form::FILLED)->addRule($form::EMAIL);
			$form->addText('supportPhone', Html::fromHtml("$shopIcon Telefon"))->setNullable();
			$form->addText('supportPhoneTime', Html::fromHtml("$shopIcon Dostupnost telefonu"))->setNullable()->setHtmlAttribute('data-info', 'Zvolte libovolný formát');
		}

		if (Arrays::contains(self::CONFIGURATION['groups'], 'map')) {
			$form->addGroup('Mapa');
			$form->addText('contactStreet', Html::fromHtml("$shopIcon Ulice"))->setHtmlAttribute('data-info', 'Např.: Josefská 15')->setNullable();
			$form->addText('contactCity', Html::fromHtml("$shopIcon Město"))->setHtmlAttribute('data-info', 'Např.: 602 00 Brno')->setNullable();
			$form->addText('contactGPSx', Html::fromHtml("$shopIcon GPS souřadnice X"))->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 16.6125203')->setNullable();
			$form->addText('contactGPSy', Html::fromHtml("$shopIcon GPS souřadnice Y"))->setHtmlAttribute('data-info', 'GPS souřadnice pro zobrazení bodu na mapě. Např.: 49.1920700')->setNullable();
		}

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne([
					'name' => $key,
					'value' => $value,
					'shop' => $this->shopsConfig->getSelectedShop()?->getPK(),
				]);
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

		$shopIcon = $this->shopsConfig->getAvailableShops() ? '<i class="fas fa-store-alt fa-sm mr-1" title="Specifické nastavení pro zvolený obchod"></i>' : null;

		$form->addText('socialFacebook', Html::fromHtml("$shopIcon Facebook"))->setNullable();
		$form->addText('socialInstagram', Html::fromHtml("$shopIcon Instagram"))->setNullable();
		$form->addText('socialTwitter', Html::fromHtml("$shopIcon Twitter"))->setNullable();

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne([
					'name' => $key,
					'value' => $value,
					'shop' => $this->shopsConfig->getSelectedShop()?->getPK(),
				]);
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

		$this->formFactory->addShopsContainerToAdminForm($form);

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

		$shopIcon = $this->shopsConfig->getAvailableShops() ? '<i class="fas fa-store-alt fa-sm mr-1" title="Specifické nastavení pro zvolený obchod"></i>' : null;

		if (isset($this::CONFIGURATION['allowedSettings']) && Arrays::contains($this::CONFIGURATION['allowedSettings'], 'headCode')) {
			$form->addTextArea('headCode', Html::fromHtml("$shopIcon HTML kód hlavičky"))->setNullable()->setHtmlAttribute('data-info', 'Tento kód bude vložen jako poslední prvek hlavičky.');
		}

		$form->addSubmit('submit', 'Uložit');

		$form->onSuccess[] = function (AdminForm $form): void {
			$values = $form->getValues('array');

			foreach ($values as $key => $value) {
				$this->settingsRepo->syncOne([
					'name' => $key,
					'value' => $value,
					'shop' => $this->shopsConfig->getSelectedShop()?->getPK(),
				]);
			}

			$this->flashMessage('Uloženo', 'success');
			$this->redirect('this');
		};

		return $form;
	}
}
