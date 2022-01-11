<?php
/*
 * @package   PlgSystemPlausible
 * @copyright Copyright © 2022 Nicholas K. Dionysopoulos
 * @license   GPLv3 or later
 */

namespace Joomla\Plugin\System\Plausible\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class Plausible extends CMSPlugin implements SubscriberInterface
{
	/**
	 * The CMS application object
	 *
	 * @var   CMSApplication
	 * @since 1.0.0
	 */
	protected $app;

	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeRender' => 'injectPlausibleScript',
		];
	}

	public function injectPlausibleScript(Event $event)
	{
		// Make sure this is the Site application
		if (!($this->app instanceof CMSApplication) || !$this->app->isClient('site'))
		{
			return;
		}

		// Make sure this is HTML output
		$doc = $this->app->getDocument();

		if (!is_object($doc) || !($doc instanceof HtmlDocument))
		{
			return;
		}

		// Check for exempted user groups
		$exempt = $this->params->get('exemptUsergroups', []) ?: [];
		$exempt = is_array($exempt) ? $exempt : [];
		$user = $this->app->getIdentity();

		if (!empty($exempt) && !empty(array_intersect($user->getAuthorisedGroups(), $exempt)))
		{
			return;
		}

		// Get the hostname without www in front
		$uri      = Uri::getInstance();
		$hostname = $uri->getHost();

		if (substr($hostname, 4) === 'www.')
		{
			$hostname = substr($hostname, 4);
		}

		// Apply a custom domain name, if specified.
		$customDomain = trim($this->params->get('custom_domain', '') ?: '');
		$domain       = $customDomain ?: $hostname;

		// Add the script, deferred
		$wam = $doc->getWebAssetManager();
		$wam->registerAndUseScript('plg_system_plausible.plausible', 'https://plausible.io/js/plausible.js', [], [
			'defer'       => true,
			'data-domain' => $domain,
		]);
	}
}