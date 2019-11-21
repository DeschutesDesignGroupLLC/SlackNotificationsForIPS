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

namespace IPS\slack\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Form Stack Class
 */
class _Stack extends \IPS\Helpers\Form\FormAbstract
{
    /**
     * @brief	Webhook Field Object
     */
    public $webhookField = NULL;

    /**
     * @brief	Channel Field Object
     */
    public $channelField = NULL;

    /**
     * Constructor
     * Creates the two date objects
     *
     * @param	string		$name			Form helper name
     * @param	mixed		$defaultValue	Default value for the helper
     * @param	bool		$required		Helper is required (TRUE) or not (FALSE)
     * @param	array		$options		Options for the helper instance
     * @see		\IPS\Helpers\Form\Abstract::__construct
     * @return	void
     */
    public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
    {
        // Merge the options arrays
        $options = array_merge( $this->defaultOptions, $options );

        // Set our fields
        $this->webhookField = new \IPS\Helpers\Form\Url( "{$name}[webhook]", isset( $defaultValue['webhook'] ) ? $defaultValue['webhook'] : NULL, FALSE, isset( $options['webhook'] ) ? $options['webhook'] : array() );
        $this->channelField = new \IPS\Helpers\Form\Text( "{$name}[channel]", isset( $defaultValue['channel'] ) ? $defaultValue['channel'] : NULL, FALSE, isset( $options['channel'] ) ? $options['channel'] : array() );

        // Call parent
        parent::__construct( $name, $defaultValue, $required, $options );
    }

    /**
     * Get HTML
     *
     * @return	string
     */
    public function html()
    {
        // Return the template
        return \IPS\Theme::i()->getTemplate( 'forms', 'slack', 'global' )->webhook( $this->webhookField->html(), $this->channelField->html() );
    }

    /**
     * Format Value
     *
     * @return	array
     */
    public function formatValue()
    {
        // Return the formatted array
        return array(
            'webhook'	=> $this->webhookField->formatValue(),
            'channel'	=> $this->channelField->formatValue()
        );
    }

    /**
     * Validate
     *
     * @throws	\InvalidArgumentException
     * @return	TRUE
     */
    public function validate()
    {
        // Call individual validations
        $this->webhookField->validate();
        $this->channelField->validate();

        // If we have custom validation code
        if( $this->customValidationCode !== NULL )
        {
            // Run it
            $validationCode = $this->customValidationCode;
            $validationCode( $this->value );
        }

        // Call parent
        parent::validate();
    }
}