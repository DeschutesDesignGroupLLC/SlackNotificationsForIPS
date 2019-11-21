<?php

namespace IPS\slack\modules\front\slack;

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

        // Load our logged in members slack configuration
        $configuration = \IPS\Member::loggedIn()->slackConfiguration();

        // Webhooks
        $form->addHeader( 'slack_webhook_url_header' );
        $form->add( new \IPS\Helpers\Form\Stack( 'slack_webhook_url', $configuration->webhooks ? $configuration->webhooks : NULL, TRUE, array(
            'stackFieldType' => 'IPS\slack\Form\Stack',
            'webhook' => array( 'placeholder' => 'Webhook URL' ),
            'channel' => array( 'placeholder' => 'Channel' )
        ) ) );

        // Add form elements
        $form->addHeader( 'settings' );
        $form->add( new \IPS\Helpers\Form\Color( 'slack_webhook_color', $configuration->data['color'] ? $configuration->data['color'] : NULL, FALSE ) );

        // If the data has saved
        if( $values = $form->values() )
        {
            // Save the slack configuration
            $configuration->saveConfiguration( $values );

            // Redirect
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings' ), 'saved' );
        }

        // Output the template
        \IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'profile/settings.css', 'slack', 'front' ) );
        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile', 'slack', 'front' )->settings( $form );
	}
}