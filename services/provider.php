<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Task.Autodeletefiles
 *
 * @copyright   (C) 2023 Tom van der Laan - TLWebdesign. <https://www.tlwebdesign.nl>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\Autodeletefiles\Extension\Autodeletefiles;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.2.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);

                $plugin = new Autodeletefiles(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('task', 'autodeletefiles')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
