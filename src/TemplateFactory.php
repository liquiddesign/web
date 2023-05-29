<?php

declare(strict_types=1);

namespace Web;

use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Caching\Cache;
use Nette\Utils\Strings;
use Pages\DB\Page;
use Pages\Pages;

abstract class TemplateFactory extends \Base\TemplateFactory
{
	/** @inject */
	public Pages $pages;
	
	public function setTemplateParameters(Template $template): void
	{
		$this->setGlobalParameters($template);

		if ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->setTranslator($this->translator);
		}

		if (!isset($template->control) || !($template->control instanceof Presenter)) {
			return;
		}

		[$module] = \Nette\Application\Helpers::splitName($template->control->getName());

		Strings::substring($module, -5) !== 'Admin' ? $this->setFrontendPresenterParameters($template) : $this->setBackendPresenterParameters($template);
	}

	protected function setFrontendPresenterParameters(Template $template): void
	{
		$page = $this->pages->getPage();
		
		$template->pages = $this->pages;
		$template->page = $this->pages->getPage();
		$template->lang = $template->control->lang ?? null;
		$template->langs = $this->mutations;
		$template->ts = $this->application->getEnvironment() === 'production' ? (new Cache($this->storage))->call('time') : \time();
		$template->shop = $this->shopsConfig->getSelectedShop();
		
		if ($page !== null && !($page instanceof Page)) {
			return;
		}

		$template->headTitle = $page ? ($page->getType() === 'index' ? $page->title : $page->title . ' | ' . $this->getBaseTitle()) : $this->getBaseTitle();
		$template->headDescription = $page ? $page->description : null;
		$template->headCanonical = $page ? $page->canonicalUrl : null;
		$template->headRobots = $this->application->getEnvironment() === 'production' ? ($page && $page->robots ? $page->robots : 'index, follow') : 'noindex, nofollow';
	}
}
