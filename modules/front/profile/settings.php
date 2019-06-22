<?php

namespace IPS\slack\modules\front\profile;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		// Call parent
		parent::execute();
	}

	/**
	 * Default Account Settings Tab
	 *
	 * @return	void
	 */
	protected function manage()
	{
	    // Create our form
        $form = new \IPS\Helpers\Form;

        // Get slack incoming webhook URL
        $configuration = \IPS\Member::loggedIn()->slackConfiguration();

        // Add form elements
        $form->add( new \IPS\Helpers\Form\Url( 'slack_webhook_url', $configuration['webhook'] ? $configuration['webhook'] : NULL, FALSE ) );
        $form->add( new \IPS\Helpers\Form\Color( 'slack_webhook_color', $configuration['color'] ? $configuration['color'] : NULL, FALSE ) );

        // If the data has saved
        if( $values = $form->values() )
        {
            // Save the slack configuration
            \IPS\slack\Notification\Slack::saveConfiguration( $values, \IPS\Member::loggedIn() );

            // Redirect
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings' ), 'saved' );
        }

        // Output the template
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( '__app_slack' );
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile', 'slack', 'front' )->settings( $form );
	}
}