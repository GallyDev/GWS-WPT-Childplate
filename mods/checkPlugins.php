<?php
//  if ( ! function_exists( 'WP_Filesystem' ) ) {
//     require_once ABSPATH . 'wp-admin/includes/file.php';
// }

class checkPlugins {
   
    private string $message = '';

    public function getPluginsArray()
    {
       return json_decode(file_get_contents('allPlugins.json'));
    }

    public function wpSetMessage($type)
    {
       $this->message = '<div class="notice notice-'.$type.' is-dismissible">';
    //    $this->message .= match($type) {
    //     'info' => '<p></p>',
    //     'warning' => '<p></p>',
    //     'error' => '<p>Nicht alle Plugins sind installiert</p>',
    //     'success' => '<p>Alle notwendingen Plugins sind installiert.</p>',
    //    };

        switch ($type) {
            case 'info':
            $this->message .= '<p></p>';
            break;
            case 'warning':
            $this->message .= '<p></p>';
            break;
            case 'error':
            $this->message .= '<p>Nicht alle Plugins sind installiert</p>';
            break;
            case 'success':
            $this->message .= '<p>Alle notwendingen Plugins sind installiert.</p>';
            break;
            default:
            break;
        }
       $this->message .='</div>';
    }

    public function wpSendMessage()
    {
        if ( current_user_can( 'administrator' ) ) {
            echo $this->message;
        }
    }

    public function isInstalled()
    {
        $pluginsObj = $this->getPluginsArray();
        $neededPlugins = $pluginsObj->{"sp-plugins"}; //json_decode create objects from type stdClass not arrays
        foreach($neededPlugins as $neededPlugin) {
            if(is_plugin_active($neededPlugin->name)) {
                $this->wpMessage('success');
                add_action('admin_notices', [$this, 'wpSendMessage']);
            }else{
                $this->wpMessage('error');
                add_action('admin_notices', [$this, 'wpSendMessage']);
            }
        }
    }
}
