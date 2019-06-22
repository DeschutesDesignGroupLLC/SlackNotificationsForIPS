<?php
/**
 * @brief		Slack Notifications Application Class
 * @author		<a href='https://www.deschutesdesigngroup.com'>Deschutes Design Group LLC</a>
 * @copyright	(c) 2019 Deschutes Design Group LLC
 * @package		Invision Community
 * @subpackage	Slack Notifications
 * @since		19 Jun 2019
 * @version		
 */
 
namespace IPS\slack;

/**
 * Slack Notifications Application Class
 */
class _Application extends \IPS\Application
{
    /**
     * [Node] Get Icon for tree
     *
     * @note    Return the class for the icon (e.g. 'globe')
     * @return    string|null
     */
    protected function get__icon()
    {
        // Return the application icon
        return 'slack';
    }
}