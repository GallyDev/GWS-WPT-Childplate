<?php
if ( ! defined( 'ABSPATH' ) ) {
    require_once __DIR__.'/../../../../wp-load.php';
}

class WpMessages {
    private $message = '';

    /**
     * Set the message which will be displayed in the WordPress Backend 
     * @param string $type          Set the of message (error, warning, info, success)
     * @param string $customMessage Set a custom message
     * @return void 
     */
    public function wpSetMessage($type, $customMessage = '', $isdismissible = true)
    {
       $this->message = '<div class="notice notice-'.$type.' '.($isdismissible ?'is-dismissible': '').'">';
    //    $this->message .= match($type) {
    //     'info' => '<p></p>',
    //     'warning' => '<p></p>',
    //     'error' => '<p>Nicht alle Plugins sind installiert</p>',
    //     'success' => '<p>Alle notwendingen Plugins sind installiert.</p>',
    //    };
        
        if($customMessage !='') {
            $this->message .= '<p>'.$customMessage.'</p>';
        }else{
            switch ($type) {
                case 'info':
                $this->message .= '<p></p>';
                break;
                case 'warning':
                $this->message .= '<p></p>';
                break;
                case 'error':
                $this->message .= '<p>Keiner der vorgegebenen Plugins sind installiert</p>';
                break;
                case 'success':
                $this->message .= '<p>Alle notwendingen Plugins sind installiert.</p>';
                break;
            }
        }
        $this->message .='</div>';
    }

     /**
     * Displayes the message which was set with wpSetMessage
     * @return void
     */
    public function printMessage()
    {
        if ( current_user_can( 'administrator' ) ) {
            echo $this->message;
        }
    }

    /**
     * Hook the message to the admin_notices
     * @return void
     */
    public function wpSendMessage() 
    {
        add_action('admin_notices', [$this, 'printMessage']);
    }
}